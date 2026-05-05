=== Image Dimensions URL ===
Contributors: javierenweb
Tags: images, dimensions, optimization, media, admin
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.5
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily check the dimensions of images on any webpage by simply entering its URL.

== Description ==

**Image Dimensions URL** allows you to quickly audit all images on any webpage. Enter a URL and instantly see each image's declared HTML dimensions versus its real file dimensions — with clear mismatch alerts to help you catch optimization issues fast.

= Features =

* Extracts image dimensions from any public webpage URL.
* Detects lazy-loaded images (data-src, data-lazy-src, data-lazy, data-original).
* Compares HTML-declared dimensions vs. real file dimensions — highlights mismatches in red.
* Displays results in a structured table with image previews.
* Exports full report as CSV (UTF-8 with BOM for Excel compatibility).
* Handles up to 150 images per scan to prevent timeouts.
* Anti-SSRF protection: blocks requests to private/internal IP ranges.
* Fallback dimension detection via wp_remote_get() for restrictive servers.
* Simple, lightweight, no external dependencies.

== Installation ==

1. Download the plugin ZIP file or clone the repository.
2. Upload the plugin folder to your `/wp-content/plugins/` directory.
3. Activate the plugin from the **Plugins** menu in WordPress.
4. Navigate to **Image Dimensions** in your WordPress admin dashboard.

== Usage ==

1. Go to **Image Dimensions** in your WordPress dashboard.
2. Enter the URL of the webpage you want to analyze.
3. Click **Ver dimensiones** to retrieve all image data.
4. Review the results — rows highlighted in red indicate dimension mismatches.
5. Download the full report as a CSV file.

== Changelog ==

= 1.5 =
* Added: Detection of HTML-declared vs. real file dimensions with mismatch highlighting.
* Added: Support for lazy-loaded images (data-src, data-lazy-src, data-lazy, data-original).
* Added: Anti-SSRF validation to block requests to private/internal IP ranges.
* Added: Fallback dimension detection via wp_remote_get() when allow_url_fopen is disabled.
* Added: Relative and protocol-relative URL resolution.
* Added: Limit of 150 images per scan with user notice when exceeded.
* Added: CSRF nonce protection on CSV download form.
* Added: UTF-8 BOM in CSV export for correct Excel rendering.
* Added: Requires at least, Tested up to, Requires PHP headers.
* Improved: Error messages now show specific failure reasons.
* Improved: CSV now includes HTML dimensions, real dimensions, and status columns.
* Fixed: CSV data no longer passed via hidden form field (now uses WP transients).

= 1.4 =
* Initial public release.

== Security ==

* All URLs are sanitized and validated before fetching.
* Private and reserved IP ranges are blocked (anti-SSRF).
* Nonce verification on all form submissions including CSV download.
* No user data is stored permanently.
