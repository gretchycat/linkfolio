<?php
// fetch-page.php

defined('ABSPATH') || exit;

/**
 * Check if a URL returns an image Content-Type.
 */
function lf_is_image_url($url)
{
    return true;
    $head = wp_remote_head($url, ['timeout' => 5]);
    if (is_wp_error($head)) return false;
    $type = wp_remote_retrieve_header($head, 'content-type');
    return (is_string($type) && strpos($type, 'image/') === 0);
}

function lf_icon_exists_in_media_library($domain, $ext = '')
{
    // Search for an attachment named $domain.$ext (or any extension if $ext is blank)
    $search = $domain . ($ext ? $ext : '');
    $args = [
        'post_type'      => 'attachment',
        'posts_per_page' => 1,
        'meta_query'     => [[
            'key'     => '_wp_attached_file',
            'value'   => $search,
            'compare' => 'LIKE',
        ]],
    ];
    $existing = get_posts($args);
    if ($existing) {
        return wp_get_attachment_url($existing[0]->ID);
    }
    return false;
}

function lf_try_standard_icon_locations($scheme_host)
{
    $candidates = [
        $scheme_host . '/favicon.ico',
        $scheme_host . '/favicon.png',
        $scheme_host . '/apple-touch-icon.png',
        $scheme_host . '/apple-touch-icon-precomposed.png'
    ];
    foreach ($candidates as $url)
    {
        if (lf_is_image_url($url)) return $url;
    }
    return false;
}

/**
 * Find all icon link URLs in HTML robustly (order, quoting, etc. don't matter).
 * Returns an array of icon URLs (absolute if possible).
 *
 * @param string $html     The HTML source (should include <head>...</head>)
 * @param string $base_url The page URL (for normalizing relative paths)
 * @return array           Array of icon URLs (strings)
 */
function lf_find_icon_links_in_html($html, $base_url)
{
    $icons = [];
    // 1. Grab all <link ...> tags
    if (!preg_match_all('/<link\s[^>]*>/i', $html, $matches)) 
    {
        return [];
    }
    foreach ($matches[0] as $tag) 
    {
        // 2. Parse each tag's attributes
        $dom = new DOMDocument();
        @$dom->loadHTML('<html><head>' . $tag . '</head></html>'); // Suppress errors for bad HTML
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) 
        {
            $rel = $link->getAttribute('rel');
            $href = $link->getAttribute('href');
            if ($rel && stripos($rel, 'icon') !== false && $href) 
            {
                // Normalize to absolute URL if necessary
                if (strpos($href, '//') === 0) 
                {
                    $parsed = parse_url($base_url);
                    $href = $parsed['scheme'] . ':' . $href;
                } 
                elseif (strpos($href, 'http') !== 0) 
                {
                    $parts = parse_url($base_url);
                    $href = $parts['scheme'] . '://' . $parts['host'] . '/' . ltrim($href, '/');
                }
                $icons[] = $href;
            }
        }
    }
    return $icons;
}

function lf_handle_discord_icon_case($html, $base_url)
{
    // Discord and some other apps use custom asset paths in their <link rel="icon">
    // (You may wish to check if the base_url domain is discord.com, then prioritize .ico in /assets/)
    $icons = lf_find_icon_links_in_html($html, $base_url);
    foreach ($icons as $icon_url) {
        if (preg_match('/discord\.com\/assets\/.+\.ico$/', $icon_url) && lf_is_image_url($icon_url)) {
            return $icon_url;
        }
    }
    return false;
}

/**
 * Download, rename, and sideload a favicon to the media library by domain.
 *
 * @param string $icon_url The icon image URL (already checked for validity).
 * @param string $domain   The domain or subdomain (for unique naming).
 * @return string          The local media URL, or '' on failure.
 */
