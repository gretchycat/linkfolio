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
 * Find the best icon URL for a given page HTML + base URL.
 */
function lf_find_icon_url($html, $base_url)
{
    $candidates = [];

    if (preg_match_all('~<link\s+[^>]*rel=["\']?([^"\'> ]+)["\']?[^>]*href=["\']?([^"\'> ]+)["\']?[^>]*>~i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $rel = strtolower($m[1]);
            $href = html_entity_decode($m[2]);
            if (strpos($rel, 'icon') !== false) {
                // Convert relative to absolute
                if (strpos($href, '//') === 0) {
                    $parsed = parse_url($base_url);
                    $href = $parsed['scheme'] . ':' . $href;
                } elseif (strpos($href, 'http') !== 0) {
                    // Make relative URL absolute
                    $href = rtrim($base_url, '/') . '/' . ltrim($href, '/');
                }
                $candidates[] = $href;
            }
        }
    }

    // Always try the default favicon.ico as a fallback
    $parts = parse_url($base_url);
    $scheme_host = $parts['scheme'] . '://' . $parts['host'];
    $candidates[] = $scheme_host . '/favicon.ico';

    foreach ($candidates as $icon_url) {
        if (lf_is_image_url($icon_url)) {
            return $icon_url;
        }
    }
    return '';
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
    $icon_url = lf_find_icon_url($html, $base_url);

    return [
        'title' => $title,
        'icon_url' => $icon_url,
        'status_code' => (int)$status_code,
    ];
}
