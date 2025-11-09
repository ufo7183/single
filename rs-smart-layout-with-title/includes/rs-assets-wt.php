<?php
/**
 * RS Smart Layout - 資源載入
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 載入前端樣式和腳本
 */
function rs_enqueue_assets() {
	// 只在前端載入
	if ( is_admin() ) {
		return;
	}

	// 檢查是否為需要套用的頁面類型
	$should_enqueue = is_category()
		|| is_tax()
		|| ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_category' ) && is_product_category() )
		|| ( function_exists( 'is_product_tag' ) && is_product_tag() );

	// 允許開發者透過 filter 控制
	$should_enqueue = apply_filters( 'rs_smart_layout_wt_should_enqueue', $should_enqueue );

	if ( ! $should_enqueue ) {
		return;
	}

	// 載入 CSS（提高優先級到 999）
	wp_enqueue_style(
		'rs-smart-layout-wt',
		RS_SMART_LAYOUT_WT_PLUGIN_URL . 'assets/css/rs-smart-layout-wt.css',
		array(),
		RS_SMART_LAYOUT_WT_VERSION . '.' . time(), // 防止快取
		'all'
	);

	// 載入 JS（如需要）
	wp_enqueue_script(
		'rs-smart-layout-wt',
		RS_SMART_LAYOUT_WT_PLUGIN_URL . 'assets/js/rs-smart-layout-wt.js',
		array( 'jquery' ),
		RS_SMART_LAYOUT_WT_VERSION,
		true
	);

	// 添加內聯樣式確保全寬生效
	$inline_css = "
		body.archive .rs-full-width-wt,
		body.tax .rs-full-width-wt,
		body.post-type-archive .rs-full-width-wt,
		.rs-full-width-wt,
		#primary.rs-full-width-wt,
		.content-area.rs-full-width-wt {
			width: 100% !important;
			max-width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			float: none !important;
			box-sizing: border-box !important;
		}
		.rs-smart-layout-wt-main {
			width: 100% !important;
			max-width: 100% !important;
			padding: 40px 0 !important;
			margin: 0 !important;
			box-sizing: border-box !important;
		}
		.rs-layout-container-wt {
			margin-left: auto !important;
			margin-right: auto !important;
		}
		#secondary,
		.sidebar,
		aside.widget-area {
			display: none !important;
		}
		@media (max-width: 767px) {
			.rs-layout-container-wt {
				padding-left: 15px !important;
				padding-right: 15px !important;
			}
		}
	";
	wp_add_inline_style( 'rs-smart-layout-wt', $inline_css );
}

add_action( 'wp_enqueue_scripts', 'rs_enqueue_assets', 999 );
