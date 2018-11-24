<?php

class PostmanWelcomeController {

	private $rootPluginFilenameAndPath, $pluginUrl, $version;

	public function __construct( $rootPluginFilenameAndPath ) {
		$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
		$this->pluginUrl = plugins_url( 'style', $rootPluginFilenameAndPath );
		$this->version = PostmanState::getInstance()->getVersion();

		add_action( 'admin_menu', array( $this, 'add_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
	}

	public function add_menus() {

		if ( current_user_can( 'manage_options' ) ) {

			// About
			add_dashboard_page(
				__( 'Welcome',  Postman::TEXT_DOMAIN ),
				__( 'Welcome',  Postman::TEXT_DOMAIN ),
				'manage_options',
				'post-about',
				array( $this, 'about_screen' )
			);

			// Credits
			add_dashboard_page(
				__( 'Credits',  Postman::TEXT_DOMAIN ),
				__( 'Credits',  Postman::TEXT_DOMAIN ),
				'manage_options',
				'post-credits',
				array( $this, 'credits_screen' )
			);

			// add_action( 'admin_print_styles-' . $page, array( $this, 'postman_about_enqueue_resources' ) );
		}
	}

	public function admin_head() {
		remove_submenu_page( 'index.php', 'post-about' );
		remove_submenu_page( 'index.php', 'post-credits' );
	}

	public function postman_about_enqueue_resources() {
		// wp_enqueue_style( 'font-awsome', '' );
	}


	public function about_screen() {
		?>
		<style type="text/css">
			.post-badge {
			    position: absolute;
			    top: 0;
			    right: 0;	
			    padding-top: 142px;
			    height: 50px;
			    width: 140px;
			    color: #000;
			    font-weight: bold;
			    font-size: 14px;
			    text-align: center;
			    margin: 0 -5px;
			    background: url( <?php echo $this->pluginUrl; ?>/images/badge.png) no-repeat;	
			}	

			.about-wrap [class$="-col"] {
				flex-wrap: nowrap !important;
			}
		</style>
		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to Post SMTP %s', Postman::TEXT_DOMAIN ), $this->version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! Post SMTP %s is bundled up and ready to take your SMTP needs to the next level!', Postman::TEXT_DOMAIN ), $this->version ); ?><br>
				<?php printf( '<strong>%s</strong>','Post SMTP support every SMTP service: Gmail/G-suite, SendGrid, Mandrill, Office365, and more...' ); ?>
			</div>
			<div class="post-badge"><?php printf( esc_html__( 'Version %s', Postman::TEXT_DOMAIN ), $this->version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'post-about' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'What&#8217;s New', Postman::TEXT_DOMAIN ); ?>
				</a><a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'post-credits' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'Credits', Postman::TEXT_DOMAIN ); ?>
				</a>
			</h2>

			<div class="changelog">
				<h3><?php esc_html_e( 'Email Log', Postman::TEXT_DOMAIN ); ?></h3>

				<div class="feature-section col two-col">
					<div class="last-feature">
						<h4><?php esc_html_e( 'Email log filter', Postman::TEXT_DOMAIN ); ?></h4>
						<p>
							<?php esc_html_e( 'You can easily filter by dates and search in your log.', Postman::TEXT_DOMAIN ); ?>
							<img src="<?php echo $this->pluginUrl; ?>/images/filter-preview.gif">	
						</p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Multiple emails resend', Postman::TEXT_DOMAIN ); ?></h4>
						<p>
							<?php esc_html_e( 'Resend any email to the original recipient or any other emails you choose.', Postman::TEXT_DOMAIN ); ?>
							<img src="<?php echo $this->pluginUrl; ?>/images/resend-preview.gif">	
						</p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php esc_html_e( 'The best delivery experience', Postman::TEXT_DOMAIN ); ?></h3>

