<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/interface-mailer.php';

/**
 * WordPress mailer using wp_mail() (PHPMailer under the hood).
 * Emits normalized error codes like "[wp_mail_failed] detail..."
 * Comments in English only.
 */
class OCFLITE_Mailer_WP implements OCFLITE_Mailer {

    public function send( $fromEmail, $fromName, $toEmail, $subject, $body, $replyToEmail = null, $replyToName = null ) {
        // Subject must be single-line
        $subject = str_replace( [ "\r", "\n" ], ' ', (string) $subject );

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
        if ( is_email( $replyToEmail ) ) {
            // Safe Reply-To header
            $headers[] = 'Reply-To: ' . ( $replyToName ? sprintf( '%s <%s>', $replyToName, $replyToEmail ) : $replyToEmail );
        }

        // Filters to override From
        $from_cb = function( $e ) use ( $fromEmail ) {
            return is_email( $fromEmail ) ? $fromEmail : $e;
        };
        $from_name_cb = function( $n ) use ( $fromName ) {
            return ( isset( $fromName ) && '' !== $fromName ) ? $fromName : $n;
        };

        // Capture wp_mail failure details
        $last_err = null;
        $fail_cb = function( $wp_error ) use ( &$last_err ) {
            if ( is_wp_error( $wp_error ) ) {
                $last_err = $wp_error->get_error_message();
            }
        };

        add_filter( 'wp_mail_from',      $from_cb,      100 );
        add_filter( 'wp_mail_from_name', $from_name_cb, 100 );
        add_action( 'wp_mail_failed',    $fail_cb,      100 );

        try {
            $sent = wp_mail( $toEmail, $subject, (string) $body, $headers );
        } finally {
            remove_filter( 'wp_mail_from',      $from_cb,      100 );
            remove_filter( 'wp_mail_from_name', $from_name_cb, 100 );
            remove_action( 'wp_mail_failed',    $fail_cb,      100 );
        }

        if ( ! $sent ) {
            // Emit a machine-readable error code; detail is for logs only.
            throw new \Exception( '[wp_mail_failed] Email sending failed.' );
        }

        return true;
    }
}
