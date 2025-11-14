<?php
/**
 * WordPress 分類文章計數 Shortcode
 * 顯示當前分類頁面的文章總數或頁面的所有文章總數
 * 支援: Category, Tag, Custom Taxonomy, Custom Post Type, Page
 *
 * 使用方式: [category_post_count]
 * 輸出格式: 共XX例
 */

// 防止直接存取
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 分類文章計數 Shortcode 函數
 */
function custom_category_post_count_shortcode($atts) {

    // 解析 shortcode 屬性
    $attributes = shortcode_atts(
        array(
            'post_type' => 'post', // 預設為 post，可指定其他文章類型
        ),
        $atts
    );

    $post_type = sanitize_key($attributes['post_type']);
    $post_count = 0;

    // 檢查是否為分類/標籤/自定義分類存檔頁面
    if (is_category() || is_tag() || is_tax()) {
        // 取得當前分類物件
        $term = get_queried_object();

        // 確認是否成功取得分類物件
        if (!$term || !isset($term->taxonomy) || !isset($term->term_id)) {
            return '<!-- 無法取得分類資訊 -->';
        }

        // 取得該分類法對應的文章類型
        $taxonomy = $term->taxonomy;
        $term_id = $term->term_id;

        // 取得此分類法關聯的文章類型
        $taxonomy_object = get_taxonomy($taxonomy);

        // 檢查分類法物件是否有效
        if (!$taxonomy_object || empty($taxonomy_object->object_type)) {
            return '<!-- 無法取得文章類型 -->';
        }

        $post_types = $taxonomy_object->object_type;

        // 查詢參數
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            ),
            'no_found_rows'  => false,
        );

        // 執行查詢
        $query = new WP_Query($args);
        $post_count = $query->found_posts;

        // 重置查詢
        wp_reset_postdata();
    }
    // 檢查是否為頁面 (page) 或單一文章頁面
    elseif (is_page() || is_singular()) {
        // 在頁面上使用時，計算所有已發布的指定文章類型數量
        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
        );

        // 執行查詢
        $query = new WP_Query($args);
        $post_count = $query->found_posts;

        // 重置查詢
        wp_reset_postdata();
    }
    else {
        // 非分類頁面也非單頁則不顯示
        return '';
    }

    // 組合輸出文字
    $output_text = '共' . $post_count . '例';

    // 包裝 HTML 並套用樣式
    $html = '<span class="category-post-count" style="color: #292929; font-feature-settings: \'case\' on; font-family: \'Noto Sans CJK TC\', sans-serif; font-size: 16px; font-style: normal; font-weight: 400; line-height: 160%; letter-spacing: 1.6px; display: inline-block;">' . esc_html($output_text) . '</span>';

    return $html;
}

// 註冊 Shortcode
add_shortcode('category_post_count', 'custom_category_post_count_shortcode');
