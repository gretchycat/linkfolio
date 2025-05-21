<?php
defined('ABSPATH') || exit;

// Automatically append links to posts/pages with assigned links
add_filter('the_content', function ($content) {
    if (is_singular() && in_the_loop() && is_main_query()) {
        $rendered = lf_render_links_for_post(get_the_ID());
        if (!empty($rendered)) {
            return $content . $rendered;
        }
    }
    return $content;
});

function lf_prepare_link_display($link, $category)
{
    // Label: prefer label, else fallback to domain from URL
    $label = trim($link->label);
    if ($label === '') {
        // Fallback: show domain only
        $host = parse_url($link->url, PHP_URL_HOST);
        $label = $host ?: $link->url;
    }

    // Display URL: truncated for display, raw for href
    $display_url = $link->url;
    $maxlen = 20;
    if (mb_strlen($display_url) > $maxlen) {
        $display_url = mb_substr($display_url, 0, $maxlen) . '…';
    }

    // Only pass through *values*; do not generate HTML
    return [
        'icon_url'     => (!empty($category->show_icon) && !empty($link->icon_url)) ? $link->icon_url : '',
        'label'        => $label,
        'display_url'  => !empty($category->show_url) ? $display_url : '',
        'href'         => $link->url,
        'desc'         => (!empty($category->show_description) && $link->description) ? $link->description : '',
    ];
}

function lf_render_link_horizontal($link, $category)
{
    $d = lf_prepare_link_display($link, $category);
    $tooltip = esc_attr($d['href'] . ($d['desc'] ? ' — ' . strip_tags($d['desc']) : ''));
    $out = '<span class="linkfolio-link linkfolio-horizontal">';
    $out .= '<a href="' . esc_url($d['href']) . '" target="_blank" rel="noopener" title="' . $tooltip . '" class="linkfolio-hlink">';
    $out .= '<div class="lf-link-horizontal" style="text-align:center;">';
    if ($d['icon_url']) {
        $out .='<img src="' . esc_url($d['icon_url']) . '" alt="'.esc_html($d['label']).'" style="height:3em;width:3em;vertical-align:middle;"><br/>';
    }
    $out .= esc_html($d['label']);
    $out .= '</a>';
    $out .= '</span>';
    return $out;
}

function lf_render_link_vertical($link, $category)
{
    $d = lf_prepare_link_display($link, $category);
    $sep = !empty($category->separator) ? $category->separator : '•';
    $out = '<li class="linkfolio-link linkfolio-vertical">';
    $out .= '<span class="lf-link-sep" style="margin-right:0.5em;">' . esc_html($sep) . '</span>';
    $out .= '<a href="' . esc_url($d['href']) . '" target="_blank" rel="noopener" title="' . esc_attr($d['href'] . ($d['desc'] ? ' — ' . strip_tags($d['desc']) : '')) . '" class="linkfolio-vlink">';
    if ($d['icon_url']) 
    {
        $out .= '<img src="' . esc_url($d['icon_url']) . '" alt="" style="height:1em;width:1em;vertical-align:middle;margin-right:0.4em;">';
    }
    $out .= esc_html($d['label']);
    $out .= '</a><br/>';
    if ($d['display_url']) 
    {
        $out .= ' <span class="lf-link-url">(' . lf_url_with_wbr(esc_html($d['href'])) . ')</span>';
    }
    if ($d['desc']) 
    {
        $out .= '<div class="lf-link-desc" style="font-size:0.9em;opacity:0.8;margin-left:2em;">' . esc_html($d['desc']) . '</div>';
    }
    $out .= '</li>';
    return $out;
}

function lf_render_links_for_post($post_id)
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'linkfolio_link_post_map';
    $links_table = $wpdb->prefix . 'linkfolio_links';
    $cat_table   = $wpdb->prefix . 'linkfolio_link_categories';

    // Get all link IDs for this post
    $link_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT link_id FROM $assoc_table WHERE post_id = %d", $post_id
    ));
    if (empty($link_ids)) return '';

    // Fetch links, ordered by category then id
    $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
    $links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $links_table WHERE id IN ($placeholders) ORDER BY category_slug, id ASC",
        ...$link_ids
    ));

    // Group by category
    $grouped = [];
    foreach ($links as $link) {
        $grouped[$link->category_slug][] = $link;
    }

    $out = '';
    foreach ($grouped as $cat_slug => $cat_links) {
        $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM $cat_table WHERE slug = %s", $cat_slug));
        $cat_name = $cat ? $cat->name : ucfirst($cat_slug);
        $layout = $cat && $cat->layout === 'horizontal' ? 'horizontal' : 'vertical';
        $out .= '<h3 class="linkfolio-category-heading">' . esc_html($cat_name) . '</h3>';
        if ($layout === 'horizontal') {
            $out .= '<div class="linkfolio-row">';
            $count = count($cat_links);
            foreach ($cat_links as $i => $link) {
                $out .= lf_render_link_horizontal($link, $cat);
                if ($i < $count - 1 && !empty($cat->separator)) {
                    $out .= '<span class="linkfolio-separator" style="margin:0 0.5em;">' . esc_html($cat->separator) . '</span>';
                }
            }
            $out .= '</div>';
        } else {
            $out .= '<ul class="linkfolio-column">';
            foreach ($cat_links as $link) {
                $out .= lf_render_link_vertical($link, $cat);
            }
            $out .= '</ul>';
        }
    }
    return $out;
}

function lf_render_links_for_category($category_slug)
{
    global $wpdb;
    $cat_table   = $wpdb->prefix . 'linkfolio_link_categories';
    $links_table = $wpdb->prefix . 'linkfolio_links';

    $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM $cat_table WHERE slug = %s", $category_slug));
    if (!$cat) return '';

    $links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $links_table WHERE category_slug = %s ORDER BY id ASC",
        $category_slug
    ));
    if (!$links) return '';

    $out = '<h3 class="linkfolio-category-heading">' . esc_html($cat->name) . '</h3>';
    if ($cat->layout === 'horizontal') {
        $out .= '<div class="linkfolio-row">';
        $count = count($links);
        foreach ($links as $i => $link) {
            $out .= lf_render_link_horizontal($link, $cat);
            if ($i < $count - 1 && !empty($cat->separator)) {
                $out .= '<span class="linkfolio-separator" style="margin:0 0.5em;">' . esc_html($cat->separator) . '</span>';
            }
        }
        $out .= '</div>';
    } else {
        $out .= '<ul class="linkfolio-column">';
        foreach ($links as $link) {
            $out .= lf_render_link_vertical($link, $cat);
        }
        $out .= '</ul>';
    }
    return $out;
}

function lf_render_links_for_post_old($post_id) {
    global $wpdb;
    $cat_table = $wpdb->prefix . 'linkfolio_link_categories';
    $link_table = $wpdb->prefix . 'linkfolio_links';
    $assoc_table = $wpdb->prefix . 'linkfolio_link_post_map';

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
    echo '<div class="lf-links-wrapper">';

    foreach ($grouped as $slug => $links_in_cat) {
        $cat = $cat_index[$slug] ?? null;
        if (!$cat || $cat->slug === 'uncategorized') continue;

        echo '<div class="lf-links-section" style="margin-top:1em;">';
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
                    echo '<div style="font-size:0.85em;">(' .lf_url_with_wbr( esc_html($link->url)) . ')</div>';
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
                    echo ' <span style="font-size:0.85em;">(' . lf_url_with_wbr(esc_html($link->url)) . ')</span>';
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
