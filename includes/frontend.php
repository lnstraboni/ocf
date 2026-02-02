<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Front-end shortcode: [oneclickform]
 */
function ocflite_render_form( $atts = [], $content = null ) {
    $nonce = wp_create_nonce( 'ocflite_send' );

    // Localized messages used both server-side and by front.js
    $success_msg       = esc_attr__( 'Thank you! Your message has been sent.', 'oneclick-form-lite' );
    $msg_required      = esc_attr__( 'Please fill out this field.', 'oneclick-form-lite' );
    $msg_email_invalid = esc_attr__( 'Please enter a valid email address.', 'oneclick-form-lite' );

    ob_start(); ?>
    <form class="ocflite-form" data-ocflite="1"
          data-success-msg="<?php echo esc_attr( $success_msg ); ?>"
          data-msg-required="<?php echo esc_attr( $msg_required ); ?>"
          data-msg-email-invalid="<?php echo esc_attr( $msg_email_invalid ); ?>"
          novalidate>
        <div class="ocflite-fields">
            <div class="ocflite-row">
                <label>
                    <?php echo esc_html__( 'Name', 'oneclick-form-lite' ); ?>
                    <input type="text" name="name" required
                           placeholder="<?php echo esc_attr__( 'Your name', 'oneclick-form-lite' ); ?>">
                </label>
            </div>
            <div class="ocflite-row">
                <label>
                    <?php echo esc_html__( 'Email', 'oneclick-form-lite' ); ?>
                    <input type="email" name="email" required
                           placeholder="<?php echo esc_attr__( 'you@example.com', 'oneclick-form-lite' ); ?>">
                </label>
            </div>
            <div class="ocflite-row">
                <label>
                    <?php echo esc_html__( 'Subject', 'oneclick-form-lite' ); ?>
                    <input type="text" name="subject"
                           placeholder="<?php echo esc_attr__( 'Subject', 'oneclick-form-lite' ); ?>">
                </label>
            </div>
            <div class="ocflite-row">
                <label>
                    <?php echo esc_html__( 'Message', 'oneclick-form-lite' ); ?>
                    <textarea name="message" rows="5" required
                              placeholder="<?php echo esc_attr__( 'Write your message…', 'oneclick-form-lite' ); ?>"></textarea>
                </label>
            </div>
            <div class="ocflite-row">
                <label class="ocflite-consent">
                    <input type="checkbox" name="consent" value="1">
                    <?php echo esc_html__( 'I consent to having this website store my submitted information so they can respond to my inquiry.', 'oneclick-form-lite' ); ?>
                </label>
            </div>

            <input type="hidden" name="ocflite_nonce" value="<?php echo esc_attr( $nonce ); ?>">

            <div class="ocflite-row ocflite-row--submit">
                <button type="submit" class="wp-element-button"><?php echo esc_html__( 'Send', 'oneclick-form-lite' ); ?></button>
            </div>
        </div>

        <!-- Message container (centered & enlarged on success) -->
        <div data-ocflite-message></div>

        <?php
        // Honeypot (hidden off-screen)
        ?>
        <div class="ocflite-hp-wrap" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label>Company
                <input type="text" name="ocflite_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
            </label>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'oneclickform', 'ocflite_render_form' );
