<?php
/**
 * ========================================
 * WordPress 分類列表 Shortcode (支援多種文章類型)
 * ========================================
 * Shortcode 使用方式：
 * [city05_categorylist] (預設: post 類型的 category，顯示頂層分類)
 * [city05_categorylist post_type="product" taxonomy="product_category" parent_id="5"]
 * [city05_categorylist post_type="portfolio" taxonomy="portfolio_category" orderby="name"]
 * [city05_categorylist parent_id="12" hide_empty="true"]
 *
 * 支援多種文章類型的階層式分類列表顯示
 * 水平排列，支援水平滾動，支援 menu_order 排序
 * ========================================
 */

// 註冊 shortcode
if (!function_exists('city05_categorylist_shortcode_handler')) {
    function city05_categorylist_shortcode_handler($atts) {
        // 解析 shortcode 參數
        $atts = shortcode_atts(array(
            'post_type' => 'post',
            'taxonomy' => '',
            'parent_id' => '',
            'auto_current_children' => false,
            'current_level' => 0,
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'include' => '',
            'exclude' => '',
        ), $atts, 'city05_categorylist');

        $post_type = sanitize_text_field($atts['post_type']);
        $taxonomy = sanitize_text_field($atts['taxonomy']);
        $manual_parent_id = $atts['parent_id'] !== '' ? intval($atts['parent_id']) : null;
        $auto_current_children = filter_var($atts['auto_current_children'], FILTER_VALIDATE_BOOLEAN);
        $current_level = intval($atts['current_level']);
        $hide_empty = filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN);
        $orderby = sanitize_text_field($atts['orderby']);
        $order = strtoupper(sanitize_text_field($atts['order']));
        $include = sanitize_text_field($atts['include']);
        $exclude = sanitize_text_field($atts['exclude']);

        // 驗證 order 參數
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'ASC';
        }

        // 如果沒有指定 taxonomy，根據 post_type 自動匹配
        if (empty($taxonomy)) {
            $taxonomy_mapping = array(
                'post' => 'category',
                'teach' => 'teachcat',
                'portfolio' => 'portfolio_category',
                'event' => 'event_category',
                'project' => 'project_category',
            );

            $taxonomy = isset($taxonomy_mapping[$post_type]) ? $taxonomy_mapping[$post_type] : 'category';
        }

        // 驗證 post_type 是否存在
        if (!post_type_exists($post_type)) {
            return '<p>指定的文章類型不存在。</p>';
        }

        // 驗證 taxonomy 是否存在
        if (!taxonomy_exists($taxonomy)) {
            return '<p>指定的分類法不存在。</p>';
        }

        // 如果指定了 manual parent_id，驗證父分類是否存在
        if ($manual_parent_id !== null && $manual_parent_id > 0) {
            $parent_term = get_term($manual_parent_id, $taxonomy);
            if (is_wp_error($parent_term) || !$parent_term) {
                return '<p>指定的父分類 ID 不存在。</p>';
            }
        }

        // 自動偵測當前分類層級邏輯
        $parent_id = 0; // 預設值
        $current_term = null;
        $is_current_tax_page = is_tax($taxonomy) || is_category() || is_tag();

        if ($is_current_tax_page) {
            $queried_object = get_queried_object();
            if ($queried_object instanceof WP_Term && $queried_object->taxonomy === $taxonomy) {
                $current_term = $queried_object;
            }
        }

        // 參數優先級：manual parent_id > auto_current_children > current_level > 預設
        if ($manual_parent_id !== null) {
            // 手動指定優先
            $parent_id = $manual_parent_id;
        } elseif ($auto_current_children && $current_term) {
            // 自動顯示當前分類的子分類
            $parent_id = $current_term->term_id;
        } elseif ($current_level > 0 && $current_term) {
            // 指定層級邏輯
            $ancestors = get_ancestors($current_term->term_id, $taxonomy);
            $current_term_level = count($ancestors) + 1; // 當前分類層級

            if ($current_level > $current_term_level) {
                // 想要顯示更深層級，但當前不夠深，顯示當前分類的子分類
                $parent_id = $current_term->term_id;
            } elseif ($current_level === $current_term_level) {
                // 顯示同層級分類，父分類是當前分類的父分類
                $parent_id = $current_term->parent;
            } elseif ($current_level < $current_term_level && $current_level > 0) {
                // 顯示更淺層級，找到對應的祖先分類
                $target_parent_index = count($ancestors) - $current_level;
                if ($target_parent_index >= 0 && isset($ancestors[$target_parent_index])) {
                    $parent_id = $ancestors[$target_parent_index];
                } else {
                    $parent_id = 0; // 頂層
                }
            }
        } else {
            // 預設顯示頂層分類
            $parent_id = 0;
        }

        ob_start();

        // 建立查詢參數
        $query_args = array(
            'taxonomy' => $taxonomy,
            'parent' => $parent_id,
            'hide_empty' => $hide_empty,
            'orderby' => $orderby,
            'order' => $order,
        );

        // 處理 include 參數
        if (!empty($include)) {
            $include_ids = array_map('intval', explode(',', $include));
            $query_args['include'] = $include_ids;
        }

        // 處理 exclude 參數
        if (!empty($exclude)) {
            $exclude_ids = array_map('intval', explode(',', $exclude));
            $query_args['exclude'] = $exclude_ids;
        }

        // 如果是 menu_order 排序，檢查是否支援
        if ($orderby === 'menu_order') {
            // 大部分 WordPress 安裝的分類表格沒有 menu_order 欄位
            // 如果沒有特殊插件支援，fallback 到 name 排序
            global $wpdb;
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->terms}");
            if (!in_array('menu_order', $columns)) {
                // 分類表格沒有 menu_order 欄位，改用 name 排序
                $query_args['orderby'] = 'name';
                echo '<script>console.warn("menu_order not supported for terms, using name instead");</script>';
            }
        }

        // Console Debug 輸出
        echo '<script>console.group("City05 Category List Debug");</script>';
        echo '<script>console.log("Post Type:", "' . esc_js($post_type) . '");</script>';
        echo '<script>console.log("Taxonomy:", "' . esc_js($taxonomy) . '");</script>';
        echo '<script>console.log("Manual Parent ID:", ' . ($manual_parent_id !== null ? $manual_parent_id : 'null') . ');</script>';
        echo '<script>console.log("Auto Current Children:", ' . ($auto_current_children ? 'true' : 'false') . ');</script>';
        echo '<script>console.log("Current Level:", ' . intval($current_level) . ');</script>';
        echo '<script>console.log("Final Parent ID:", ' . intval($parent_id) . ');</script>';
        echo '<script>console.log("Current Term:", ' . json_encode($current_term ? array('id' => $current_term->term_id, 'name' => $current_term->name, 'parent' => $current_term->parent) : null) . ');</script>';
        echo '<script>console.log("Hide Empty:", ' . ($hide_empty ? 'true' : 'false') . ');</script>';
        echo '<script>console.log("Order By:", "' . esc_js($orderby) . '");</script>';
        echo '<script>console.log("Order:", "' . esc_js($order) . '");</script>';
        echo '<script>console.log("Query Args:", ' . json_encode($query_args) . ');</script>';

        // 執行查詢
        $terms = get_terms($query_args);

        echo '<script>console.log("Terms Query Result:", ' . json_encode($terms) . ');</script>';

        if (is_wp_error($terms)) {
            echo '<script>console.error("WP Error:", "' . esc_js($terms->get_error_message()) . '");</script>';
            echo '<script>console.log("Error Details:", ' . json_encode($terms->get_error_data()) . ');</script>';
            echo '<script>console.groupEnd();</script>';
            echo '<p>查詢分類時發生錯誤：' . esc_html($terms->get_error_message()) . '</p>';
            return ob_get_clean();
        }

        if (empty($terms)) {
            echo '<script>console.warn("No terms found!");</script>';

            // 額外檢查：列出所有可用的 taxonomies
            $all_taxonomies = get_taxonomies(array('public' => true), 'names');
            echo '<script>console.log("Available Public Taxonomies:", ' . json_encode($all_taxonomies) . ');</script>';

            // 檢查指定的 taxonomy 是否有任何分類
            $all_terms_in_taxonomy = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));
            echo '<script>console.log("All terms in taxonomy ' . esc_js($taxonomy) . ':", ' . json_encode($all_terms_in_taxonomy) . ');</script>';

            // 檢查 taxonomy 是否存在
            echo '<script>console.log("Taxonomy exists:", ' . (taxonomy_exists($taxonomy) ? 'true' : 'false') . ');</script>';

            // 檢查 post type 是否存在
            echo '<script>console.log("Post type exists:", ' . (post_type_exists($post_type) ? 'true' : 'false') . ');</script>';

            // 如果指定了 parent_id，檢查父分類詳情
            if (!empty($parent_id)) {
                $parent_term = get_term($parent_id, $taxonomy);
                echo '<script>console.log("Parent term (ID: ' . $parent_id . '):", ' . json_encode($parent_term) . ');</script>';

                if (!is_wp_error($parent_term) && $parent_term) {
                    // 查看這個父分類下的所有子分類
                    $children_terms = get_terms(array(
                        'taxonomy' => $taxonomy,
                        'parent' => $parent_id,
                        'hide_empty' => false,
                    ));
                    echo '<script>console.log("Children of parent ' . $parent_id . ':", ' . json_encode($children_terms) . ');</script>';

                    // 查看所有分類的層級結構
                    $all_terms_with_parent = array();
                    foreach ($all_terms_in_taxonomy as $term) {
                        $all_terms_with_parent[] = array(
                            'term_id' => $term->term_id,
                            'name' => $term->name,
                            'parent' => $term->parent,
                        );
                    }
                    echo '<script>console.log("All terms with parent info:", ' . json_encode($all_terms_with_parent) . ');</script>';
                } else {
                    echo '<script>console.error("Parent term ID ' . $parent_id . ' does not exist!");</script>';
                }
            }

            echo '<script>console.groupEnd();</script>';
            echo '<p>沒有找到相關分類。</p>';
            return ob_get_clean();
        }

        echo '<script>console.log("Found ' . count($terms) . ' terms");</script>';
        echo '<script>console.groupEnd();</script>';

        // 取得當前頁面資訊用於 active 狀態判斷
        $active_term_id = 0;
        $active_term_ancestors = array();

        if ($is_current_tax_page && $current_term) {
            $active_term_id = $current_term->term_id;
            $active_term_ancestors = get_ancestors($active_term_id, $taxonomy);
        }

        $active_link_class = 'city05-category-link-active';

        // 輸出樣式（僅輸出一次）
        static $city05_categorylist_styles_loaded = false;
        if (!$city05_categorylist_styles_loaded) {
            echo <<<CSS
<style>
.city05-category-list-container {
    width: 100%;
    overflow: hidden;
    position: relative;
}

.city05-category-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;

    /* 隱藏捲軸 */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge */
}

