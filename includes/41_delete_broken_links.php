<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lf_delete_broken_links($post_id, $post)
{
    if (!isset($post['lf_delete_broken_links'])]) return;

    $content = $post['post_content'];
    if (!is_string($content)) return;


    echo 'delete run';
    preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
        stripslashes($content),
        $matches,
        PREG_SET_ORDER);
    if (!$matches) return;

    $added = 0;
    $skipped = 0;
    global $wpdb;
    $links_table = $wpdb->prefix . 'custom_links';
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
}
