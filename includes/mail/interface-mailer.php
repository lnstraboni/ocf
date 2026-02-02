<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Interface: OCFLITE_Mailer
 *
 * Purpose:
 *  Defines a minimal, swappable email transport for OneClick Form Lite.
 *  Implementations: WordPress (wp_mail), SMTP (built-in), File (dev).
 *
 * Contract:
 *  - Return `true` on successful send, `false` otherwise.
 *  - Implementations may throw \Exception for transport-level errors.
 *  - Do not emit user-facing strings here; keep transports silent and let callers map errors to i18n messages.
 *
 * Parameters:
 *  @param string      $fromEmail      Envelope/header From email (validated upstream).
 *  @param string      $fromName       Human-readable From name (may be empty).
 *  @param string      $toEmail        Recipient email (validated upstream).
 *  @param string      $subject        Message subject (UTF-8).
 *  @param string      $body           Plain-text body (UTF-8).
 *  @param string|null $replyToEmail   Optional Reply-To email (user input).
 *  @param string|null $replyToName    Optional Reply-To name (user input).
 *
 * @return bool Success flag.
 */
interface OCFLITE_Mailer {
    /**
     * Send an email using the underlying transport.
     *
     * @param string      $fromEmail
     * @param string      $fromName
     * @param string      $toEmail
     * @param string      $subject
     * @param string      $body
     * @param string|null $replyToEmail
     * @param string|null $replyToName
     *
     * @return bool
     */
    public function send( $fromEmail, $fromName, $toEmail, $subject, $body, $replyToEmail = null, $replyToName = null );
}
