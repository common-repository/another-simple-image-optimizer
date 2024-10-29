<?php
/**
 * Plugin Name: Another simple image optimizer
 * Plugin URI: https://codeberg.org/raffaelj/wordpress-another-simple-image-optimizer
 * Description: Automatically optimize uploaded images using the Spatie image-optimizer library and binaries on your host system (jpegoptim, optipng, pngquant, cwebp, gifsicle, svgo, avifenc)
 * Author: Raffael Jesche
 * Author URI: https://www.rlj.me
 * Version: 0.2.1
 * License: MIT
 * License URI: https://codeberg.org/raffaelj/wordpress-another-simple-image-optimizer/src/branch/main/LICENSE
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Run image optimizer when attachment meta data is generated.
 *
 * Fires on file uploads or when using wp cli `wp media regenerate`
 */
add_filter('wp_generate_attachment_metadata', [
    'AnotherSimpleImageOptimizer',
    'hook_wp_generate_attachment_metadata'
]);

/**
 * Load admin ui logic
 */
add_action('init', function() {
    if (!is_user_logged_in()) return;
    include __DIR__ . '/admin.php';
});
