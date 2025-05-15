<?php
// fetch-page.php

defined('ABSPATH') || exit;

function lf_find_icon_url($html, $base_url)
{
    // Find all rel="icon" and related tags
    if (preg_match_all('~<link\s+[^>]*rel=["\']?([^"\'> ]+)["\']?[^>]*href=["\']?([^"\'> ]+)["\']?[^>]*>~i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $rel = strtolower($m[1]);
            $href = $m[2];
            if (
                strpos($rel, 'apple-touch-icon') !== false ||
                strpos($rel, 'icon') !== false ||
                strpos($rel, 'shortcut icon') !== false ||
                strpos($rel, 'mask-icon') !== false ||
                strpos($rel, 'fluid-icon') !== false ||
                strpos($rel, 'alternate icon') !== false
            ) {
                // Make absolute if relative
                if (strpos($href, '//') === 0) {
                    // Protocol-relative URL
                    $parsed = parse_url($base_url);
                    $href = $parsed['scheme'] . ':' . $href;
                } elseif (strpos($href, 'http') !== 0) {
                    $href = rtrim($base_url, '/') . '/' . ltrim($href, '/');
                }
                return $href;
            }
        }
    }
    // Fallback to /favicon.ico
    $parts = parse_url($base_url);
    $scheme_host = $parts['scheme'] . '://' . $parts['host'];
    return $scheme_host . '/favicon.ico';
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
        return [];
    }
    $html = $response['body'];

    // Get title
    $title = '';
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $title = trim($m[1]);
    }

    // Get icon using new function
    $base_url = preg_replace('~(/[^/]+)?$~', '', $url);
    $icon_url = lf_find_icon_url($html, $base_url);

    // Optional: Check if icon exists
    $icon_response = wp_remote_head($icon_url, ['timeout' => 5]);
    if (is_wp_error($icon_response) || wp_remote_retrieve_response_code($icon_response) >= 400) {
        $icon_url = '';
    }

    return [
        'title' => $title,
        'icon_url' => $icon_url,
    ];
}