function lf_sideload_and_store_icon($icon_url, $domain)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // HEAD request to get Content-Type (not just extension)
    $head = wp_remote_head($icon_url, ['timeout' => 8]);
    $type = strtolower(wp_remote_retrieve_header($head, 'content-type'));
    $ext = '.ico'; // fallback

    if (strpos($type, 'png') !== false) $ext = '.png';
    elseif (strpos($type, 'jpeg') !== false) $ext = '.jpg';
    elseif (strpos($type, 'gif') !== false) $ext = '.gif';
    elseif (strpos($type, 'svg') !== false) $ext = '.svg';
    elseif (strpos($type, 'bmp') !== false) $ext = '.bmp';
    elseif (strpos($type, 'webp') !== false) $ext = '.webp';

    $filename = $domain . $ext;

    // Check if this icon already exists in the Media Library
    $existing = get_posts([
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'meta_query' => [[
            'key' => '_wp_attached_file',
            'value' => $filename,
            'compare' => 'LIKE',
        ]],
    ]);
    if (!empty($existing)) {
        return wp_get_attachment_url($existing[0]->ID);
    }

    // Download the file to temp
    $tmp = download_url($icon_url);
    if (is_wp_error($tmp)) {
        return $icon_url;
    }

    $file_array = [
        'name' => $filename,
        'tmp_name' => $tmp,
    ];

    // Sideload into the Media Library (post_id = 0 for unattached)
    $attach_id = media_handle_sideload($file_array, 0, 'Site Icon for ' . $domain);

    // Cleanup temp file
    @unlink($tmp);

    if (is_wp_error($attach_id)) {
        return $icon_url;
    }

    return wp_get_attachment_url($attach_id);
}

function lf_get_root_domain($host)
{
    $parts = explode('.', $host);
    if (count($parts) > 2) {
        return $parts[count($parts)-2] . '.' . $parts[count($parts)-1];
    }
    return $host;
}

function lf_get_icon_for_url($page_url)
{
    $parts = parse_url($page_url);
    $domain = strtolower($parts['host']);
    $scheme_host = $parts['scheme'] . '://' . $domain;

    // 1. Check if file exists locally (in Media Library)
    foreach (['.png', '.ico', '.jpg', '.svg', '.gif', '.bmp', '.webp', ''] as $ext) {
        $found = lf_icon_exists_in_media_library($domain, $ext);
        if ($found) return $found;
    }

    // 4. Fetch page HTML for further checks
    $success=false;
    $response = wp_remote_get($page_url, ['timeout' => 8]);
    if (is_wp_error($response) && !empty($response['body']))
    {
        $response = wp_remote_get($schema_host, ['timeout' => 8]);
        if (!is_wp_error($response) && !empty($response['body']))
            $success=true;
    }
    else
        $success=true;
    if ($success)
    {
        $html = $response['body'];
        // 5. Check Discord-style icon paths at full domain
        $icon_url = lf_handle_discord_icon_case($html, $scheme_host);
        if ($icon_url) return lf_sideload_and_store_icon($icon_url, $domain);
        // 7. Check <link rel="icon"> in page HTML at full domain
        foreach (lf_find_icon_links_in_html($html, $scheme_host) as $url) {
            if (lf_is_image_url($url)) return lf_sideload_and_store_icon($url, $domain);
        }
    }
    // 2. Check static standard locations at full domain
    $icon_url = lf_try_standard_icon_locations($scheme_host);
    if ($icon_url) return lf_sideload_and_store_icon($icon_url, $domain);

    return ''; // fallback, or your default icon
}

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
            'status_code' => 499,
        ];
    }

    $html = $response['body'];
    $status_code = wp_remote_retrieve_response_code($response);

    // Get title
    $title = '';
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $title = trim($m[1]);
    }

    // Get best icon image URL
    $icon_url = lf_get_icon_for_url($url); // or $page_url as appropriate

    return [
        'title' => $title,
        'icon_url' => $icon_url,
        'status_code' => (int)$status_code,
    ];
}
