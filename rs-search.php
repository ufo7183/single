<?php
/**
 * WordPress Shortcode: rs-search (階層式自定義分類快篩)
 *
 * 目的：以一段可置入 Snippets 的 PHP Shortcode，於彈窗（Pop）中呈現
 * 自定義階層分類（taxonomy：searchtag）的雙層瀏覽／快篩。
 *
 * 使用方式：
 * [rs-search]
 * [rs-search root="123" orderby="name" order="ASC" show_count="true"]
 *
 * @package RS_Search
 * @version 1.0.1
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * RS Search 主類別
 */
class RS_Search_Shortcode {

    /**
     * 單例實例
     */
    private static $instance = null;

    /**
     * 版本號
     */
    const VERSION = '1.0.1';

    /**
     * Taxonomy 名稱
     */
    const TAXONOMY = 'searchtag';

    /**
     * Nonce Action
     */
    const NONCE_ACTION = 'rs-search';

    /**
     * 獲取單例實例
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 建構子
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

        // AJAX 處理器（登入與未登入）
        add_action( 'wp_ajax_rs_search_load_children', array( $this, 'ajax_load_children' ) );
        add_action( 'wp_ajax_nopriv_rs_search_load_children', array( $this, 'ajax_load_children' ) );
    }

    /**
     * 註冊 Shortcode
     */
    public function register_shortcode() {
        if ( ! shortcode_exists( 'rs-search' ) ) {
            add_shortcode( 'rs-search', array( $this, 'shortcode_handler' ) );
        }
    }

    /**
     * 註冊資源（CSS 與 JS）
     */
    public function register_assets() {
        // 註冊 CSS
        wp_register_style(
            'rs-search-style',
            plugins_url( 'assets/rs-search.css', __FILE__ ),
            array(),
            self::VERSION,
            'all'
        );

        // 註冊 JS
        wp_register_script(
            'rs-search-script',
            plugins_url( 'assets/rs-search.js', __FILE__ ),
            array(),
            self::VERSION,
            true
        );
    }

    /**
     * Shortcode 處理器
     *
     * @param array $atts 短代碼屬性
     * @return string HTML 輸出
     */
    public function shortcode_handler( $atts ) {
        // 解析參數
        $args = shortcode_atts(
            array(
                'root'       => 0,
                'include'    => '',
                'exclude'    => '',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'show_count' => false,
                'class'      => '',
            ),
            $atts,
            'rs-search'
        );

        // 驗證 taxonomy 是否存在
        if ( ! taxonomy_exists( self::TAXONOMY ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<p class="rs-search__error">Taxonomy "' . esc_html( self::TAXONOMY ) . '" 不存在。</p>';
            }
            return '';
        }

        // 載入資源
        wp_enqueue_style( 'rs-search-style' );
        wp_enqueue_script( 'rs-search-script' );

        // 將配置注入 JavaScript
        wp_localize_script( 'rs-search-script', 'RS_SEARCH', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
            'taxonomy' => self::TAXONOMY,
            'show_count' => (bool) $args['show_count'],
        ) );

        // 處理 include/exclude 參數
        $include = $this->parse_comma_separated( $args['include'] );
        $exclude = $this->parse_comma_separated( $args['exclude'] );

        // 查詢第一層分類
        $level1_args = array(
            'taxonomy'   => self::TAXONOMY,
            'parent'     => absint( $args['root'] ),
            'hide_empty' => false,
            'orderby'    => sanitize_key( $args['orderby'] ),
            'order'      => strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC',
        );

        if ( ! empty( $include ) ) {
            $level1_args['include'] = $include;
        }

        if ( ! empty( $exclude ) ) {
            $level1_args['exclude'] = $exclude;
        }

        $level1_terms = get_terms( $level1_args );

        // 檢查錯誤
        if ( is_wp_error( $level1_terms ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<p class="rs-search__error">查詢錯誤: ' . esc_html( $level1_terms->get_error_message() ) . '</p>';
            }
            return '';
        }

        // 建立 HTML
        $container_classes = array( 'rs-search' );
        if ( ! empty( $args['class'] ) ) {
            $container_classes[] = sanitize_html_class( $args['class'] );
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" data-rs-taxonomy="<?php echo esc_attr( self::TAXONOMY ); ?>">
            <div class="rs-search__level1" role="tablist" aria-label="第一層分類">
                <?php if ( empty( $level1_terms ) ) : ?>
                    <p class="rs-search__empty">無可用分類</p>
                <?php else : ?>
                    <?php foreach ( $level1_terms as $term ) : ?>
                        <button
                            class="rs-search__l1-tag"
                            role="tab"
                            aria-selected="false"
                            aria-controls="rs-search-level2-<?php echo esc_attr( $term->term_id ); ?>"
                            data-term-id="<?php echo esc_attr( $term->term_id ); ?>"
                            type="button"
                        >
                            <span class="rs-search__l1-text"><?php echo esc_html( $term->name ); ?></span>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div
                class="rs-search__level2"
                role="list"
                aria-live="polite"
                aria-busy="false"
            >
                <!-- 第二層分類將由 AJAX 載入 -->
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * AJAX 處理器：載入子分類
     */
    public function ajax_load_children() {
        // 驗證 Nonce（允許未登入）
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        // 取得參數
        $parent_id = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;
        $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : self::TAXONOMY;

        // 安全性：只允許查詢 searchtag
        if ( $taxonomy !== self::TAXONOMY ) {
            wp_send_json( array(
                'ok'      => false,
                'message' => '參數錯誤：不支援的 taxonomy',
            ) );
        }

        // 驗證父分類是否存在
        if ( $parent_id > 0 ) {
            $parent_term = get_term( $parent_id, $taxonomy );
            if ( is_wp_error( $parent_term ) || ! $parent_term ) {
                wp_send_json( array(
                    'ok'      => false,
                    'message' => '參數錯誤：無效的父分類 ID',
                ) );
            }
        }

        // 查詢子分類
        $children = get_terms( array(
            'taxonomy'   => $taxonomy,
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        // 檢查錯誤
        if ( is_wp_error( $children ) ) {
            wp_send_json( array(
                'ok'      => false,
                'message' => '查詢錯誤：' . $children->get_error_message(),
            ) );
        }

        // 建立回應資料
        $data = array();
        foreach ( $children as $term ) {
            $data[] = array(
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'url'   => get_term_link( $term->term_id, $taxonomy ),
                'count' => $term->count,
            );
        }

        // 回傳 JSON
        wp_send_json( array(
            'ok'       => true,
            'children' => $data,
        ) );
    }

    /**
     * 解析逗號分隔的參數
     *
     * @param string $value 逗號分隔的字串
     * @return array 整數陣列
     */
    private function parse_comma_separated( $value ) {
        if ( empty( $value ) ) {
            return array();
        }

        $items = explode( ',', $value );
        $items = array_map( 'trim', $items );
        $items = array_map( 'absint', $items );
        $items = array_filter( $items );

        return array_values( $items );
    }
}

// 初始化
RS_Search_Shortcode::get_instance();
