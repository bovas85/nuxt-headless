<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://wordpress.org/plugins/rest-api-enabler
 * @since      1.0.0
 *
 * @package    REST_API_Enabler
 * @subpackage REST_API_Enabler/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    REST_API_Enabler
 * @subpackage REST_API_Enabler/admin
 * @author     Mickey Kay Creative mickey@mickeykaycreative.com
 */
class REST_API_Enabler_Admin {

	/**
	 * The main plugin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      REST_API_Enabler    $plugin    The main plugin instance.
	 */
	private $plugin;

	/**
	 * The slug of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_slug    The slug of this plugin.
	 */
	private $plugin_slug;

	/**
	 * The display name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The plugin display name.
	 */
	protected $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The instance of this class.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      REST_API_Enabler_Admin    $instance    The instance of this class.
	 */
	private static $instance = null;

	/**
	 * Plugin options.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 */
	protected $options;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return    REST_API_Enabler_Admin    A single instance of this class.
	 */
	public static function get_instance( $plugin ) {

		if ( null == self::$instance ) {
			self::$instance = new self( $plugin );
		}

		return self::$instance;

	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_slug       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;
		$this->plugin_slug = $this->plugin->get( 'slug' );
		$this->plugin_name = $this->plugin->get( 'name' );
		$this->version = $this->plugin->get( 'version' );
		$this->options = $this->plugin->get( 'options' );

	}

	/**
	 * Add settings page.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {

		$this->settings_page = add_options_page(
			__( 'REST API Enabler', 'rest-api-enabler' ), // Page title
			__( 'REST API Enabler', 'rest-api-enabler' ), // Menu title
			'manage_options', // Capability
			$this->plugin_slug, // Page ID
			array( $this, 'do_settings_page' ) // Callback
		);

	}

	/**
	 * Output contents of settings page.
	 *
	 * @since 1.0.0
	 */
	public function do_settings_page() {
		?>
		<div class="wrap <?php echo $this->plugin_slug; ?>-settings">
	        <h1><?php echo $this->plugin_name; ?></h1>
	        <?php

			if ( ! defined( 'REST_API_VERSION' ) ) {
				echo '<p>' . sprintf( __( 'You need to install the %sWordPress REST API%s for this plugin to work.' ), '<a href="https://wordpress.org/plugins/rest-api/" target="_blank">', '</a>' ) . '</p>';
				return;
			}

			// Set up tab/settings.
			$tab_base_url = "?page={$this->plugin_slug}";
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : null;

			?>
	        <h2 class="nav-tab-wrapper">
	        	<a href="<?php echo $tab_base_url; ?>&tab=post-types" class="nav-tab <?php echo ( ! $active_tab || $active_tab == 'post-types' ) ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Post Types', 'rest-api-enabler' ); ?></a>
	        	<a href="<?php echo $tab_base_url; ?>&tab=post-meta" class="nav-tab <?php echo $active_tab == 'post-meta' ? 'nav-tab-active' : ''; ?>"><?php echo __( 'Post Meta', 'rest-api-enabler' ); ?></a>
	        </h2>
			<form action='options.php' method='post'>
				<?php settings_fields( $this->plugin_slug ); ?>
				<div class="rae-settings-tab rae-post-types-settings <?php echo ( ! $active_tab || $active_tab == 'post-types' ) ? 'active' : ''; ?>"><?php $this->do_post_types_settings(); ?></div>
				<div class="rae-settings-tab rae-post-meta-settings <?php echo ( $active_tab == 'post-meta' ) ? 'active' : ''; ?>"><?php $this->do_post_meta_settings(); ?></div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php

	}

	/**
	 * Add settings fields to the settings page.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_fields() {

		global $wp_post_types;

		ksort( $wp_post_types );

		register_setting(
			$this->plugin_slug, // Option group
			$this->plugin_slug, // Option name
			array( $this, 'validate_settings' ) // Sanitization
		);

		// Post Types settings section
		add_settings_section(
			'post_types', // Section ID
			null, // Title
			null, // Callback
			"{$this->plugin_slug}-post_types_settings" // Page
		);

		// Post Meta settings section
		add_settings_section(
			'post_meta', // Section ID
			null, // Title
			null, // Callback
			"{$this->plugin_slug}-post_types_meta" // Page
		);

		// Add post type settings.
		foreach ( $wp_post_types as $post_type => $post_type_object ) {

			$id = "post_type_{$post_type}";

			add_settings_field(
				$id, // ID
				$post_type_object->labels->name . '<br/><small>(' . $post_type_object->name . ')</small>',
				array( $this, 'render_post_type_settings' ), // Callback
				"{$this->plugin_slug}-post_types_settings", // Page
				'post_types', // Section
				array( // Args
					'id'               => $id,
					'post_type_object' => $post_type_object,
				)
			);

		}

		// Post Meta checkboxes.
		$id = 'post-meta-checkboxes';
		add_settings_field(
			$id, // ID
			null,
			array( $this, 'do_post_meta_settings' ), // Callback
			"{$this->plugin_slug}-post_types_meta", // Page
			'post_meta', // Section
			array( // Args
				'id' => $id,
			)
		);

	}

	/**
	 * Output post types setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML for entire post types settings.
	 */
	public function do_post_types_settings() {

		printf( '<p>%s</p>',
			__( 'Select which post types to enable, and specify their REST bases.', 'rest-api-enabler' )
		);

		do_settings_sections( "{$this->plugin_slug}-post_types_settings" );

	}

