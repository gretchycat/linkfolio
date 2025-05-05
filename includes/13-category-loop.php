<?php

defined('ABSPATH') || exit;

function lf_render_all_categories() {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_link_categories';

    $pinned = ['uncategorized', 'social', 'references'];
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

    usort($rows, function ($a, $b) use ($pinned) {
        $apos = array_search($a->slug, $pinned);
        $bpos = array_search($b->slug, $pinned);
        if ($apos !== false && $bpos !== false) return $apos - $bpos;
        if ($apos !== false) return -1;
        if ($bpos !== false) return 1;
        return strcasecmp($a->name, $b->name);
    });

    echo '<form method="post">';

    foreach ($rows as $row) {
        $id = (int)$row->id;

        $editing = isset($_POST["edit_$id"]) &&
                   !isset($_POST["cancel_triggered_$id"]) &&
                   !isset($_POST["saved_$id"]);

        if ($editing) {
            lf_render_category_row_editor($row);
        } else {
            lf_render_category_row_view($row);
        }
    }

    if (isset($_POST['add_new_category'])) {
        lf_render_category_row_editor((object) [
            'id' => 'new',
            'name' => '',
            'layout' => 'vertical',
            'separator' => '•',
            'show_icon' => 1,
            'show_url' => 0,
            'show_description' => 1,
        ]);
    }

    echo '<p><button class="button" name="add_new_category" value="1">+ Add Category</button></p>';
    echo '</form>';
}