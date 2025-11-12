<?php
/**
 * RS Smart Layout Shortcode - 資源載入
 *
 * @package RS_Smart_Layout_Shortcode
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 載入前端樣式和腳本
 */
function rs_enqueue_assets_sc() {
	// 只在前端載入
	if ( is_admin() ) {
		return;
	}

	// 總是載入（因為短代碼可能在任何地方）
	// 如果需要優化，可以使用 has_shortcode() 檢測
	wp_enqueue_style(
		'rs-smart-layout-sc',
		RS_SMART_LAYOUT_SC_PLUGIN_URL . 'assets/css/rs-smart-layout-sc.css',
		array(),
		RS_SMART_LAYOUT_SC_VERSION,
		'all'
	);

	// 載入 JS
	wp_enqueue_script(
		'rs-smart-layout-sc',
		RS_SMART_LAYOUT_SC_PLUGIN_URL . 'assets/js/rs-smart-layout-sc.js',
		array( 'jquery' ),
		RS_SMART_LAYOUT_SC_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'rs_enqueue_assets_sc', 999 );
