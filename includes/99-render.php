<?php
defined('ABSPATH') || exit;

// Enqueue frontend CSS
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'linkfolio-style',
        plugin_dir_url(__DIR__) . 'assets/linkfolio.css',
        [],
        defined('LINKFOLIO_SCHEMA_VERSION') ? LINKFOLIO_SCHEMA_VERSION : null
    );
});

// Optionally, append links to content automatically
add_filter('the_content', function ($content) {
    if (is_singular() && in_the_loop() && is_main_query()) {
        $rendered = lf_render_links_for_post(get_the_ID());
        if (!empty($rendered)) {
            return $content . $rendered;
        }
    }
    return $content;
});

/**
 * Prepare link display values based on category settings.
 */
function lf_prepare_link_display($link, $category)
{
    // Fallback label: domain if label missing
    $label = trim($link->label);
    if ($label === '') {
        $host = parse_url($link->url, PHP_URL_HOST);
        $label = $host ?: $link->url;
    }

    // Display URL: truncated for display, raw for href
    $display_url = $link->url;
    $maxlen = 32;
    if (mb_strlen($display_url) > $maxlen) {
        $display_url = mb_substr($display_url, 0, $maxlen) . '…';
    }

    return [
        'icon_url'     => (!empty($category->show_icon) && !empty($link->icon_url)) ? $link->icon_url : '',
        'label'        => $label,
        'display_url'  => !empty($category->show_url) ? $display_url : '',
        'href'         => $link->url,
        'desc'         => (!empty($category->show_description) && $link->description) ? $link->description : '',
    ];
}

/**
 * Generate a single link block for horizontal display.
 */
function lf_render_link_horizontal($link, $category)
{
    $d = lf_prepare_link_display($link, $category);
    $tooltip = esc_attr($d['href'] . ($d['desc'] ? ' — ' . strip_tags($d['desc']) : ''));
    $out = '<div style="text-align:center;">';
    $out .= '<a href="' . esc_url($d['href']) . '" target="_blank" rel="noopener" title="' . $tooltip . '" class="linkfolio-hlink">';
    if ($d['icon_url']) 
    {
        $out .= '<img src="' . esc_url($d['icon_url']) . '" alt="'.esc_html($d['label']).'" class="lf-linkfolio-icon" style="width:3em;height:3em;object-fit:contain;margin-bottom:0.2em;"><br/>';
    }
    $lab = mb_strlen($d['label']) > 20 ? mb_substr($d['label'],0,20).'…' : $d['label'];
    $out .= esc_html($lab) ;
    $out .= '</a></div>';
    return $out;
}

/**
 * Generate a single link item for vertical display.
 */
function lf_render_link_vertical($link, $category)
{
    $d = lf_prepare_link_display($link, $category);
    $sep = !empty($category->separator) ? $category->separator : '•';
    $out .= '<span class="lf-link-sep" style="margin-right:0.5em;">' . esc_html($sep) . '</span>';
    $out .= '<a href="' . esc_url($d['href']) . '" target="_blank" rel="noopener" title="' . esc_attr($d['href'] . ($d['desc'] ? ' — ' . strip_tags($d['desc']) : '')) . '" class="linkfolio-vlink">';
    if ($d['icon_url']) {
        $out .= '<img src="' . esc_url($d['icon_url']) . '" alt="" class="lf-linkfolio-icon" style="height:1em;width:1em;vertical-align:middle;margin-right:0.4em;">';
    }
    $out .= esc_html($d['label']);
    $out .= '</a><br/>';
    if ($d['display_url']) {
        $out .= ' <span class="lf-link-url">(' . lf_url_with_wbr(esc_html($d['href'])) . ')</span><br/>';
    }
    if ($d['desc']) {
        $out .= '<div class="lf-link-desc" style="font-size:0.9em;opacity:0.8;margin-left:2em;">' . esc_html($d['desc']) . '</div><br/>';
    }
    return $out;
}

/**
 * Render all links associated with a given post, grouped by category.
 */
function lf_render_links_for_post($post_id)
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'linkfolio_link_post_map';
    $links_table = $wpdb->prefix . 'linkfolio_links';
    $cat_table   = $wpdb->prefix . 'linkfolio_link_categories';

    $link_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT link_id FROM $assoc_table WHERE post_id = %d", $post_id
    ));
    if (empty($link_ids)) return '';

    // Avoid empty IN ()
    $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
    $links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $links_table WHERE id IN ($placeholders) ORDER BY category_slug, id ASC",
        ...$link_ids
    ));

    // Group links by category
    $grouped = [];
    foreach ($links as $link) {
        $grouped[$link->category_slug][] = $link;
    }

    $out = '';
    foreach ($grouped as $cat_slug => $cat_links) 
    {
        $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM $cat_table WHERE slug = %s", $cat_slug));
        $cat_name = $cat ? $cat->name : ucfirst($cat_slug);
        $layout = $cat && $cat->layout === 'horizontal' ? 'horizontal' : 'vertical';
        $out .= '<h3 class="linkfolio-category-heading">' . esc_html($cat_name) . '</h3>';
        if ($layout === 'horizontal') 
        {
            $out .= '<div style="display:flex; gap:1em; flex-wrap:wrap;">';
            $count = count($cat_links);
            foreach ($cat_links as $i => $link) {
                $out .= lf_render_link_horizontal($link, $cat);
                if ($i < $count - 1 && !empty($cat->separator)) 
                {
                    $out .= esc_html($cat->separator);
                }
            }
            $out .= '</div>';
        } else {
            foreach ($cat_links as $link) {
                $out .= lf_render_link_vertical($link, $cat);
            }
        }
    }
    return $out;
}

/**
 * Render all links in a given category (useful for shortcodes).
 */
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
        $out .= '<div style="display:flex; gap:1em; flex-wrap:wrap;">';
        $count = count($links);
        foreach ($links as $i => $link) {
            $out .= lf_render_link_horizontal($link, $cat);
            if ($i < $count - 1 && !empty($cat->separator))
            {
                $out .= esc_html($cat->separator);
            }
        }
        $out .= '</div>';
    } 
    else 
    {
        foreach ($links as $link) 
        {
            $out .= lf_render_link_vertical($link, $cat);
        }
    }
    return $out;
}

