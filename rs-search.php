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
 * @version 1.0.4
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
    const VERSION = '1.0.4';

    /**
     * Taxonomy 名稱
     */
    const TAXONOMY = 'searchtag';

    /**
     * Nonce Action
     */
    const NONCE_ACTION = 'rs-search';

    /**
     * 是否已輸出樣式
     */
    private static $styles_printed = false;

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

        // 處理 include/exclude 參數
        $include = $this->parse_comma_separated( $args['include'] );
        $exclude = $this->parse_comma_separated( $args['exclude'] );

        // 取得 treatment post type 的所有文章 ID（用於過濾 terms）
        $treatment_posts = get_posts( array(
            'post_type'   => 'treatment',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ) );

        // 查詢第一層分類
        $level1_args = array(
            'taxonomy'   => self::TAXONOMY,
            'parent'     => absint( $args['root'] ),
            'hide_empty' => false,
            'orderby'    => sanitize_key( $args['orderby'] ),
            'order'      => strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC',
        );

        // 只顯示有關聯到 treatment post type 的 terms
        if ( ! empty( $treatment_posts ) ) {
            $level1_args['object_ids'] = $treatment_posts;
        }

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

        // 生成唯一 ID
        $unique_id = 'rs-search-' . uniqid();

        ob_start();

        // 輸出樣式（只輸出一次）
        if ( ! self::$styles_printed ) {
            echo $this->get_inline_styles();
            self::$styles_printed = true;
        }

        ?>
        <div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" id="<?php echo esc_attr( $unique_id ); ?>" data-rs-taxonomy="<?php echo esc_attr( self::TAXONOMY ); ?>">
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
        // 輸出 JavaScript
        echo $this->get_inline_script( $unique_id, $args );

        return ob_get_clean();
    }

    /**
     * 取得內嵌樣式
     */
    private function get_inline_styles() {
        return '<style type="text/css">
/* RS Search - 階層式自定義分類快篩 v1.0.4 */
.rs-search{width:100%!important;max-width:100%!important;font-family:"Noto Sans TC",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif!important;box-sizing:border-box!important}
.rs-search *,.rs-search *::before,.rs-search *::after{box-sizing:inherit!important}
.rs-search__level1{display:flex!important;flex-wrap:wrap!important;gap:10px!important;margin-bottom:20px!important;align-items:center!important}
.rs-search__l1-tag{display:inline-flex!important;padding:6px 16px!important;justify-content:center!important;align-items:center!important;gap:10px!important;background:transparent!important;border:none!important;border-radius:0!important;cursor:pointer!important;transition:all 0.2s ease!important;outline:none!important;margin:5px!important}
.rs-search__l1-text{color:#292929!important;text-align:center!important;font-feature-settings:"case" on!important;font-family:"Noto Sans TC",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;font-size:18px!important;font-style:normal!important;font-weight:500!important;line-height:160%!important;letter-spacing:1.8px!important}
.rs-search__l1-tag:hover,.rs-search__l1-tag:focus,.rs-search__l1-tag.is-active{border-radius:20.5px!important;background:#E83743!important}
.rs-search__l1-tag:hover .rs-search__l1-text,.rs-search__l1-tag:focus .rs-search__l1-text,.rs-search__l1-tag.is-active .rs-search__l1-text{color:#FFF!important}
.rs-search__l1-tag:focus-visible{outline:2px solid #E83743!important;outline-offset:2px!important}
.rs-search__level2{display:flex!important;flex-wrap:wrap!important;gap:10px!important;min-height:40px!important;align-items:center!important}
.rs-search__level2.rs-search__l2--loading{opacity:0.6!important;pointer-events:none!important;position:relative!important}
.rs-search__level2.rs-search__l2--loading::after{content:"載入中..."!important;display:block!important;width:100%!important;text-align:center!important;color:#666!important;font-size:14px!important;font-family:"Noto Sans TC",sans-serif!important}
.rs-search__l2-tag{display:flex!important;padding:4px 16px!important;justify-content:center!important;align-items:center!important;gap:10px!important;border-radius:18.5px!important;border:1px solid #D9D9D9!important;text-decoration:none!important;background:transparent!important;transition:all 0.2s ease!important;min-height:40px!important}
.rs-search__l2-text{color:#88888C!important;text-align:center!important;font-feature-settings:"case" on!important;font-family:"Noto Sans TC",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important;font-size:18px!important;font-style:normal!important;font-weight:500!important;line-height:160%!important;letter-spacing:1.8px!important}
.rs-search__l2-tag:hover,.rs-search__l2-tag:focus,.rs-search__l2-tag.is-active{background:#E83743!important;border-color:#E83743!important;text-decoration:none!important}
.rs-search__l2-tag:hover .rs-search__l2-text,.rs-search__l2-tag:focus .rs-search__l2-text,.rs-search__l2-tag.is-active .rs-search__l2-text{color:#FFF!important}
.rs-search__l2-tag:focus-visible{outline:2px solid #E83743!important;outline-offset:2px!important}
.rs-search__empty,.rs-search__error{font-size:14px!important;margin-top:8px!important;padding:10px 15px!important;border-radius:4px!important;font-family:"Noto Sans TC",sans-serif!important;width:100%!important;text-align:center!important}
.rs-search__empty{color:#666!important;background:#f5f5f5!important;border:1px solid #ddd!important}
.rs-search__error{color:#d32f2f!important;background:#ffebee!important;border:1px solid #ef5350!important}
@media (max-width:768px){.rs-search__l1-text,.rs-search__l2-text{font-size:16px!important;letter-spacing:1.6px!important}.rs-search__l1-tag{padding:5px 14px!important}.rs-search__l2-tag{padding:3px 14px!important}}
@media (max-width:480px){.rs-search__level1,.rs-search__level2{gap:8px!important}.rs-search__l1-text,.rs-search__l2-text{font-size:15px!important;letter-spacing:1.5px!important}.rs-search__l1-tag{padding:4px 12px!important}.rs-search__l2-tag{padding:3px 12px!important;min-height:36px!important}.rs-search__empty,.rs-search__error{font-size:13px!important;padding:8px 12px!important}}
@media (prefers-contrast:high){.rs-search__l1-tag,.rs-search__l2-tag{border:2px solid currentColor!important}}
@media (prefers-reduced-motion:reduce){.rs-search__l1-tag,.rs-search__l2-tag{transition:none!important}}
@media print{.rs-search{display:none!important}}
@media (pointer:coarse){.rs-search__l1-tag,.rs-search__l2-tag{min-height:44px!important;min-width:44px!important}}
[dir="rtl"] .rs-search__level1,[dir="rtl"] .rs-search__level2{direction:rtl!important}
@media (prefers-color-scheme:dark){.rs-search__l1-text{color:#e0e0e0!important}.rs-search__l2-tag{border-color:#555!important}.rs-search__l2-text{color:#aaa!important}.rs-search__empty{color:#aaa!important;background:#2a2a2a!important;border-color:#444!important}.rs-search__error{color:#ef5350!important;background:#3a1f1f!important;border-color:#d32f2f!important}}
</style>';
    }

    /**
     * 取得內嵌腳本
     */
    private function get_inline_script( $container_id, $args ) {
        $ajax_url = admin_url( 'admin-ajax.php' );
        $nonce = wp_create_nonce( self::NONCE_ACTION );
        $taxonomy = self::TAXONOMY;
        $show_count = (bool) $args['show_count'] ? 'true' : 'false';

        return '<script type="text/javascript">
(function(){
"use strict";
var container=document.getElementById("' . esc_js( $container_id ) . '");
if(!container)return;
var config={ajax_url:"' . esc_js( $ajax_url ) . '",nonce:"' . esc_js( $nonce ) . '",taxonomy:"' . esc_js( $taxonomy ) . '",show_count:' . $show_count . '};
var level1Container=container.querySelector(".rs-search__level1");
var level2Container=container.querySelector(".rs-search__level2");
var isLoading=false;
function setActiveButton(button){
var allButtons=level1Container.querySelectorAll(".rs-search__l1-tag");
allButtons.forEach(function(btn){btn.classList.remove("is-active");btn.setAttribute("aria-selected","false")});
button.classList.add("is-active");
button.setAttribute("aria-selected","true")
}
function setLoadingState(loading){
isLoading=loading;
level2Container.setAttribute("aria-busy",loading?"true":"false");
if(loading){level2Container.classList.add("rs-search__l2--loading")}else{level2Container.classList.remove("rs-search__l2--loading")}
}
function showMessage(message,type){
var className="rs-search__"+type;
var div=document.createElement("div");
div.textContent=message;
level2Container.innerHTML="<div class=\""+className+"\">"+div.innerHTML+"</div>"
}
function renderLevel2(children){
var fragment=document.createDocumentFragment();
children.forEach(function(term){
var link=document.createElement("a");
link.className="rs-search__l2-tag";
link.href=term.url;
link.setAttribute("role","listitem");
var span=document.createElement("span");
span.className="rs-search__l2-text";
var text=term.name;
if(config.show_count&&term.count>0){text+=" ("+term.count+")"}
span.textContent=text;
link.appendChild(span);
fragment.appendChild(link)
});
level2Container.innerHTML="";
level2Container.appendChild(fragment)
}
function loadLevel2(parentId){
setLoadingState(true);
var formData=new URLSearchParams();
formData.append("action","rs_search_load_children");
formData.append("nonce",config.nonce);
formData.append("parent",parentId);
formData.append("taxonomy",config.taxonomy);
fetch(config.ajax_url,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:formData})
.then(function(response){if(!response.ok){throw new Error("HTTP error! status: "+response.status)}return response.json()})
.then(function(data){
if(!data.ok){showMessage(data.message||"載入失敗","error");return}
if(!data.children||data.children.length===0){level2Container.innerHTML="";return}
renderLevel2(data.children)
})
.catch(function(error){console.error("RS Search AJAX Error:",error);showMessage("載入失敗，請重試","error")})
.finally(function(){setLoadingState(false)})
}
function handleLevel1Click(button){
if(isLoading)return;
var termId=button.getAttribute("data-term-id");
if(!termId)return;
setActiveButton(button);
loadLevel2(termId)
}
level1Container.addEventListener("click",function(e){
var button=e.target.closest(".rs-search__l1-tag");
if(!button)return;
e.preventDefault();
handleLevel1Click(button)
});
var buttons=level1Container.querySelectorAll(".rs-search__l1-tag");
buttons.forEach(function(button,index){
button.addEventListener("keydown",function(e){
if(e.key==="Enter"||e.key===" "){e.preventDefault();handleLevel1Click(button)}
else if(e.key==="ArrowLeft"){e.preventDefault();var prevButton=buttons[index-1]||buttons[buttons.length-1];prevButton.focus()}
else if(e.key==="ArrowRight"){e.preventDefault();var nextButton=buttons[index+1]||buttons[0];nextButton.focus()}
})
})
})();
</script>';
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

        // 取得 treatment post type 的所有文章 ID（用於過濾 terms）
        $treatment_posts = get_posts( array(
            'post_type'   => 'treatment',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ) );

        // 查詢子分類
        $children_args = array(
            'taxonomy'   => $taxonomy,
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        // 只顯示有關聯到 treatment post type 的 terms
        if ( ! empty( $treatment_posts ) ) {
            $children_args['object_ids'] = $treatment_posts;
        }

        $children = get_terms( $children_args );

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
