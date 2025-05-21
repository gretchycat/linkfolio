<?php
// 05-settings.php
defined('ABSPATH') || exit;
require_once plugin_dir_path(__FILE__) . 'lib/Parsedown/Parsedown.php';
// Add main Linkfolio admin menu with subpages
add_action('admin_menu', function () {
    add_menu_page(
        'Linkfolio',
        'Linkfolio',
        'read',
        'linkfolio',
        'lf_render_linkfolio_page',
        'dashicons-admin-links',
        80
    );

    add_submenu_page(
        'linkfolio',
        'Edit Links',
        'Edit Links',
        'read',
        'linkfolio-edit-links',
        'lf_render_link_settings_page'
    );

    add_submenu_page(
        'linkfolio',
        'Edit Categories',
        'Edit Categories',
        'read',
        'linkfolio-edit-categories',
        'lf_render_category_settings_page'
    );
}, 11);

add_action('admin_init', function() {
    lf_ensure_tables_exist();
    lf_prune_orphaned_link_associations();
    lf_prune_orphaned_post_associations();
});

// Default Linkfolio "About" page
function lf_render_linkfolio_page()
{
    $parsedown = new Parsedown();
    $base= plugin_dir_path(__DIR__) . 'docs/';
    $about_file = $base.'about.md';
    $admin_links_file = $base.'admin-links.md';
    $instructions_file = $base.'instructions.md';
    echo <<<EOF
    <style>
        ul { list-style-type: disc !important; margin-left: 2em !important; }
        li { display: list-item !important; }
    </style>
    EOF;
    echo $parsedown->text(file_get_contents($about_file));
    echo $parsedown->text(file_get_contents($admin_links_file));
    echo $parsedown->text(file_get_contents($instructions_file));

    echo '<ul style="list-style-type:disc; margin-left:2em;"><li>Test</li></ul>';

}
