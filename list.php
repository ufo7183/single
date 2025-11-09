/**
 * WordPress 文章+分類混合列表 Shortcode 系統
 * Shortcode: [si_postlist post_type="product|post|custom"]
 */

class SI_PostList_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
        add_action( 'wp_ajax_si_save_post_order', array( $this, 'ajax_save_post_order' ) );
        add_action( 'wp_ajax_si_save_category_data', array( $this, 'ajax_save_category_data' ) );
        add_action( 'wp_ajax_si_delete_category', array( $this, 'ajax_delete_category' ) );
        add_action( 'delete_post', array( $this, 'cleanup_post_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // 創建虛擬分類表
        register_activation_hook( __FILE__, array( $this, 'create_virtual_categories_table' ) );
    }
    
    public function init() {
        if ( ! shortcode_exists( 'si_postlist' ) ) {
            add_shortcode( 'si_postlist', array( $this, 'shortcode_handler' ) );
        }
    }
    
    public function shortcode_handler( $atts ) {
        $attributes = shortcode_atts(
            array(
                'post_type' => 'post',
            ),
            $atts
        );

        $post_type = sanitize_key( $attributes['post_type'] );
        
        $enabled_post_types = get_option( 'si_postlist_enabled_types', array() );
        if ( ! in_array( $post_type, $enabled_post_types, true ) ) {
            return '<p>此文章類型尚未啟用文章列表功能。</p>';
        }

        // 獲取所有已發布文章
        $all_posts = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC'
        ) );
        
        // 獲取虛擬分類
        $virtual_categories = $this->get_virtual_categories( $post_type );
        
        // 合併文章和虛擬分類並排序
        $all_items = $this->merge_and_sort_items( $all_posts, $virtual_categories );
        
        // 建立樹狀結構
        if ( ! empty( $all_items ) ) {
            $item_tree = $this->build_item_tree( $all_items );
        } else {
            $item_tree = array();
        }

        $active_post_ids = $this->get_active_post_path( $post_type );

        // 直接在輸出中包含樣式，確保CSS一定會載入
        $output = $this->get_inline_styles();
        $output .= '<div class="si-post-container" role="tree">';
        
        // 調試信息（開發時使用）
        if ( current_user_can( 'manage_options' ) ) {
            $output .= '<!-- 調試: 找到 ' . count( $all_posts ) . ' 篇文章，' . count( $virtual_categories ) . ' 個虛擬分類，樹狀結構有 ' . count( $item_tree ) . ' 個頂層項目 -->';
        }

        if ( empty( $item_tree ) && empty( $all_items ) ) {
            $output .= '<p class="si-no-posts">無內容</p>';
        } elseif ( empty( $item_tree ) && ! empty( $all_items ) ) {
            // 有項目但沒有樹狀結構，直接顯示列表
            $output .= $this->render_simple_item_list( $all_items, $active_post_ids );
        } else {
            $output .= $this->render_item_level( $item_tree, 1, $active_post_ids );
        }

        $output .= '</div>';
        
        // 添加JavaScript
        $output .= $this->get_inline_scripts();

        return $output;
    }
    
    // 創建虛擬分類表
    public function create_virtual_categories_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'si_virtual_categories';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_type varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            url varchar(500) DEFAULT '',
            parent_id int(11) DEFAULT 0,
            parent_post_id int(11) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_type (post_type),
            KEY parent_id (parent_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    // 獲取虛擬分類
    private function get_virtual_categories( $post_type ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'si_virtual_categories';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_type = %s ORDER BY sort_order ASC",
            $post_type
        ) );
        
        return $results ? $results : array();
    }
    
    // 合併文章和虛擬分類並排序
    private function merge_and_sort_items( $posts, $categories ) {
        $all_items = array();
        
        // 添加文章
        foreach ( $posts as $post ) {
            $sort_order = get_post_meta( $post->ID, '_si_sort_order', true );
            if ( $sort_order === '' && ! metadata_exists( 'post', $post->ID, '_si_sort_order' ) ) {
                $sort_order = 999999 + $post->ID;
            } else {
                $sort_order = (int) $sort_order;
            }
            
            $all_items[] = array(
                'type' => 'post',
                'data' => $post,
                'sort_order' => $sort_order,
                'id' => $post->ID,
                'parent_id' => (int) get_post_meta( $post->ID, '_si_parent_post', true ),
                'parent_category_id' => (int) get_post_meta( $post->ID, '_si_parent_category', true ),
            );
        }
        
        // 添加虛擬分類
        foreach ( $categories as $category ) {
            $all_items[] = array(
                'type' => 'category',
                'data' => $category,
                'sort_order' => (int) $category->sort_order,
                'id' => 'cat_' . $category->id,
                'parent_id' => (int) $category->parent_post_id,
                'parent_category_id' => (int) $category->parent_id,
            );
        }
        
        // 按排序值排序
        usort( $all_items, function( $a, $b ) {
            return $a['sort_order'] - $b['sort_order'];
        } );
        
        return $all_items;
    }
    
    // 建立項目樹狀結構
    private function build_item_tree( $items ) {
        $by_id = array();
        $children = array();

        foreach ( $items as $item ) {
            $by_id[ $item['id'] ] = $item;
            
            // 決定父級ID
            $parent_key = 0;
            if ( $item['parent_category_id'] > 0 ) {
                $parent_key = 'cat_' . $item['parent_category_id'];
            } elseif ( $item['parent_id'] > 0 ) {
                $parent_key = $item['parent_id'];
            }
            
            if ( ! isset( $children[ $parent_key ] ) ) {
                $children[ $parent_key ] = array();
            }
            $children[ $parent_key ][] = $item['id'];
        }

        return $this->build_tree_recursive( 0, $children, $by_id );
    }
    
    private function build_tree_recursive( $parent_id, $children, $by_id ) {
        $tree = array();
        if ( empty( $children[ $parent_id ] ) ) {
            return $tree;
        }
        
        foreach ( $children[ $parent_id ] as $item_id ) {
            $tree[ $item_id ] = array(
                'item'     => $by_id[ $item_id ],
                'children' => $this->build_tree_recursive( $item_id, $children, $by_id )
            );
        }
        
        return $tree;
    }
    
    // 直接輸出內聯樣式
    private function get_inline_styles() {
        return '<style type="text/css">
/* SI PostList 樣式 - 內聯版本 */
.si-post-container {
    width: 100% !important;
    font-family: "Noto Sans TC", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    line-height: 1.4 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.si-post-item {
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
.si-post-container .si-main-tab {
    border-bottom: 1px solid #F2F2F2 !important;
    margin-bottom: 8px !important;
    padding-bottom: 12px !important;
}

.si-post-container .si-main-tab .si-post-link {
    color: #000 !important;
    font-weight: 600 !important;
    font-size: 16px !important;
}

.si-post-container .si-main-tab:hover {
    border-bottom: 2px solid #ccc !important;
}

/* 子標籤樣式 */
.si-post-container .si-sub-tab {
    margin: 4px 0 !important;
    padding: 8px 0 !important;
}

.si-post-container .si-last-tab {
    margin: 4px 0 !important;
    padding: 8px 0 !important;
}

.si-post-container .si-sub-tab .si-post-link,
.si-post-container .si-last-tab .si-post-link {
    color: rgba(153,153,153,1) !important;
    font-size: 15px !important;
    font-weight: 500 !important;
}

/* 層級縮排 */
.si-post-container .si-post-item[data-si-level="1"] { 
    margin-left: 0 !important; 
}
.si-post-container .si-post-item[data-si-level="2"] { 
    margin-left: 20px !important; 
}
.si-post-container .si-post-item[data-si-level="3"] { 
    margin-left: 40px !important; 
}
.si-post-container .si-post-item[data-si-level="4"] { 
    margin-left: 60px !important; 
}
.si-post-container .si-post-item[data-si-level="5"] { 
    margin-left: 80px !important; 
}

/* 子項目容器 */
.si-post-container .si-sub-posts {
    width: 100% !important;
    background: #fff !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* 連結樣式 */
.si-post-container .si-post-link {
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

.si-post-container .si-post-link:hover,
.si-post-container .si-post-link:focus,
.si-post-container .si-post-link:active,
.si-post-container .si-post-link:visited {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* 啟用狀態樣式 */
.si-post-container .si-post-item.si-clicked {
    padding-left: 20px !important;
    color: #00b5e2 !important;
    border-left: 3px solid #00b5e2 !important;
    background-color: rgba(0, 181, 226, 0.05) !important;
}

.si-post-container .si-post-item.si-clicked .si-post-link {
    color: #00b5e2 !important;
    font-weight: 600 !important;
}

/* 分類項目樣式 */
.si-post-container .si-category-item {
    cursor: pointer !important;
}

.si-post-container .si-category-item.si-no-link {
    cursor: default !important;
}

/* 無內容訊息 */
.si-post-container .si-no-posts {
    color: #666 !important;
    font-style: italic !important;
    padding: 20px 0 !important;
    text-align: center !important;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .si-post-container {
        font-size: 14px !important;
    }
    
    .si-post-container .si-post-item[data-si-level="2"] { margin-left: 15px !important; }
    .si-post-container .si-post-item[data-si-level="3"] { margin-left: 30px !important; }
    .si-post-container .si-post-item[data-si-level="4"] { margin-left: 45px !important; }
    .si-post-container .si-post-item[data-si-level="5"] { margin-left: 60px !important; }
    
    .si-post-container .si-main-tab .si-post-link {
        font-size: 15px !important;
    }
    
    .si-post-container .si-sub-tab .si-post-link,
    .si-post-container .si-last-tab .si-post-link {
        font-size: 14px !important;
    }
}

@media (max-width: 480px) {
    .si-post-container .si-post-item[data-si-level="2"] { margin-left: 10px !important; }
    .si-post-container .si-post-item[data-si-level="3"] { margin-left: 20px !important; }
    .si-post-container .si-post-item[data-si-level="4"] { margin-left: 30px !important; }
    .si-post-container .si-post-item[data-si-level="5"] { margin-left: 40px !important; }
}
</style>';
    }
    
    // 直接輸出內聯腳本
    private function get_inline_scripts() {
        return '<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    var container = document.querySelector(".si-post-container");
    if (!container) return;

    container.addEventListener("click", function(e) {
        var item = e.target.closest("[data-si-item-id]");
        if (!item) return;

        var itemType = item.dataset.siItemType;
        var level = parseInt(item.dataset.siLevel || "1", 10);
        var link = item.querySelector("a.si-post-link");
        var hasSub = !!(item.nextElementSibling && item.nextElementSibling.classList && item.nextElementSibling.classList.contains("si-sub-posts"));
        var clickedAnchor = !!e.target.closest("a.si-post-link");

        var wantNewTab = (e.button === 1) || e.ctrlKey || e.metaKey;
        if (wantNewTab && clickedAnchor) return;

        // 處理分類點擊
        if (itemType === "category") {
            var hasLink = link && link.getAttribute("href") && link.getAttribute("href") !== "#";
            
            if (hasSub) {
                e.preventDefault();
                var panel = item.nextElementSibling;
                if (panel && panel.classList.contains("si-sub-posts")) {
                    var isOpen = panel.style.display === "block";
                    panel.style.display = isOpen ? "none" : "block";
                    item.setAttribute("aria-expanded", isOpen ? "false" : "true");
                }
                
                // 如果有連結且點擊的是連結，則導航
                if (hasLink && clickedAnchor && !wantNewTab) {
                    setTimeout(function() {
                        window.location.href = link.getAttribute("href");
                    }, 100);
                }
            } else if (hasLink && clickedAnchor) {
                // 沒有子項目但有連結，直接導航
                return;
            } else {
                e.preventDefault();
            }
            return;
        }

        // 處理文章點擊
        if (hasSub && !clickedAnchor) {
            e.preventDefault();
            var panel = item.nextElementSibling;
            if (panel && panel.classList.contains("si-sub-posts")) {
                var isOpen = panel.style.display === "block";
                panel.style.display = isOpen ? "none" : "block";
                item.setAttribute("aria-expanded", isOpen ? "false" : "true");
            }
            return;
        }

        if (!clickedAnchor && link) {
            e.preventDefault();
            link.click();
        }
    });
});
</script>';
    }
    
    private function render_simple_item_list( $items, $active_ids ) {
        $output = '';
        
        foreach ( $items as $item ) {
            $is_active = false;
            $item_id = '';
            $link_text = '';
            $link_url = '';
            
            if ( $item['type'] === 'post' ) {
                $post = $item['data'];
                $is_active = in_array( $post->ID, $active_ids, true );
                $item_id = $post->ID;
                $link_text = $post->post_title;
                $link_url = get_permalink( $post->ID );
            } else {
                $category = $item['data'];
                $item_id = 'cat_' . $category->id;
                $link_text = $category->name;
                $link_url = $category->url ? $category->url : '#';
            }
            
            $classes = array( 'si-post-item', 'si-main-tab' );
            
            if ( $item['type'] === 'category' ) {
                $classes[] = 'si-category-item';
                if ( ! $category->url ) {
                    $classes[] = 'si-no-link';
                }
            }
            
            if ( $is_active ) {
                $classes[] = 'si-clicked';
            }

            $output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-si-level="1" data-si-item-id="' . esc_attr( $item_id ) . '" data-si-item-type="' . esc_attr( $item['type'] ) . '" role="treeitem">';
            $output .= '<a href="' . esc_url( $link_url ) . '" class="si-post-link">' . esc_html( $link_text ) . '</a>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    private function get_active_post_path( $post_type ) {
        $active_ids = array();
        
        if ( is_singular( $post_type ) ) {
            $current_post_id = get_the_ID();
            if ( $current_post_id ) {
                $active_ids[] = $current_post_id;
                
                $parent_id = (int) get_post_meta( $current_post_id, '_si_parent_post', true );
                while ( $parent_id > 0 ) {
                    $active_ids[] = $parent_id;
                    $parent_id = (int) get_post_meta( $parent_id, '_si_parent_post', true );
                }
            }
        }
        
        return array_unique( $active_ids );
    }
    
    private function render_item_level( $nodes, $level, $active_ids ) {
        $output = '';
        
        foreach ( $nodes as $node ) {
            $item = $node['item'];
            $has_children = ! empty( $node['children'] );
            
            $is_active = false;
            $item_id = '';
            $link_text = '';
            $link_url = '';
            $item_type = $item['type'];
            
            if ( $item_type === 'post' ) {
                $post = $item['data'];
                $is_active = in_array( $post->ID, $active_ids, true );
                $item_id = $post->ID;
                $link_text = $post->post_title;
                $link_url = get_permalink( $post->ID );
            } else {
                $category = $item['data'];
                $item_id = 'cat_' . $category->id;
                $link_text = $category->name;
                $link_url = $category->url ? $category->url : '#';
            }
            
            $has_active_children = $this->check_children_active( $node['children'], $active_ids );
            
            $classes = array( 'si-post-item' );
            
            if ( 1 === $level ) {
                $classes[] = 'si-main-tab';
            } elseif ( 2 === $level ) {
                $classes[] = 'si-sub-tab';
            } else {
                $classes[] = 'si-last-tab';
            }
            
            if ( $item_type === 'category' ) {
                $classes[] = 'si-category-item';
                if ( $item_type === 'category' && ! $item['data']->url ) {
                    $classes[] = 'si-no-link';
                }
            }
            
            if ( $is_active ) {
                $classes[] = 'si-clicked';
            }
            
            if ( $has_children ) {
                $classes[] = 'si-has-children';
            }

            // 分類預設收合，文章根據是否有啟用的子項目決定
            $should_show_children = false;
            if ( $item_type === 'post' ) {
                $should_show_children = $has_children && ( $is_active || $has_active_children );
            } else {
                // 分類：如果有啟用的子項目則展開
                $should_show_children = $has_children && $has_active_children;
            }
            
            $aria_expanded = $has_children ? ( $should_show_children ? 'true' : 'false' ) : 'false';

            $output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-si-level="' . esc_attr( $level ) . '" data-si-item-id="' . esc_attr( $item_id ) . '" data-si-item-type="' . esc_attr( $item_type ) . '" role="treeitem" ' . ( $has_children ? 'aria-expanded="' . esc_attr( $aria_expanded ) . '"' : '' ) . '>';

            $output .= '<a href="' . esc_url( $link_url ) . '" class="si-post-link">' . esc_html( $link_text ) . '</a>';

            $output .= '</div>';

            if ( $has_children ) {
                $show_style = $should_show_children ? 'block' : 'none';
                $output .= '<div id="si-children-' . esc_attr( $item_id ) . '" class="si-sub-posts" style="display:' . esc_attr( $show_style ) . ';" role="group">';
                $output .= $this->render_item_level( $node['children'], $level + 1, $active_ids );
                $output .= '</div>';
            }
        }
        
        return $output;
    }
    
    private function check_children_active( $children, $active_ids ) {
        foreach ( $children as $child ) {
            $child_item = $child['item'];
            if ( $child_item['type'] === 'post' && in_array( $child_item['data']->ID, $active_ids, true ) ) {
                return true;
            }
            if ( ! empty( $child['children'] ) && $this->check_children_active( $child['children'], $active_ids ) ) {
                return true;
            }
        }
        return false;
    }
    
    public function add_admin_menus() {
        add_options_page(
            '文章列表設定',
            '文章列表設定',
            'manage_options',
            'si-postlist-settings',
            array( $this, 'render_settings_page' )
        );
        
        $enabled_types = get_option( 'si_postlist_enabled_types', array() );
        foreach ( $enabled_types as $post_type ) {
            $post_type_obj = get_post_type_object( $post_type );
            if ( $post_type_obj && current_user_can( 'edit_posts' ) ) {
                add_submenu_page(
                    'edit.php' . ( $post_type !== 'post' ? '?post_type=' . $post_type : '' ),
                    '列表排序',
                    '列表排序',
                    'edit_posts',
                    'si-postlist-order-' . $post_type,
                    array( $this, 'render_order_page' )
                );
            }
        }
    }
    
    public function render_settings_page() {
        $message = '';
        if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'si_postlist_settings' ) ) {
            $enabled_types = isset( $_POST['enabled_post_types'] ) ? array_map( 'sanitize_key', $_POST['enabled_post_types'] ) : array();
            update_option( 'si_postlist_enabled_types', $enabled_types );
            $message = '<div class="notice notice-success"><p>設定已儲存。</p></div>';
        }
        
        $current_enabled = get_option( 'si_postlist_enabled_types', array() );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        ?>
        <div class="wrap">
            <h1>文章列表設定</h1>
            <?php echo wp_kses_post( $message ); ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'si_postlist_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用的文章類型</th>
                        <td>
                            <?php foreach ( $post_types as $post_type ) : ?>
                                <label>
                                    <input type="checkbox" name="enabled_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $current_enabled, true ) ); ?>>
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_order_page() {
        $post_type = $this->get_current_post_type_from_page();
        if ( ! $post_type ) {
            wp_die( '無法識別文章類型。' );
        }
        
        // 獲取所有已發布文章
        $all_posts = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC'
        ) );
        
        // 獲取虛擬分類
        $virtual_categories = $this->get_virtual_categories( $post_type );
        
        // 合併並排序
        $all_items = $this->merge_and_sort_items( $all_posts, $virtual_categories );
        
        $total_posts = wp_count_posts( $post_type );
        
        ?>
        <div class="wrap">
            <h1>列表排序 - <?php echo esc_html( get_post_type_object( $post_type )->label ); ?></h1>
            
            <div class="notice notice-info">
                <p><strong>調試信息：</strong></p>
                <p>文章類型：<?php echo esc_html( $post_type ); ?></p>
                <p>已發布文章總數：<?php echo esc_html( $total_posts->publish ?? 0 ); ?></p>
                <p>虛擬分類數：<?php echo count( $virtual_categories ); ?></p>
                <p>查詢到的項目數：<?php echo count( $all_items ); ?></p>
            </div>
            
            <!-- 虛擬分類管理 -->
            <div class="si-category-management">
                <h2>虛擬分類管理</h2>
                <div class="si-add-category">
                    <input type="text" id="si-new-category-name" placeholder="分類名稱" />
                    <input type="url" id="si-new-category-url" placeholder="連結 URL（可選）" />
                    <button id="si-add-category" class="button">新增分類</button>
                </div>
                <div id="si-category-list">
                    <?php echo wp_kses_post( $this->render_category_list( $virtual_categories ) ); ?>
                </div>
            </div>
            
            <?php if ( empty( $all_items ) ) : ?>
                <div class="notice notice-warning">
                    <p>目前沒有已發布的 <?php echo esc_html( get_post_type_object( $post_type )->label ); ?>。</p>
                    <p>請先建立一些文章，然後回到這裡進行排序設定。</p>
                </div>
            <?php else : ?>
                <div class="notice notice-info">
                    <p><strong>使用說明：</strong></p>
                    <ul>
                        <li>拖拉項目來調整順序</li>
                        <li>點擊項目後，按 <kbd>→</kbd> 增加層級</li>
                        <li>點擊項目後，按 <kbd>←</kbd> 減少層級</li>
                        <li>調整完成後點擊「儲存排序」</li>
                        <li>虛擬分類和文章可以混合排序</li>
                    </ul>
                </div>
                <div id="si-order-container">
                    <div id="si-sortable-posts" class="si-sortable-container">
                        <?php echo wp_kses_post( $this->render_sortable_items( $all_items ) ); ?>
                    </div>
                    <button id="si-save-order" class="button button-primary">儲存排序</button>
                    <span id="si-save-status"></span>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .si-category-management {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .si-add-category {
            margin-bottom: 20px;
        }
        .si-add-category input {
            margin-right: 10px;
            width: 200px;
        }
        .si-category-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .si-category-actions button {
            margin-left: 5px;
        }
        .si-sortable-container {
            max-width: 800px;
            margin: 20px 0;
        }
        .si-post-item, .si-category-item-sortable {
            background: #fff;
            border: 1px solid #ddd;
            margin: 5px 0;
            padding: 15px;
            cursor: move;
            position: relative;
            transition: all 0.2s ease;
        }
        .si-post-item:hover, .si-category-item-sortable:hover {
            background: #f8f9fa;
        }
        .si-post-item.selected, .si-category-item-sortable.selected {
            background: #e3f2fd !important;
            border-color: #2196F3 !important;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.3);
        }
        .si-post-item:focus, .si-category-item-sortable:focus {
            outline: none;
        }
        .si-post-item.si-level-1, .si-category-item-sortable.si-level-1 { margin-left: 0; border-left: 3px solid #ccc; }
        .si-post-item.si-level-2, .si-category-item-sortable.si-level-2 { margin-left: 20px; border-left: 3px solid #2196F3; }
        .si-post-item.si-level-3, .si-category-item-sortable.si-level-3 { margin-left: 40px; border-left: 3px solid #4CAF50; }
        .si-post-item.si-level-4, .si-category-item-sortable.si-level-4 { margin-left: 60px; border-left: 3px solid #FF9800; }
        .si-post-item.si-level-5, .si-category-item-sortable.si-level-5 { margin-left: 80px; border-left: 3px solid #f44336; }
        .si-post-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .si-post-controls {
            font-size: 12px;
            color: #666;
        }
        .level-indicator {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .item-type-badge {
            background: #666;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-right: 5px;
        }
        .item-type-badge.category {
            background: #4CAF50;
        }
        .ui-sortable-helper {
            background: #f0f8ff !important;
            border-color: #0073aa !important;
        }
        .ui-sortable-placeholder {
            background: #f9f9f9;
            border: 2px dashed #ddd;
        }
        #si-save-status {
            margin-left: 10px;
            font-weight: bold;
        }
        kbd {
            background: #f1f1f1;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 2px 4px;
            font-size: 11px;
        }
        </style>
        <?php
    }
    
    private function render_category_list( $categories ) {
        $output = '';
        
        foreach ( $categories as $category ) {
            $output .= '<div class="si-category-item" data-category-id="' . esc_attr( $category->id ) . '">';
            $output .= '<div>';
            $output .= '<strong>' . esc_html( $category->name ) . '</strong>';
            if ( $category->url ) {
                $output .= ' - <a href="' . esc_url( $category->url ) . '" target="_blank">' . esc_html( $category->url ) . '</a>';
            }
            $output .= '</div>';
            $output .= '<div class="si-category-actions">';
            $output .= '<button class="button si-edit-category" data-category-id="' . esc_attr( $category->id ) . '">編輯</button>';
            $output .= '<button class="button si-delete-category" data-category-id="' . esc_attr( $category->id ) . '">刪除</button>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    private function get_current_post_type_from_page() {
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'si-postlist-order-' ) === 0 ) {
            return substr( $_GET['page'], strlen( 'si-postlist-order-' ) );
        }
        return false;
    }
    
    private function render_sortable_items( $items ) {
        $output = '';
        $order = 0;
        
        foreach ( $items as $item ) {
            $item_type = $item['type'];
            $level = 1;
            $item_id = $item['id'];
            $sort_order = $item['sort_order'];
            
            if ( $item_type === 'post' ) {
                $post = $item['data'];
                $parent_id = $item['parent_id'];
                $parent_category_id = $item['parent_category_id'];
                
                $level = $this->calculate_item_level( $post->ID, 'post' );
                
                $output .= '<div class="si-post-item si-level-' . esc_attr( $level ) . '" data-item-id="' . esc_attr( $post->ID ) . '" data-item-type="post" data-parent-id="' . esc_attr( $parent_id ) . '" data-parent-category-id="' . esc_attr( $parent_category_id ) . '" tabindex="0">';
                $output .= '<div class="si-post-title">';
                $output .= '<span class="item-type-badge">文章</span>';
                $output .= esc_html( $post->post_title );
                $output .= '</div>';
                $output .= '<div class="si-post-controls">';
                $output .= 'ID: ' . esc_html( $post->ID );
                $output .= ' | 發布日期: ' . esc_html( get_the_date( 'Y-m-d', $post->ID ) );
                $output .= ' | 排序值: ' . esc_html( $sort_order );
                $output .= ' | 層級: <span class="level-indicator">' . esc_html( $level ) . '</span>';
                $output .= '</div>';
                $output .= '</div>';
            } else {
                $category = $item['data'];
                $parent_id = $item['parent_id'];
                $parent_category_id = $item['parent_category_id'];
                
                $level = $this->calculate_item_level( $category->id, 'category' );
                
                $output .= '<div class="si-category-item-sortable si-level-' . esc_attr( $level ) . '" data-item-id="cat_' . esc_attr( $category->id ) . '" data-item-type="category" data-parent-id="' . esc_attr( $parent_id ) . '" data-parent-category-id="' . esc_attr( $parent_category_id ) . '" tabindex="0">';
                $output .= '<div class="si-post-title">';
                $output .= '<span class="item-type-badge category">分類</span>';
                $output .= esc_html( $category->name );
                if ( $category->url ) {
                    $output .= ' <small>(' . esc_html( $category->url ) . ')</small>';
                }
                $output .= '</div>';
                $output .= '<div class="si-post-controls">';
                $output .= 'ID: cat_' . esc_html( $category->id );
                $output .= ' | 排序值: ' . esc_html( $sort_order );
                $output .= ' | 層級: <span class="level-indicator">' . esc_html( $level ) . '</span>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            $order++;
        }
        
        return $output;
    }
    
    private function calculate_item_level( $item_id, $type ) {
        $level = 1;
        
        if ( $type === 'post' ) {
            $parent_category_id = (int) get_post_meta( $item_id, '_si_parent_category', true );
            $parent_post_id = (int) get_post_meta( $item_id, '_si_parent_post', true );
            
            if ( $parent_category_id > 0 ) {
                $level += $this->calculate_item_level( $parent_category_id, 'category' );
            } elseif ( $parent_post_id > 0 ) {
                $level += $this->calculate_item_level( $parent_post_id, 'post' );
            }
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . 'si_virtual_categories';
            
            $category = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $item_id
            ) );
            
            if ( $category ) {
                if ( $category->parent_id > 0 ) {
                    $level += $this->calculate_item_level( $category->parent_id, 'category' );
                } elseif ( $category->parent_post_id > 0 ) {
                    $level += $this->calculate_item_level( $category->parent_post_id, 'post' );
                }
            }
        }
        
        return min( $level, 5 ); // 最大5層
    }
    
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'si-postlist-order-' ) !== false ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_add_inline_script( 'jquery-ui-sortable', $this->get_admin_js() );
        }
    }
    
    private function get_admin_js() {
        $nonce = wp_create_nonce( 'si_postlist_ajax' );
        $post_type = $this->get_current_post_type_from_page();
        
        return '
        jQuery(document).ready(function($) {
            console.log("初始化分層排序系統");
            
            // 初始化排序
            $("#si-sortable-posts").sortable({
                placeholder: "ui-sortable-placeholder",
                helper: "clone",
                opacity: 0.8,
                tolerance: "pointer"
            });
            
            // 項目選擇
            $(document).on("click", ".si-post-item, .si-category-item-sortable", function(e) {
                e.preventDefault();
                $(".si-post-item, .si-category-item-sortable").removeClass("selected");
                $(this).addClass("selected").focus();
                console.log("選中項目:", $(this).find(".si-post-title").text());
            });
            
            // 鍵盤控制
            $(document).on("keydown", function(e) {
                var $selected = $(".si-post-item.selected, .si-category-item-sortable.selected");
                if ($selected.length === 0) return;
                
                if (e.which === 39) {
                    e.preventDefault();
                    increaseLevel($selected);
                }
                else if (e.which === 37) {
                    e.preventDefault();
                    decreaseLevel($selected);
                }
            });
            
            // 新增分類
            $("#si-add-category").click(function() {
                var name = $("#si-new-category-name").val().trim();
                var url = $("#si-new-category-url").val().trim();
                
                if (!name) {
                    alert("請輸入分類名稱");
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "si_save_category_data",
                        post_type: "' . $post_type . '",
                        name: name,
                        url: url,
                        nonce: "' . $nonce . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("新增失敗: " + response.data);
                        }
                    }
                });
            });
            
            // 刪除分類
            $(document).on("click", ".si-delete-category", function() {
                if (!confirm("確定要刪除此分類嗎？")) return;
                
                var categoryId = $(this).data("category-id");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "si_delete_category",
                        category_id: categoryId,
                        nonce: "' . $nonce . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("刪除失敗: " + response.data);
                        }
                    }
                });
            });
            
            // 編輯分類
            $(document).on("click", ".si-edit-category", function() {
                var categoryId = $(this).data("category-id");
                var $row = $(this).closest(".si-category-item");
                var currentName = $row.find("strong").text().trim();
                var currentUrl = $row.find("a").attr("href") || "";

                var newName = window.prompt("分類名稱", currentName);
                if (newName === null) return; // 取消

                var newUrl = window.prompt("連結 URL（可留空）", currentUrl);
                if (newUrl === null) return; // 取消

                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "si_save_category_data",
                        post_type: "' . esc_js( $post_type ) . '",
                        name: newName.trim(),
                        url: newUrl.trim(),
                        category_id: categoryId,
                        nonce: "' . esc_js( $nonce ) . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("更新失敗: " + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("連線錯誤: " + (xhr.responseText || status || error));
                    }
                });
            });
            
            function increaseLevel($item) {
                var currentLevel = getCurrentLevel($item);
                console.log("當前層級:", currentLevel);
                
                if (currentLevel < 5) {
                    var newLevel = currentLevel + 1;
                    setLevel($item, newLevel);
                    updateParentChild();
                    console.log("增加層級到:", newLevel);
                } else {
                    console.log("已達到最大層級");
                }
            }
            
            function decreaseLevel($item) {
                var currentLevel = getCurrentLevel($item);
                console.log("當前層級:", currentLevel);
                
                if (currentLevel > 1) {
                    var newLevel = currentLevel - 1;
                    setLevel($item, newLevel);
                    updateParentChild();
                    console.log("減少層級到:", newLevel);
                } else {
                    console.log("已是頂層級");
                }
            }
            
            function getCurrentLevel($item) {
                var classes = $item.attr("class").split(/\s+/);
                for (var i = 0; i < classes.length; i++) {
                    if (classes[i].match(/^si-level-(\d+)$/)) {
                        var level = parseInt(classes[i].replace("si-level-", ""));
                        console.log("找到層級 class:", classes[i], "層級:", level);
                        return level;
                    }
                }
                console.log("未找到層級 class，預設為 1");
                return 1;
            }
            
            function setLevel($item, level) {
                console.log("設定層級:", level, "項目:", $item.find(".si-post-title").text());
                
                $item.removeClass(function(index, className) {
                    return (className.match(/(^|\s)si-level-\S+/g) || []).join(" ");
                });
                
                $item.addClass("si-level-" + level);
                $item.find(".level-indicator").text(level);
                
                console.log("設定完成，當前 class:", $item.attr("class"));
            }
            
            function updateParentChild() {
                var items = $("#si-sortable-posts .si-post-item, #si-sortable-posts .si-category-item-sortable");
                console.log("更新父子關係，總項目數:", items.length);
                
                items.each(function(index) {
                    var $item = $(this);
                    var level = getCurrentLevel($item);
                    var parentId = 0;
                    var parentCategoryId = 0;
                    
                    if (level > 1) {
                        for (var i = index - 1; i >= 0; i--) {
                            var $prevItem = items.eq(i);
                            var prevLevel = getCurrentLevel($prevItem);
                            var prevType = $prevItem.data("item-type");
                            
                            if (prevLevel === level - 1) {
                                if (prevType === "category") {
                                    parentCategoryId = $prevItem.data("item-id").toString().replace("cat_", "");
                                } else {
                                    parentId = $prevItem.data("item-id");
                                }
                                break;
                            }
                            if (prevLevel < level - 1) {
                                break;
                            }
                        }
                    }
                    
                    $item.attr("data-parent-id", parentId);
                    $item.attr("data-parent-category-id", parentCategoryId);
                });
            }
            
            // 儲存排序
            $("#si-save-order").click(function() {
                console.log("開始儲存排序");
                updateParentChild();
                
                var orderData = [];
                
                $("#si-sortable-posts .si-post-item, #si-sortable-posts .si-category-item-sortable").each(function(index) {
                    var $item = $(this);
                    var itemId = $item.data("item-id");
                    var itemType = $item.data("item-type");
                    var parentId = parseInt($item.attr("data-parent-id")) || 0;
                    var parentCategoryId = parseInt($item.attr("data-parent-category-id")) || 0;
                    var level = getCurrentLevel($item);
                    
                    var data = {
                        id: itemId,
                        type: itemType,
                        parent_id: parentId,
                        parent_category_id: parentCategoryId,
                        order: index * 10
                    };
                    
                    orderData.push(data);
                    console.log("準備儲存:", data, "層級:", level);
                });
                
                $("#si-save-status").text("儲存中...").css("color", "#666");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "si_save_post_order",
                        order_data: JSON.stringify(orderData),
                        nonce: "' . $nonce . '"
                    },
                    success: function(response) {
                        console.log("AJAX成功回應:", response);
                        if (response.success) {
                            $("#si-save-status").text("✓ 已儲存").css("color", "green");
                        } else {
                            $("#si-save-status").text("✗ 儲存失敗: " + response.data).css("color", "red");
                        }
                        setTimeout(function() {
                            $("#si-save-status").text("");
                        }, 3000);
                    },
                    error: function(xhr, status, error) {
                        console.log("AJAX錯誤:", xhr.responseText, status, error);
                        $("#si-save-status").text("✗ 連線錯誤").css("color", "red");
                        setTimeout(function() {
                            $("#si-save-status").text("");
                        }, 3000);
                    }
                });
            });
            
            $(".si-post-item, .si-category-item-sortable").attr("tabindex", "0");
            console.log("初始化完成，找到", $(".si-post-item, .si-category-item-sortable").length, "個項目");
        });';
    }
    
    public function ajax_save_post_order() {
        if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( $_POST['nonce'], 'si_postlist_ajax' ) ) {
            wp_die( 'Permission denied' );
        }
        
        $order_data = json_decode( stripslashes( $_POST['order_data'] ), true );
        
        if ( ! is_array( $order_data ) ) {
            wp_send_json_error( '資料格式錯誤' );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'si_virtual_categories';
        
        foreach ( $order_data as $item ) {
            $item_id = $item['id'];
            $item_type = $item['type'];
            $parent_id = (int) $item['parent_id'];
            $parent_category_id = (int) $item['parent_category_id'];
            $sort_order = (int) $item['order'];
            
            if ( $item_type === 'post' ) {
                $post_id = (int) $item_id;
                update_post_meta( $post_id, '_si_parent_post', $parent_id );
                update_post_meta( $post_id, '_si_parent_category', $parent_category_id );
                update_post_meta( $post_id, '_si_sort_order', $sort_order );
            } else {
                $category_id = (int) str_replace( 'cat_', '', $item_id );
                $wpdb->update(
                    $table_name,
                    array(
                        'parent_post_id' => $parent_id,
                        'parent_id' => $parent_category_id,
                        'sort_order' => $sort_order
                    ),
                    array( 'id' => $category_id ),
                    array( '%d', '%d', '%d' ),
                    array( '%d' )
                );
            }
        }
        
        $this->clear_related_cache();
        
        wp_send_json_success( '排序已儲存' );
    }
    
    public function ajax_save_category_data() {
        if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( $_POST['nonce'], 'si_postlist_ajax' ) ) {
            wp_die( 'Permission denied' );
        }
        
        $post_type = sanitize_key( $_POST['post_type'] );
        $name = sanitize_text_field( $_POST['name'] );
        $url = esc_url_raw( $_POST['url'] );
        $category_id = isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : 0;
        
        if ( empty( $name ) ) {
            wp_send_json_error( '分類名稱不能為空' );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'si_virtual_categories';
        
        // 保底：若表不存在就立即建立
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like( $table_name )
        ) );

        if ( $table_exists !== $table_name ) {
            // 需要載入 dbDelta
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $this->create_virtual_categories_table();
            // 再次確認，若仍不存在就回報錯誤
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->esc_like( $table_name )
            ) );
            if ( $table_exists !== $table_name ) {
                wp_send_json_error( '資料表未建立（si_virtual_categories），請重新啟用外掛或檢查資料庫權限' );
            }
        }
        
        if ( $category_id > 0 ) {
            // 更新
            $result = $wpdb->update(
                $table_name,
                array(
                    'name' => $name,
                    'url' => $url
                ),
                array( 'id' => $category_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // 新增
            $result = $wpdb->insert(
                $table_name,
                array(
                    'post_type' => $post_type,
                    'name' => $name,
                    'url' => $url,
                    'sort_order' => time() // 使用時間戳作為初始排序
                ),
                array( '%s', '%s', '%s', '%d' )
            );
        }
        
        if ( $result === false ) {
            wp_send_json_error( '資料庫操作失敗' );
        }
        
        $this->clear_related_cache();
        
        wp_send_json_success( $category_id > 0 ? '分類已更新' : '分類已新增' );
    }
    
    public function ajax_delete_category() {
        if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( $_POST['nonce'], 'si_postlist_ajax' ) ) {
            wp_die( 'Permission denied' );
        }
        
        $category_id = (int) $_POST['category_id'];
        
        if ( $category_id <= 0 ) {
            wp_send_json_error( '無效的分類ID' );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'si_virtual_categories';
        
        $result = $wpdb->delete(
            $table_name,
            array( 'id' => $category_id ),
            array( '%d' )
        );
        
        if ( $result === false ) {
            wp_send_json_error( '刪除失敗' );
        }
        
        $this->clear_related_cache();
        
        wp_send_json_success( '分類已刪除' );
    }
    
    private function clear_related_cache() {
        // 清除 WordPress 對象快取
        wp_cache_flush();
        
        // 清除 transient 快取
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_si_post_tree_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_si_post_tree_%'" );
        
        // 清除文章相關的快取
        if ( function_exists( 'wp_cache_delete_group' ) ) {
            wp_cache_delete_group( 'posts' );
        }
    }
    
    public function cleanup_post_meta( $post_id ) {
        delete_post_meta( $post_id, '_si_parent_post' );
        delete_post_meta( $post_id, '_si_parent_category' );
        delete_post_meta( $post_id, '_si_sort_order' );
        
        $this->clear_related_cache();
    }
}

SI_PostList_System::get_instance();

register_activation_hook( __FILE__, 'si_postlist_activate' );
register_deactivation_hook( __FILE__, 'si_postlist_deactivate' );

function si_postlist_activate() {
    add_option( 'si_postlist_enabled_types', array( 'post' ) );
    
    // 創建虛擬分類表
    $system = SI_PostList_System::get_instance();
    $system->create_virtual_categories_table();
    
    flush_rewrite_rules();
}

function si_postlist_deactivate() {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_si_post_tree_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_si_post_tree_%'" );
    flush_rewrite_rules();
}

register_uninstall_hook( __FILE__, 'si_postlist_uninstall' );

function si_postlist_uninstall() {
    delete_option( 'si_postlist_enabled_types' );
    
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_si_parent_post', '_si_parent_category', '_si_sort_order')" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_si_post_tree_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_si_post_tree_%'" );
    
    // 刪除虛擬分類表
    $table_name = $wpdb->prefix . 'si_virtual_categories';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}