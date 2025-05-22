<?php
/*
Plugin Name: Linkfolio
Plugin URI: https://hopefaithless.42web.io/linkfolio
Description: Manage, categorize, and scan links across your site with bulk actions and metadata detection.
Version: 0.1.5
Author: Gretchen Maculo
Author URI: https://hopefaithless.42web.io
License: GPL2+
Text Domain: Linkfolio
# Support: https://hopefaithless.42web.io/contact
*/
defined('ABSPATH') || exit;
define('plugin_name', 'Linkfolio');
// Define constants early
if (!defined('LM_PLUGIN_PATH')) {
    define('LM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('LM_PLUGIN_URL')) {
    define('LM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Load all PHP files in the includes directory in sorted order
$files = glob(LM_PLUGIN_PATH . 'includes/*.php');
if ($files) {
    sort($files, SORT_STRING | SORT_FLAG_CASE); // Ensure alphabetical, case-insensitive
    foreach ($files as $file) {
        require_once $file;
    }
}

// Add a Settings link on the Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=' . strtolower(plugin_name)) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});

function linkfolio_register_block()
{
    wp_register_script(
        'linkfolio-block-script',
        plugins_url('assets/block/index.js', __FILE__),
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ],
        filemtime(plugin_dir_path(__FILE__) . 'assets/block/index.js')
    );

    register_block_type(
        'linkfolio/shortcode',
        array(
            'editor_script' => 'linkfolio-block-script',
        )
    );
}
add_action('init', 'linkfolio_register_block');

add_action('rest_api_init', function() {
    register_rest_route('linkfolio/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => function() {
            global $wpdb;
            $table = $wpdb->prefix . 'linkfolio_link_categories';
            $rows = $wpdb->get_results("SELECT name, slug FROM $table ORDER BY name ASC");
            return $rows;
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
});

add_action('admin_init', function () {
    add_filter('mce_external_plugins', function ($plugins) {
        $plugins['linkfolio_shortcode'] = plugins_url('assets/linkfolio-tinymce.js', __FILE__);
        return $plugins;
    });
    add_filter('mce_buttons', function ($buttons) {
        array_push($buttons, 'linkfolio_shortcode');
        return $buttons;
    });
});

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('wp-api');
});

$plugins['linkfolio_shortcode'] = plugins_url('assets/linkfolio-tinymce.js', __FILE__);

// Add TinyMCE external plugin
add_filter('mce_external_plugins', function($plugins) {
    $plugins['linkfolio'] = plugin_dir_url(__FILE__) . 'assets/linkfolio-tinymce.js';
    return $plugins;
});

// Add the button
add_filter('mce_buttons', function($buttons) {
    array_push($buttons, 'linkfolio');
    return $buttons;
});
