<?php
// 04-link-settings.php
defined('ABSPATH') || exit;

// Main Linkfolio settings page
function lf_render_link_settings_page()
{
    add_action('admin_enqueue_scripts', function ($hook) {
        if (strpos($hook, 'linkfolio') !== false) 
        {
            wp_enqueue_media(); // this is essential
        }
        });

    foreach ($_POST as $key => $val)
    {
        if ( $key === 'rescan_broken_links')
            lf_rescan_broken_links();

        if ( $key === 'delete_broken_links')
            lf_delete_broken_links();

        if (preg_match('/^save_link_(\d+)$/', $key, $m) || $key === 'save_link_new')
        {
            $id = $m[1] ?? 'new';
            $status_code= $_POST["status_code_$id"] ?? '404';
            if (isset($_POST["force_valid_$id"]))
                $status_code='299';
            else
                $status_code="290";
            lf_save_link([
                'label' => $_POST["label_$id"] ?? '',
                'url' => $_POST["url_$id"] ?? '',
                'icon_url' => $_POST["icon_$id"] ?? '',
                'description' => $_POST["desc_$id"] ?? '',
                'category_slug' => $_POST["category_$id"] ?? 'uncategorized',
                'status_code' => $status_code,
                'clear_icon' => isset($_POST["clear_icon_$id"]),
            ], $id === 'new' ? null : $id);
            $_POST["saved_link_$id"] = true;
        }
        if (preg_match('/^cancel_link_(\d+)$/', $key, $m) || $key === 'cancel_link_new')
        {
            $id = $m[1] ?? 'new';
            $_POST["cancel_link_triggered_$id"] = true;
        }
        if (preg_match('/^delete_link_(\d+)$/', $key, $m))
        {
            lf_delete_link($m[1]);
        }
    }
    echo '<div class="wrap">';
    echo '<h1>Linkfolio</h1>';
    do_action('admin_notices');
    // Render tab buttons
    $categories = lf_get_all_categories();
    echo '<div class="lf-tab-bar" style="margin-bottom:1em">';
    foreach ($categories as $cat)
    {
        $tab='data-scroll-to="tab-' . esc_attr($cat->slug).'"'; 
        echo '<a href="#" '.$tab.'" class="lf-tab-button" data-tab="tab-' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</a> ';
    }
    echo '<a href="#" data-scroll-to="tab-broken" class="lf-tab-button" data-tab="tab-broken" style="color:#d33">Broken Links</a> ';
    echo '<a href="#" data-scroll-to="tab-new" class="lf-tab-button" data-tab="tab-new" style="color:#33d">New Link</a>';
    echo '</div>';
    echo '<form method="post" class="lf-linkfolio-form">';
    // all the buttons and link/category rows here

    // Render category-based link sections
    foreach ($categories as $cat)
    {
        echo '<div id="tab-' . esc_attr($cat->slug) . '" class="lf-tab-content" style="background: rgba(0,0,0,0.10); padding: 1em; margin-bottom: 2em;">';
        echo '<h2>' . esc_html($cat->name) . '</h2>';
        lf_render_links_by_category($cat->slug, false);
        echo '</div>';
    }

    // Render broken links section
    echo '<div id="tab-broken" class="lf-tab-content" style="background: rgba(0,0,0,0.10); padding: 1em;">';
    echo '<h2 style="color:#d33">Broken Links</h2>';
    lf_render_broken_links();
    // show check broken links
    echo <<<BROKENLINKBULK
    <button type="submit" name="rescan_broken_links">Rescan</button>
    <button type="submit" name="delete_broken_links">Purge</button>
    BROKENLINKBULK;
    // show purge broken links
    echo '</div>';

    // Render new link section
    echo '<div id="tab-new" class="lf-tab-content" style="background: rgba(0,0,0,0.10); padding: 1em;">';
    echo '<h2 style="color:#33d">New Link</h2>';

    $newlink = (object)[
        'id'           => 'new',
        'label'        => '',
        'url'          => '',
        'icon_url'     => '',
        'description'  => '',
        'category_slug'=> '',
        'status_code'  => 404,
    ];
    lf_render_link_row_editor($newlink);
    echo '</div>';
    echo '</form>';
    echo '</div>';

}
