<?php
/**
 * RS Smart Layout - 智慧模板路由
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 智慧模板路由器
 *
 * @param string $template 原始模板路徑
 * @return string 新的模板路徑
 */
function rs_smart_template_router( $template ) {
	// 檢查是否為需要套用的頁面類型
	$should_apply = is_category()
		|| is_tax()
		|| ( function_exists( 'is_product_category' ) && is_product_category() )
		|| ( function_exists( 'is_product_tag' ) && is_product_tag() );

	// 允許開發者透過 filter 控制
	$should_apply = apply_filters( 'rs_smart_layout_should_apply', $should_apply );

	if ( $should_apply ) {
		$custom_template = RS_SMART_LAYOUT_PLUGIN_DIR . 'templates/rs-layout-grid.php';

		// 確認模板文件存在
		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}
	}

	return $template;
}

add_filter( 'template_include', 'rs_smart_template_router', 99 );
