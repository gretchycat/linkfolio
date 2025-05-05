<?php
// db.php

defined('ABSPATH') || exit;

function lm_initialize_database() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;
    $categories_table = $prefix . 'custom_link_categories';
    $links_table = $prefix . 'custom_links';
    $assoc_table = $prefix . 'custom_link_post_map';

    $sql = <<<SQL
CREATE TABLE $categories_table (
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
) $charset_collate;

CREATE TABLE $links_table (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    label varchar(100) NOT NULL,
    url text NOT NULL,
    icon_url text DEFAULT '',
    description text DEFAULT '',
    category_slug varchar(100),
    status_code smallint(4),
    PRIMARY KEY (id)
) $charset_collate;

CREATE TABLE $assoc_table (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    link_id mediumint(9) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY post_link (post_id, link_id)
) $charset_collate;
SQL;

    dbDelta($sql);

    $columns = $wpdb->get_col("DESC $links_table", 0);
    if (!in_array('status_code', $columns)) {
        $wpdb->query("ALTER TABLE $links_table ADD status_code smallint(4) NULL");
    }

    if (!get_option('links_manager_first_run')) {
        add_option('links_manager_first_run', 'yes');
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

function lm_get_all_categories() {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_link_categories';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
}

function lm_save_category($data, $id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_link_categories';
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

function lm_delete_category($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_link_categories';
    return $wpdb->delete($table, ['id' => (int)$id]);
}

function lm_save_link($data, $id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';
    // Ensure the 'status_code' column exists
    $has_column = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'status_code'");
    if (!$has_column) {
    $wpdb->query("ALTER TABLE $table ADD COLUMN status_code SMALLINT DEFAULT NULL");
}
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';

    $manual_icon = $data['icon_url'] ?? '';
    $url = $data['url'] ?? '';
    $status_code = $data['status_code'] ?? 404;

    if ((empty($manual_icon) || empty($data['label']) || $data['status_code']>=400) && !empty($url))
    {
        $fetched = lm_fetch_page_metadata($url);
        if (empty($manual_icon) && !empty($fetched['icon_url'])) {
            $manual_icon = $fetched['icon_url'];
        }
        if (empty($data['label']) && !empty($fetched['title'])) {
            $data['label'] = $fetched['title'];
        }
        if (isset($fetched['status_code'])) {
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

    if ($id && is_numeric($id)) {
        return $wpdb->update($table, $payload, ['id' => (int)$id]);
    } else {
        return $wpdb->insert($table, $payload);
    }
}

function lm_delete_link($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_links';
    return $wpdb->delete($table, ['id' => (int)$id]);
}
