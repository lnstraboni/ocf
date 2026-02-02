<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/interface-mailer.php';

/**
 * Built-in SMTP mailer (no external deps).
 * Emits normalized error codes like "[smtp_connect_error] detail..."
 * Comments in English only.
 */
class OCFLITE_Mailer_SMTP implements OCFLITE_Mailer {

    private $host;
    private $port = 587;
    /** tls|ssl|none */
    private $secure = 'tls';
    private $user = '';
    private $pass = '';
    /** seconds */
    private $timeout = 15;
    /** @var resource|null */
    private $conn;
    private $debug = false;

    public function __construct( $opts = [] ) {
        $this->host    = isset( $opts['host'] )    ? (string) $opts['host']   : '';
        $this->port    = isset( $opts['port'] )    ? (int) $opts['port']      : 587;
        $this->secure  = isset( $opts['secure'] ) && in_array( $opts['secure'], [ 'tls', 'ssl', 'none' ], true ) ? $opts['secure'] : 'tls';
        $this->user    = isset( $opts['user'] )    ? trim( (string) $opts['user'] ) : '';
        $this->pass    = isset( $opts['pass'] )    ? trim( (string) $opts['pass'] ) : '';
        $this->timeout = isset( $opts['timeout'] ) ? (int) $opts['timeout']   : 15;
        $this->debug   = ! empty( $opts['debug'] );
    }

    public function send( $fromEmail, $fromName, $toEmail, $subject, $body, $replyToEmail = null, $replyToName = null ) {
        // Basic checks
        if ( ! $this->host || ! $this->port ) {
            throw new \Exception( '[smtp_connect_error] SMTP host/port not configured.' );
        }
        if ( ! is_email( $fromEmail ) || ! is_email( $toEmail ) ) {
            throw new \Exception( '[send_failed] Invalid email address.' );
        }

        // Subject must be single-line
        $subject = str_replace( [ "\r", "\n" ], ' ', (string) $subject );

        try {
            $this->connect(); // may throw [smtp_connect_error]
            $this->expect( 220, 'smtp_unexpected' );

            if ( ! $this->ehlo_or_helo() ) {
                throw new \Exception( '[smtp_unexpected] EHLO/HELO rejected.' );
            }

            if ( 'tls' === $this->secure ) {
                $this->cmd( 'STARTTLS' );
                $this->expect( 220, 'smtp_unexpected' );
                $this->enableTLS(); // may throw [smtp_tls_failed]
                if ( ! $this->ehlo_or_helo() ) {
                    throw new \Exception( '[smtp_unexpected] EHLO after STARTTLS rejected.' );
                }
            }

            if ( $this->user !== '' && $this->pass !== '' ) {
                $this->authLogin(); // may throw [smtp_auth_failed]
            }

            $this->cmd( 'MAIL FROM:<' . $fromEmail . '>' );
            $this->expect( 250, 'smtp_unexpected' );

            $this->cmd( 'RCPT TO:<' . $toEmail . '>' );
            $this->expect( [ 250, 251, 252 ], 'smtp_unexpected' );

            $this->cmd( 'DATA' );
            $this->expect( 354, 'smtp_unexpected' );

            $headers = $this->buildHeaders( $fromEmail, $fromName, $toEmail, $subject, $replyToEmail, $replyToName );
            $message = $headers . "\r\n\r\n" . $this->normalizeBody( (string) $body ) . "\r\n.\r\n";

            $this->write( $message ); // may throw [smtp_write_failed]
            $this->expect( 250, 'smtp_unexpected' );

            $this->cmd( 'QUIT' );
            $this->close();

            return true;

        } catch ( \Exception $e ) {
            $this->close();
            throw $e;
        }
    }

    /** Create TCP (or SSL) connection with sane TLS defaults */
    private function connect() {
        $remote = ( 'ssl' === $this->secure ? 'ssl://' : '' ) . $this->host . ':' . $this->port;

        $ctx = stream_context_create( [
            'ssl' => [
                'crypto_method'     => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                'SNI_enabled'       => true,
                'peer_name'         => $this->host,
            ]
        ] );

        $errno = 0; $errstr = '';
        $this->conn = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if ( ! $this->conn ) {
            throw new \Exception( '[smtp_connect_error] Connection failed.' );
        }


        stream_set_timeout( $this->conn, $this->timeout );
    }