	/**
	 * Output post meta settings.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML for entire post meta settings.
	 */
	public function do_post_meta_settings() {

		echo '<div class="rae-post-meta-checkboxes">';

		if ( $this->plugin->post_meta_exists_to_enable() ) {

			printf( '<p>%s</p>',
				__( 'Select which post meta to include in REST API response.', 'rest-api-enabler' )
			);

			$this->do_post_meta_check_buttons();
			$this->do_post_meta_checkboxes();

		} else {
			printf( '<p>%s</p>',
				__( 'There is no unprotected post meta to enable.', 'rest-api-enabler' )
			);
		}

		echo '</div>';

	}

	/**
	 * Output post meta 'check/uncheck all' buttons.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML for post meta buttons.
	 */
	private function do_post_meta_check_buttons() {

		echo '<div class="rae-post-meta-check-buttons">';

		printf( '<a href="#" class="%s">%s %s</a>',
			'button rae-check-all',
			'<span class="dashicons dashicons-yes"></span>',
			__( 'Check all', 'rest-api-enabler' )
		);

		printf( '<a href="#" class="%s">%s %s</a>',
			'button rae-uncheck-all',
			'<span class="dashicons dashicons-no-alt"></span>',
			__( 'Uncheck all', 'rest-api-enabler' )
		);

		echo '</div>';

	}

	/**
	 * Output post meta checkboxes in columns.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML output for post meta checkboxes.
	 */
	private function do_post_meta_checkboxes() {

		// Break up post meta into groups for columns.
		$post_meta_keys = $this->plugin->get_post_meta_keys();
		$post_meta_count = count( $post_meta_keys );
		$post_meta_groups = array_chunk( $post_meta_keys, (int) ceil( $post_meta_count / 3 ) );

		foreach ( $post_meta_groups as $post_meta_keys ) {
			?>
			<div class="rae-col">
			<?php

			foreach ( $post_meta_keys as $post_meta_key ) {

				$args = array(
					'id'           => 'post_meta_individual',
					'secondary_id' => $post_meta_key,
					'description'  => $post_meta_key,
					'save_null' => true,
				);

				$this->render_checkbox( $args );
				echo '<br />';

			}

			?>
			</div>
			<?php

		}

	}

	/**
	 * Render combined inputs for post types settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $args Array of settings args.
	 */
	public function render_post_type_settings( $args ) {

		// Do post type checkbox.
		$args['secondary_id'] = 'show_in_rest';
		$this->render_checkbox( $args );

		// Do post type REST base.
		$args['secondary_id'] = 'rest_base';

		// Get input markup.
		ob_start();
		$this->render_text_input( $args );
		$rest_base_output = ob_get_clean();
		printf( '&nbsp;&nbsp;&nbsp;<span class="description rae-rest-base rae-fadeable-opacity rae-hidden">%s%s</span>', rest_url( '/wp/v2/' ), $rest_base_output );

	}

	/**
	 * Render checkbox input for settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $args Args from add_settings_field().
	 */
	public function render_checkbox( $args ) {

		// Set up option name and value.
		if ( isset( $args['secondary_id'] ) ) {
			$option_name = $this->get_option_name( $args['id'], $args['secondary_id'] );
			$option_value = $this->get_option_value( $args['id'], $args['secondary_id'] );
		} else {
			$option_name = $this->get_option_name( $args['id'] );
			$option_value = $this->get_option_value( $args['id'] );
		}

		$checked = isset( $option_value ) ? $option_value : null;

		// Get post type REST info.
		if ( isset ( $args['post_type_object'] ) ) {

			$post_type_object = $args['post_type_object'];
			$init_rest_base = isset( $post_type_object->rest_base ) ? $post_type_object->rest_base : '';

			// Get checked value based on saved value, or existing value if option doesn't exist.
			if ( isset( $option_value ) ) {
				$checked = $option_value;
			} elseif ( $init_rest_base ) {
				$checked = true;
			}

		}

		// Render hidden input set to 0 to save unchecked value as non-null.
		if ( empty( $args['save_null'] ) ) {

			printf(
				'<input type="hidden" value="0" id="%s" name="%s"/>',
				$option_name,
				$option_name
			);

		}

		printf(
			'<label for="%s"><input type="checkbox" value="1" id="%s" name="%s" %s/>&nbsp;%s</label>',
			$option_name,
			$option_name,
			$option_name,
			checked( 1, $checked, false ),
			! empty( $args['description'] ) ? '<span class="rae-description">' . $args['description'] . '</span>': ''
		);

	}

