<?php
/**
 * Smush installer (update/upgrade procedures): WP_Smush_Installer class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.8.0
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

/**
 * Class WP_Smush_Installer for handling updates and upgrades of the plugin.
 *
 * @since 2.8.0
 */
class WP_Smush_Installer {
	/**
	 * Check if a existing install or new.
	 *
	 * @since 2.8.0  Moved to this class from wp-smush.php file.
	 */
	public static function smush_activated() {
		if ( ! defined( 'WP_SMUSH_ACTIVATING' ) ) {
			define( 'WP_SMUSH_ACTIVATING', true );
		}

		/* @var WpSmushSettings $wpsmush_settings */
		global $wpsmush_settings;

		$version  = get_site_option( WP_SMUSH_PREFIX . 'version' );
		$settings = ! empty( $wpsmush_settings->settings ) ? $wpsmush_settings->settings : $wpsmush_settings->init_settings();

		// If the version is not saved or if the version is not same as the current version,.
		if ( ! $version || WP_SMUSH_VERSION !== $version ) {
			global $wpdb;
			// Check if there are any existing smush stats.
			$results = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key=%s LIMIT 1",
					'wp-smpro-smush-data'
				)
			); // db call ok; no-cache ok.

			if ( $results ) {
				update_site_option( 'wp-smush-install-type', 'existing' );
			} else {
				// Check for existing settings.
				if ( false !== $settings['auto'] ) {
					update_site_option( 'wp-smush-install-type', 'existing' );
				}
			}

			// Create directory smush table.
			self::directory_smush_table();

			// Store the plugin version in db.
			update_site_option( WP_SMUSH_PREFIX . 'version', WP_SMUSH_VERSION );
		}
	}

	/**
	 * Handle plugin upgrades.
	 *
	 * @since 2.8.0
	 */
	public static function upgrade_settings() {
		// Avoid to execute this over an over in same thread.
		if ( defined( 'WP_SMUSH_ACTIVATING' ) || ( defined( 'WP_SMUSH_UPGRADING' ) && WP_SMUSH_UPGRADING ) ) {
			return;
		}

		$version = get_site_option( WP_SMUSH_PREFIX . 'version' );

		if ( false === $version ) {
			self::smush_activated();
		}

		if ( false !== $version && WP_SMUSH_VERSION !== $version ) {

			if ( ! defined( 'WP_SMUSH_UPGRADING' ) ) {
				define( 'WP_SMUSH_UPGRADING', true );
			}

			if ( version_compare( $version, '2.8.0', '<' ) ) {
				self::upgrade_2_8_0();
			}

			// Create/upgrade directory smush table.
			self::directory_smush_table();

			// Store the latest plugin version in db.
			update_site_option( WP_SMUSH_PREFIX . 'version', WP_SMUSH_VERSION );
		}
	}

	/**
	 * Upgrade old settings to new if required.
	 *
	 * We have changed exif data setting key from version 2.8
	 * Update the existing value to new one.
	 *
	 * @since 2.8.0
	 */
	private static function upgrade_2_8_0() {
		/* @var WpSmushSettings $wpsmush_settings */
		global $wpsmush_settings;

		// If exif is not preserved, it will be stripped by default.
		if ( $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'keep_exif' ) ) {
			// Set not to strip exif value.
			$wpsmush_settings->update_setting( WP_SMUSH_PREFIX . 'strip_exif', 0 );
			// Delete the old exif setting.
			$wpsmush_settings->delete_setting( WP_SMUSH_PREFIX . 'keep_exif' );
		}
	}

	/**
	 * Create or upgrade custom table for directory Smush.
	 *
	 * After creating or upgrading the custom table, update the path_hash
	 * column value and structure if upgrading from old version.
	 *
	 * @since 2.9.0
	 */
	public static function directory_smush_table() {
		global $wpsmush_dir;

		// Create a class object, if doesn't exists.
		if ( empty( $wpsmush_dir ) && class_exists( 'WP_Smush_Dir' ) ) {
			$wpsmush_dir = new WP_Smush_Dir();
		}

		// No need to continue on sub sites.
		if ( ! $wpsmush_dir->should_continue() ) {
			return;
		}

		// Create/upgrade directory smush table.
		$wpsmush_dir->create_table();

		// Run the directory smush table update.
		$wpsmush_dir->update_dir_path_hash();
	}
}
