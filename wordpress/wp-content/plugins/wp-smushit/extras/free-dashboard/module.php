<?php
/**
 * WPMUDEV Frash - Free Dashboard Notification module.
 * Used by wordpress.org hosted plugins.
 *
 * @version 1.2
 * @author  Incsub (Philipp Stracker)
 */
if ( ! class_exists( 'WDev_Frash' ) ) {
	class WDev_Frash {

		/**
		 * List of all registered plugins.
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		protected $plugins = array();

		/**
		 * Module options that are stored in database.
		 * Timestamps are stored here.
		 *
		 * Note that this option is stored in site-meta for multisite installs.
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		protected $stored = array();

		/**
		 * User id /API Key for Mailchimp subscriber list
		 *
		 * @since 1.2
		 *
		 * @var string
		 *
		 */
		private $mc_user_id = '53a1e972a043d1264ed082a5b';

		/**
		 * Initializes and returns the singleton instance.
		 *
		 * @since  1.0.0
		 */
		static public function instance() {
			static $Inst = null;

			if ( null === $Inst ) {
				$Inst = new WDev_Frash();
			}

			return $Inst;
		}

		/**
		 * Set up the WDev_Frash module. Private singleton constructor.
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			$this->read_stored_data();

			$this->add_action( 'wdev-register-plugin', 5 );
			$this->add_action( 'load-index.php' );

			$this->add_action( 'wp_ajax_frash_act' );
			$this->add_action( 'wp_ajax_frash_dismiss' );
		}

		/**
		 * Load persistent module-data from the WP Database.
		 *
		 * @since  1.0.0
		 */
		protected function read_stored_data() {
			$data = get_site_option( 'wdev-frash', false, false );

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			// A list of all plugins with timestamp of first registration.
			if ( ! isset( $data['plugins'] ) || ! is_array( $data['plugins'] ) ) {
				$data['plugins'] = array();
			}

			// A list with pending messages and earliest timestamp for display.
			if ( ! isset( $data['queue'] ) || ! is_array( $data['queue'] ) ) {
				$data['queue'] = array();
			}

			// A list with all messages that were handles already.
			if ( ! isset( $data['done'] ) || ! is_array( $data['done'] ) ) {
				$data['done'] = array();
			}

			$this->stored = $data;
		}

		/**
		 * Save persistent module-data to the WP database.
		 *
		 * @since  1.0.0
		 */
		protected function store_data() {
			update_site_option( 'wdev-frash', $this->stored );
		}

		/**
		 * Action handler for 'wdev-register-plugin'
		 * Register an active plugin.
		 *
		 * @since  1.0.0
		 * @param  string $plugin_id WordPress plugin-ID (see: plugin_basename).
		 * @param  string $title Plugin name for display.
		 * @param  string $url_wp URL to the plugin on wp.org (domain not needed)
		 * @param  string $cta_email Title of the Email CTA button.
		 * @param  string $mc_list_id required. Mailchimp mailing list id for the plugin.
		 */
		public function wdev_register_plugin( $plugin_id, $title, $url_wp, $cta_email = '', $mc_list_id = '' ) {
			// Ignore incorrectly registered plugins to avoid errors later.
			if ( empty( $plugin_id ) ) { return; }
			if ( empty( $title ) ) { return; }
			if ( empty( $url_wp ) ) { return; }

			if ( false === strpos( $url_wp, '://' ) ) {
				$url_wp = 'https://wordpress.org/' . trim( $url_wp, '/' );
			}

			$this->plugins[$plugin_id] = (object) array(
				'id' => $plugin_id,
				'title' => $title,
				'url_wp' => $url_wp,
				'cta_email' => $cta_email,
				'mc_list_id' => $mc_list_id,
			);

			/*
			 * When the plugin is registered the first time we store some infos
			 * in the persistent module-data that help us later to find out
			 * if/which message should be displayed.
			 */
			if ( empty( $this->stored['plugins'][$plugin_id] ) ) {
				// First register the plugin permanently.
				$this->stored['plugins'][$plugin_id] = time();

				// Second schedule the messages to display.
				$hash = md5( $plugin_id . '-email' );
				$this->stored['queue'][$hash] = array(
					'plugin' => $plugin_id,
					'type' => 'email',
					'show_at' => time(),  // Earliest time to display note.
				);

				$hash = md5( $plugin_id . '-rate' );
				$this->stored['queue'][$hash] = array(
					'plugin' => $plugin_id,
					'type' => 'rate',
					'show_at' => time() + 7 * DAY_IN_SECONDS,
				);

				// Finally save the details.
				$this->store_data();
			}
		}

		/**
		 * Ajax handler called when the user chooses the CTA button.
		 *
		 * @since  1.0.0
		 */
		public function wp_ajax_frash_act() {
			$plugin = $_POST['plugin_id'];
			$type = $_POST['type'];

			$this->mark_as_done( $plugin, $type, 'ok' );

			echo 1;
			exit;
		}

		/**
		 * Ajax handler called when the user chooses the dismiss button.
		 *
		 * @since  1.0.0
		 */
		public function wp_ajax_frash_dismiss() {
			$plugin = $_POST['plugin_id'];
			$type = $_POST['type'];

			$this->mark_as_done( $plugin, $type, 'ignore' );

			echo 1;
			exit;
		}

		/**
		 * Action handler for 'load-index.php'
		 * Set-up the Dashboard notification.
		 *
		 * @since  1.0.0
		 */
		public function load_index_php() {
			if ( is_super_admin() ) {
				$this->add_action( 'all_admin_notices' );
			}
		}

		/**
		 * Action handler for 'admin_notices'
		 * Display the Dashboard notification.
		 *
		 * @since  1.0.0
		 */
		public function all_admin_notices() {
			$info = $this->choose_message();
			if ( ! $info ) { return; }

			$this->render_message( $info );
		}

		/**
		 * Check to see if there is a pending message to display and returns
		 * the message details if there is.
		 *
		 * Note that this function is only called on the main Dashboard screen
		 * and only when logged in as super-admin.
		 *
		 * @since  1.0.0
		 * @return object|false
		 *         string $type   [rate|email] Which message type?
		 *         string $plugin WordPress plugin ID?
		 */
		protected function choose_message() {
			$obj = false;
			$chosen = false;
			$earliest = false;

			$now = time();

			// The "current" time can be changed via $_GET to test the module.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $_GET['time'] ) ) {
				$custom_time = $_GET['time'];
				if ( ' ' == $custom_time[0] ) { $custom_time[0] = '+'; }
				if ( $custom_time ) { $now = strtotime( $custom_time ); }
				if ( ! $now ) { $now = time(); }
			}

			$tomorrow = $now + DAY_IN_SECONDS;

			foreach ( $this->stored['queue'] as $hash => $item ) {
				$show_at = intval( $item['show_at'] );
				$is_sticky = ! empty( $item['sticky'] );

				if ( ! isset( $this->plugins[ $item['plugin'] ] ) ) {
					// Deactivated plugin before the message was displayed.
					continue;
				}
				$plugin = $this->plugins[ $item['plugin'] ];

				$can_display = true;
				if ( wp_is_mobile() ) {
					// Do not display rating message on mobile devices.
					if ( 'rate' == $item['type'] ) {
						$can_display = false;
					}
				}
				if ( 'email' == $item['type'] ) {
					//If we don't have mailchimp list id
					if ( ! $plugin->mc_list_id || ! $plugin->cta_email ) {
						// Do not display email message with missing email params.
						$can_display = false;
					}
				}
				if ( $now < $show_at ) {
					// Do not display messages that are not due yet.
					$can_display = false;
				}

				if ( ! $can_display ) { continue; }

				if ( $is_sticky ) {
					// If sticky item is present then choose it!
					$chosen = $hash;
					break;
				} elseif ( ! $earliest || $earliest < $show_at ) {
					$earliest = $show_at;
					$chosen = $hash;
					// Don't use `break` because a sticky item might follow...
					// Find the item with the earliest schedule.
				}
			}

			if ( $chosen ) {
				// Make the chosen item sticky.
				$this->stored['queue'][$chosen]['sticky'] = true;

				// Re-schedule other messages that are due today.
				foreach ( $this->stored['queue'] as $hash => $item ) {
					$show_at = intval( $item['show_at'] );

					if ( empty( $item['sticky'] ) && $tomorrow > $show_at ) {
						$this->stored['queue'][$hash]['show_at'] = $tomorrow;
					}
				}

				// Save the changes.
				$this->store_data();

				$obj = (object) $this->stored['queue'][$chosen];
			}

			return $obj;
		}

		/**
		 * Moves a message from the queue to the done list.
		 *
		 * @since  1.0.0
		 * @param  string $plugin Plugin ID.
		 * @param  string $type [rate|email] Message type.
		 * @param  string $state [ok|ignore] Button clicked.
		 */
		protected function mark_as_done( $plugin, $type, $state ) {
			$done_item = false;

			foreach ( $this->stored['queue'] as $hash => $item ) {
				unset( $this->stored['queue'][$hash]['sticky'] );

				if ( $item['plugin'] == $plugin && $item['type'] == $type ) {
					$done_item = $item;
					unset( $this->stored['queue'][$hash] );
				}
			}

			if ( $done_item ) {
				$done_item['state'] = $state;
				$done_item['hash'] = $hash;
				$done_item['handled_at'] = time();
				unset( $done_item['sticky'] );

				$this->stored['done'][] = $done_item;
				$this->store_data();
			}
		}

		/**
		 * Renders the actual Notification message.
		 *
		 * @since  1.0.0
		 */
		protected function render_message( $info ) {
			$plugin = $this->plugins[$info->plugin];
			$css_url = plugin_dir_url( __FILE__ ) . '/admin.css';
			$js_url = plugin_dir_url( __FILE__ ) . '/admin.js';

			?>
			<link rel="stylesheet" type="text/css" href="<?php echo esc_url( $css_url ); ?>" />
			<div class="notice frash-notice frash-notice-<?php echo esc_attr( $info->type ); ?>" style="display:none">
				<input type="hidden" name="type" value="<?php echo esc_attr( $info->type ); ?>" />
				<input type="hidden" name="plugin_id" value="<?php echo esc_attr( $info->plugin ); ?>" />
				<input type="hidden" name="url_wp" value="<?php echo esc_attr( $plugin->url_wp ); ?>" />
				<?php
				if ( 'email' == $info->type ) {
					$this->render_email_message( $plugin );
				} elseif ( 'rate' == $info->type ) {
					$this->render_rate_message( $plugin );
				}
				?>
			</div>
			<script src="<?php echo esc_url( $js_url ); ?>"></script>
			<?php
		}

		/**
		 * Output the contents of the email message.
		 * No return value. The code is directly output.
		 *
		 * @since  1.0.0
		 */
		protected function render_email_message( $plugin ) {
			$admin_email = get_site_option( 'admin_email' );
			$action = "https://edublogs.us1.list-manage.com/subscribe/post-json?u={$this->mc_user_id}&id={$plugin->mc_list_id}&c=?";

			$msg = __( "We're happy that you've chosen to install %s! Are you interested in how to make the most of this plugin? How would you like a quick 5 day email crash course with actionable advice on building your membership site? Only the info you want, no subscription!", 'wdev_frash' );
			$msg = apply_filters( 'wdev-email-message-' . $plugin->id, $msg );

			?>
			<div class="frash-notice-logo"><span></span></div>
				<div class="frash-notice-message">
					<?php
					printf(
						$msg,
						'<strong>' . $plugin->title . '</strong>'
					);
					?>
				</div>
				<div class="frash-notice-cta">
					<form action="<?php echo $action; ?>" method="get" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
						<input type="email" name="EMAIL" class="email" id="mce-EMAIL" value="<?php echo esc_attr( $admin_email ); ?>" required="required"/>
						<button class="frash-notice-act button-primary" data-msg="<?php _e( 'Thanks :)', 'wdev_frash' ); ?>" type="submit">
							<?php echo esc_html( $plugin->cta_email ); ?>
						</button>
						<button class="frash-notice-dismiss" data-msg="<?php _e( 'Saving', 'wdev_frash' ); ?>">
							<?php _e( 'No thanks', 'wdev_frash' ); ?>
						</button>
					</form>
				</div>
			<?php
		}

		/**
		 * Output the contents of the rate-this-plugin message.
		 * No return value. The code is directly output.
		 *
		 * @since  1.0.0
		 */
		protected function render_rate_message( $plugin ) {
			$user = wp_get_current_user();
			$user_name = $user->display_name;

			$msg = __( "Hey %s, you've been using %s for a while now, and we hope you're happy with it.", 'wdev_frash' ) . '<br />'. __( "We've spent countless hours developing this free plugin for you, and we would really appreciate it if you dropped us a quick rating!", 'wdev_frash' );
			$msg = apply_filters( 'wdev-rating-message-' . $plugin->id, $msg );

			?>
			<div class="frash-notice-logo"><span></span></div>
				<div class="frash-notice-message">
					<?php
					printf(
						$msg,
						'<strong>' . $user_name . '</strong>',
						'<strong>' . $plugin->title . '</strong>'
					);
					?>
				</div>
				<div class="frash-notice-cta">
					<button class="frash-notice-act button-primary" data-msg="<?php _e( 'Thanks :)', 'wdev_frash' ); ?>">
						<?php
						printf(
							__( 'Rate %s', 'wdev_frash' ),
							esc_html( $plugin->title )
						); ?>
					</button>
					<button class="frash-notice-dismiss" data-msg="<?php _e( 'Saving', 'wdev_frash' ); ?>">
						<?php _e( 'No thanks', 'wdev_frash' ); ?>
					</button>
				</div>
			<?php
		}

		/**
		 * Registers a new action handler. The callback function has the same
		 * name as the action hook.
		 *
		 * @since 1.0.0
		 */
		protected function add_action( $hook, $params = 1 ) {
			$method_name = strtolower( $hook );
			$method_name = preg_replace( '/[^a-z0-9]/', '_', $method_name );
			$handler = array( $this, $method_name );
			add_action( $hook, $handler, 5, $params );
		}
	}

	// Initialize the module.
	WDev_Frash::instance();
}