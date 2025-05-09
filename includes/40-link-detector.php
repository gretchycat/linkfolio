<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lf_detect_links_in_post($post_id, $post)
{
    if (!isset($post['lf_detect_external']) && !isset($post['lf_detect_internal']) && !isset($post['lf_detect_email'])) return;

    $content = $post['post_content'];
    if (!is_string($content)) return;

    preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
        stripslashes($content),
        $matches,
        PREG_SET_ORDER);
    if (!$matches) return;

    $skipped = 0;

    global $wpdb;
    $links_table = $wpdb->prefix . 'custom_links';
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';

    foreach ($matches as $m)
    {
        $url = esc_url_raw(trim($m[1]));
        $label = wp_strip_all_tags($m[2]);
        if (empty($url)) continue;

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $scheme = $parsed['scheme'] ?? '';

        // Filter types
        if (str_starts_with($url, 'mailto:') && empty($post['lf_detect_email'])) continue;
        if (!empty($host) && $host !== $_SERVER['HTTP_HOST'] && empty($post['lf_detect_external'])) continue;
        if ((!$host || $host === $_SERVER['HTTP_HOST']) && empty($post['lf_detect_internal'])) continue;

        // Get status code
        $response = wp_remote_head($url, ['timeout' => 5]);
        $code = wp_remote_retrieve_response_code($response);

        // Look up existing link
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $links_table WHERE url = %s", $url));
        $link_id = $existing->id ?? null;

        if (!$link_id)
        {
            // Fetch page metadata
            $details = lf_fetch_page_metadata($url);
            $label = !empty($details['title']) ? $details['title'] : ($label ?: $url);
            $icon = $details['icon_url'] ?? '';

            // Insert new link
            $wpdb->insert($links_table, [
                'label'         => $label,
                'url'           => $url,
                'icon_url'      => $icon,
                'description'   => '',
                'category_slug' => 'references',
                'status_code'   => $code,
            ]);
            $link_id = $wpdb->insert_id;
        }

        // Add association if not already present
        if ($link_id)
        {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $assoc_table WHERE post_id = %d AND link_id = %d",
                $post_id,
                $link_id
            ));
            if (!$exists)
            {
                $wpdb->insert($assoc_table, [
                    'post_id' => $post_id,
                    'link_id' => $link_id,
                ]);
            }
        }
    }

    if ($skipped > 0) {
        add_action('admin_notices', function () use ($skipped) {
            echo '<div class="notice notice-warning is-dismissible"><p>' .
                esc_html("$skipped broken links were skipped. See the broken links report on the settings page.") .
                '</p></div>';
        });
    }
}
