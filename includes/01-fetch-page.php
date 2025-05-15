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
function lf_find_icon_url($html, $page_url)
{
    $candidates = [];

    // 1. Parse all <link> tags for icon candidates
    if (preg_match_all('/<link\s+([^>]+)>/i', $html, $matches)) {
        foreach ($matches[1] as $attrs) {
            // Match rel attribute
            if (preg_match('/rel=["\']?([^"\'> ]+)["\']?/i', $attrs, $rel_match)) {
                $rel = strtolower($rel_match[1]);
                if (
                    strpos($rel, 'icon') !== false ||
                    strpos($rel, 'apple-touch-icon') !== false ||
                    strpos($rel, 'mask-icon') !== false ||
                    strpos($rel, 'fluid-icon') !== false ||
                    strpos($rel, 'alternate icon') !== false
                ) {
                    // Find href
                    if (preg_match('/href=["\']?([^"\'> ]+)["\']?/i', $attrs, $href_match)) {
                        $href = html_entity_decode($href_match[1]);
                        // Convert to absolute if necessary
                        if (strpos($href, '//') === 0) {
                            // Protocol-relative
                            $parsed = parse_url($page_url);
                            $href = $parsed['scheme'] . ':' . $href;
                        } elseif (strpos($href, 'http') !== 0) {
                            // Relative path
                            $parts = parse_url($page_url);
                            $base = $parts['scheme'] . '://' . $parts['host'];
                            if (!empty($parts['port'])) $base .= ':' . $parts['port'];
                            $href = $base . '/' . ltrim($href, '/');
                        }
                        $candidates[] = $href;
                    }
                }
            }
        }
    }

    // 2. Fallback: /favicon.ico at subdomain and root
    $parts = parse_url($page_url);
    $scheme_host = $parts['scheme'] . '://' . $parts['host'];
    $candidates[] = $scheme_host . '/favicon.ico';
    // Try root (naked) domain
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
