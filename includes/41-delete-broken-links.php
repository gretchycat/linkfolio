<?php
// 41-deletee_broken_links.php
defined('ABSPATH') || exit;

function lf_delete_broken_links()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';

    $broken_links = $wpdb->get_results("SELECT * FROM $table WHERE status_code BETWEEN 400 AND 499");
    if (empty($broken_links)) return;

    // Prepare a cache of post content
    $post_cache = [];

    foreach ($broken_links as $bl) {
        lf_delete_broken_link($bl->url, $post_cache);
    }

    // After all deletions, flush modified posts
    foreach ($post_cache as $post_id => $modified) {
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $modified['content'],
        ]);

        // Admin notice
        if (!empty($modified['touched_by'])) {
            $titles = esc_html(get_the_title($post_id));
            $links = implode(', ', array_map('esc_html', $modified['touched_by']));

            add_action('admin_notices', function () use ($titles, $links) {
                echo "<div class='notice notice-success is-dismissible'><p>";
                printf('Removed broken link(s) [%s] from post: %s', $links, $titles);
                echo "</p></div>";
            });
        }
    }
}

function lf_delete_broken_link($url, &$post_cache = [])
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
    $links_table = $wpdb->prefix . 'custom_links';

    $url = esc_url_raw($url);
    $link_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $links_table WHERE url = %s", $url));
    if (!$link_id) return;

    $post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $assoc_table WHERE link_id = %d", $link_id));

    foreach ($post_ids as $post_id) {
        if (!isset($post_cache[$post_id])) {
            $post = get_post($post_id);
            if (!$post || empty($post->post_content)) continue;

            $post_cache[$post_id] = [
                'content' => $post->post_content,
                'touched_by' => [],
            ];
        }

        $original = $post_cache[$post_id]['content'];
        $modified = preg_replace(
            '#<a[^>]+href=["\']' . preg_quote($url, '#') . '["\'][^>]*>.*?</a>#is',
            '',
            $original
        );

        if ($modified !== $original) {
            $post_cache[$post_id]['content'] = $modified;
            $post_cache[$post_id]['touched_by'][] = $url;
        }
    }

    // Remove from DB
    $wpdb->delete($assoc_table, ['link_id' => $link_id]);
    $wpdb->delete($links_table, ['id' => $link_id]);
}

