<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/interface-mailer.php';

/**
 * File-based mailer for local/dev environments.
 * Writes emails into uploads/ocf-mails/.
 * Emits normalized error codes like "[file_write_failed] detail..."
 */
class OCFLITE_Mailer_File implements OCFLITE_Mailer {

    /** 'eml' | 'txt' */
    private $format = 'eml';

    public function __construct( $opts = [] ) {
        $fmt = isset( $opts['format'] ) ? strtolower( (string) $opts['format'] ) : 'eml';
        $this->format = in_array( $fmt, [ 'eml', 'txt' ], true ) ? $fmt : 'eml';
    }

    public function send( $fromEmail, $fromName, $toEmail, $subject, $body, $replyToEmail = null, $replyToName = null ) {
        // Basic validation
        if ( ! is_email( $toEmail ) || ! is_email( $fromEmail ) ) {
            throw new \Exception( '[send_failed] Invalid email address.' );
        }

        // Subject must be single-line
        $subject = str_replace( [ "\r", "\n" ], ' ', (string) $subject );

        // Resolve uploads directory
        $upload = wp_upload_dir();
        if ( ! empty( $upload['error'] ) ) {
            throw new \Exception( '[file_write_failed] Upload directory is not available.' );
        }

        $dir = trailingslashit( (string) $upload['basedir'] ) . 'ocf-mails/';
        if ( ! wp_mkdir_p( $dir ) ) {
            throw new \Exception( '[file_write_failed] Could not create directory' );
        }

        $ext      = $this->format === 'txt' ? 'txt' : 'eml';
        $filename = sprintf( 'ocf-%s-%s.%s', gmdate( 'Ymd-His' ), wp_generate_password( 6, false, false ), $ext );
        $path     = trailingslashit( $dir ) . $filename;

        // Build file content
        if ( $ext === 'eml' ) {
            $headers = $this->buildHeaders( $fromEmail, (string) $fromName, $toEmail, $subject, $replyToEmail, $replyToName );
            $content = $headers . "\r\n\r\n" . $this->normalizeBody( (string) $body ) . "\r\n";
        } else {
            $content = "From: " . ( $fromName ? (string) $fromName . " <{$fromEmail}>" : $fromEmail ) . "\n";
            $content .= "To: <{$toEmail}>\n";
            $content .= "Subject: {$subject}\n";
            if ( is_email( $replyToEmail ) ) {
                $rt = ( $replyToName ? (string) $replyToName . " <{$replyToEmail}>" : $replyToEmail );
                $content .= "Reply-To: {$rt}\n";
            }
            $content .= "Date: " . gmdate( 'c' ) . "\n\n";
            $content .= (string) $body;
            $content  = preg_replace( "/\r\n|\r|\n/", PHP_EOL, (string) $content );
        }

        // Prefer WP_Filesystem when available.
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $written = false;
        $fs_ok   = WP_Filesystem();
        global $wp_filesystem;

        if ( $fs_ok && $wp_filesystem ) {
            if ( ! $wp_filesystem->is_writable( $dir ) ) {
                throw new \Exception( '[file_write_failed] Directory not writable' );
            }
            $written = (bool) $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );
        } if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            global $wp_filesystem;
            $fs_ok = WP_Filesystem();

            if ( ! $fs_ok || ! $wp_filesystem ) {
                throw new \Exception( '[file_write_failed] WP_Filesystem unavailable.' );
            }

            if ( ! $wp_filesystem->is_writable( $dir ) ) {
                throw new \Exception( '[file_write_failed] Directory not writable' );
            }

            $written = (bool) $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );

            if ( ! $written ) {
                throw new \Exception( '[file_write_failed] put_contents() failed' );
            }


        if ( ! $written ) {
            throw new \Exception( '[file_write_failed] put_contents() failed' );
        }

        return true;
    }

    /** Build basic headers suitable for .eml preview */
    private function buildHeaders( $fromEmail, $fromName, $toEmail, $subject, $replyToEmail, $replyToName ) {
        $date = gmdate( 'r' );

        $from = ( isset( $fromName ) && $fromName !== '' )
            ? $this->encodeHeader( $fromName ) . " <{$fromEmail}>"
            : $fromEmail;

        $msgId = sprintf(
            '<%s.%s@%s>',
            time(),
            wp_generate_password( 8, false, false ),
            wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost'
        );

        $headers = [
            'Date: ' . $date,
            'Message-ID: ' . $msgId,
            'From: ' . $from,
            'To: <' . $toEmail . '>',
            'Subject: ' . $this->encodeHeader( (string) $subject ),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: OneClick Form Lite',
        ];

        if ( is_email( $replyToEmail ) ) {
            $rt = ( isset( $replyToName ) && $replyToName !== '' )
                ? $this->encodeHeader( (string) $replyToName ) . " <{$replyToEmail}>"
                : $replyToEmail;
            $headers[] = 'Reply-To: ' . $rt;
        }

        return implode( "\r\n", $headers );
    }

    /** Encode non-ASCII header segments */
    private function encodeHeader( $text ) {
        $text = (string) $text;
        if ( preg_match( '/[^\x20-\x7E]/', $text ) ) {
            return '=?UTF-8?B?' . base64_encode( $text ) . '?=';
        }
        return str_replace( [ "\r", "\n" ], ' ', $text );
    }

    /** Normalize to CRLF for .eml readers */
    private function normalizeBody( $text ) {
        $text = str_replace( [ "\r\n", "\r" ], "\n", (string) $text ); // normalize to LF
        return str_replace( "\n", "\r\n", $text );                        // convert to CRLF
    }
}
