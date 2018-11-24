<?php
require_once 'mailgun/mailgun.php';
use Mailgun\Mailgun;

if ( ! class_exists( 'PostmanMailgunMailEngine' ) ) {

	/**
	 * Sends mail with the SendGrid API
	 * https://sendgrid.com/docs/API_Reference/Web_API/mail.html
	 *
	 * @author jasonhendriks
	 */
	class PostmanMailgunMailEngine implements PostmanMailEngine {

		// logger for all concrete classes - populate with setLogger($logger)
		protected $logger;

		// the result
		private $transcript;

		private $apiKey;
		private $domainName;
		private $mandrillMessage;

		/**
		 *
		 * @param unknown $senderEmail
		 * @param unknown $accessToken
		 */
		function __construct( $apiKey, $domainName ) {
			assert( ! empty( $apiKey ) );
			$this->apiKey = $apiKey;
			$this->domainName = $domainName;

			// create the logger
			$this->logger = new PostmanLogger( get_class( $this ) );
			$this->mailgunMessage = array(
			    'from'    => '',
			    'to'      => '',
			    'subject' => '',
			);
		}

		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanSmtpEngine::send()
		 */
		public function send( PostmanMessage $message ) {
			$options = PostmanOptions::getInstance();

			// add the Postman signature - append it to whatever the user may have set
			if ( ! $options->isStealthModeEnabled() ) {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
				$this->addHeader( 'X-Mailer', sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' ) );
			}

			// add the headers - see http://framework.zend.com/manual/1.12/en/zend.mail.additional-headers.html
			foreach ( ( array ) $message->getHeaders() as $header ) {
				$this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
				$this->addHeader( $header ['name'], $header ['content'], true );
			}

			// if the caller set a Content-Type header, use it
			$contentType = $message->getContentType();
			if ( ! empty( $contentType ) ) {
				$this->logger->debug( 'Adding content-type ' . $contentType );
				$this->addHeader( 'Content-Type', $contentType );
			}

			// add the From Header
			$sender = $message->getFromAddress();
			{
				$senderEmail = PostmanOptions::getInstance()->getMessageSenderEmail();
				$senderName = $sender->getName();
				assert( ! empty( $senderEmail ) );

				$senderText = ! empty( $senderName ) ? $senderName : $senderEmail;
				$this->mailgunMessage ['from'] = "{$senderText} <{$senderEmail}>";
				// now log it
				$sender->log( $this->logger, 'From' );
			}

			// add the Sender Header, overriding what the user may have set
			$this->addHeader( 'Sender', $options->getEnvelopeSender() );

			// add the to recipients
			$recipients = array();
			foreach ( ( array ) $message->getToRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'To' );
				$recipients[] = $recipient->getEmail();
			}
			$this->mailgunMessage['to'] = $recipients;

			// add the cc recipients
			$recipients = array();
			foreach ( ( array ) $message->getCcRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'Cc' );
				$recipients[] = $recipient->getEmail();
			}
			$this->mailgunMessage['cc'] = implode( ',', $recipients );

			// add the bcc recipients
			$recipients = array();
			foreach ( ( array ) $message->getBccRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'Bcc' );
				$recipients[] = $recipient->getEmail();
			}
			$this->mailgunMessage['bcc'] = implode( ',', $recipients );

			// add the reply-to
			$replyTo = $message->getReplyTo();
			// $replyTo is null or a PostmanEmailAddress object
			if ( isset( $replyTo ) ) {
				$this->addHeader( 'reply-to', $replyTo->format() );
			}

			// add the date
			$date = $message->getDate();
			if ( ! empty( $date ) ) {
				$this->addHeader( 'date', $message->getDate() );
			}

			// add the messageId
			$messageId = $message->getMessageId();
			if ( ! empty( $messageId ) ) {
				$this->addHeader( 'message-id', $messageId );
			}

			// add the subject
			if ( null !== $message->getSubject() ) {
				$this->mailgunMessage ['subject'] = $message->getSubject();
			}

			// add the message content
			{
				$textPart = $message->getBodyTextPart();
			if ( ! empty( $textPart ) ) {
				$this->logger->debug( 'Adding body as text' );
				$this->mailgunMessage ['text'] = $textPart;
			}
					$htmlPart = $message->getBodyHtmlPart();
			if ( ! empty( $htmlPart ) ) {
				$this->logger->debug( 'Adding body as html' );
				$this->mailgunMessage ['html'] = $htmlPart;
			}
			}

			// add attachments
			$this->logger->debug( 'Adding attachments' );
			$this->addAttachmentsToMail( $message );

			$result = array();
			try {
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Creating Mandrill service with apiKey=' . $this->apiKey );
				}

				// send the message
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}

				$mg = Mailgun::create( $this->apiKey );

				// Make the call to the client.
				$result = $this->processSend( $mg );

				if ( $this->logger->isInfo() ) {
					$this->logger->info( sprintf( 'Message %d accepted for delivery', PostmanState::getInstance()->getSuccessfulDeliveries() + 1 ) );
				}

				$this->transcript = print_r( $result, true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $this->mailgunMessage, true );
			} catch ( Exception $e ) {
				$this->transcript = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $this->mailgunMessage, true );
				throw $e;
			}
		}


		private function processSend( $mg ) {

			if ( count( $this->mailgunMessage['to'] ) == 1 ) {

				return $mg->messages()->send( $this->domainName, array_filter( $this->mailgunMessage ) );
			} else {
				$chunks = array_chunk( $this->mailgunMessage['to'], 1000, true );

				$result = array();
				foreach ( $chunks as $key => $emails ) {
					$this->mailgunMessage['to'] = $emails;
					$recipient_variables = $this->getRecipientVariables( $emails );
					$this->mailgunMessage['recipient-variables'] = $recipient_variables;

					$result[] = $mg->messages()->send( $this->domainName, array_filter( $this->mailgunMessage ) );

					// Don't have a reason just wait a bit before sending the next chunk
					sleep(2);
				}

				return $result;
			}
		}

		private function getRecipientVariables( $emails ) {
			$recipient_variables = array();
			foreach ( $emails as $key => $email ) {
				$recipient_variables[$email] = array( 'id' => $key );
			}

			return json_encode( $recipient_variables );
		}

		private function addHeader( $name, $value, $deprecated = '' ) {
			if ( $value && ! empty( $value ) ) {
				$this->mailgunMessage['h:' . $name] = $value;
			}
		}

		/**
		 * Add attachments to the message
		 *
		 * @param Postman_Zend_Mail $mail
		 */
		private function addAttachmentsToMail( PostmanMessage $message ) {
			$attachments = $message->getAttachments();
			if ( ! is_array( $attachments ) ) {
				// WordPress may a single filename or a newline-delimited string list of multiple filenames
				$attArray[] = explode( PHP_EOL, $attachments );
			} else {
				$attArray = $attachments;
			}

			$attachments = array();
			foreach ( $attArray as $file ) {
				if ( ! empty( $file ) ) {
					$this->logger->debug( 'Adding attachment: ' . $file );
					$attachments[] = array( 'filePath' => $file );
				}
			}

			if ( ! empty( $attachments ) ) {
				if ( $this->logger->isTrace() ) {
					$this->logger->trace( $attachments );
				}
				$this->mailgunMessage['attachment'] = $attachments;
			}
		}

		// return the SMTP session transcript
		public function getTranscript() {
			return $this->transcript;
		}
	}
}