.city05-category-list::-webkit-scrollbar {
    display: none; /* Chrome/Safari/Opera */
}

.city05-category-item {
    margin: 0;
    padding: 0;
    flex-shrink: 0;
}

.city05-category-link {
    /* Normal 分類樣式 */
    display: flex;
    padding: 10px 25px;
    justify-content: center;
    align-items: center;
    gap: 10px;
    border-radius: 23px;
    background: #F5F3F3;
    border: none;

    /* Normal 分類文字樣式 */
    color: #292929;
    font-feature-settings: 'case' on;
    font-family: "Noto Sans CJK TC", sans-serif;
    font-size: 16px;
    font-style: normal;
    font-weight: 400;
    line-height: 160%;
    letter-spacing: 1.6px;
    text-align: center;

    text-decoration: none;
    white-space: nowrap;
}

/* Hover 和 Active 狀態（樣式相同） */
.city05-category-link:hover,
.city05-category-link:focus,
.city05-category-link.city05-category-link-active {
    /* Active 分類樣式 */
    display: flex;
    padding: 10px 25px;
    justify-content: center;
    align-items: center;
    gap: 10px;
    border-radius: 23px;
    background: #E83743;
    border: none;

    /* Active 分類文字樣式 */
    color: #FFF;
    font-feature-settings: 'case' on;
    font-family: "Noto Sans CJK TC", sans-serif;
    font-size: 16px;
    font-style: normal;
    font-weight: 400;
    line-height: 160%;
    letter-spacing: 1.6px;
    text-align: center;
}
</style>
CSS;
            $city05_categorylist_styles_loaded = true;
        }

        echo '<div class="city05-category-list-container">';
        echo '<ul class="city05-category-list">';

        // 添加「全部」按鈕
        $all_button_url = '';
        $all_button_is_active = false;

        if ($parent_id > 0) {
            // 有指定 parent_id：連到父分類頁面
            $all_button_url = get_term_link($parent_id, $taxonomy);
            // 判斷是否 active：當前頁面是該父分類頁面
            if ($is_current_tax_page && $current_term && $current_term->term_id === $parent_id) {
                $all_button_is_active = true;
            }
        } else {
            // 沒有指定 parent_id：連到文章類型歸檔頁或首頁
            if ($post_type === 'post') {
                // post 類型特殊處理
                $page_for_posts = get_option('page_for_posts');
                if ($page_for_posts) {
                    $all_button_url = get_permalink($page_for_posts);
                } else {
                    $all_button_url = home_url('/');
                }
            } else {
                // 其他文章類型使用歸檔頁
                $all_button_url = get_post_type_archive_link($post_type);
                if (!$all_button_url) {
                    $all_button_url = home_url('/');
                }
            }

            // 判斷是否 active：不在分類頁面時
            if (!$is_current_tax_page || !$current_term) {
                $all_button_is_active = true;
            } elseif (is_post_type_archive($post_type)) {
                $all_button_is_active = true;
            }
        }

        // 輸出「全部」按鈕
        $all_button_classes = ['city05-category-link'];
        if ($all_button_is_active) {
            $all_button_classes[] = $active_link_class;
        }

        $all_escaped_classes = esc_attr(implode(' ', $all_button_classes));
        $all_li_active_class = $all_button_is_active ? ' current-category' : '';

        echo '<li class="city05-category-item' . esc_attr($all_li_active_class) . '">';
        echo '<a href="' . esc_url($all_button_url) . '" class="' . $all_escaped_classes . '">全部</a>';
        echo '</li>';

        // 輸出分類按鈕
        foreach ($terms as $term) {
            $term_id = $term->term_id;
            $term_link = get_term_link($term);
            $term_name = $term->name;

            // 判斷是否為 active 狀態
            $is_current_term_active = false;

            if ($is_current_tax_page && $current_term) {
                // 當前分類頁面
                if ($active_term_id === $term_id) {
                    $is_current_term_active = true;
                }
                // 或者當前分類是這個分類的子分類（祖先判斷）
                elseif (in_array($term_id, $active_term_ancestors)) {
                    $is_current_term_active = true;
                }
            }

            $link_classes = ['city05-category-link'];
            if ($is_current_term_active) {
                $link_classes[] = $active_link_class;
            }

            $escaped_classes = esc_attr(implode(' ', $link_classes));
            $li_active_class = $is_current_term_active ? ' current-category' : '';

            // 處理可能的 WP_Error
            if (is_wp_error($term_link)) {
                continue;
            }

            echo '<li class="city05-category-item' . esc_attr($li_active_class) . '">';
            echo '<a href="' . esc_url($term_link) . '" class="' . $escaped_classes . '">' . esc_html($term_name) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';

        return ob_get_clean();
    }
}

