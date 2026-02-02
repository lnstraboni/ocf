# OneClick Form Lite

A lightweight contact form plugin for WordPress, designed to be GDPR-friendly and simple to deploy. Includes built-in SMTP support, a test email tool, optional File mode, and Google reCAPTCHA v3 support.

## WordPress.org
Plugin page: https://wordpress.org/plugins/oneclick-form-lite/

## Features

- Shortcode contact form: `[oneclickform]`
- Fields: Name, Email, Subject, Message + GDPR consent checkbox
- Translations included (front-end + settings): **FR, ES, PT-BR** (English default). The plugin language follows your WordPress site language.
- Honeypot anti-spam field
- Rate limiting (server-side)
- 3 delivery modes:
  - **WP Mail** (uses `wp_mail()`)
  - **SMTP** (built-in)
  - **File mode** (writes messages to files in `wp-content/uploads/ocf-mails/`)
- Optional **Google reCAPTCHA v3** (score-based) validation
- No message storage in the WordPress database (messages are delivered by email or written to files)

## Requirements

- WordPress: 6.0+
- PHP: 7.4+

## Installation

1. Upload the plugin folder to `wp-content/plugins/` (or install via “Upload Plugin”).
2. Activate **OneClick Form Lite**.
3. Go to **Settings → OneClick Form Lite**.
4. Configure at least:
   - **To email** (recipient)
   - **Transport** (WP Mail / SMTP / File mode)
5. Add the form to any page:
   - `[oneclickform]`

## Configuration notes

### Transport

- **WP Mail**: simplest, but deliverability depends on your host.
- **SMTP**: recommended for better deliverability. Fill host/port/encryption/user/password.
- **File mode**: useful for debugging or non-email environments. Messages are written to:
  - `wp-content/uploads/ocf-mails/`
  - Format: `.eml` or `.txt` (select in settings)

### Test email

Use the **Send test email** tool on the settings page to confirm delivery.

### reCAPTCHA v3

1. Create a reCAPTCHA v3 key pair in Google reCAPTCHA admin.
2. Add your site domain(s).
3. In plugin settings:
   - Enable reCAPTCHA
   - Paste **Site Key** and **Secret Key**
   - Set **Action** (default: `contact_form`)
   - Set **Threshold** (default: `0.5`)

## Privacy / GDPR

- The plugin does not store messages in the WordPress database.
- Delivery happens via email or file output. Your mail provider/server may store emails in logs and mailboxes.
- If you enable Google reCAPTCHA, your site will send verification requests to Google. Disclose this in your privacy policy.

## Uninstall

On uninstall, the plugin removes its options. Rate-limit transients (if any) expire naturally.

## Documentation

Open the user guide: `docs/oneclick-form-lite-user-guide.html`

## Support

Support via GitHub Issues

## External services

This plugin can connect to an external service **only if you enable Google reCAPTCHA v3** in the plugin settings.

- Service: Google reCAPTCHA v3
- Used on: form submission (server-side verification)
- Endpoint: `https://www.google.com/recaptcha/api/siteverify`
- Data sent: the reCAPTCHA token (`g-recaptcha-response`), your Secret Key, and the visitor IP address (`remoteip`).
- Terms: https://policies.google.com/terms
- Privacy: https://policies.google.com/privacy

## Assets / build process

This plugin ships with **human-readable** JavaScript and CSS files. No minified/compiled assets are included and no build step is required.
Edit directly:
- `assets/js/front.js`
- `assets/css/ocflite.css`
- `assets/js/ocflite-admin.js`
- `assets/css/ocflite-admin.css`

## License

GPLv2 or later.

## Contributing

Issues and pull requests are welcome.

- Please open an issue to describe the problem / proposal.
- For larger changes, discuss the approach in an issue before submitting a PR.