    /** Upgrade to TLS after STARTTLS */
    private function enableTLS() {
        $ok = @stream_socket_enable_crypto(
            $this->conn,
            true,
            STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
        );
        if ( ! $ok ) {
            $ok = @stream_socket_enable_crypto( $this->conn, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT );
        }
        if ( ! $ok ) {
            throw new \Exception( '[smtp_tls_failed] Unable to negotiate TLS.' );
        }
    }

    /** EHLO then fallback to HELO */
    private function ehlo_or_helo() {
        $this->cmd( 'EHLO ' . $this->ehloName() );
        $resp = $this->readResponse();
        $code = (int) substr( $resp, 0, 3 );
        if ( 250 === $code ) { return true; }

        $this->cmd( 'HELO ' . $this->ehloName() );
        $resp = $this->readResponse();
        $code = (int) substr( $resp, 0, 3 );
        return ( 250 === $code );
    }

    /** EHLO name derived from site host */
    private function ehloName() {
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        return $host ? $host : 'localhost';
    }

    /** AUTH LOGIN with base64 user/pass */
    private function authLogin() {
        $this->cmd( 'AUTH LOGIN' );
        $this->expect( 334, 'smtp_auth_failed' );

        $this->write( base64_encode( (string) $this->user ) . "\r\n" );
        $this->expect( 334, 'smtp_auth_failed' );

        $this->write( base64_encode( (string) $this->pass ) . "\r\n" );
        $this->expect( 235, 'smtp_auth_failed' );
    }

    /** Build RFC-ish headers */
    private function buildHeaders( $fromEmail, $fromName, $toEmail, $subject, $replyToEmail, $replyToName ) {
        $date = gmdate( 'r' );

        $from = $fromName !== null && $fromName !== ''
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
            $rt = $replyToName !== null && $replyToName !== ''
                ? $this->encodeHeader( $replyToName ) . " <{$replyToEmail}>"
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

    /** Dot-stuff and normalize newlines to CRLF */
    private function normalizeBody( $text ) {
        $text  = str_replace( [ "\r\n", "\r" ], "\n", (string) $text );
        $lines = explode( "\n", $text );
        foreach ( $lines as &$ln ) {
            if ( isset( $ln[0] ) && $ln[0] === '.' ) { $ln = '.' . $ln; }
        }
        unset( $ln );
        return implode( "\r\n", $lines );
    }

    /** Write a single command line */
    private function cmd( $line ) { $this->write( $line . "\r\n" ); }

    /** Low-level write with error normalization */
    private function write( $data ) {
        $bytes = @fwrite( $this->conn, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
        if ( false === $bytes ) {
            throw new \Exception( '[smtp_write_failed] fwrite() returned false' );
        }
    }

    /** Check server response code(s) */
    private function expect( $codes, $codeOnFail = 'smtp_unexpected' ) {
        $codes = (array) $codes;
        $resp  = $this->readResponse();
        $code  = (int) substr( $resp, 0, 3 );
        if ( ! in_array( $code, $codes, true ) ) {
            if ( 'smtp_auth_failed' === $codeOnFail ) {
                throw new \Exception( '[smtp_auth_failed] SMTP authentication failed.' );
            }
            throw new \Exception( '[smtp_unexpected] Unexpected SMTP response.' );
        }
    }

    /** Read multiline server response */
    private function readResponse() {
        $lines = '';
        while ( ($line = @fgets( $this->conn, 515 )) !== false ) {
            $lines .= $line;
            if ( isset( $line[3] ) && $line[3] !== '-' ) { break; }
        }
        if ( $lines === '' ) {
            $meta = @stream_get_meta_data( $this->conn );
            if ( isset( $meta['timed_out'] ) && $meta['timed_out'] ) {
                throw new \Exception( '[smtp_timeout] Read timed out' );
            }
            throw new \Exception( '[smtp_unexpected] Empty response' );
        }
        return $lines;
    }

    /** Close connection safely */
    private function close() {
        if ( $this->conn ) {
            @fclose( $this->conn ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            $this->conn = null;
        }
    }
}
