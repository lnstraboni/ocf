<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Settings screen for OneClick Form Lite.
 * - 3-column responsive grid + reCAPTCHA box
 * - Page title band
 * - Test-email form in File (dev) box
 * - All strings via i18n
 */

if ( ! class_exists( 'OCFLITE_Settings' ) ) :

class OCFLITE_Settings {

    /** Boot hooks */
    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'admin_post_ocflite_test_send', [ __CLASS__, 'handle_test_send' ] );

    }

    /** Register options */
    public static function register() {
        register_setting( 'ocflite_settings', 'ocflite_transport',   [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field', 'default'=>'wp' ] );
        register_setting( 'ocflite_settings', 'ocflite_to_email',    [ 'type'=>'string',  'sanitize_callback'=>'sanitize_email',       'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_from_email',  [ 'type'=>'string',  'sanitize_callback'=>'sanitize_email',       'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_from_name',   [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );

        register_setting( 'ocflite_settings', 'ocflite_smtp_host',   [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_smtp_port',   [ 'type'=>'integer', 'sanitize_callback'=>'absint',               'default'=>587 ] );
        register_setting( 'ocflite_settings', 'ocflite_smtp_secure', [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'tls' ] );
        register_setting( 'ocflite_settings', 'ocflite_smtp_user',   [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_smtp_pass',   [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );

        register_setting( 'ocflite_settings', 'ocflite_file_format', [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'eml' ] );

        // reCAPTCHA v3
        register_setting( 'ocflite_settings', 'ocflite_recaptcha_enable',      [ 'type'=>'integer', 'sanitize_callback'=>'absint',               'default'=>0 ] );
        register_setting( 'ocflite_settings', 'ocflite_recaptcha_site_key',    [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_recaptcha_secret_key',  [ 'type'=>'string',  'sanitize_callback'=>'sanitize_text_field',  'default'=>'' ] );
        register_setting( 'ocflite_settings', 'ocflite_recaptcha_threshold',   [ 'type'=>'number',  'sanitize_callback'=>[ __CLASS__, 'sanitize_score' ], 'default'=>0.5 ] );
        register_setting( 'ocflite_settings', 'ocflite_recaptcha_action',      [ 'type'=>'string',  'sanitize_callback'=>'sanitize_key',         'default'=>'contact_form' ] );
    }

    public static function sanitize_score( $v ) {
        $f = floatval( $v );
        if ( $f < 0 ) $f = 0;
        if ( $f > 1 ) $f = 1;
        return $f;
    }

    /** Menu item */
    public static function menu() {
        // Top-level menu, with envelope icon, just above "Settings"
        add_menu_page(
            __( ' OneClick Form Lite', 'oneclick-form-lite' ), // page title
            __( ' OneClick Form Lite', 'oneclick-form-lite' ), // menu title
            'manage_options',
            'ocflite',
            [ __CLASS__, 'render' ],
            'dashicons-email',
            79
        );
    }

    public static function enqueue_admin_assets( $hook ) {

        // Load only on the plugin settings page.
        if ( $hook !== 'toplevel_page_ocflite' ) {
            return;
        }

        wp_enqueue_style(
            'ocflite-admin',
            plugins_url( 'assets/css/ocflite-admin.css', OCFLITE_PLUGIN_FILE ),
            [],
            OCFLITE_VERSION
        );

        wp_enqueue_script(
            'ocflite-admin',
            plugins_url( 'assets/js/ocflite-admin.js', OCFLITE_PLUGIN_FILE ),
            [],
            OCFLITE_VERSION,
            true
        );
    }

    /** Page renderer */
    public static function render() { ?>
        <div class="wrap ocflite-settings">
            <h1 class="ocflite-title-band">
                <img
                    src="<?php echo esc_url( plugins_url( 'assets/img/ocf-logo-admin.png', OCFLITE_PLUGIN_FILE ) ); ?>"
                    class="ocflite-logo"
                    alt="<?php echo esc_attr__( ' OneClick Form Lite', 'oneclick-form-lite' ); ?>"
                />
                <span><?php echo esc_html__( ' OneClick Form Lite', 'oneclick-form-lite' ); ?></span>
            </h1>

            <?php self::maybe_show_test_notice(); ?>
            <?php settings_errors(); ?>

            <form id="ocflite-options-form" method="post" action="options.php" style="display:none">
                <?php settings_fields( 'ocflite_settings' ); ?>
            </form>

            <div class="ocflite-settings-grid">
                <!-- Column 1: Email & Sending -->
                <div class="ocflite-box">
                    <h2><?php echo esc_html__( 'Email & Sending', 'oneclick-form-lite' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Transport', 'oneclick-form-lite' ); ?></th>
                            <td>
                                <?php $t = get_option( 'ocflite_transport', 'wp' ); ?>
                                <select name="ocflite_transport" form="ocflite-options-form">
                                    <option value="wp"   <?php selected( $t, 'wp' ); ?>><?php echo esc_html__( 'WordPress (wp_mail)', 'oneclick-form-lite' ); ?></option>
                                    <option value="smtp" <?php selected( $t, 'smtp' ); ?>><?php echo esc_html__( 'SMTP (built-in)', 'oneclick-form-lite' ); ?></option>
                                    <option value="file" <?php selected( $t, 'file' ); ?>><?php echo esc_html__( 'File mode', 'oneclick-form-lite' ); ?></option>
                                </select>
                                <p class="description">
                                    <?php echo esc_html__(
                                        'Choose how contact form emails are delivered.',
                                        'oneclick-form-lite'
                                    ); ?>
                                </p>

                            </td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_to_email"><?php echo esc_html__( 'Recipient', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="email" class="regular-text" id="ocflite_to_email" name="ocflite_to_email" value="<?php echo esc_attr( get_option( 'ocflite_to_email', '' ) ); ?>" required form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_from_email"><?php echo esc_html__( 'From (email)', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="email" class="regular-text" id="ocflite_from_email" name="ocflite_from_email" value="<?php echo esc_attr( get_option( 'ocflite_from_email', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_from_name"><?php echo esc_html__( 'From (name)', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="text" class="regular-text" id="ocflite_from_name" name="ocflite_from_name" value="<?php echo esc_attr( get_option( 'ocflite_from_name', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                    </table>
                </div>

                <!-- Column 2: SMTP -->
                <div class="ocflite-box ocflite-box--smtp">
                    <h2><?php echo esc_html__( 'SMTP', 'oneclick-form-lite' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="ocflite_smtp_host"><?php echo esc_html__( 'SMTP Host', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="text" class="regular-text" id="ocflite_smtp_host" name="ocflite_smtp_host" value="<?php echo esc_attr( get_option( 'ocflite_smtp_host', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_smtp_port"><?php echo esc_html__( 'SMTP Port', 'oneclick-form-lite' ); ?></label></th>
                            <td>
                                <input type="number" class="small-text" id="ocflite_smtp_port" name="ocflite_smtp_port" value="<?php echo esc_attr( get_option( 'ocflite_smtp_port', 587 ) ); ?>" form="ocflite-options-form">
                                <span class="description"><?php echo esc_html__( '(465=SSL, 587=TLS)', 'oneclick-form-lite' ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_smtp_secure"><?php echo esc_html__( 'Security', 'oneclick-form-lite' ); ?></label></th>
                            <td>
                                <?php $sec = get_option( 'ocflite_smtp_secure', 'tls' ); ?>
                                <select id="ocflite_smtp_secure" name="ocflite_smtp_secure" form="ocflite-options-form">
                                    <option value="tls" <?php selected( $sec, 'tls' ); ?>><?php echo esc_html__( 'TLS (STARTTLS)', 'oneclick-form-lite' ); ?></option>
                                    <option value="ssl" <?php selected( $sec, 'ssl' ); ?>><?php echo esc_html__( 'SSL', 'oneclick-form-lite' ); ?></option>
                                    <option value="none"<?php selected( $sec, 'none' ); ?>><?php echo esc_html__( 'None (not recommended)', 'oneclick-form-lite' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_smtp_user"><?php echo esc_html__( 'Username', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="text" class="regular-text" id="ocflite_smtp_user" name="ocflite_smtp_user" value="<?php echo esc_attr( get_option( 'ocflite_smtp_user', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_smtp_pass"><?php echo esc_html__( 'Password', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="password" class="regular-text" id="ocflite_smtp_pass" name="ocflite_smtp_pass" value="<?php echo esc_attr( get_option( 'ocflite_smtp_pass', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                    </table>
                </div>

                <!-- Column 3: File Mode -->
                <div class="ocflite-box ocflite-box--file">
                    <div class="ocflite-file-config">
                        <h2><?php echo esc_html__( 'File Mode', 'oneclick-form-lite' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th>
                                    <label for="ocflite_file_format">
                                        <?php echo esc_html__( 'File format', 'oneclick-form-lite' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php $ff = get_option( 'ocflite_file_format', 'eml' ); ?>
                                    <select id="ocflite_file_format" name="ocflite_file_format" form="ocflite-options-form">
                                        <option value="eml" <?php selected( $ff, 'eml' ); ?>>
                                            <?php echo esc_html__( '.eml', 'oneclick-form-lite' ); ?>
                                        </option>
                                        <option value="txt" <?php selected( $ff, 'txt' ); ?>>
                                            <?php echo esc_html__( '.txt', 'oneclick-form-lite' ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <?php
                        $upload   = wp_get_upload_dir();
                        $basedir  = ! empty( $upload['basedir'] ) ? trailingslashit( $upload['basedir'] ) . 'ocf-mails/' : '';
                        $baseurl  = ! empty( $upload['baseurl'] ) ? trailingslashit( $upload['baseurl'] ) . 'ocf-mails/' : '';
                        ?>
                        <p class="description ocflite-file-path">
                            <?php
                            if ( $basedir ) {
                                printf(
                                    esc_html__( 'Test emails are saved in:', 'oneclick-form-lite' ) . '<br><code>%s</code>',
                                    esc_html( $basedir )
                                );
                            } else {
                                echo esc_html__( 'Test emails are saved in the uploads directory.', 'oneclick-form-lite' );
                            }

                            if ( $baseurl ) {
                                echo '<br><br>';
                                printf(
                                    /* translators: %s = base URL to the dev email files folder (may not be browsable). */
                                    esc_html__( 'Base URL for dev email files (directory listing may be disabled by your server):', 'oneclick-form-lite' )
                                    . '<br>%s',
                                    '<code>' . esc_html( $baseurl ) . '</code>'
                                );
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Row 2: reCAPTCHA v3 -->
                <div class="ocflite-box ocflite-box--recaptcha">
                    <h2><?php echo esc_html__( 'reCAPTCHA v3', 'oneclick-form-lite' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="ocflite_recaptcha_enable"><?php esc_html_e( 'Enable', 'oneclick-form-lite' ); ?></label></th>
                            <td>
                                <?php $en = (int) get_option( 'ocflite_recaptcha_enable', 0 ); ?>
                                <label><input type="checkbox" id="ocflite_recaptcha_enable" name="ocflite_recaptcha_enable" value="1" <?php checked( $en, 1 ); ?> form="ocflite-options-form"> <?php esc_html_e( 'Protect submissions with Google reCAPTCHA v3', 'oneclick-form-lite' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_recaptcha_site_key"><?php esc_html_e( 'Site key', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="text" class="regular-text" id="ocflite_recaptcha_site_key" name="ocflite_recaptcha_site_key" value="<?php echo esc_attr( get_option( 'ocflite_recaptcha_site_key', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_recaptcha_secret_key"><?php esc_html_e( 'Secret key', 'oneclick-form-lite' ); ?></label></th>
                            <td><input type="password" class="regular-text" id="ocflite_recaptcha_secret_key" name="ocflite_recaptcha_secret_key" value="<?php echo esc_attr( get_option( 'ocflite_recaptcha_secret_key', '' ) ); ?>" form="ocflite-options-form"></td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_recaptcha_threshold"><?php esc_html_e( 'Threshold', 'oneclick-form-lite' ); ?></label></th>
                            <td>
                                <input type="number" step="0.1" min="0" max="1" class="small-text" id="ocflite_recaptcha_threshold" name="ocflite_recaptcha_threshold" value="<?php echo esc_attr( get_option( 'ocflite_recaptcha_threshold', 0.5 ) ); ?>" form="ocflite-options-form">
                                <span class="description"><?php esc_html_e( 'Recommended: 0.5 (higher = stricter)', 'oneclick-form-lite' ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="ocflite_recaptcha_action"><?php esc_html_e( 'Action', 'oneclick-form-lite' ); ?></label></th>
                            <td>
                                <input type="text" class="regular-text" id="ocflite_recaptcha_action" name="ocflite_recaptcha_action" value="<?php echo esc_attr( get_option( 'ocflite_recaptcha_action', 'contact_form' ) ); ?>" form="ocflite-options-form">
                                <span class="description"><?php esc_html_e( 'Keep this value consistent with the front-end action.', 'oneclick-form-lite' ); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Row 2: Documentation (user guide) -->
                <?php
                $ocflite_doc_html = plugins_url( 'docs/oneclick-form-lite-user-guide.html', OCFLITE_PLUGIN_FILE );
?>
                <div class="ocflite-box ocflite-box--docs">
                    <h2><?php echo esc_html__( 'Documentation', 'oneclick-form-lite' ); ?></h2>
                    <div class="ocflite-box--docs-content">
                        <p class="description">
                            <?php echo esc_html__( 'Open the user guide in your browser.', 'oneclick-form-lite' ); ?>
                        </p>
                        <ul>
                            <li>
                                <a href="<?php echo esc_url( $ocflite_doc_html ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html__( 'Open HTML guide', 'oneclick-form-lite' ); ?>
                                </a>
                            </li></ul>
                    </div>
                </div>

                <!-- Row 2: Test Email (global for current transport) -->
                <div class="ocflite-box ocflite-box--test">
                    <h2><?php echo esc_html__( 'Test Email', 'oneclick-form-lite' ); ?></h2>
                    <div class="ocflite-box-test-inner">
                        <p class="description">
                            <?php
                            echo nl2br( esc_html__(
                                "Use this to verify your current delivery method.\nA simple test message will be sent to the configured Recipient.",
                                'oneclick-form-lite'
                            ) );
                            ?>
                        </p>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'ocflite_test_send', 'ocflite_test_nonce' ); ?>
                            <input type="hidden" name="action" value="ocflite_test_send">
                            <button type="submit" class="button ocflite-accent">
                                <?php echo esc_html__( 'Send test email', 'oneclick-form-lite' ); ?>
                            </button>
                        </form>
                    </div>
                </div>

            <!-- Global Save -->
            <p>
                <button type="submit" class="button button-primary" form="ocflite-options-form">
                    <?php echo esc_html__( 'Save', 'oneclick-form-lite' ); ?>
                </button>
            </p>

        </div>
    <?php }

    /** Handle test email */
    public static function handle_test_send() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Unauthorized', 'oneclick-form-lite' ), 403 );
        check_admin_referer( 'ocflite_test_send', 'ocflite_test_nonce' );

        $to        = get_option( 'ocflite_to_email' );
        $fromEmail = get_option( 'ocflite_from_email' ) ?: 'no-reply@' . wp_parse_url( home_url(), PHP_URL_HOST );
        $fromName  = get_option( 'ocflite_from_name' ) ?: get_bloginfo( 'name' );
        $subject   = __( ' OneClick Form Lite — Test email', 'oneclick-form-lite' );
        $body      = __( 'This is a OneClick Form Lite test email.', 'oneclick-form-lite' ) . "\nTime: " . gmdate('c') . "\nSite: " . home_url();

        $dir = trailingslashit( OCFLITE_PLUGIN_DIR ) . 'includes/mail/';
        if ( file_exists( $dir . 'interface-mailer.php' ) ) require_once $dir . 'interface-mailer.php';
        if ( file_exists( $dir . 'mailer-wp.php' ) )       require_once $dir . 'mailer-wp.php';
        if ( file_exists( $dir . 'mailer-smtp.php' ) )     require_once $dir . 'mailer-smtp.php';
        if ( file_exists( $dir . 'mailer-file.php' ) )     require_once $dir . 'mailer-file.php';

        $transport = get_option( 'ocflite_transport', 'wp' );
        switch ( $transport ) {
            case 'smtp':
                $mailer = class_exists('OCFLITE_Mailer_SMTP') ? new OCFLITE_Mailer_SMTP( [
                    'host'   => get_option( 'ocflite_smtp_host', '' ),
                    'port'   => (int) get_option( 'ocflite_smtp_port', 587 ),
                    'secure' => get_option( 'ocflite_smtp_secure', 'tls' ),
                    'user'   => get_option( 'ocflite_smtp_user', '' ),
                    'pass'   => get_option( 'ocflite_smtp_pass', '' ),
                ] ) : null;
                break;
            case 'file':
                $mailer = class_exists('OCFLITE_Mailer_File') ? new OCFLITE_Mailer_File( [
                    'format' => get_option( 'ocflite_file_format', 'eml' ),
                ] ) : null;
                break;
            case 'wp':
            default:
                $mailer = class_exists('OCFLITE_Mailer_WP') ? new OCFLITE_Mailer_WP() : null;
        }

        $ok = false; $err_code = ''; $err_detail = '';
        try {
            if ( ! is_email( $to ) ) { throw new Exception( '[invalid_recipient] Invalid recipient email.' ); }
            $ok = $mailer ? (bool) $mailer->send( $fromEmail, $fromName, $to, $subject, $body, null, null ) : false;
            if ( ! $ok ) { throw new Exception( '[send_failed] Email sending failed.' ); }
        } catch ( Exception $e ) {
            $msg = (string) $e->getMessage();
            if ( preg_match('/^\[([a-z0-9_]+)\]\s*(.*)$/i', $msg, $m) ) {
                $err_code   = strtolower( $m[1] );
                $err_detail = isset($m[2]) ? $m[2] : '';
            } else {
                $err_code   = 'send_failed';
                $err_detail = $msg;
            }
            $ok = false;
        }

        $url = add_query_arg(
            array(
                'page'                => 'ocflite',
                'ocflite_test'        => $ok ? 'ok' : 'fail',
                'ocflite_code'        => $err_code,
                'ocflite_notice_nonce'=> wp_create_nonce( 'ocflite_test_notice' ),
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $url );
        exit;

    }

    /** Show admin notice after test */
    public static function maybe_show_test_notice() {

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'ocflite' !== $page ) {
            return;
        }

        $notice_nonce = isset( $_GET['ocflite_notice_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ocflite_notice_nonce'] ) ) : '';
        if ( empty( $notice_nonce ) || ! wp_verify_nonce( $notice_nonce, 'ocflite_test_notice' ) ) {
            return;
        }

        if ( isset( $_GET['ocflite_test'] ) ) {
            $test = sanitize_key( wp_unslash( $_GET['ocflite_test'] ) );
            $ok   = ( 'ok' === $test );

            $code = isset( $_GET['ocflite_code'] ) ? sanitize_key( wp_unslash( $_GET['ocflite_code'] ) ) : '';

            if ( $ok ) {
                printf( '<div class="notice notice-success ocflite-own"><p>%s</p></div>', esc_html__( 'Test email sent successfully.', 'oneclick-form-lite' ) );
                return;
            }

            $lookup = [
                'smtp_connect_error' => __( 'SMTP connection failed.', 'oneclick-form-lite' ),
                'smtp_tls_failed'    => __( 'TLS negotiation failed.', 'oneclick-form-lite' ),
                'smtp_auth_failed'   => __( 'SMTP authentication failed.', 'oneclick-form-lite' ),
                'smtp_write_failed'  => __( 'Could not write to SMTP socket.', 'oneclick-form-lite' ),
                'smtp_unexpected'    => __( 'Unexpected SMTP response from server.', 'oneclick-form-lite' ),
                'smtp_timeout'       => __( 'SMTP read timed out.', 'oneclick-form-lite' ),
                'wp_mail_failed'     => __( 'Email sending failed.', 'oneclick-form-lite' ),
                'file_write_failed'  => __( 'File write failed.', 'oneclick-form-lite' ),
                'invalid_recipient'  => __( 'Email sending failed.', 'oneclick-form-lite' ),
                'send_failed'        => __( 'Email sending failed.', 'oneclick-form-lite' ),
            ];
            $human = isset($lookup[$code]) ? $lookup[$code] : __( 'Email sending failed.', 'oneclick-form-lite' );
            printf( '<div class="notice notice-error ocflite-own"><p>%s</p></div>', esc_html( $human ) );
        }
    }
}
endif;

OCFLITE_Settings::init();
