<?php
/**
 * ========================================
 * WordPress 商品分類階層列表 Shortcode (YAO前綴版) - 響應式版本
 * - A：純陣列樹狀結構（避免 WP_Term 動態屬性）
 * - B：快取鍵綁定 wp_cache_get_last_changed('terms')
 * - UX：桌面版顯示樹狀列表，手機版(≤768px)顯示下拉選單
 * - 規格：移除 ALL 選項，寬度 100%
 * ========================================
 * Shortcode:
 * [yao_categorylist]
 * [yao_categorylist post_type="works|post"]
 * ========================================
 */

if ( ! function_exists( 'yao_categorylist_shortcode_handler' ) ) {
    function yao_categorylist_shortcode_handler( $atts ) {
        $yao_post_type_taxonomy_map = array(
            'works' => 'workscategory',
            'post'    => 'category',
        );

        $attributes = shortcode_atts(
            array(
                'post_type' => 'works',
            ),
            $atts
        );

        $yao_current_post_type = sanitize_key( $attributes['post_type'] );
        if ( ! array_key_exists( $yao_current_post_type, $yao_post_type_taxonomy_map ) ) {
            return '<p>錯誤：指定的文章類型 "' . esc_html( $yao_current_post_type ) . '" 未被設定或無效。</p>';
        }

        $yao_taxonomy_to_query = $yao_post_type_taxonomy_map[ $yao_current_post_type ];

        ob_start();

        // B. 綁定 terms 版本的快取（分類變動自動失效）
        $version   = function_exists( 'wp_cache_get_last_changed' ) ? wp_cache_get_last_changed( 'terms' ) : 'v1';
        $cache_key = 'yao_term_tree_' . $yao_taxonomy_to_query . '_' . $version;
        $yao_category_tree = get_transient( $cache_key );

        if ( false === $yao_category_tree ) {
            $yao_terms_data = get_terms(
                array(
                    'taxonomy'   => $yao_taxonomy_to_query,
                    'hide_empty' => false,
                    'orderby'    => 'menu_order',
                    'order'      => 'ASC',
                )
            );
            if ( is_wp_error( $yao_terms_data ) ) {
                echo '<p>獲取分類時發生錯誤。</p>';
                return ob_get_clean();
            }
            if ( ! is_array( $yao_terms_data ) ) {
                $yao_terms_data = array();
            }

            // A. 純陣列樹
            $yao_category_tree = yao_build_category_tree_array( $yao_terms_data );
            set_transient( $cache_key, $yao_category_tree, DAY_IN_SECONDS );
        }

        $yao_active_term_ids = yao_get_active_category_path( $yao_taxonomy_to_query );

        // 直接在輸出中包含樣式，確保CSS一定會載入
        echo yao_get_inline_categorylist_styles();

        // 桌面版列表（預設顯示）
        echo '<div class="yao-category-container yao-desktop-view" role="tree">';
        if ( ! empty( $yao_category_tree ) ) {
            yao_render_category_level_array( $yao_category_tree, 1, $yao_active_term_ids, $yao_taxonomy_to_query );
        }
        echo '</div>';

        // 手機版下拉選單（≤768px顯示）
        echo '<div class="yao-category-mobile-select">';
        echo '<select class="yao-mobile-dropdown" aria-label="選擇分類">';
        echo '<option value="">選擇分類...</option>';
        if ( ! empty( $yao_category_tree ) ) {
            yao_render_select_options( $yao_category_tree, 0, $yao_active_term_ids, $yao_taxonomy_to_query );
        }
        echo '</select>';
        echo '</div>';

        // 添加JavaScript
        echo yao_get_inline_categorylist_scripts();

        return ob_get_clean();
    }
}

