<?php
// link-meta-box.php

defined('ABSPATH') || exit;

function lm_render_link_meta_box($post)
{
    global $wpdb;
    $post_id = $post->ID;
    $links_table = $wpdb->prefix . 'custom_links';
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
    $all_links = $wpdb->get_results("SELECT * FROM $links_table ORDER BY label ASC");
    $selected_links = $wpdb->get_col($wpdb->prepare("SELECT link_id FROM $assoc_table WHERE post_id = %d", $post_id));

    echo '<div style="font-size:14px">';
    $skipped_broken_links = 0;
    foreach ($all_links as $link)
    {
        if (trim(strtolower($link->label)) === '404') {
            $skipped_broken_links++;
            continue;
        }
        $checked = in_array($link->id, $selected_links) ? 'checked' : '';
        echo '<div style="margin-bottom:1em;display:flex;align-items:center;gap:0.5em">';
        echo '<input type="checkbox" name="lm_links[]" value="' . intval($link->id) . '" ' . $checked . '>';
        lm_render_link_row_view($link, false);
        echo '</div>';
    }
    echo '</div>';

    echo '<p style="margin-top:1em;">';
    echo '<label><input type="checkbox" name="lm_detect_external" value="1" checked> Auto-detect external links</label><br>';
    echo '<label><input type="checkbox" name="lm_detect_internal" value="1"> Auto-detect internal links</label><br>';
    echo '<label><input type="checkbox" name="lm_detect_emails" value="1"> Auto-detect email links</label>';
    echo '</p>';

    if ($skipped_broken_links > 0)
    {
        add_action('admin_notices', function () use ($skipped_broken_links) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Warning:</strong> ' . $skipped_broken_links . ' broken link(s) skipped. See the <a href="' . admin_url('admin.php?page=links-manager') . '">Link Manager settings</a>.</p>';
            echo '</div>';
        });
    }
}

function lm_save_link_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    global $wpdb;
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
    $wpdb->delete($assoc_table, ['post_id' => $post_id]);

    if (!empty($_POST['lm_links']) && is_array($_POST['lm_links']))
    {
        foreach ($_POST['lm_links'] as $link_id)
        {
            $wpdb->insert($assoc_table, [
                'post_id' => $post_id,
                'link_id' => intval($link_id),
            ]);
        }
    }

    if (!empty($_POST['lm_detect_external']) && !get_post_meta($post_id, '_linkfolio_notice_lm_detect_external', true))
    {
        update_post_meta($post_id, '_linkfolio_notice_lm_detect_external', '1');
        add_action('admin_notices', function () {
            echo '<div class="notice notice-info is-dismissible"><p><strong>Linkfolio:</strong> External link detection is enabled.</p></div>';
        });
    }
    if (!empty($_POST['lm_detect_internal']) && !get_post_meta($post_id, '_linkfolio_notice_lm_detect_internal', true))
    {
        update_post_meta($post_id, '_linkfolio_notice_lm_detect_internal', '1');
        add_action('admin_notices', function () {
            echo '<div class="notice notice-info is-dismissible"><p><strong>Linkfolio:</strong> Internal link detection is enabled.</p></div>';
        });
    }
    if (!empty($_POST['lm_detect_emails']) && !get_post_meta($post_id, '_linkfolio_notice_lm_detect_emails', true))
    {
        update_post_meta($post_id, '_linkfolio_notice_lm_detect_emails', '1');
        add_action('admin_notices', function () {
            echo '<div class="notice notice-info is-dismissible"><p><strong>Linkfolio:</strong> Email link detection is enabled.</p></div>';
        });
    }

// Handle auto-detect links if requested
    if (!empty($_POST['lm_detect_external']) || !empty($_POST['lm_detect_internal']) || !empty($_POST['lm_detect_emails']))
    {
        lm_detect_links_in_post($post_id, $_POST);
    }
}

add_action('add_meta_boxes', function ()
{
    add_meta_box('lm-links-meta-box', 'Link Manager', 'lm_render_link_meta_box', ['post', 'page'], 'side', 'default');
});

add_action('save_post', 'lm_save_link_meta_box');
