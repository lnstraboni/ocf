=== OneClick Form Lite ===
Contributors: kitcode
Tags: contact form, smtp, recaptcha, gdpr, honeypot
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.1.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight contact form with built-in SMTP, optional file delivery, and Google reCAPTCHA v3.



== Description ==

OneClick Form Lite adds a lightweight contact form to your WordPress site via shortcode:

* Shortcode: [oneclickform]
* Fields: Name, Email, Subject, Message + GDPR consent checkbox
* Anti-spam: honeypot + server-side rate limiting
* Delivery modes:
  - WordPress wp_mail()
  - Built-in SMTP
  - File mode (writes messages to your uploads directory)
* Optional Google reCAPTCHA v3 (score-based)
* Translations: EN (default) + FR, ES, PT-BR (via included .mo files)

Important notes:
* This plugin does NOT store submitted messages in the WordPress database.
* Messages are delivered by email (wp_mail/SMTP) or written to files (File mode).
* Your host/mail provider may store emails in logs and mailboxes.
* No minified/compiled assets are shipped with this plugin. All distributed CSS/JS is human-readable.

Documentation is included in the plugin:
docs/oneclick-form-lite-user-guide.html



== Source code ==

The plugin ships unminified, human-readable JS/CSS source files. No build process is used.



== External services ==

1) This plugin can optionally integrate Google reCAPTCHA v3 to help prevent spam form submissions.

When enabled:
- The visitor’s browser loads Google’s reCAPTCHA script:
  https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY
- The browser communicates with Google to obtain a reCAPTCHA token.

On form submission:
- The plugin sends the reCAPTCHA token to Google for verification:
  https://www.google.com/recaptcha/api/siteverify
- The verification request includes the token and the submitter’s IP address (remoteip) when available.

Service provider: Google LLC
Terms of service: https://policies.google.com/terms
Privacy policy: https://policies.google.com/privacy

2) This plugin can also send emails via an SMTP server configured by the site administrator (optional).

When SMTP is enabled and an email is sent:
- The plugin connects to the configured SMTP host and port.
- Email data is transmitted to the SMTP server in order to deliver the message.

Data sent to the SMTP server may include:
- Sender and recipient addresses, subject, message content
- Technical email headers
- Authentication data (username/password) if configured by the site administrator

Service provider: The SMTP provider selected by the site administrator (e.g., hosting provider, Google Workspace, Microsoft 365, etc.)
Terms of service: See the selected SMTP provider’s terms
Privacy policy: See the selected SMTP provider’s privacy policy



== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (or Plugins > Add New > Upload Plugin).
2. Activate the plugin.
3. Go to Settings → OneClick Form Lite.
4. Configure at least:
   - To email (recipient)
   - Transport (WP Mail / SMTP / File mode)
5. Add the form to a page: [oneclickform]

Note: The plugin language follows your WordPress site language (Settings → General → Site Language).



== Frequently Asked Questions ==

= Are messages stored in the database? =
No. Messages are delivered by email (WP Mail/SMTP) or written as files in File mode.

= Where does File mode write messages? =
In your uploads directory, typically:
`wp-content/uploads/ocf-mails/`

= Does this plugin send emails via SMTP? =
Yes. You can choose WordPress (wp_mail), SMTP (built-in), or File mode.

= How do I enable reCAPTCHA v3? =
Enable reCAPTCHA in settings, then paste your Site Key + Secret Key.
Adjust Action and Threshold if needed.

= Does reCAPTCHA send data to a third party? =
If you enable Google reCAPTCHA, your site will send verification requests to Google.
You should disclose this in your site privacy policy.

= Is GDPR consent included? =
Yes. The form includes a consent checkbox and rejects submissions without consent.

= Does the form support multiple languages? =
Yes. The plugin includes translations (FR, ES, PT-BR). The front-end and settings screens use WordPress’ translation system and will display in the site language.



== Screenshots ==

1. Settings overview (Email delivery, SMTP, File mode, reCAPTCHA, test email, documentation).
2. SMTP settings (built-in SMTP host, port, security, and credentials).
3. Google reCAPTCHA v3 settings (site key, secret key, action, and threshold).
4. Theme compatibility (light/dark previews).
5. Front-end translations (EN, ES, PT, FR).
6. Front-end confirmation message after successful submission.



== Changelog ==

= 1.1.3 =
* Fix: Do not hide WordPress “Settings saved” admin notice.

= 1.1.2 =
* Initial public release (submission build).



== Upgrade Notice ==

= 1.1.3 =
Fixes admin notice visibility after saving settings.

= 1.1.2 =
Initial release.
