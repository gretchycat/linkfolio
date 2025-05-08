<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lf_delete_broken_links()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';

    // Get all broken links attached to this post/page
    $broken_links = $wpdb->get_results( "SELECT * FROM $table WHERE status_code BETWEEN 400 AND 499");
    if (empty($broken_links)) return;

    foreach($broken_links as $bl)
        lf_delete_broken_link($bl->url;)
}

function lf_delete_broken_link($url)
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'custom_link_post_map';
    $links_table = $wpdb->prefix . 'custom_links';

    $url = esc_url_raw($url);
    $link_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $links_table WHERE url = %s",
        $url
    ));

    if (!$link_id) return;

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $assoc_table WHERE link_id = %d",
        $link_id
    ));

    $updated_count = 0;
    $updated_titles = [];

    foreach ($post_ids as $post_id) 
    {
        $post = get_post($post_id);
        if (!$post || empty($post->post_content)) continue;

        $original = $post->post_content;
        $modified = preg_replace(
            '#<a[^>]+href=["\']' . preg_quote($url, '#') . '["\'][^>]*>.*?</a>#is',
            '',
            $original
        );

        if ($modified !== $original) 
        {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $modified,
            ]);
            $updated_count++;
            $updated_titles[] = get_the_title($post_id);
        }
    }

    // Clean up DB: remove associations and the link itself
    $wpdb->delete($assoc_table, ['link_id' => $link_id]);
    $wpdb->delete($links_table, ['id' => $link_id]);

    // Show notice if anything was updated
    add_action('admin_notices', function () use ($url, $updated_count, $updated_titles) {
        echo '<div class="notice notice-success is-dismissible"><p>';
        if ($updated_count > 0) 
        {
            printf(
                'Removed broken link <code>%s</code> from %d post%s: %s',
                esc_html($url),
                $updated_count,
                $updated_count === 1 ? '' : 's',
                implode(', ', array_map('esc_html', $updated_titles))
            );
        } 
        else 
        {
            printf(
                'Removed broken link <code>%s</code>, no matching <code>&lt;a href&gt;</code> blocks were found in post content.',
                esc_html($url)
            );
        }
        echo '</p></div>';
    });
}
