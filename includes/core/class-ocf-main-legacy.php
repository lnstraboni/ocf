<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main bootstrap for legacy environment (WP < 6.4).
 *
 * Responsibility:
 * - Entry point for legacy-specific behavior.
 * - Allows to diverge from modern code only where needed.
 */
final class OCFLITE_Main_Legacy {

	/**
	 * Bootstraps the legacy mode.
	 *
	 * Called once from includes/ocf-loader-legacy.php:
	 * OCFLITE_Main_Legacy::init();
	 *
	 * @return void
	 */
	public static function init() : void {
		// Placeholder for future legacy-only wiring.
		// For now, legacy will still rely on the common logic
		// already present in the main plugin file.
	}

	/**
	 * Example future method for legacy admin loading.
	 *
	 * @return void
	 */
	private static function load_admin() : void {
		// Example for later if you need different behavior on old WP.
	}

	/**
	 * Example future method for legacy front loading.
	 *
	 * @return void
	 */
	private static function load_front() : void {
		// Example for later if enqueue / assets differ on old WP.
	}
}
