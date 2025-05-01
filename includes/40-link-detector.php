<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lm_detect_links_in_post($post_id, $post) {
    if (!isset($post['lm_detect_external']) && !isset($post['lm_detect_internal']) && !isset($post['lm_detect_email'])) {
        return;
    }

    $content = stripslashes($post->post_content);
    if (!is_string($content)) return;
i
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER);
    if (!$matches) return;

    $added = 0;
    $skipped = 0;
    global $wpdb;
    $links_table = $wpdb->prefix . 'custom_links';
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
    error_log('found ' . count($matches) . 'links.');
    foreach ($matches as $m) {
        error_log('found ' . $m );
        $url = esc_url_raw(trim($m[1]));
        $label = wp_strip_all_tags($m[2]);
        if (empty($url)) continue;

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $scheme = $parsed['scheme'] ?? '';

        // Determine type
        if (str_starts_with($url, 'mailto:') && empty($post['lm_detect_email'])) continue;
        if (!empty($host) && $host !== $_SERVER['HTTP_HOST'] && empty($post['lm_detect_external'])) continue;
        if ((!$host || $host === $_SERVER['HTTP_HOST']) && empty($post['lm_detect_internal'])) continue;

        // Check status
        $response = wp_remote_head($url, ['timeout' => 5]);
        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400 && $code < 600) {
            $wpdb->insert($links_table, [
                'label' => $code,
                'url' => $url,
                'icon_url' => '',
                'description' => '',
                'category_slug' => 'references',
            ]);
            $skipped++;
            continue;
        }

        // Check if already exists
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $links_table WHERE url = %s", $url));
        $link_id = $existing->id ?? null;

        if (!$link_id) {
            $details = lm_fetch_page_metadata($url);
            $label = !empty($details['title']) ? $details['title'] : ($label ?: $url);
            $icon = $details['icon_url'] ?? '';
            $wpdb->insert($links_table, [
                'label' => $label,
                'url' => $url,
                'icon_url' => $icon,
                'description' => '',
                'category_slug' => 'references',
            ]);
            $link_id = $wpdb->insert_id;
        }

        if ($link_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $assoc_table WHERE post_id = %d AND link_id = %d",
                $post_id,
                $link_id
            ));
            if (!$exists) {
                $wpdb->insert($assoc_table, [
                    'post_id' => $post_id,
                    'link_id' => $link_id,
                ]);
                $added++;
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
