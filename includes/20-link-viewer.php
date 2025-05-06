<?php

defined('ABSPATH') || exit;

function lf_render_link_row_view($link, $include_edit = true, $show_broken = true)
{
    $link = (array) $link;
    $label = esc_html($link['label'] ?? '');
    $url = esc_url($link['url'] ?? '');
    $description = esc_html($link['description'] ?? '');
    $icon_url = esc_url($link['icon_url'] ?? '');
    $category = esc_html($link['category_slug'] ?? '');
    $id = (int)($link['id'] ?? 0);
    $code=(int)($link['status_code'] ?? 404);
    $codestr='<span style="color: #090;">'.$code.'</span>'; 
    if (floor($code/100)==3 || floor($code/100)==5)
        $codestr='<span style=" color: #770;">'.$code.'</span>'; 
    if (floor($code/100)==4)
        $codestr='<span style="color: #900;">'.$code.'</span>'; 

    if($show_broken || $code<400 || $code>=500)
    {
        echo '<div id="link_'.$id.'" class="lf-link-viewer" style="display:flex;align-items:flex-start;margin-bottom:1em;gap:1em">';
        // icon block
        echo '<div class="lf-link-icon" style="flex-shrink:0;width:96px;height:96px;overflow:hidden;border-radius:8px;background:#222;text-align:center;line-height:96px">';
        if ($icon_url)
        {
            echo '<img src="' . $icon_url . '" style="max-width:96px;max-height:96px;object-fit:cover;vertical-align:middle" alt="icon">';
        } else
        {
            echo '<span style="color:#777;font-size:12px">no icon</span>';
        }
        echo '</div>';

        // text block
        echo '<div class="lf-link-info">';
        if ($label && $url)
        {
            echo '<strong><a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></strong>';
        } else
        {
            echo '<strong>' . $label . '</strong>';
        }
        if ($url) echo ' <code>' . $url . '</code>(' . $codestr . ')<br>';
        if ($description) echo '<div style="margin-top:0.25em">' . $description . '</div>';
        if ($category) echo '<div style="margin-top:0.25em;font-size:12px;color:#aaa">category: <strong>' . $category . '</strong></div>';

        if ($include_edit && $id)
        {
            echo '<button type="submit" name="edit_link_' . $id . '" class="button">edit</button> ';
            echo '<button type="submit" name="delete_link_' . $id . '" class="button" style="color:#d33">delete</button>';
        }

        echo '</div></div>';
    }
}

function lf_render_link_mini-row_view($link, $include_edit = true, $show_broken = true)
{
    $link = (array) $link;
    $label = esc_html($link['label'] ?? '');
    $url = esc_url($link['url'] ?? '');
    $description = esc_html($link['description'] ?? '');
    $icon_url = esc_url($link['icon_url'] ?? '');
    $category = esc_html($link['category_slug'] ?? '');
    $id = (int)($link['id'] ?? 0);
    $code=(int)($link['status_code'] ?? 404);
    $codestr='<span style="color: #090;">'.$code.'</span>'; 
    if (floor($code/100)==3 || floor($code/100)==5)
        $codestr='<span style=" color: #770;">'.$code.'</span>'; 
    if (floor($code/100)==4)
        $codestr='<span style="color: #900;">'.$code.'</span>'; 

    if($show_broken || $code<400 || $code>=500)
    {
        // icon block
        echo '<div class="lf-link-icon" style="flex-shrink:0;width:32px;height:32px;overflow:hidden;border-radius:8px;text-align:center;line-height:32px">';
        if ($icon_url)
        {
            echo '<img src="' . $icon_url . '" style="max-width:32px;max-height:32px;object-fit:cover;vertical-align:middle" alt="icon">';
        } 
        echo '</div>';
        // text block
        echo '<div class="lf-link-info">';
        if ($label && $url)
        {
            echo '<strong><a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></strong>';
        } 
        echo '</div>';
    }
}


