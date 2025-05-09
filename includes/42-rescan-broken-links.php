<?php
// 40-link-detector.php
defined('ABSPATH') || exit;

function lf_rescan_broken_links()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';

    // Get all broken links attached to this post/page
    $broken_links = $wpdb->get_results( "SELECT * FROM $table WHERE status_code BETWEEN 400 AND 499");
    if (empty($broken_links)) return;
    $links=[];
    foreach($broken_links as $bl)
    {
        $bl->label='';
        $links[]=$bl->url
        lf_save_link(get_object_vars($bl), $bl->id);
    }
    add_action('admin_notices', function () use ($titles, $links) {
        echo "<div class='notice notice-success is-dismissible'><p>";
        printf('Rescanned broken link(s) [%s]', implode(', ',$links));
        echo "</p></div>";

}

