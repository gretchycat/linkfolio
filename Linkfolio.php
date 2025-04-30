<?php
/*
Plugin Name: Linkfolio
Description: Create, manage, and display custom link collections with categories, automatic title/icon fetching, and post/page link detection.
Version: 0.1.3
Author: Gretchen Maculo
*/

defined('ABSPATH') || exit;

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
    $settings_link = '<a href="' . admin_url('admin.php?page=linkfolio') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});
