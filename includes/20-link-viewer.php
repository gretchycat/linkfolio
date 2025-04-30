<?php

defined('ABSPATH') || exit;

function lm_render_link_row_view($link, $include_edit = true) {
    $link = (array) $link;

    $label = esc_html($link['label'] ?? '');
    $url = esc_url($link['url'] ?? '');
    $description = esc_html($link['description'] ?? '');
    $icon_url = esc_url($link['icon_url'] ?? '');
    $category = esc_html($link['category_slug'] ?? '');
    $id = (int)($link['id'] ?? 0);

    echo '<div class="lm-link-viewer" style="display:flex;align-items:flex-start;margin-bottom:1em;gap:1em">';

    // Icon block
    echo '<div class="lm-link-icon" style="flex-shrink:0;width:96px;height:96px;overflow:hidden;border-radius:8px;background:#222;text-align:center;line-height:96px">';
    if ($icon_url) {
        echo '<img src="' . $icon_url . '" style="max-width:96px;max-height:96px;object-fit:cover;vertical-align:middle" alt="icon">';
    } else {
        echo '<span style="color:#777;font-size:12px">No icon</span>';
    }
    echo '</div>';

    // Text block
    echo '<div class="lm-link-info">';
    if ($label && $url) {
        echo '<strong><a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></strong>'; 
    } else {
        echo '<strong>' . $label . '</strong>';
    }
    if ($url) echo ' <code>' . $url . '</code><br>';
    if ($description) echo '<div style="margin-top:0.25em">' . $description . '</div>';
    if ($category) echo '<div style="margin-top:0.25em;font-size:12px;color:#aaa">Category: <strong>' . $category . '</strong></div>';

    if ($include_edit && $id) {
        echo '<button type="submit" name="edit_link_' . $id . '" class="button">Edit</button> ';
        echo '<button type="submit" name="delete_link_' . $id . '" class="button" style="color:#d33">Delete</button>';
    }

    echo '</div></div>';
}