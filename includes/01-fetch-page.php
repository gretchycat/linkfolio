<?php
// fetch-page.php

defined('ABSPATH') || exit;

/**
 * Check if a URL returns an image Content-Type.
 */
function lf_is_image_url($url)
{
    $head = wp_remote_head($url, ['timeout' => 5]);
    if (is_wp_error($head)) return false;
    $type = wp_remote_retrieve_header($head, 'content-type');
    return (is_string($type) && strpos($type, 'image/') === 0);
}

/**
 * Finds the best icon URL from a page's HTML and base URL.
 *
 * @param string $html      The HTML source of the page.
 * @param string $page_url  The original URL fetched (used for absolute paths).
 * @return string           The first valid image URL found, or empty string.
 */
function lf_find_icon_url($html, $page_url)
{
    $candidates = [];

    // 1. Parse all <link> tags for icon candidates (robust rel check)
    if (preg_match_all('/<link\s+[^>]*rel=["\']?([^"\'> ]+)["\']?[^>]*href=["\']?([^"\'> ]+)["\']?[^>]*>/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $rel = strtolower($m[1]);
            $href = html_entity_decode($m[2]);
            if (
                strpos($rel, 'icon') !== false ||
                strpos($rel, 'apple-touch-icon') !== false ||
                strpos($rel, 'mask-icon') !== false ||
                strpos($rel, 'fluid-icon') !== false ||
                strpos($rel, 'alternate icon') !== false
            ) {
                // Protocol-relative (starts with //)
                if (strpos($href, '//') === 0) {
                    $parsed = parse_url($page_url);
                    $href = $parsed['scheme'] . ':' . $href;
                }
                // Relative (does not start with http/https)
                elseif (strpos($href, 'http') !== 0) {
                    $parts = parse_url($page_url);
                    $base = $parts['scheme'] . '://' . $parts['host'];
                    if (!empty($parts['port'])) $base .= ':' . $parts['port'];
                    $href = $base . '/' . ltrim($href, '/');
                }
                $candidates[] = $href;
            }
        }
    }

    // 2. Fallback: /favicon.ico at subdomain and root
    $parts = parse_url($page_url);
    $scheme_host = $parts['scheme'] . '://' . $parts['host'];
    $candidates[] = $scheme_host . '/favicon.ico';
    // Try root (naked) domain if this is a subdomain
    $domain_parts = explode('.', $parts['host']);
    if (count($domain_parts) > 2) {
        $root_domain = $domain_parts[count($domain_parts) - 2] . '.' . $domain_parts[count($domain_parts) - 1];
        $candidates[] = $parts['scheme'] . '://' . $root_domain . '/favicon.ico';
    }

    // 3. Return the first valid image
    foreach ($candidates as $icon_url) {
        if (lf_is_image_url($icon_url)) {
            return $icon_url;
        }
    }
    return '';
}

/**
 * Returns the icon media URL for a given icon, avoiding duplicates by domain.
 *
 * @param string $icon_url  The icon image URL.
 * @param string $page_url  The page URL (for extracting domain).
 * @return string           Attachment URL in media library, or '' on failure.
 */
function lf_sideload_icon_unique($icon_url, $page_url)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // 1. Extract domain for filename
    $parts = parse_url($page_url);
    $domain = $parts['host'] ?? 'site-icon';
    $domain = preg_replace('/^www\./', '', strtolower($domain)); // remove 'www.'

    // 2. Get the content type from HTTP headers
    $head = wp_remote_head($icon_url);
    $type = strtolower(wp_remote_retrieve_header($head, 'content-type'));
    $ext = '.ico'; // default fallback
    if (strpos($type, 'png') !== false) $ext = '.png';
    elseif (strpos($type, 'jpeg') !== false) $ext = '.jpg';
    elseif (strpos($type, 'gif') !== false) $ext = '.gif';
    elseif (strpos($type, 'svg') !== false) $ext = '.svg';
    elseif (strpos($type, 'bmp') !== false) $ext = '.bmp';
    elseif (strpos($type, 'webp') !== false) $ext = '.webp';

    $filename = $domain . $ext;

    // 3. See if file already exists in Media Library
    $existing = get_posts([
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_wp_attached_file',
                'value' => $filename,
                'compare' => 'LIKE'
            ]
        ]
    ]);
    if (!empty($existing)) {
        return wp_get_attachment_url($existing[0]->ID);
    }

    // 4. Download and sideload new icon
    $tmp = download_url($icon_url);
    if (is_wp_error($tmp)) {
        return '';
    }
    $file_array = [
        'name' => $filename,
        'tmp_name' => $tmp,
    ];
    $attach_id = media_handle_sideload($file_array, 0, 'Site Icon for ' . $domain);
    @unlink($tmp);
    if (is_wp_error($attach_id)) {
        return '';
    }
    return wp_get_attachment_url($attach_id);
}

/**
 * Fetches page metadata: title and best icon (image) URL.
 */
function lf_fetch_page_metadata($url)
{
    $response = wp_remote_get($url, [
        'timeout' => 8,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; Linkfolio/1.0; +https://hopefaithless.xyz/)',
            'Referer' => home_url(),
        ]
    ]);
    if (is_wp_error($response) || empty($response['body'])) {
        return [
            'title' => '',
            'icon_url' => '',
            'status_code' => 0,
        ];
    }

    $html = $response['body'];
    $status_code = wp_remote_retrieve_response_code($response);

    // Get title
    $title = '';
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $title = trim($m[1]);
    }

    // Compute base URL
    $parts = parse_url($url);
    $base_url = $parts['scheme'] . '://' . $parts['host'];

    // Get best icon image URL
    $icon_url = lf_sideload_icon_unique($html, $base_url);

    return [
        'title' => $title,
        'icon_url' => $icon_url,
        'status_code' => (int)$status_code,
    ];
}
