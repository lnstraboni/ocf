<?php
/**
 * Plugin Name: OneClick Form Lite
 * Description: RGPD-ready contact form plugin with built-in SMTP, test email tool, optional file mode, and Google reCAPTCHA v3.
 * Version: 1.1.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: KitCode
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oneclick-form-lite
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 */

/** === Core constants === */
define( 'OCFLITE_VERSION',     '1.1.3' );
define( 'OCFLITE_PLUGIN_FILE', __FILE__ );
define( 'OCFLITE_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'OCFLITE_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
if ( ! defined( 'OCFLITE_EDITION' ) ) define( 'OCFLITE_EDITION', 'lite' );
/** Optional convenience constants for legacy code */
if ( ! defined( 'OCFLITE_PATH' ) ) define( 'OCFLITE_PATH', OCFLITE_PLUGIN_DIR );
if ( ! defined( 'OCFLITE_URL' ) )  define( 'OCFLITE_URL',  OCFLITE_PLUGIN_URL );

/** === Runtime router: select modern vs legacy mode === */
global $wp_version;
$ocflite_wp_version = isset( $wp_version ) ? floatval( $wp_version ) : 0.0;

if ( ! defined( 'OCFLITE_MODE' ) ) {
	define(
		'OCFLITE_MODE',
		( $ocflite_wp_version >= 6.4 ) ? 'modern' : 'legacy'
	);
}

if ( OCFLITE_MODE === 'modern' ) {
    require_once OCFLITE_PLUGIN_DIR . 'includes/ocf-loader-modern.php';
} else {
    require_once OCFLITE_PLUGIN_DIR . 'includes/ocf-loader-legacy.php';
}

/* =============================================================================
 * I18n — always follow WordPress/User locale
 * ============================================================================= */

/** Return the effective locale for this plugin (admin uses user locale). */
function ocflite_effective_locale() {
    if ( function_exists( 'is_admin' ) && is_admin() && function_exists( 'get_user_locale' ) ) { return get_user_locale(); }
    if ( function_exists( 'determine_locale' ) ) { return determine_locale(); }
    return get_locale();
}

/** Scope the locale override ONLY to this plugin's text domain. */
add_filter( 'plugin_locale', function( $locale, $domain ){
    if ( 'oneclick-form-lite' !== $domain ) { return $locale; }
    return ocflite_effective_locale();
}, 1, 2 );

// Plugin row links: Settings + Documentation + Support.
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    function ( $links ) {
        // Settings page.
        $settings_url  = admin_url( 'admin.php?page=ocflite' );
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $settings_url ),
            esc_html__( 'Settings', 'oneclick-form-lite' )
        );

        // HTML documentation in /docs/.
        $docs_url  = plugins_url( 'docs/oneclick-form-lite-user-guide.html', __FILE__ );
        $docs_link = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url( $docs_url ),
            esc_html__( 'Documentation', 'oneclick-form-lite' )
        );

        $new_links = [
            'settings' => $settings_link,
            'docs'     => $docs_link,
        ];

        // Our links first, then the default ones (Deactivate, etc.).
        return array_merge( $new_links, $links );
    }
);

add_filter(
    'plugin_row_meta',
    function ( $links, $file ) {
        if ( plugin_basename( __FILE__ ) !== $file ) {
            return $links;
        }

        // Support mailto link under the description.
        $support_link = sprintf(
            '<a href="%s">%s</a>',
            esc_attr( 'mailto:support@oneclickform.com' ),
            esc_html__( 'Support', 'oneclick-form-lite' )
        );

        $links[] = $support_link;

        return $links;
    },
    10,
    2
);

/**
 * Fallback dictionary used if .mo files are not present.
 * IMPORTANT: Keys must exactly match the original English strings used in the plugin.
 */
