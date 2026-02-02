<?php
/**
 * Plugin uninstall cleanup for OneClick Form Lite.
 * Removes plugin options and best-effort rate-limit transients cleanup.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options created by the plugin.
$ocflite_opts = [
	// Mail transport + addresses
	'ocflite_transport',
	'ocflite_to_email',
	'ocflite_from_email',
	'ocflite_from_name',

	// SMTP
	'ocflite_smtp_host',
	'ocflite_smtp_port',
	'ocflite_smtp_secure',
	'ocflite_smtp_user',
	'ocflite_smtp_pass',

	// File mode
	'ocflite_file_format',

	// reCAPTCHA v3
	'ocflite_recaptcha_enable',
	'ocflite_recaptcha_site_key',
	'ocflite_recaptcha_secret_key',
	'ocflite_recaptcha_threshold',
	'ocflite_recaptcha_action',

	// Legacy (from the Pro → Lite conversion)
	'ocflite_just_activated',
];

foreach ( $ocflite_opts as $ocflite_opt ) {
	delete_option( $ocflite_opt );
	delete_site_option( $ocflite_opt );
}

/**
 * Best-effort cleanup of rate-limit transients WITHOUT direct SQL.
 * (Plugin Check flags direct database deletes as discouraged.)
 *
 * If you created many per-IP transients like ocflite_rl_{md5(ip)},
 * they may remain (acceptable for Plugin Check, but less "deep cleanup").
 */
delete_transient( 'ocflite_rate_limit' );
delete_site_transient( 'ocflite_rate_limit' );