				<div class="feature-section col one-col">
					<div class="last-feature">
						<p><?php esc_html_e( 'Easy-to-use, powerful Setup Wizard for perfect configuration,
						Commercial-grade Connectivity Tester to diagnose server issues,
						Log and resend all emails; see the exact cause of failed emails,
						Supports International alphabets, HTML Mail and MultiPart/Alternative,
						Supports forced recipients (cc, bcc, to) and custom email headers,
						SASL Support: Plain/Login/CRAM-MD5/XOAUTH2 authentication,
						Security Support: SMTPS and STARTTLS (SSL/TLS),
						Copy configuration to other instances of Post.', Postman::TEXT_DOMAIN ); ?></p>
					</div>
				</div>

				<div class="feature-section col three-col">
					<div>
						<h4><?php esc_html_e( 'Email log HTML preview', Postman::TEXT_DOMAIN ); ?></h4>
						<p><?php esc_html_e( 'You can now see sent emails as HTML.', Postman::TEXT_DOMAIN ); ?></p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Continues email delivery', Postman::TEXT_DOMAIN ); ?></h4>
						<p><?php esc_html_e( 'if email fail to sent you will get notified using the local mail system.', Postman::TEXT_DOMAIN ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php esc_html_e( 'The best debugging tools.', Postman::TEXT_DOMAIN ); ?></h4>
						<p><?php esc_html_e( 'Full Transcripts, Connectivity Test, Diagnostic Test.', Postman::TEXT_DOMAIN ); ?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'postman' ), 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Go to Post SMTP Settings', Postman::TEXT_DOMAIN ); ?></a>
			</div>

		</div>

		<?php
	}

	public function credits_screen() {
		?>
		<style type="text/css">
			.post-badge {
			    position: absolute;
			    top: 0;
			    right: 0;	
			    padding-top: 142px;
			    height: 50px;
			    width: 140px;
			    color: #000;
			    font-weight: bold;
			    font-size: 14px;
			    text-align: center;
			    margin: 0 -5px;
			    background: url( <?php echo $this->pluginUrl; ?>/images/badge.png) no-repeat;	
			}			
		</style>
		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to Post SMTP %s', Postman::TEXT_DOMAIN ), $this->version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! bbPress %s is waxed, polished, and ready for you to take it for a lap or two around the block!', Postman::TEXT_DOMAIN ), $this->version ); ?></div>
			<div class="post-badge"><?php printf( esc_html__( 'Version %s', Postman::TEXT_DOMAIN ), $this->version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'post-about' ), 'index.php' ) ) ); ?>" class="nav-tab">
					<?php esc_html_e( 'What&#8217;s New', Postman::TEXT_DOMAIN ); ?>
				</a><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'post-credits' ), 'index.php' ) ) ); ?>" class="nav-tab nav-tab-active">
					<?php esc_html_e( 'Credits', Postman::TEXT_DOMAIN ); ?>
				</a>
			</h2>

			<p class="about-description"><?php esc_html_e( 'Post SMTP started by Jason Hendriks, Jason left the project and Yehuda Hassine (me) continue his work.', Postman::TEXT_DOMAIN ); ?></p>

			<h4 class="wp-people-group"><?php esc_html_e( 'Project Leaders', Postman::TEXT_DOMAIN ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-jasonhendriks">
					<a href="https://profiles.wordpress.org/jasonhendriks"><img src="https://secure.gravatar.com/avatar/8692c7b6084517a592f6cad107f7bcb0?s=60&d=mm&r=g" class="gravatar" alt="Jason Hendriks " /></a>
					<a class="web" href="http://profiles.wordpress.org/matt">Jason Hendriks</a>
					<span class="title"><?php esc_html_e( 'Founding Developer (abandoned)', Postman::TEXT_DOMAIN ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-yehudah">
					<a href="http://profiles.wordpress.org/yehudah"><img src="https://secure.gravatar.com/avatar/c561638d04ea8fef351f974dbb9ece39?s=60&d=mm&r=g" class="gravatar" alt="Yehuda Hassine" /></a>
					<a class="web" href="http://profiles.wordpress.org/yehudah">Yehuda Hassine</a>
					<span class="title"><?php esc_html_e( 'Lead Developer', Postman::TEXT_DOMAIN ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php esc_html_e( 'Top Community Members', Postman::TEXT_DOMAIN ); ?></h4>
			<h5><?php esc_html_e( 'Here I will list top users that help Post SMTP grow (bugs, features, etc...)', Postman::TEXT_DOMAIN ); ?>
			<p class="wp-credits-list">
				<a href="http://profiles.wordpress.org/diegocanal">diegocanal</a>,
				<a href="http://profiles.wordpress.org/jyourstone">Johan Yourstone</a>,
				<a href="http://profiles.wordpress.org/bodhirayo">bodhirayo</a>,
				<a href="http://profiles.wordpress.org/buzztone">Neil Murray </a>,
				<a href="#">A place waiting for you? :-) </a>
			</p>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'postman' ), 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Go to Post SMTP Settings', Postman::TEXT_DOMAIN ); ?></a>
			</div>

		</div>

		<?php
	}
}