function ocflite_gettext_fallback_map() {
    return [
        'fr_FR' => [
            ' OneClick Form Lite' => ' OneClick Form Lite',
            'Settings' => 'Réglages',
            'Save' => 'Enregistrer',
            'Save Changes' => 'Enregistrer les modifications',
            'Settings saved.' => 'Réglages enregistrés.',            'Email & Sending' => 'E-mail & envoi',
            'Transport' => 'Méthode d’envoi',
            'Recipient' => 'Destinataire',
            'From (email)' => 'Adresse d’expéditeur',
            'From (name)'  => 'Nom d’expéditeur',
            'WordPress (wp_mail)' => 'WordPress (wp_mail)',
            'SMTP (built-in)' => 'SMTP (intégré)',
            'File mode' => 'Mode fichier',
            'In File mode, emails are saved to uploads/ocf-mails/ for offline testing.' =>
                'En mode Fichier, les e-mails sont enregistrés dans uploads/ocf-mails/ pour des tests hors-ligne.',
            'SMTP' => 'SMTP',
            'SMTP Host' => 'Hôte SMTP',
            'SMTP Port' => 'Port SMTP',
            'Security'  => 'Sécurité',
            'Username'  => 'Nom d’utilisateur',
            'Password'  => 'Mot de passe',
            'TLS (STARTTLS)' => 'TLS (STARTTLS)',
            'SSL' => 'SSL',
            'None (not recommended)' => 'Aucune (déconseillé)',
            'File Mode' => 'Mode Fichier',
            'File format' => 'Format de fichier',
            '.eml' => '.eml',
            '.txt' => '.txt',
            'Test Email' => 'E-mail de Test',
            'Send test email' => 'Envoyer un e-mail de test',
            'Use this to verify your transport configuration. It will send a simple test message to the configured Recipient.' =>
                'Utilisez ceci pour vérifier votre configuration d’envoi. Un message de test sera envoyé au Destinataire configuré.',
            'Test email sent successfully.' => 'E-mail de test envoyé avec succès.',
            'Email sending failed.' => 'L’envoi de l’e-mail a échoué.',
            'Unauthorized' => 'Non autorisé',
            'OneClick Form Lite — Test email' => 'OneClick Form Lite — E-mail de test',
            'This is a OneClick Form Lite test email.' => 'Ceci est un e-mail de test OneClick Form Lite.',
            'Name' => 'Nom','Email' => 'E-mail','Subject' => 'Objet','Message' => 'Message',
            'Send' => 'Envoyer','Sending' => 'Envoi en cours',
            'Please fill out this field.' => 'Veuillez compléter ce champ.',
            'Please enter a valid email address.' => 'Veuillez saisir une adresse e-mail valide.',
            'Thank you! Your message has been sent.' => 'Merci ! Votre message a été envoyé.',
            'I consent to having this website store my submitted information so they can respond to my inquiry.' =>
                'J’accepte que ce site stocke mes informations envoyées afin de répondre à ma demande.',
            // Front error messages
            'Security token is invalid. Please refresh the page.' => 'Le jeton de sécurité est invalide. Veuillez rafraîchir la page.',
            'Too many attempts. Please try again later.' => 'Trop de tentatives. Réessayez plus tard.',
            'Please check your inputs and try again.' => 'Vérifiez vos champs et réessayez.',
            'You must consent to data processing to send this form.' => 'Vous devez consentir au traitement des données pour envoyer ce formulaire.',
            'Recipient email is not configured. Contact the site administrator.' => 'Le destinataire n’est pas configuré. Contactez l’administrateur du site.',
            'Your message could not be sent. Please try again later.' => 'Votre message n’a pas pu être envoyé. Réessayez plus tard.',
            'Name is too long.' => 'Le nom est trop long.',
            'Subject is too long.' => 'L’objet est trop long.',
            'Message is too long.' => 'Le message est trop long.',
            'Spam detected. Please try again.' => 'Spam détecté. Veuillez réessayer.',
            'Network error. Please check your connection.' => 'Erreur réseau. Vérifiez votre connexion.',
            'Unexpected response from the server.' => 'Réponse inattendue du serveur.',
            // reCAPTCHA
            'reCAPTCHA token is missing.' => 'Le jeton reCAPTCHA est manquant.',
            'reCAPTCHA verification failed.' => 'La vérification reCAPTCHA a échoué.',
            'reCAPTCHA score is too low.' => 'Score reCAPTCHA trop faible.',
            'reCAPTCHA action mismatch.' => 'Action reCAPTCHA non valide.',
            'reCAPTCHA service error.' => 'Erreur du service reCAPTCHA.',
        ],
        'es_ES' => [
            'Settings' => 'Ajustes',
            'Save' => 'Guardar',
            'Settings saved.' => 'Ajustes guardados.',
            'Email & Sending' => 'Correo y envío',
            'Transport' => 'Método de envío',
            'Recipient' => 'Destinatario',
            'From (email)' => 'Correo del remitente',
            'From (name)'  => 'Nombre del remitente',
            'WordPress (wp_mail)' => 'WordPress (wp_mail)',
            'SMTP (built-in)' => 'SMTP (integrado)',
            'File mode' => 'Modo archivo',
            'In File mode, emails are saved to uploads/ocf-mails/ for offline testing.' =>
                'En modo Archivo, los correos se guardan en uploads/ocf-mails/ para pruebas sin conexión.',
            'SMTP' => 'SMTP','SMTP Host' => 'Servidor SMTP','SMTP Port' => 'Puerto SMTP',
            'Security' => 'Seguridad','Username' => 'Usuario','Password' => 'Contraseña',
            'TLS (STARTTLS)' => 'TLS (STARTTLS)','SSL' => 'SSL','None (not recommended)' => 'Ninguna (no recomendado)',
            'File Mode' => 'Modo Archivo','File format' => 'Formato de archivo','.eml' => '.eml','.txt' => '.txt',
            'Test Email' => 'Correo de Prueba',
            'Send test email' => 'Enviar correo de prueba',
            'Test email sent successfully.' => 'Correo de prueba enviado correctamente.',
            'Email sending failed.' => 'Falló el envío del correo.',
            'Unauthorized' => 'No autorizado',
            'Name' => 'Nombre','Email' => 'Correo electrónico','Subject' => 'Asunto','Message' => 'Mensaje',
            'Send' => 'Enviar','Sending' => 'Enviando',
            'Please fill out this field.' => 'Por favor complete este campo.',
            'Please enter a valid email address.' => 'Por favor introduce una dirección de correo válida.',
            'Thank you! Your message has been sent.' => '¡Gracias! Su mensaje ha sido enviado.',
            'I consent to having this website store my submitted information so they can respond to my inquiry.' =>
                'Autorizo que este sitio almacene la información enviada para poder responder a mi consulta.',
            'Security token is invalid. Please refresh the page.' => 'El token de seguridad no es válido. Actualiza la página.',
            'Too many attempts. Please try again later.' => 'Demasiados intentos. Inténtalo más tarde.',
            'Please check your inputs and try again.' => 'Revisa los campos e inténtalo de nuevo.',
            'You must consent to data processing to send this form.' => 'Debes aceptar el tratamiento de datos para enviar este formulario.',
            'Recipient email is not configured. Contact the site administrator.' => 'El destinatario no está configurado. Contacta con el administrador.',
            'Your message could not be sent. Please try again later.' => 'No se pudo enviar tu mensaje. Inténtalo más tarde.',
            'Name is too long.' => 'El nombre es demasiado largo.',
            'Subject is too long.' => 'El asunto es demasiado largo.',
            'Message is too long.' => 'El mensaje es demasiado largo.',
            'Spam detected. Please try again.' => 'Se detectó spam. Inténtalo de nuevo.',
            'Network error. Please check your connection.' => 'Error de red. Revisa tu conexión.',
            'Unexpected response from the server.' => 'Respuesta inesperada del servidor.',
            'reCAPTCHA token is missing.' => 'Falta el token de reCAPTCHA.',
            'reCAPTCHA verification failed.' => 'La verificación de reCAPTCHA falló.',
            'reCAPTCHA score is too low.' => 'La puntuación de reCAPTCHA es demasiado baja.',
            'reCAPTCHA action mismatch.' => 'Acción de reCAPTCHA no válida.',
            'reCAPTCHA service error.' => 'Error del servicio reCAPTCHA.',
        ],
        'pt_BR' => [
            'Settings' => 'Configurações',
            'Save' => 'Salvar',
            'Settings saved.' => 'Configurações salvas.',
            'Email & Sending' => 'E-mail e envio',
            'Transport' => 'Método de envio',
            'Recipient' => 'Destinatário',
            'From (email)' => 'E-mail do remetente',
            'From (name)'  => 'Nome do remetente',
            'WordPress (wp_mail)' => 'WordPress (wp_mail)',
            'SMTP (built-in)' => 'SMTP (integrado)',
            'File mode' => 'Modo arquivo',
            'In File mode, emails are saved to uploads/ocf-mails/ for offline testing.' =>
                'No modo Arquivo, os e-mails são salvos em uploads/ocf-mails/ para testes off-line.',
            'SMTP' => 'SMTP','SMTP Host' => 'Servidor SMTP','SMTP Port' => 'Porta SMTP',
            'Security' => 'Segurança','Username' => 'Nome de usuário','Password' => 'Senha',
            'TLS (STARTTLS)' => 'TLS (STARTTLS)','SSL' => 'SSL','None (not recommended)' => 'Nenhuma (não recomendado)',
            'File Mode' => 'Modo Arquivo','File format' => 'Formato de arquivo','.eml' => '.eml','.txt' => '.txt',
            'Test Email' => 'E-mail de Teste',
            'Send test email' => 'Enviar e-mail de teste',
            'Test email sent successfully.' => 'E-mail de teste enviado com sucesso.',
            'Email sending failed.' => 'Falha no envio do e-mail.',
            'Unauthorized' => 'Não autorizado',
            'Name' => 'Nome','Email' => 'E-mail','Subject' => 'Assunto','Message' => 'Mensagem',
            'Send' => 'Enviar','Sending' => 'Enviando',
            'Please fill out this field.' => 'Preencha este campo.',
            'Please enter a valid email address.' => 'Insira um endereço de e-mail válido.',
            'Thank you! Your message has been sent.' => 'Obrigado! Sua mensagem foi enviada.',
            'I consent to having this website store my submitted information so they can respond to my inquiry.' =>
                'Autorizo que este site armazene as informações enviadas para responder à minha solicitação.',
            'Security token is invalid. Please refresh the page.' => 'O token de segurança é inválido. Atualize a página.',
            'Too many attempts. Please try again later.' => 'Muitas tentativas. Tente novamente mais tarde.',
            'Please check your inputs and try again.' => 'Verifique os campos e tente novamente.',
            'You must consent to data processing to send this form.' => 'Você deve consentir com o processamento de dados para enviar este formulário.',
            'Recipient email is not configured. Contact the site administrator.' => 'Destinatário não configurado. Contate o administrador.',
            'Your message could not be sent. Please try again later.' => 'Não foi possível enviar sua mensagem. Tente novamente mais tarde.',
            'Name is too long.' => 'O nome é muito longo.',
            'Subject is too long.' => 'O assunto é muito longo.',
            'Message is too long.' => 'A mensagem é muito longa.',
            'Spam detected. Please try again.' => 'Spam detectado. Tente novamente.',
            'Network error. Please check your connection.' => 'Erro de rede. Verifique sua conexão.',
            'Unexpected response from the server.' => 'Resposta inesperada do servidor.',
            'reCAPTCHA token is missing.' => 'Token do reCAPTCHA ausente.',
            'reCAPTCHA verification failed.' => 'Falha na verificação do reCAPTCHA.',
            'reCAPTCHA score is too low.' => 'Pontuação do reCAPTCHA muito baixa.',
            'reCAPTCHA action mismatch.' => 'Ação do reCAPTCHA inválida.',
            'reCAPTCHA service error.' => 'Erro do serviço reCAPTCHA.',
        ],
    ];
}

