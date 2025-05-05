<?php
// category-settings.php

defined('ABSPATH') || exit;

// Enqueue admin styles
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'linkfolio') !== false) {
        wp_enqueue_style('linkfolio-admin-style', plugin_dir_url(__DIR__) . 'assets/admin.css');
    }
});

// Render the c=ategory settings subpage
function lf_render_category_settings_page() {
    echo '<div class="wrap lm-category-settings-wrap">';
    echo '<h1 class="lm-category-settings-title">Edit Link Categories</h1>';
    do_action('admin_notices');

    foreach ($_POST as $key => $val) {
        if (preg_match('/^save_(\d+)$/', $key, $m) || $key === 'save_new') {
            $id = $m[1] ?? 'new';
            lf_save_category([
                'name' => $_POST["name_$id"] ?? '',
                'layout' => $_POST["layout_$id"] ?? 'vertical',
                'separator' => $_POST["separator_$id"] ?? '•',
                'show_url' => isset($_POST["url_$id"]),
                'show_icon' => isset($_POST["icon_$id"]),
                'show_description' => isset($_POST["desc_$id"]),
            ], $id === 'new' ? null : $id);
            $_POST["saved_$id"] = true;
        }
        if (preg_match('/^cancel_(\d+)$/', $key, $m) || $key === 'cancel_new') {
            $id = $m[1] ?? 'new';
            $_POST["cancel_triggered_$id"] = true;
        }
        if (preg_match('/^delete_(\d+)$/', $key, $m)) {
            lf_delete_category($m[1]);
        }
    }

    echo '<div class="lm-category-settings-body">';
    lf_render_all_categories();
    echo '</div></div>';
}