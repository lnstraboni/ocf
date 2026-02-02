<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main bootstrap for modern environment (WP >= 6.4).
 *
 * Responsibility:
 * - Central place to wire all modern hooks.
 * - Ready for future differences vs legacy without touching the main plugin file.
 *
 * Note:
 * For now, most logic still lives in the main plugin file and existing includes.
 * This class is a safe, extendable entry point used by the router.
 */
final class OCFLITE_Main_Modern {

	/**
	 * Bootstraps the modern mode.
	 *
	 * Called once from includes/ocf-loader-modern.php:
	 * OCFLITE_Main_Modern::init();
	 *
	 * @return void
	 */
	public static function init() : void {
		// Placeholder for future modern-only wiring.
		// Example (later):
		// - self::load_admin();
		// - self::load_front();
		// - self::load_assets();
	}

	/**
	 * Example future method for admin-only loading.
	 * Keep empty for now to avoid duplicate includes.
	 *
	 * @return void
	 */
	private static function load_admin() : void {
		// Example for later:
		// $settings_file = OCFLITE_PLUGIN_DIR . 'admin/settings.php';
		// if ( file_exists( $settings_file ) ) {
		//     require_once $settings_file;
		// }
		//
		// $router_file = OCFLITE_PLUGIN_DIR . 'includes/class-ocflite-router.php';
		// if ( file_exists( $router_file ) ) {
		//     require_once $router_file;
		// }
	}

	/**
	 * Example future method for front-only loading.
	 *
	 * @return void
	 */
	private static function load_front() : void {
		// Example for later:
		// $frontend_file = OCFLITE_PLUGIN_DIR . 'includes/frontend.php';
		// if ( file_exists( $frontend_file ) ) {
		//     require_once $frontend_file;
		// }
		//
		// }
	}
}

