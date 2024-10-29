<?php

defined('ABSPATH') or die;

/**
 * Add settings page
 */
add_action('admin_menu', function() {
    add_options_page(
        esc_html__('Image optimizer', 'another-simple-image-optimizer'),
        esc_html__('Image optimizer', 'another-simple-image-optimizer'),
        'manage_options',
        'simple-image-optimizer',
        ['AnotherSimpleImageOptimizer', 'settingsPage']
    );
});

/**
 * Link to settings page from plugins screen
 */
add_filter('plugin_action_links_' . basename(__DIR__) . '/plugin.php', function($links) {
    return array_merge([
        '<a href="' . admin_url('options-general.php?page=simple-image-optimizer') . '">' . esc_html__('Settings') . '</a>',
    ], $links);
});

/**
 * Add column to media library to display optimizer info and link
 */
add_action('admin_init', function() {

    add_filter('manage_media_columns', function($columns) {
        return array_merge($columns, [
            'optimized' => esc_html__('File size', 'another-simple-image-optimizer')
        ]);
    }, 10);

    add_action('manage_media_custom_column', function ($name, $id) {

        if ($name !== 'optimized') return;

        $post = wp_get_attachment_metadata($id);

        // avoid "Optimize" link if media is no image
        if (!isset($post['file']) || !is_string($post['file'])) {

            if (isset($post['filesize']) && is_int($post['filesize'])) {
                $fileSizeStr = size_format($post['filesize'], 2);
                echo esc_html($fileSizeStr);
            }
            return;
        }

        $isOptimized = isset($post['simple-image-optimizer']);

        $thumbsAreOptimized = true;
        if (isset($post['sizes'])) {
            foreach ($post['sizes'] as $thumb) {
                if (!isset($thumb['simple-image-optimizer'])) {
                    $thumbsAreOptimized = false;
                    break;
                }
            }
        }

        $fileSizeStr = \AnotherSimpleImageOptimizer::formatFileSize($post);
        echo esc_html($fileSizeStr);

        if (!$isOptimized || !$thumbsAreOptimized) {

            $url = wp_nonce_url(admin_url('options-general.php?page=simple-image-optimizer&action=optimize&id='.$id), 'optimize');

            echo '<br /><a href="'.esc_url($url).'">' . esc_html__('Optimize', 'another-simple-image-optimizer') . '</a>';
        }

        elseif (defined('ALLOW_FORCE_OPTIMIZE_IMAGES') && ALLOW_FORCE_OPTIMIZE_IMAGES) {

            $url = wp_nonce_url(admin_url('options-general.php?page=simple-image-optimizer&action=optimize&force=1&id='.$id), 'optimize');

            echo '<br /><a href="'.esc_url($url).'">' . esc_html__('Optimize again', 'another-simple-image-optimizer') . '</a>';
        }


    }, 10, 2 );

    add_action('admin_head', function() {
        echo '<style>.upload-php .fixed .column-optimized {width: 8%;}</style>';
    });

});
