<?php
// fetch-page.php

defined('ABSPATH') || exit;

function lf_fetch_page_metadata($url) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $result = [
        'title' => '',
        'icon_url' => '',
        'status_code' => null,
    ];

    if (empty($url)) {
        return $result;
    }

    $response = wp_remote_get($url, [
        'timeout' => 5,
        'redirection' => 3,
        'user-agent' => 'WordPress Linkfolio Plugin',
    ]);

    if (is_wp_error($response)) {
        return $result;
    }

    $result['status_code'] = wp_remote_retrieve_response_code($response);
    $html = wp_remote_retrieve_body($response);

    if (empty($html)) {
        return $result;
    }

    // Extract title
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        $title = trim($matches[1]);
        $result['title'] = sanitize_text_field($title);
    }

    // Extract icon
    $icon_url = null;
    if (preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
        $icon_url = $matches[1];
    } elseif (preg_match('/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
        $icon_url = $matches[1];
    }

    // Normalize icon URL
    if (!empty($icon_url)) {
        $parsed = parse_url($icon_url);
        if (empty($parsed['host'])) {
            $base = parse_url($url);
            if (isset($base['scheme'], $base['host'])) {
                $icon_url = $base['scheme'] . '://' . $base['host'] . '/' . ltrim($icon_url, '/');
            }
        }
    } else {
        $base = parse_url($url);
        if (isset($base['scheme'], $base['host'])) {
            $icon_url = $base['scheme'] . '://' . $base['host'] . '/favicon.ico';
        }
    }

    if (!empty($icon_url)) {
        $tmp = download_url($icon_url);
        if (!is_wp_error($tmp)) {
            $file_array = [
                'name'     => basename(parse_url($icon_url, PHP_URL_PATH)),
                'tmp_name' => $tmp,
            ];
            $attachment_id = media_handle_sideload($file_array, 0);
            if (!is_wp_error($attachment_id)) {
                $stored_url = wp_get_attachment_url($attachment_id);
                if ($stored_url) {
                    $result['icon_url'] = $stored_url;
                }
            } else {
                @unlink($tmp);
            }
        }
    }

    return $result;
}