	/**
	 * Render text input for settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $args Args from add_settings_field().
	 */
	public function render_text_input( $args ) {

		// Set up option name and value.
		if ( isset( $args['secondary_id'] ) ) {
			$option_name = $this->get_option_name( $args['id'], $args['secondary_id'] );
			$option_value = $this->get_option_value( $args['id'], $args['secondary_id'] );
		} else {
			$option_name = $this->get_option_name( $args['id'] );
			$option_value = $this->get_option_value( $args['id'] );
		}

		$value = $option_value;

		// Get post type REST info.
		if ( ! $value && isset ( $args['post_type_object'] ) ) {

			$post_type_object = $args['post_type_object'];
			$rest_base = isset( $post_type_object->rest_base ) ? $post_type_object->rest_base : '';

			// Auto-generate initial rest_base if not already set.
			if ( ! $rest_base ) {
				$rest_base = str_replace( '_', '-', $args['post_type_object']->name );
			}

			$value = $rest_base;

		}

		printf(
			'%s<input type="text" value="%s" id="%s" name="%s" class="regular-text %s"/>%s',
			! empty( $args['sub_heading'] ) ? '<b>' . $args['sub_heading'] . '</b><br />' : '',
			$value,
			$option_name,
			$option_name,
			! empty( $args['class'] ) ? $args['class'] : '',
			! empty( $args['description'] ) ? sprintf( '<br /><p class="description" for="%s">%s</p>',
				$option_name, $args['description'] ) : ''
		);

	}

	/**
	 * Render select input for settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Args from add_settings_field().
	 */
	public function render_select( $args ) {

		if ( ! isset( $args['options'] ) ) {
			return;
		}

		// Set up option name and value.
		if ( isset( $args['secondary_id'] ) ) {
			$option_name = $this->get_option_name( $args['id'], $args['secondary_id'] );
			$option_value = $this->get_option_value( $args['id'], $args['secondary_id'] );
		} else {
			$option_name = $this->get_option_name( $args['id'] );
			$option_value = $this->get_option_value( $args['id'] );
		}

		printf(
			'<select id="%s" name="%s" %s"/>',
			$option_name,
			$option_name,
			! empty( $args['class'] ) ? $args['class'] : ''
		);

		// Output each option.
		foreach ( $args['options'] as $option_slug => $option_name ) {

			printf(
				'<option %s value="%s"/>%s</option>',
				selected( $option_value, $option_slug, false ),
				$option_slug,
				$option_name
			);

		}

		echo '</select>';

	}

	/**
	 * Output <hr /> tag below settings section titles.
	 *
	 * @since 1.0.0
	 */
	public function do_hr() {
		echo '<hr />';
	}

	public function string_compare( $a, $b ) {
		return strcmp( strtolower( $a->meta_key ), strtolower( $b->meta_key ) );
	}

	/**
	 * Get option name based on primary and secondary id's.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $option_id    Primary option id.
	 * @param string  $secondary_id Secondary option id.
	 *
	 * @return string Option name.
	 */
	private function get_option_name( $option_id, $secondary_id = '' ) {
		if ( $secondary_id ) {
			return sprintf( '%s[%s][%s]', $this->plugin_slug, $option_id, $secondary_id );
		} else {
			return sprintf( '%s[%s]', $this->plugin_slug, $option_id );
		}
	}

	/**
	 * Get option value based on primary and secondary id's.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $option_id    Primary option id.
	 * @param string  $secondary_id Secondary option id.
	 *
	 * @return mixed Option value.
	 */
	private function get_option_value( $option_id, $secondary_id = '' ) {

		if ( $secondary_id ) {
			return isset( $this->options[ $option_id ][ $secondary_id ] ) ? $this->options[ $option_id ][ $secondary_id ] : null;
		} else {
			return isset( $this->options[ $option_id ] ) ? $this->options[ $option_id ] : null;
		}

	}

	/**
	 * Validate saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $input Saved inputs.
	 *
	 * @return array Update settings.
	 */
	public function validate_settings( $input ) {

		$new_input = $input;

		return $new_input;

	}

	/**
	 * Register the stylesheets for the admin.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_slug, plugin_dir_url( __FILE__ ) . 'css/rest-api-enabler-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the scripts for the admin.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_slug, plugin_dir_url( __FILE__ ) . 'js/rest-api-enabler-admin.js', array( 'jquery' ), $this->version, false );

	}

}
