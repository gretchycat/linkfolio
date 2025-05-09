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

    foreach($broken_links as $bl)
    {
        $bl->label='';
        lf_save_link(get_object_vars($bl), $bl->id);
    }
}

