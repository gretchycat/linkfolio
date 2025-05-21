<?php
// 05-settings.php
defined('ABSPATH') || exit;
if (!class_exists('Parsedown'))
{
    require_once plugin_dir_path(__FILE__) . 'lib/Parsedown/Parsedown.php';
}

if (!class_exists('ParsedownExtra'))
{
    require_once plugin_dir_path(__FILE__) . 'lib/parsedown-extra/ParsedownExtra.php';
}
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
    $parsedown = new ParsedownExtra();
    $base= plugin_dir_path(__DIR__) . 'docs/';
    $about_file = $base.'about.md';
    $admin_links_file = $base.'admin-links.md';
    $instructions_file = $base.'instructions.md';

    echo '<div id="lf-markdown-docs">';
    echo <<<EOF
    <style>
    #lf-markdown-docs ul, #lf-markdown-docs ol {
        list-style-type: disc !important;
        margin-left: 2em !important;
        display: block !important;
    }
    #lf-markdown-docs li {
        display: list-item !important;
    }
    #lf-markdown-docs .lf-btn {
        display: inline-block;
        margin: 0 0.5em 0.5em 0;
        padding: 0.5em 1.2em;
        background: #2271b1;
        color: #fff !important;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1em;
        transition: background 0.2s;
        cursor: pointer;
    }
    #lf-markdown-docs .lf-btn:hover, #lf-markdown-docs .lf-btn:focus {
        background: #005177;
        color: #fff !important;
        text-decoration: none;
    }
    </style>
    EOF;
    echo $parsedown->text(file_get_contents($about_file));
    echo $parsedown->text(file_get_contents($admin_links_file));
    echo $parsedown->text(file_get_contents($instructions_file));
    echo '</div>';

}