/** Normalize locale key used by the fallback map. */
function ocflite_normalize_locale_key_for_map( $loc ) {
    $loc = (string) $loc;
    if ( stripos( $loc, 'fr' ) === 0 ) return 'fr_FR';
    if ( stripos( $loc, 'es' ) === 0 ) return 'es_ES';
    if ( stripos( $loc, 'pt' ) === 0 ) return 'pt_BR';
    return $loc;
}

/** Apply fallback translations for our domain and specific notices. */
add_filter( 'gettext', function( $translated, $text, $domain ){
    if ( 'oneclick-form-lite' !== $domain && 'default' !== $domain ) return $translated;

    $loc_raw = ocflite_effective_locale();
    $loc     = ocflite_normalize_locale_key_for_map( $loc_raw );
    $map     = ocflite_gettext_fallback_map();

    if ( 'oneclick-form-lite' === $domain && isset( $map[$loc][$text] ) ) {
        return $map[$loc][$text];
    }
    if ( 'default' === $domain && is_admin() ) {

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'toplevel_page_ocflite' ) {
            return $translated;
        }
        if ( ($text === 'Settings saved.' || $text === 'Settings updated.') && isset($map[$loc][$text]) ) {
            return $map[$loc][$text];
        }
    }
    return $translated;
}, 10, 3 );

