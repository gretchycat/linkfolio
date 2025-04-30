<?php
defined('ABSPATH') || exit;

// Automatically append links to posts/pages with assigned links
add_filter('the_content', function ($content) {
    if (is_singular() && in_the_loop() && is_main_query()) {
        $rendered = lm_render_links_for_post(get_the_ID());
        if (!empty($rendered)) {
            return $content . $rendered;
        }
    }
    return $content;
});

function lm_render_links_for_post($post_id) {
    global $wpdb;
    $cat_table = $wpdb->prefix . 'custom_link_categories';
    $link_table = $wpdb->prefix . 'custom_links';
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';

    $link_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT link_id FROM $assoc_table WHERE post_id = %d",
        $post_id
    ));

    if (empty($link_ids)) return '';

    $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));

    $links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $link_table WHERE id IN ($placeholders)",
        ...$link_ids
    ));

    if (empty($links)) return '';

    // Group links by category slug
    $grouped = [];
    foreach ($links as $link) {
        $grouped[$link->category_slug][] = $link;
    }

    // Fetch categories used
    $used_slugs = array_keys($grouped);
    $slug_placeholders = implode(',', array_fill(0, count($used_slugs), '%s'));

    $categories = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $cat_table WHERE slug IN ($slug_placeholders)",
        ...$used_slugs
    ));

    // Index by slug
    $cat_index = [];
    foreach ($categories as $cat) {
        $cat_index[$cat->slug] = $cat;
    }

    // Render HTML
    ob_start();
    echo '<div class="lm-links-wrapper">';

    foreach ($grouped as $slug => $links_in_cat) {
        $cat = $cat_index[$slug] ?? null;
        if (!$cat || $cat->slug === 'uncategorized') continue;

        echo '<div class="lm-links-section" style="margin-top:1em;">';
        echo '<h3>' . esc_html($cat->name) . '</h3>';

        if ($cat->layout === 'horizontal') {
            echo '<div style="display:flex; gap:1em; flex-wrap:wrap;">';
            foreach ($links_in_cat as $link) {
                echo '<div style="text-align:center;">';
                if (!empty($cat->show_icon) && !empty($link->icon_url)) {
                    echo '<img src="' . esc_url($link->icon_url) . '" alt="" style="width:48px;height:48px;"><br>';
                }
                echo '<strong><a href="' . esc_url($link->url) . '" target="_blank">' . esc_html($link->label) . '</a></strong>';
                if (!empty($cat->show_url)) {
                    echo '<div style="font-size:0.85em;">(' . esc_html($link->url) . ')</div>';
                }
                if (!empty($cat->show_description) && $link->description) {
                    echo '<div style="font-size:0.85em;">' . esc_html($link->description) . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        } else { // vertical
            echo '<ul style="list-style: none; padding-left: 0;">';
            foreach ($links_in_cat as $link) {
                echo '<li style="margin-bottom:0.5em;">';
                if (!empty($cat->separator)) echo '<span>' . esc_html($cat->separator) . '</span> ';
                if (!empty($cat->show_icon) && !empty($link->icon_url)) {
                    echo '<img src="' . esc_url($link->icon_url) . '" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;">';
                }
                echo '<strong><a href="' . esc_url($link->url) . '" target="_blank">' . esc_html($link->label) . '</a></strong>';
                if (!empty($cat->show_url)) {
                    echo ' <span style="font-size:0.85em;">(' . esc_html($link->url) . ')</span>';
                }
                if (!empty($cat->show_description) && $link->description) {
                    echo '<div style="font-size:0.85em;margin-left:1.5em;">' . esc_html($link->description) . '</div>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}