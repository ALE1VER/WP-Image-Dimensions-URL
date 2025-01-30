# WP Image Dimensions URL
Easily check the dimensions of images on your website by simply entering its URL. This plugin quickly retrieves image sizes and helps you ensure your images are optimized for your website. 

**Image Dimensions Checker for URL** is a WordPress plugin that allows you to quickly check the dimensions of all images on a given webpage by simply entering its URL. This helps you ensure that your images are properly sized and optimized for performance.

## Features

- Extracts image dimensions from any webpage URL.
- Displays results in a structured table.
- Allows downloading reports as CSV files.
- Provides image previews in the WordPress admin panel.
- Simple and lightweight with minimal impact on performance.

## Installation

1. Download the plugin ZIP file or clone this repository.
2. Upload the plugin folder to your `/wp-content/plugins/` directory.
3. Activate the plugin from the **Plugins** menu in WordPress.
4. Access the plugin from the **Image Dimensions** menu in the WordPress admin dashboard.

## Usage

1. Navigate to **Image Dimensions** in your WordPress dashboard.
2. Enter the URL of the webpage you want to analyze.
3. Click **Check Dimensions** to retrieve all image sizes.
4. View the results in a table format, with an option to download a CSV report.

## Contributing

We welcome contributions! To contribute:

1. Fork the repository.
2. Create a new branch (`feature-branch` or `bugfix-branch`).
3. Make your changes and commit them.
4. Push your branch and create a Pull Request.

## Security Considerations

- The plugin sanitizes and validates URLs before fetching content.
- It does not store any user data.
- Uses `sanitize_text_field()` to prevent malicious input.

## License

This plugin is released under the [GPL-2.0+ License](https://www.gnu.org/licenses/gpl-2.0.html).