<?php
// db.php

define('LINKFOLIO_SCHEMA_VERSION', '1');

defined('ABSPATH') || exit;

/**
 * Initialize database tables for Linkfolio
 */
function lf_initialize_database()
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;
    $categories_table = $prefix . 'linkfolio_link_categories';
    $links_table = $prefix . 'linkfolio_links';
    $assoc_table = $prefix . 'linkfolio_link_post_map';

    $sql_categories = "CREATE TABLE $categories_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        layout varchar(20) DEFAULT 'vertical',
        `separator` varchar(10) DEFAULT '•',
        show_url tinyint(1) DEFAULT 0,
        show_icon tinyint(1) DEFAULT 1,
        show_description tinyint(1) DEFAULT 1,
        is_default tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
        ) $charset_collate;";

    $sql_links = "CREATE TABLE $links_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        label varchar(100) NOT NULL,
        url varchar(255) NOT NULL,
        icon_url text DEFAULT '',
        description text DEFAULT '',
        category_slug varchar(100),
        status_code smallint(4),
        PRIMARY KEY (id),
        UNIQUE KEY unique_url (url)
        ) $charset_collate;";

    $sql_assoc = "CREATE TABLE $assoc_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED NOT NULL,
        link_id mediumint(9) UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY post_link (post_id, link_id),
        INDEX link_idx (link_id)
        ) $charset_collate;";

    dbDelta($sql_categories);
    dbDelta($sql_links);
    dbDelta($sql_assoc);
    $required_slugs = ['uncategorized', 'social', 'references'];
    $missing_required = false;

    foreach ($required_slugs as $slug)
    {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $categories_table WHERE slug = %s", $slug
        ));
        if (!$exists)
        {
            $missing_required = true;
            break;
        }
    }

    if (!get_option('linkfolio_first_run') || $missing_required)
    {
        add_option('linkfolio_first_run', 'yes');
        $defaults = [
            ['Uncategorized', 'uncategorized', 0],
            ['Social', 'social', 0],
            ['References', 'references', 0],
            ['Tools', 'tools', 1],
            ['Favorites', 'favorites', 1],
            ['Community', 'community', 1],
        ];
        foreach ($defaults as [$name, $slug, $soft]) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $categories_table WHERE slug = %s", $slug));
            if (!$exists) {
                $wpdb->insert($categories_table, [
                    'name' => $name,
                    'slug' => $slug,
                    'layout' => ($slug === 'social') ? 'horizontal' : 'vertical',
                    'separator' => '•',
                    'show_url' => 1,
                    'show_icon' => 1,
                    'show_description' => 1,
                    'is_default' => $soft ? 0 : 1,
                ]);
            }
        }
    }
}

function lf_check_and_upgrade_schema()
{
    $current_version = get_option('linkfolio_schema_version');

    if ($current_version !== LINKFOLIO_SCHEMA_VERSION) {
        lf_initialize_database(); // also handles upgrades internally
        update_option('linkfolio_schema_version', LINKFOLIO_SCHEMA_VERSION);
    }
}

function lf_ensure_tables_exist()
{
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'linkfolio_links',
        $wpdb->prefix . 'linkfolio_link_post_map',
        $wpdb->prefix . 'linkfolio_link_categories'
    ];
    $need_create = false;
    foreach ($tables as $table) {
        // Use $wpdb->get_var("SHOW TABLES LIKE '$table'")
        if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
            $need_create = true;
            break;
        }
    }
    if ($need_create) {
        lf_initialize_database(); // call your schema creation routine
    }
}

function lf_prune_orphaned_link_associations()
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'linkfolio_link_post_map';
    $links_table = $wpdb->prefix . 'linkfolio_links';

    // Delete associations where link_id no longer exists
    $wpdb->query("
        DELETE a FROM $assoc_table a
        LEFT JOIN $links_table l ON a.link_id = l.id
        WHERE l.id IS NULL
    ");
}

function lf_prune_orphaned_post_associations()
{
    global $wpdb;
    $assoc_table = $wpdb->prefix . 'linkfolio_link_post_map';
    $posts_table = $wpdb->prefix . 'posts';

    // Delete associations where post_id no longer exists
    $wpdb->query("
        DELETE a FROM $assoc_table a
        LEFT JOIN $posts_table p ON a.post_id = p.ID
        WHERE p.ID IS NULL
    ");
}

register_activation_hook(__FILE__, 'lf_initialize_database');
add_action('admin_init', 'lf_check_and_upgrade_schema');

function lf_get_all_categories() {
    global $wpdb;
    $table = $wpdb->prefix . 'linkfolio_link_categories';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
}

function lf_save_category($data, $id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'linkfolio_link_categories';
    $payload = [
        'name' => sanitize_text_field($data['name'] ?? ''),
        'slug' => sanitize_title($data['name'] ?? ''),
        'layout' => in_array($data['layout'] ?? '', ['horizontal', 'vertical']) ? $data['layout'] : 'vertical',
        'separator' => $data['separator'] ?? '•',
        'show_icon' => !empty($data['show_icon']) ? 1 : 0,
        'show_url' => !empty($data['show_url']) ? 1 : 0,
        'show_description' => !empty($data['show_description']) ? 1 : 0,
    ];
    if ($id && is_numeric($id)) {
        return $wpdb->update($table, $payload, ['id' => (int)$id]);
    } else {
        $payload['is_default'] = 0;
        return $wpdb->insert($table, $payload);
    }
}

function lf_delete_category($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'linkfolio_link_categories';
    return $wpdb->delete($table, ['id' => (int)$id]);
}

function lf_save_link($data, $id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'linkfolio_links';
    // Ensure the 'status_code' column exists

    $manual_icon = $data['icon_url'] ?? '';
    $url = $data['url'] ?? '';
    $status_code = $data['status_code'] ?? 404;

    if (!empty($url))
    {
        $fetched = lf_fetch_page_metadata($url);
        if (empty($manual_icon) && !empty($fetched['icon_url']))
        {
            $manual_icon = $fetched['icon_url'];
        }
        if (empty($data['label']) && !empty($fetched['title']))
        {
            $data['label'] = $fetched['title'];
        }
        if (isset($fetched['status_code']))
        {
            $status_code = (int)$fetched['status_code'];
        }
    }

    $payload = [
        'label' => sanitize_text_field($data['label'] ?? ''),
        'url' => esc_url_raw($url),
        'icon_url' => esc_url_raw($manual_icon),
        'description' => sanitize_textarea_field($data['description'] ?? ''),
        'category_slug' => sanitize_title($data['category'] ?? ''),
        'status_code' => is_numeric($status_code) ? (int)$status_code : null,
    ];

    if ($id && is_numeric($id))
    {
        return $wpdb->update($table, $payload, ['id' => (int)$id]);
    }
    else
    {
        return $wpdb->insert($table, $payload);
    }
}

function lf_delete_link($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'linkfolio_links';
    return $wpdb->delete($table, ['id' => (int)$id]);
}
