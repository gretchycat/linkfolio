<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lf_delete_broken_links($post_id, $post)
{
    if (!isset($_POST['lf_delete_broken_links'])) return;

    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';

    // Get all broken links attached to this post/page
    $broken_links = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE post_id = %d AND status_code BETWEEN 400 AND 499",
        $post_id
    ));

    if (empty($broken_links)) return;

    // Cache URLs and delete from DB
    $urls = array_map(fn($link) => esc_url_raw($link->url), $broken_links);
    $placeholders = implode(',', array_fill(0, count($urls), '%s'));
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE post_id = %d AND url IN ($placeholders)",
        array_merge([$post_id], $urls)
    ));

    // Strip <a href="..."> blocks from content
    $content = $post->post_content;
    foreach ($urls as $url)
    {
        $content = preg_replace('#<a[^>]+href=["\']' . preg_quote($url, '#') . '["\'][^>]*>.*?</a>#is', '', $content);
    }

    // Update post
    wp_update_post([
        'ID' => $post_id,
        'post_content' => $content,
    ]);

    // Show notice
    $count = count($urls);
    add_action('admin_notices', function () use ($count) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>Removed %d broken link%s from this post.</p></div>',
            $count,
            $count === 1 ? '' : 's'
        );
    });
}
