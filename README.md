# Image Dimensions URL

Easily check the dimensions of images on your website by simply entering its URL. Detects mismatches between HTML-declared and real file dimensions, supports lazy-loaded images, and exports results as CSV.

![Banner](http://javierenweb.com/wp-content/uploads/2025/01/image-1.jpg)

**Image Dimensions URL** is a WordPress plugin that audits all images on any public webpage. It compares the dimensions declared in HTML attributes against the actual file dimensions, highlights discrepancies in red, and supports modern lazy-loading patterns.

## Features

- Extracts image dimensions from any public webpage URL.
- **Detects mismatches** between HTML-declared dimensions and real file dimensions (highlighted in red).
- **Supports lazy-loaded images** via `data-src`, `data-lazy-src`, `data-lazy`, `data-original`.
- Displays results in a structured table with image previews.
- Exports full report as CSV (UTF-8 with BOM for Excel compatibility).
- Handles up to 150 images per scan to prevent server timeouts.
- Anti-SSRF protection blocks requests to private/internal IPs.
- Fallback dimension detection for servers with `allow_url_fopen` disabled.
- Simple, lightweight, no external dependencies.

## Installation

1. Download the plugin ZIP file or clone this repository.
2. Upload the plugin folder to your `/wp-content/plugins/` directory.
3. Activate the plugin from the **Plugins** menu in WordPress.
4. Access the plugin from the **Image Dimensions** menu in the WordPress admin dashboard.

## Usage

1. Navigate to **Image Dimensions** in your WordPress dashboard.
2. Enter the URL of the webpage you want to analyze.
3. Click **Ver dimensiones** to retrieve all image data.
4. Review the results — rows in **red** indicate dimension mismatches between HTML attributes and the real file.
5. Click **Descargar reporte CSV** to download the full report.

## Changelog

### v1.5
- Added mismatch detection: compares HTML dimensions vs. real file dimensions
- Added lazy-load support (`data-src`, `data-lazy-src`, `data-lazy`, `data-original`)
- Added anti-SSRF URL validation
- Added fallback dimension detection via `wp_remote_get()`
- Added relative/protocol-relative URL resolution
- Added 150-image scan limit with user notice
- Added nonce protection to CSV download form
- Added UTF-8 BOM to CSV for correct Excel rendering
- Improved error messages
- Improved CSV: now includes HTML dims, real dims, and status columns
- Fixed: CSV data now uses WP transients instead of hidden form fields

### v1.4
- Initial public release

## Security

- All URLs are sanitized and validated before fetching.
- Private and reserved IP ranges are blocked (anti-SSRF).
- Nonce verification on all form submissions including CSV download.
- No user data is stored permanently.

## Contributing

Contributions are welcome!

1. Fork the repository.
2. Create a new branch (`feature/my-feature` or `fix/my-bug`).
3. Make your changes and commit them.
4. Push your branch and create a Pull Request.

## License

This plugin is released under the [GPL-2.0+ License](https://www.gnu.org/licenses/gpl-2.0.html).
