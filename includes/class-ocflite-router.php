<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * REST router for OneClick Form Lite.
 */
class OCFLITE_Router {

	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() {
                $args = [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_submit' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'name'                 => [ 'type' => 'string',  'required' => false ],
                'email'                => [ 'type' => 'string',  'required' => false ],
                'subject'              => [ 'type' => 'string',  'required' => false ],
                'message'              => [ 'type' => 'string',  'required' => false ],
                'consent'              => [ 'type' => 'boolean', 'required' => false ],
                'ocflite_hp'           => [ 'type' => 'string',  'required' => false ],
                // Keep only the standard reCAPTCHA fields
                'g-recaptcha-response' => [ 'type' => 'string',  'required' => false ],
                'ocflite_action'       => [ 'type' => 'string',  'required' => false ],
            ],
        ];

		register_rest_route( 'oneclick-form-lite/v1', '/submit', $args );
	}

	public static function handle_submit( WP_REST_Request $req ) {

		// --- 1) CSRF: custom nonce OR REST nonce OR same-origin referer ---
		$nonce      = $req->get_param( 'ocflite_nonce' );
		$rest_nonce = $req->get_header( 'X-WP-Nonce' );
		$ok_custom  = $nonce      ? wp_verify_nonce( $nonce, 'ocflite_send' ) : false;
		$ok_rest    = $rest_nonce ? wp_verify_nonce( $rest_nonce, 'wp_rest' ) : false;

		$ref = '';
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		$ref_host  = $ref ? wp_parse_url( $ref, PHP_URL_HOST ) : '';
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$ok_origin = $ref_host && $site_host && ( strcasecmp( (string) $ref_host, (string) $site_host ) === 0 );

		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';

		if ( ! $ok_custom && ! $ok_rest && ! $ok_origin ) {
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_nonce' ], 403 );
		}

		// --- 2) IP rate limit: 10 / 15min (skip for authenticated backend users) ---
		$ip = '0.0.0.0';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		$key = 'ocflite_rl_' . md5( $ip );

		// Detect logged-in user via auth cookie instead of REST X-WP-Nonce header.
		$user_id = 0;
		if ( function_exists( 'wp_validate_auth_cookie' ) ) {
			$user_id = (int) wp_validate_auth_cookie( '', 'logged_in' );
		}
		$is_backend_user = $user_id && user_can( $user_id, 'edit_posts' );

		/**
		 * Whether to apply rate limiting.
		 *
		 * Default: true only for anonymous users (no valid logged-in cookie).
		 */
		$apply_rate_limit = apply_filters(
			'ocflite_use_rate_limit',
			! $is_backend_user,
			[
				'ip'      => $ip,
				'user_id' => $user_id,
				'request' => $req,
			]
		);

		if ( $apply_rate_limit ) {
			$cnt = (int) get_transient( $key );
			if ( $cnt > 10 ) {
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'rate_limited' ], 429 );
			}
			set_transient( $key, $cnt + 1, 15 * MINUTE_IN_SECONDS );
		}

		// --- 3) Sanitize & validate input ---
		$name     = sanitize_text_field( (string) $req->get_param( 'name' ) );
		$email    = sanitize_email(      (string) $req->get_param( 'email' ) );
		$subjectI = sanitize_text_field( (string) $req->get_param( 'subject' ) );
		$message  = wp_kses_post(       (string) $req->get_param( 'message' ) );
		$consent  = (bool) $req->get_param( 'consent' );
		$hp       = trim( (string) $req->get_param( 'ocflite_hp' ) );

		if ( $hp !== '' ) { return new WP_REST_Response( [ 'ok' => false, 'error' => 'spam_detected' ], 400 ); }
		if ( strlen( $name ) > 200 )    { return new WP_REST_Response( [ 'ok' => false, 'error' => 'name_too_long' ], 400 ); }
		if ( strlen( $subjectI ) > 200 ){ return new WP_REST_Response( [ 'ok' => false, 'error' => 'subject_too_long' ], 400 ); }
		if ( strlen( $message ) > 5000 ){ return new WP_REST_Response( [ 'ok' => false, 'error' => 'message_too_long' ], 400 ); }
		if ( ! $consent )               { return new WP_REST_Response( [ 'ok' => false, 'error' => 'consent_required' ], 400 ); }
		if ( empty( $name ) || ! is_email( $email ) || empty( trim( wp_strip_all_tags( $message ) ) ) ) {
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_input' ], 400 );
		}

		// --- 4) Optional reCAPTCHA v3 (single verification point) ---
		$rc_enabled   = (int) get_option( 'ocflite_recaptcha_enable', 0 ) === 1;
		$rc_secret    = trim( (string) get_option( 'ocflite_recaptcha_secret_key', '' ) );
		$rc_threshold = (float) get_option( 'ocflite_recaptcha_threshold', 0.5 );
		$rc_expected  = sanitize_key( (string) get_option( 'ocflite_recaptcha_action', 'contact_form' ) );


		if ( $rc_enabled && $rc_secret !== '' ) {
			$token  = (string) ( $req->get_param( 'g-recaptcha-response' )
				?: $req->get_param( 'ocf_recaptcha_token' )
				?: $req->get_param( 'recaptcha_token' )
				?: $req->get_param( 'recaptcha' )
				?: $req->get_param( 'token' )
				?: '' );

			$action = sanitize_key( (string) ( $req->get_param( 'ocflite_action' )
				?: $req->get_param( 'recaptcha_action' )
				?: $req->get_param( 'action_name' )
				?: '' ) );

		if ( $rc_expected && $action && $action !== $rc_expected ) {
			    return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_bad_action' ], 400 );
		}


			if ( $token === '' ) {
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_missing' ], 400 );
			}

			$response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				[
					'timeout' => 10,
					'body'    => [
						'secret'   => $rc_secret,
						'response' => $token,
						'remoteip' => $ip,
					],
				]
			);
			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_error' ], 400 );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$json = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code !== 200 || ! is_array( $json ) || empty( $json['success'] ) ) {
				if ( ! empty( $json['error-codes'] ) && in_array( 'timeout-or-duplicate', (array) $json['error-codes'], true ) ) {
					return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_timeout' ], 400 );
				}
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_failed' ], 400 );
			}
			if ( isset( $json['score'] ) ) {
				if ( (float) $json['score'] < $rc_threshold ) {
					return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_score_low' ], 400 );
				}
			} else {
				// Treat missing score as a verification failure to reuse existing i18n.
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_failed' ], 400 );
			}

			// Ensure action matches expected (reCAPTCHA v3).
			$remote_action = isset( $json['action'] ) ? sanitize_key( (string) $json['action'] ) : '';
			if ( $remote_action === '' ) {
				$remote_action = $action;
			}
			if ( $rc_expected && ( $remote_action === '' || $remote_action !== $rc_expected ) ) {
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_bad_action' ], 400 );
			}

			if ( $rc_expected && ! empty( $json['action'] ) ) {
			$real_action = sanitize_key( (string) $json['action'] );
			if ( $real_action !== $rc_expected ) {
				return new WP_REST_Response( [ 'ok' => false, 'error' => 'recaptcha_bad_action' ], 400 );
			}
		}
		
		}

		// --- 5) Mail configuration ---
		$to = get_option( 'ocflite_to_email' );
		if ( ! is_email( $to ) ) {
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'config_missing_to_email' ], 500 );
		}

		$fromEmail = sanitize_email( (string) get_option( 'ocflite_from_email', '' ) );

		if ( ! is_email( $fromEmail ) ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );

			if ( $host && strpos( $host, '.' ) !== false ) {
				$fromEmail = 'no-reply@' . $host;
			} else {
				$fromEmail = sanitize_email( (string) get_option( 'admin_email' ) );
			}

			if ( ! is_email( $fromEmail ) ) {
				$fromEmail = 'wordpress@example.com';
			}
		}
		$fromName = get_option( 'ocflite_from_name' );
		if ( ! $fromName ) {
			$fromName = get_bloginfo( 'name' );
		}

		// --- 6) Compose email ---
		/* translators: %s = site name */
		$subject = $subjectI ?: sprintf( __( 'New message — %s', 'oneclick-form-lite' ), get_bloginfo( 'name' ) );

		$lines   = [];
		/* translators: %s: sender name. */
		$lines[] = sprintf( __( 'Name: %s', 'oneclick-form-lite' ), $name );
		/* translators: %s: sender email. */
		$lines[] = sprintf( __( 'Email: %s', 'oneclick-form-lite' ), $email );
		/* translators: %s: sender IP address. */
		$lines[] = sprintf( __( 'IP: %s', 'oneclick-form-lite' ), $ip );
		/* translators: %s: date in ISO 8601 format. */
		$lines[] = sprintf( __( 'Date: %s', 'oneclick-form-lite' ), gmdate( 'c' ) );
		/* translators: %s: site URL. */
		$lines[] = sprintf( __( 'Site: %s', 'oneclick-form-lite' ), home_url() );
		/* translators: %s: site URL. */
		$lines[] = '';
		$lines[] = __( 'Message:', 'oneclick-form-lite' );
		$lines[] = trim( preg_replace( "/\r\n|\r|\n/", PHP_EOL, (string) $message ) );
		$body    = implode( PHP_EOL, $lines );

		$context = [
			'name'       => $name,
			'email'      => $email,
			'ip'         => $ip,
			'fromEmail'  => $fromEmail,
			'fromName'   => $fromName,
			'to'         => $to,
			'subject'    => $subject,
			'body'       => $body,
			'transport'  => get_option( 'ocflite_transport', 'wp' ),
			'site_url'   => home_url(),
		];

		$subject = apply_filters( 'ocflite_mail_subject', $subject, $context );
		$body    = apply_filters( 'ocflite_mail_body',    $body,    $context );

		do_action( 'ocflite_before_send', $context );

		// --- 7) Resolve mailer ---
		$mailer = self::resolve_mailer( $context['transport'] );

		// --- 8) Send ---
		try {
			$sent = $mailer
				? (bool) $mailer->send( $fromEmail, $fromName, $to, $subject, $body, $email, $name )
				: false;
		} catch ( Exception $e ) {
			do_action( 'ocflite_after_send', false, $context + [ 'exception' => $e->getMessage() ] );
			// Front should only show a generic failure message; technical details stay in admin logs.
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'send_failed' ], 500 );
		}

		do_action( 'ocflite_after_send', $sent, $context );

		if ( ! $sent ) {
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'send_failed' ], 500 );
		}

		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	private static function resolve_mailer( $transport ) {
		$dir = trailingslashit( OCFLITE_PLUGIN_DIR ) . 'includes/mail/';
		foreach ( [ 'interface-mailer.php', 'mailer-wp.php', 'mailer-smtp.php', 'mailer-file.php' ] as $f ) {
			$path = $dir . $f;
			if ( file_exists( $path ) ) require_once $path;
		}

		switch ( $transport ) {
			case 'smtp':
				if ( class_exists( 'OCFLITE_Mailer_SMTP' ) ) {
					return new OCFLITE_Mailer_SMTP( [
						'host'   => get_option( 'ocflite_smtp_host', '' ),
						'port'   => (int) get_option( 'ocflite_smtp_port', 587 ),
						'secure' => get_option( 'ocflite_smtp_secure', 'tls' ),
						'user'   => get_option( 'ocflite_smtp_user', '' ),
						'pass'   => get_option( 'ocflite_smtp_pass', '' ),
					] );
				}
				break;
			case 'file':
				if ( class_exists( 'OCFLITE_Mailer_File' ) ) {
					return new OCFLITE_Mailer_File( [
						'format' => get_option( 'ocflite_file_format', 'eml' ),
					] );
				}
				break;
			case 'wp':
			default:
				if ( class_exists( 'OCFLITE_Mailer_WP' ) ) {
					return new OCFLITE_Mailer_WP();
				}
		}
		return null;
	}
}
OCFLITE_Router::init();
