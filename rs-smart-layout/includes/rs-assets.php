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
	$should_enqueue = apply_filters( 'rs_smart_layout_should_enqueue', $should_enqueue );

	if ( ! $should_enqueue ) {
		return;
	}

	// 載入 CSS
	wp_enqueue_style(
		'rs-smart-layout',
		RS_SMART_LAYOUT_PLUGIN_URL . 'assets/css/rs-smart-layout.css',
		array(),
		RS_SMART_LAYOUT_VERSION,
		'all'
	);

	// 載入 JS（如需要）
	wp_enqueue_script(
		'rs-smart-layout',
		RS_SMART_LAYOUT_PLUGIN_URL . 'assets/js/rs-smart-layout.js',
		array( 'jquery' ),
		RS_SMART_LAYOUT_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'rs_enqueue_assets' );
