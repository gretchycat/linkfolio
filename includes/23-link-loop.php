<?php
// link-loop.php

defined('ABSPATH') || exit;

function lf_render_all_links() 
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';
    $links = $wpdb->get_results("SELECT * FROM $table ORDER BY id ASC");
    foreach ($links as $link) 
    {
        $id = $link->id;
        if (isset($_POST["edit_link_$id"])) 
        {
            lf_render_link_row_editor($link);
        } elseif (isset($_POST["saved_link_$id"])) 
        {
            lf_render_link_row_view($link);
        } elseif (isset($_POST["cancel_link_triggered_$id"])) 
        {
            lf_render_link_row_view($link);
        } else 
        {
            lf_render_link_row_view($link);
        }
    }

    if (isset($_POST['add_new_link'])) {
        lf_render_link_row_editor((object)['id' => 'new', 'label' => '', 'url' => '', 'icon_url' => '', 'description' => '', 'category_slug' => 'uncategorized']);
    }

    echo '<p><button class="button" name="add_new_link" value="1">+ Add Link</button></p>';
}

function lf_render_links_by_category($slug, $show_broken) 
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';
    $links = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE category_slug = %s ORDER BY id ASC", $slug));

    foreach ($links as $link) 
    {
        $id = $link->id;
        if (isset($_POST["edit_link_$id"])) 
        {
            lf_render_link_row_editor($link);
        } 
        elseif (isset($_POST["saved_link_$id"])) 
        {
            lf_render_link_row_view($link, true, $show_broken);
        } 
        elseif (isset($_POST["cancel_link_triggered_$id"])) 
        {
            lf_render_link_row_view($link, true, $show_broken);
        } 
        else 
        {
            lf_render_link_row_view($link, true, $show_broken);
        }
    }
    $link = [
        'id'          => 'new',
        'label'       => '',
        'url'         => '',
        'icon_url'    => '',
        'description' => '',
        'category'    => $slug ?? '',
        'status_code' => 404,
    ];
    lf_render_link_row_editor($link);
}

function lf_render_broken_links() {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';
    $links = $wpdb->get_results("SELECT * FROM $table WHERE status_code >= 400 AND status_code < 500 ORDER BY id ASC");

    foreach ($links as $link) {
        $id = $link->id;
        if (isset($_POST["edit_link_$id"])) {
            lf_render_link_row_editor($link);
        } elseif (isset($_POST["saved_link_$id"])) {
            lf_render_link_row_view($link, true, true);
        } elseif (isset($_POST["cancel_link_triggered_$id"])) {
            lf_render_link_row_view($link, true, true);
        } else {
            lf_render_link_row_view($link, true, true);
        }
    }
}