/* =============================================================================
 * Front assets (enqueue JS/CSS with robust fallbacks) + reCAPTCHA v3
 * ============================================================================= */

/** Map WP locale → Google reCAPTCHA `hl` code. */
function ocflite_recaptcha_hl() {
    $l = strtolower( (string) ocflite_effective_locale() );
    if ( strpos($l,'fr') === 0 ) return 'fr';
    if ( strpos($l,'es') === 0 ) return 'es';
    if ( strpos($l,'pt_br') === 0 || strpos($l,'pt-br') === 0 || strpos($l,'pt_') === 0 || strpos($l,'pt-') === 0 ) return 'pt-BR';
    return 'en';
}

add_action( 'wp_enqueue_scripts', function () {
    // Read reCAPTCHA options
    $rc_enabled  = (int) get_option( 'ocflite_recaptcha_enable', 0 ) === 1;
	$rc_site_key = sanitize_text_field( (string) get_option( 'ocflite_recaptcha_site_key', '' ) );
	$rc_action   = sanitize_key( (string) get_option( 'ocflite_recaptcha_action', 'contact_form' ) );

    $enqueue_asset = function( $type, $rel, $handle ) {
	$path = OCFLITE_PLUGIN_DIR . $rel;
	$url  = OCFLITE_PLUGIN_URL . $rel;

	if ( ! file_exists( $path ) ) {
		return false;
	}

	if ( 'style' === $type ) {
		wp_enqueue_style( $handle, $url, [], OCFLITE_VERSION );
	} else {
		wp_enqueue_script( $handle, $url, [], OCFLITE_VERSION, true );
	}

	return true;
};

    // Our front script
    if ( $enqueue_asset( 'script', 'assets/js/front.js', 'ocflite-front' ) ) {

        // Localize i18n and config for JS (no hardcoded strings in JS)
        $i18n = [
            'sending'      => __( 'Sending', 'oneclick-form-lite' ),
            'required'     => __( 'Please fill out this field.', 'oneclick-form-lite' ),
            'emailInvalid' => __( 'Please enter a valid email address.', 'oneclick-form-lite' ),
            'success'      => __( 'Thank you! Your message has been sent.', 'oneclick-form-lite' ),
            'consent'      => __( 'I consent to having this website store my submitted information so they can respond to my inquiry.', 'oneclick-form-lite' ),
            'errors'       => [
                'invalid_nonce'          => __( 'Security token is invalid. Please refresh the page.', 'oneclick-form-lite' ),
                'rate_limited'           => __( 'Too many attempts. Please try again later.', 'oneclick-form-lite' ),
                'invalid_input'          => __( 'Please check your inputs and try again.', 'oneclick-form-lite' ),
                'consent_required'       => __( 'You must consent to data processing to send this form.', 'oneclick-form-lite' ),
                'config_missing_to_email'=> __( 'Recipient email is not configured. Contact the site administrator.', 'oneclick-form-lite' ),
                'send_failed'            => __( 'Your message could not be sent. Please try again later.', 'oneclick-form-lite' ),
                'name_too_long'          => __( 'Name is too long.', 'oneclick-form-lite' ),
                'subject_too_long'       => __( 'Subject is too long.', 'oneclick-form-lite' ),
                'message_too_long'       => __( 'Message is too long.', 'oneclick-form-lite' ),
                'spam_detected'          => __( 'Spam detected. Please try again.', 'oneclick-form-lite' ),
                'network_error'          => __( 'Network error. Please check your connection.', 'oneclick-form-lite' ),
                'invalid_response'       => __( 'Unexpected response from the server.', 'oneclick-form-lite' ),
                'recaptcha_missing'      => __( 'reCAPTCHA token is missing.', 'oneclick-form-lite' ),
                'recaptcha_failed'       => __( 'reCAPTCHA verification failed.', 'oneclick-form-lite' ),
                'recaptcha_score_low'    => __( 'reCAPTCHA score is too low.', 'oneclick-form-lite' ),
                'recaptcha_bad_action'   => __( 'reCAPTCHA action mismatch.', 'oneclick-form-lite' ),
                'recaptcha_error'        => __( 'reCAPTCHA service error.', 'oneclick-form-lite' ),
            ],
        ];

        wp_localize_script( 'ocflite-front', 'OCFLITE', [
            'endpoint'  => esc_url_raw( rest_url( 'oneclick-form-lite/v1/submit' ) ),
            'nonce'     => wp_create_nonce( 'ocflite_send' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'locale'    => ocflite_effective_locale(),
            'i18n'      => $i18n,
            'recaptcha' => [
                'enabled' => $rc_enabled && ! empty( $rc_site_key ),
                'siteKey' => $rc_site_key,
                'action'  => $rc_action,
            ],
        ] );
    }

    // Google reCAPTCHA v3 script only if enabled + key present
    if ( $rc_enabled && ! empty( $rc_site_key ) ) {
        $src = sprintf(
            'https://www.google.com/recaptcha/api.js?render=%s&hl=%s',
            rawurlencode( $rc_site_key ),
            rawurlencode( ocflite_recaptcha_hl() )
        );
        wp_enqueue_script( 'google-recaptcha', $src, [], OCFLITE_VERSION, true );
        // Add async/defer to the tag
        add_filter( 'script_loader_tag', function( $tag, $handle ){
            if ( 'google-recaptcha' !== $handle ) return $tag;
            $attrs = '';
            if ( false === strpos( $tag, ' async' ) ) { $attrs .= ' async'; }
            if ( false === strpos( $tag, ' defer' ) ) { $attrs .= ' defer'; }

            if ( '' !== $attrs ) {
                $tag = str_replace( ' src=', $attrs . ' src=', $tag );
            }
            return $tag;
        }, 10, 2 );
    }
	$enqueue_asset( 'style', 'assets/css/ocflite.css', 'ocflite' );
}, 20 );

/* =============================================================================
 * Safe backend loading (settings screen + REST/mailers)
 * ============================================================================= */
add_action( 'plugins_loaded', function () {
    $ocflite_settings_file = OCFLITE_PLUGIN_DIR . 'admin/settings.php';
    if ( file_exists( $ocflite_settings_file ) ) {
        require_once $ocflite_settings_file;
    }
    $router_file = OCFLITE_PLUGIN_DIR . 'includes/class-ocflite-router.php';
    if ( file_exists( $router_file ) ) {
        require_once $router_file;
    }
});

/* =============================================================================
 * Front-end rendering (shortcode)
 * ============================================================================= */
$ocflite_frontend_file = OCFLITE_PLUGIN_DIR . 'includes/frontend.php';
if ( file_exists( $ocflite_frontend_file ) ) {
    require_once $ocflite_frontend_file;
}