// 直接輸出內聯樣式，確保CSS一定會載入
if ( ! function_exists( 'yao_get_inline_categorylist_styles' ) ) {
    function yao_get_inline_categorylist_styles() {
        return '<style type="text/css">
/* YAO CategoryList 樣式 - 響應式版本 */

/* 手機版下拉選單預設隱藏 */
.yao-category-mobile-select {
    display: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* 桌面版列表 */
.yao-category-container {
    width: 100% !important;
    font-family: "Noto Sans TC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    line-height: 1.4 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.yao-category-item {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    box-sizing: border-box !important;
    min-height: 44px !important;
    padding: 12px 0 !important;
    width: 100% !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    margin: 0 !important;
}

/* 主要標籤（第一層）樣式 */
.yao-category-container .yao-main-tab {
    border-bottom: 1px solid #F2F2F2 !important;
    margin-bottom: 8px !important;
    padding-bottom: 12px !important;
}

.yao-category-container .yao-main-tab .yao-category-link {
    color: #000 !important;
    font-weight: 600 !important;
    font-size: 16px !important;
}

.yao-category-container .yao-main-tab:hover {
    border-bottom: 2px solid #ccc !important;
}

/* 子標籤樣式 */
.yao-category-container .yao-sub-tab {
    margin: 4px 0 !important;
    padding: 8px 0 8px 20px !important;
}

.yao-category-container .yao-last-tab {
    margin: 4px 0 !important;
    padding: 8px 0 8px 40px !important;
}

.yao-category-container .yao-sub-tab .yao-category-link,
.yao-category-container .yao-last-tab .yao-category-link {
    color: rgba(153,153,153,1) !important;
    font-size: 15px !important;
    font-weight: 500 !important;
}

/* 層級縮排 - 使用屬性選擇器確保優先級 */
.yao-category-container .yao-category-item[data-yao-level="1"] {
    margin-left: 0 !important;
}
.yao-category-container .yao-category-item[data-yao-level="2"] {
    margin-left: 20px !important;
}
.yao-category-container .yao-category-item[data-yao-level="3"] {
    margin-left: 40px !important;
}
.yao-category-container .yao-category-item[data-yao-level="4"] {
    margin-left: 60px !important;
}
.yao-category-container .yao-category-item[data-yao-level="5"] {
    margin-left: 80px !important;
}

/* 子分類容器 */
.yao-category-container .yao-sub-categories {
    width: 100% !important;
    background: #FAFAFA !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* 連結樣式 */
.yao-category-container .yao-category-link {
    text-decoration: none !important;
    color: inherit !important;
    display: block !important;
    flex: 1 !important;
    width: 100% !important;
    line-height: 1.3 !important;
    font-family: "Noto Sans TC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    text-transform: capitalize !important;
    padding: 4px 0 !important;
    border-bottom: none !important;
    box-shadow: none !important;
    transition: color 0.2s ease !important;
    margin: 0 !important;
}

.yao-category-container .yao-category-link:hover,
.yao-category-container .yao-category-link:focus,
.yao-category-container .yao-category-link:active,
.yao-category-container .yao-category-link:visited {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* 啟用狀態樣式 */
.yao-category-container .yao-category-item.yao-clicked {
    padding-left: 20px !important;
    color: #00b5e2 !important;
    border-left: 3px solid #00b5e2 !important;
    background-color: rgba(0, 181, 226, 0.05) !important;
}

.yao-category-container .yao-category-item.yao-clicked .yao-category-link {
    color: #00b5e2 !important;
    font-weight: 600 !important;
}

/* 手機版下拉選單樣式 */
.yao-category-mobile-select {
    position: relative !important;
}

.yao-category-mobile-select::after {
    content: "▼" !important;
    position: absolute !important;
    right: 16px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    pointer-events: none !important;
    color: #666 !important;
    font-size: 12px !important;
}

.yao-mobile-dropdown {
    width: 100% !important;
    padding: 12px 16px !important;
    padding-right: 40px !important;
    font-size: 16px !important;
    font-family: "Noto Sans TC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    border: 2px solid #e0e0e0 !important;
    border-radius: 8px !important;
    background-color: #fff !important;
    color: #333 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
}

.yao-mobile-dropdown:focus {
    outline: none !important;
    border-color: #00b5e2 !important;
    box-shadow: 0 0 0 3px rgba(0, 181, 226, 0.1) !important;
}

.yao-mobile-dropdown:hover {
    border-color: #00b5e2 !important;
}

.yao-mobile-dropdown option {
    padding: 10px !important;
    font-size: 15px !important;
}

/* 響應式設計 - 手機版 (≤768px) */
@media (max-width: 768px) {
    /* 隱藏桌面版列表 */
    .yao-category-container.yao-desktop-view {
        display: none !important;
    }

    /* 顯示手機版下拉選單 */
    .yao-category-mobile-select {
        display: block !important;
    }
}

/* 桌面版響應式調整 */
@media (min-width: 769px) {
    .yao-category-container {
        font-size: 16px !important;
    }
}

@media (max-width: 480px) {
    .yao-mobile-dropdown {
        font-size: 15px !important;
        padding: 10px 14px !important;
    }
}
</style>';
    }
}

// 直接輸出內聯腳本
if ( ! function_exists( 'yao_get_inline_categorylist_scripts' ) ) {
    function yao_get_inline_categorylist_scripts() {
        return '<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    // 桌面版列表點擊邏輯
    var container = document.querySelector(".yao-category-container");
    if (container) {
        container.addEventListener("click", function(e) {
            var item = e.target.closest("[data-yao-term-id]");
            if (!item) return;

            var level = parseInt(item.dataset.yaoLevel || "1", 10);
            var link = item.querySelector("a.yao-category-link");
            var hasSub = !!(item.nextElementSibling && item.nextElementSibling.classList && item.nextElementSibling.classList.contains("yao-sub-categories"));
            var clickedAnchor = !!e.target.closest("a.yao-category-link");

            var wantNewTab = (e.button === 1) || e.ctrlKey || e.metaKey;
            if (wantNewTab && clickedAnchor) return;

            // 主分類點擊邏輯
            if (level === 1) {
                // 如果有子分類，先展開
                if (hasSub) {
                    var panel = item.nextElementSibling;
                    if (panel && panel.classList.contains("yao-sub-categories")) {
                        panel.style.display = "block";
                        item.setAttribute("aria-expanded", "true");
                    }
                }

                // 然後立即導航
                if (link) {
                    if (!clickedAnchor) {
                        e.preventDefault();
                        setTimeout(function() {
                            link.click();
                        }, 50);
                    }
                }
                return;
            }

            // 子分類點擊邏輯
            if (level > 1) {
                if (hasSub) {
                    if (clickedAnchor) return;

                    e.preventDefault();
                    var panel = item.nextElementSibling;
                    if (panel && panel.classList.contains("yao-sub-categories")) {
                        var isOpen = panel.style.display === "block";
                        panel.style.display = isOpen ? "none" : "block";
                        item.setAttribute("aria-expanded", isOpen ? "false" : "true");
                    }
                    return;
                } else {
                    if (!clickedAnchor && link) {
                        e.preventDefault();
                        link.click();
                    }
                    return;
                }
            }
        }, false);
    }

    // 手機版下拉選單邏輯
    var mobileSelect = document.querySelector(".yao-mobile-dropdown");
    if (mobileSelect) {
        mobileSelect.addEventListener("change", function(e) {
            var selectedUrl = e.target.value;
            if (selectedUrl) {
                window.location.href = selectedUrl;
            }
        });
    }
});
</script>';
    }
}

/** ---------- A. 純陣列建樹 ---------- */
if ( ! function_exists( 'yao_build_category_tree_array' ) ) {
    function yao_build_category_tree_array( $yao_terms ) {
        $by_id    = array();
        $children = array();

        foreach ( $yao_terms as $t ) {
            $by_id[ (int) $t->term_id ] = $t;
            $p = (int) $t->parent;
            if ( ! isset( $children[ $p ] ) ) $children[ $p ] = array();
            $children[ $p ][] = (int) $t->term_id;
        }

        $build = function ( $parent_id ) use ( &$build, $children, $by_id ) {
            $out = array();
            if ( empty( $children[ $parent_id ] ) ) return $out;
            foreach ( $children[ $parent_id ] as $cid ) {
                $out[ $cid ] = array(
                    'term'     => $by_id[ $cid ],
                    'children' => $build( $cid ),
                );
            }
            return $out;
        };

        return $build( 0 );
    }
}

if ( ! function_exists( 'yao_get_active_category_path' ) ) {
    function yao_get_active_category_path( $yao_taxonomy ) {
        $yao_active_term_ids = array();

        if ( is_singular() && ! is_admin() && ! is_front_page() && ! is_home() ) {
            $yao_current_post_id = get_the_ID();
            if ( $yao_current_post_id ) {
                $yao_post_terms = get_the_terms( $yao_current_post_id, $yao_taxonomy );
                if ( ! is_wp_error( $yao_post_terms ) && ! empty( $yao_post_terms ) ) {
                    foreach ( $yao_post_terms as $yao_term ) {
                        $yao_active_term_ids[] = (int) $yao_term->term_id;
                        $yao_ancestors         = get_ancestors( $yao_term->term_id, $yao_taxonomy );
                        $yao_active_term_ids   = array_merge( $yao_active_term_ids, array_map( 'intval', $yao_ancestors ) );
                    }
                }
            }
        } elseif ( ( is_category() && 'category' === $yao_taxonomy ) || is_tax( $yao_taxonomy ) ) {
            $q = get_queried_object();
            if ( $q instanceof WP_Term && $q->taxonomy === $yao_taxonomy ) {
                $yao_active_term_ids[] = (int) $q->term_id;
                $yao_ancestors         = get_ancestors( $q->term_id, $yao_taxonomy );
                $yao_active_term_ids   = array_merge( $yao_active_term_ids, array_map( 'intval', $yao_ancestors ) );
            }
        }

        return array_values( array_unique( $yao_active_term_ids ) );
    }
}

if ( ! function_exists( 'yao_render_category_level_array' ) ) {
    function yao_render_category_level_array( $nodes, $level, $active_ids, $taxonomy ) {
        foreach ( $nodes as $node ) {
            $term         = $node['term'];
            $has_children = ! empty( $node['children'] );
            $is_active    = in_array( (int) $term->term_id, $active_ids, true );

            // 針對主分類：檢查是否有子分類也是 active（這樣主分類也應該顯示為 active）
            $main_category_active = false;
            if ( 1 === $level ) {
                $main_category_active = $is_active;
                // 檢查子分類是否有 active，如果有，主分類也要顯示 active
                if ( $has_children && ! $main_category_active ) {
                    $main_category_active = yao_check_children_active( $node['children'], $active_ids );
                }
            }

            $classes = array( 'yao-category-item' );
            if ( 1 === $level )      $classes[] = 'yao-main-tab';
            elseif ( 2 === $level )  $classes[] = 'yao-sub-tab';
            else                      $classes[] = 'yao-last-tab';

            // active 樣式邏輯：主分類用 main_category_active，子分類用 is_active
            if ( 1 === $level && $main_category_active ) $classes[] = 'yao-clicked';
            elseif ( $level > 1 && $is_active )          $classes[] = 'yao-clicked';

            if ( $has_children )      $classes[] = 'yao-has-children';

            $link = get_term_link( $term, $taxonomy );
            if ( is_wp_error( $link ) ) continue;

            $should_show_children = $has_children && ( $is_active || yao_check_children_active( $node['children'], $active_ids ) );
            $aria_expanded = $has_children ? ( $should_show_children ? 'true' : 'false' ) : 'false';

            echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-yao-level="' . esc_attr( $level ) . '" data-yao-term-id="' . esc_attr( $term->term_id ) . '" role="treeitem" ' . ( $has_children ? 'aria-expanded="' . esc_attr( $aria_expanded ) . '"' : '' ) . '>';

            echo '<a href="' . esc_url( $link ) . '" class="yao-category-link" ' . ( $has_children ? 'aria-haspopup="true"' : '' ) . '>' . esc_html( $term->name ) . '</a>';

            echo '</div>';

            if ( $has_children ) {
                $should_show = $should_show_children ? 'block' : 'none';
                echo '<div id="yao-children-' . esc_attr( $term->term_id ) . '" class="yao-sub-categories yao-level-' . esc_attr( $level + 1 ) . '" style="display:' . esc_attr( $should_show ) . ';" role="group">';
                yao_render_category_level_array( $node['children'], $level + 1, $active_ids, $taxonomy );
                echo '</div>';
            }
        }
    }
}

/** ---------- 渲染手機版下拉選單選項 ---------- */
if ( ! function_exists( 'yao_render_select_options' ) ) {
    function yao_render_select_options( $nodes, $level, $active_ids, $taxonomy ) {
        $indent = str_repeat( '&nbsp;&nbsp;', $level );

        foreach ( $nodes as $node ) {
            $term      = $node['term'];
            $is_active = in_array( (int) $term->term_id, $active_ids, true );
            $link      = get_term_link( $term, $taxonomy );

            if ( is_wp_error( $link ) ) continue;

            $selected = $is_active ? ' selected' : '';

            echo '<option value="' . esc_url( $link ) . '"' . $selected . '>';
            echo $indent . esc_html( $term->name );
            echo '</option>';

            // 遞迴渲染子分類
            if ( ! empty( $node['children'] ) ) {
                yao_render_select_options( $node['children'], $level + 1, $active_ids, $taxonomy );
            }
        }
    }
}

/** ---------- 輔助函數：檢查子分類是否有 active ---------- */
if ( ! function_exists( 'yao_check_children_active' ) ) {
    function yao_check_children_active( $children, $active_ids ) {
        foreach ( $children as $child ) {
            if ( in_array( (int) $child['term']->term_id, $active_ids, true ) ) {
                return true;
            }
            if ( ! empty( $child['children'] ) && yao_check_children_active( $child['children'], $active_ids ) ) {
                return true;
            }
        }
        return false;
    }
}

if ( ! shortcode_exists( 'yao_categorylist' ) ) {
    add_shortcode( 'yao_categorylist', 'yao_categorylist_shortcode_handler' );
}
