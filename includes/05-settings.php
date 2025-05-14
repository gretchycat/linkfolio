<?php
// 05-settings.php
defined('ABSPATH') || exit;

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

// Default Linkfolio "About" page
function lf_render_linkfolio_page() {
    echo '<div class="wrap">';
    echo '<h1>Linkfolio</h1>';
    echo '<div class="lf-about-tab" style="background: rgba(0,0,0,0.05); padding: 1em; border-radius: 6px;">';
    echo '<h2>About Linkfolio</h2>';
    echo '<p><strong>Linkfolio</strong> is a lightweight, privacy-conscious WordPress plugin for managing curated links and displaying them cleanly on your posts or pages.</p>';
    echo '<ul style="list-style: disc; padding-left: 1.5em;">';
    echo '<li>Auto-detects links in post/page content</li>';
    echo '<li>Fetches icons and page titles automatically</li>';
    echo '<li>Groups links using customizable categories</li>';
    echo '<li>Tracks and reports broken (4xx) links</li>';
    echo '<li>Lets you assign links per page or post</li>';
    echo '</ul>';
    echo '<p>Use the sidebar menu to manage <strong>Links</strong>, <strong>Categories</strong>, and <strong>Broken Links</strong>.</p>';
    echo '<a href="/wp-admin/admin.php?page=linkfolio-edit-links">edit links</a>' ;
    echo '<a href="/wp-admin/admin.php?page=linkfolio-edit-categoriess">edit categories</a>' ;
    echo '<p style="margin-top:2em;color:#777;font-size:0.9em;">Plugin by <strong>Gretchen Maculo</strong>. Version 0.1.3</p>';
    echo '</div>';
    echo '</div>';
}