if (!shortcode_exists('city05_categorylist')) {
    add_shortcode('city05_categorylist', 'city05_categorylist_shortcode_handler');
}

// 前端統一掛載拖拉腳本（footer），避免在 shortcode 生命週期中再決定是否輸出
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return; // 後台不需要

    // 僅注入一次
    static $done = false;
    if ($done) return;
    $done = true;

    // 建立一個 handle，並把 JS 以 inline 方式掛上去（無外部檔案）
    wp_register_script('city05-categorylist', '', array(), '1.0', true);
    wp_enqueue_script('city05-categorylist');

    $drag_js = <<<JS
(function() {
    function initCategoryScroll() {
        const containers = document.querySelectorAll('.city05-category-list-container');
        containers.forEach(container => {
            const list = container.querySelector('.city05-category-list');
            if (!list) return;

            let isDown = false;
            let startX;
            let scrollLeft;
            let hasMoved = false;

            list.addEventListener('mousedown', (e) => {
                isDown = true;
                hasMoved = false;
                list.style.cursor = 'grabbing';
                list.style.userSelect = 'none';
                startX = e.pageX - list.getBoundingClientRect().left;
                scrollLeft = list.scrollLeft;
            });

            ['mouseleave','mouseup'].forEach(evt => {
                list.addEventListener(evt, () => {
                    isDown = false;
                    list.style.cursor = 'grab';
                    list.style.userSelect = '';
                });
            });

            list.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                hasMoved = true;
                const x = e.pageX - list.getBoundingClientRect().left;
                const walk = (x - startX) * 2;
                list.scrollLeft = scrollLeft - walk;
            });

            list.addEventListener('click', (e) => {
                if (hasMoved) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            list.style.cursor = 'grab';
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategoryScroll);
    } else {
        initCategoryScroll();
    }
})();
JS;

    wp_add_inline_script('city05-categorylist', $drag_js);
});
