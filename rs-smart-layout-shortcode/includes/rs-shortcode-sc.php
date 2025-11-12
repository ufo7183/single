<?php
/**
 * RS Smart Layout Shortcode - 短代碼處理
 *
 * @package RS_Smart_Layout_Shortcode
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 註冊短代碼
 */
function rs_register_shortcode_sc() {
	add_shortcode( 'rs_smart_layout', 'rs_smart_layout_shortcode_handler' );
}
add_action( 'init', 'rs_register_shortcode_sc' );

/**
 * 短代碼處理函數
 *
 * @param array $atts 短代碼屬性
 * @return string 輸出的 HTML
 */
function rs_smart_layout_shortcode_handler( $atts ) {
	// 解析參數
	$atts = shortcode_atts(
		array(
			'category_id'     => '',
			'taxonomy'        => '',
			'show_breadcrumb' => 'true',
			'columns'         => '4',
			'columns_mobile'  => '2',
			'posts_per_page'  => '8',
			'orderby'         => 'menu_order',
			'max_width'       => '1280',
		),
		$atts,
		'rs_smart_layout'
	);

	// 轉換參數類型
	$category_id     = ! empty( $atts['category_id'] ) ? absint( $atts['category_id'] ) : 0;
	$taxonomy        = sanitize_text_field( $atts['taxonomy'] );
	$show_breadcrumb = filter_var( $atts['show_breadcrumb'], FILTER_VALIDATE_BOOLEAN );
	$columns         = absint( $atts['columns'] );
	$columns_mobile  = absint( $atts['columns_mobile'] );
	$posts_per_page  = absint( $atts['posts_per_page'] );
	$orderby         = sanitize_text_field( $atts['orderby'] );
	$max_width       = absint( $atts['max_width'] );

	// 參數驗證
	if ( $columns < 1 || $columns > 6 ) {
		$columns = 4;
	}
	if ( $columns_mobile < 1 || $columns_mobile > 4 ) {
		$columns_mobile = 2;
	}
	if ( $posts_per_page < 1 ) {
		$posts_per_page = 8;
	}
	if ( $max_width < 100 ) {
		$max_width = 1280;
	}

	// 自動偵測分類和 taxonomy
	if ( empty( $category_id ) ) {
		$queried_object = get_queried_object();

		if ( $queried_object && ( $queried_object instanceof WP_Term ) ) {
			$category_id = $queried_object->term_id;
			if ( empty( $taxonomy ) ) {
				$taxonomy = $queried_object->taxonomy;
			}
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			// Shop 頁面特殊處理
			$category_id = 0; // 用 0 表示 shop 頁面
			$taxonomy    = 'product_cat';
		} else {
			return '<p class="rs-shortcode-error">' . esc_html__( '請在分類頁面使用此短代碼，或指定 category_id 參數。', 'rs-smart-layout-sc' ) . '</p>';
		}
	}

	// 如果指定了 category_id 但沒有 taxonomy，嘗試自動偵測
	if ( $category_id > 0 && empty( $taxonomy ) ) {
		$term = get_term( $category_id );
		if ( $term && ! is_wp_error( $term ) ) {
			$taxonomy = $term->taxonomy;
		} else {
			return '<p class="rs-shortcode-error">' . esc_html__( '無效的分類 ID。', 'rs-smart-layout-sc' ) . '</p>';
		}
	}

	// 麵包屑只在分類頁面顯示
	$is_archive_page = is_category() || is_tax() || ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_shop' ) && is_shop() );
	$should_show_breadcrumb = $show_breadcrumb && $is_archive_page;

	// 開始輸出緩衝
	ob_start();
	?>
	<div class="rs-shortcode-wrapper-sc" data-columns="<?php echo esc_attr( $columns ); ?>" data-columns-mobile="<?php echo esc_attr( $columns_mobile ); ?>" data-max-width="<?php echo esc_attr( $max_width ); ?>">
		<style>
			.rs-shortcode-wrapper-sc[data-max-width="<?php echo esc_attr( $max_width ); ?>"] .rs-layout-container-sc {
				max-width: <?php echo esc_attr( $max_width ); ?>px !important;
			}
			.rs-shortcode-wrapper-sc[data-columns="<?php echo esc_attr( $columns ); ?>"] .rs-card-grid-sc {
				grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr) !important;
			}
			@media (max-width: 1079px) {
				.rs-shortcode-wrapper-sc[data-columns-mobile="<?php echo esc_attr( $columns_mobile ); ?>"] .rs-card-grid-sc {
					grid-template-columns: repeat(<?php echo esc_attr( $columns_mobile ); ?>, 1fr) !important;
				}
			}
		</style>

		<?php if ( $should_show_breadcrumb ) : ?>
			<!-- 分類標題與面包屑 -->
			<header class="rs-archive-header-sc">
				<?php
				rs_render_breadcrumb_sc( $category_id, $taxonomy );
				?>
			</header>
		<?php endif; ?>

		<div class="rs-layout-container-sc">
			<?php
			// 執行智慧判定並渲染內容
			rs_smart_render_sc(
				array(
					'category_id'    => $category_id,
					'taxonomy'       => $taxonomy,
					'posts_per_page' => $posts_per_page,
					'orderby'        => $orderby,
				)
			);
			?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * 渲染麵包屑
 *
 * @param int    $term_id  分類 ID (0 表示 shop 頁面)
 * @param string $taxonomy 分類法名稱
 */
function rs_render_breadcrumb_sc( $term_id, $taxonomy ) {
	// Shop 頁面
	if ( 0 === $term_id && function_exists( 'woocommerce_page_title' ) ) {
		echo '<div class="rs-breadcrumb-sc"><span class="current-term">' . esc_html( woocommerce_page_title( false ) ) . '</span></div>';
		return;
	}

	// 一般分類頁面
	$term = get_term( $term_id, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	// 獲取父分類
	$parent_terms = array();
	$current_term = $term;

	while ( $current_term->parent != 0 ) {
		$parent = get_term( $current_term->parent, $current_term->taxonomy );
		if ( ! is_wp_error( $parent ) ) {
			array_unshift( $parent_terms, $parent );
			$current_term = $parent;
		} else {
			break;
		}
	}

	// 輸出麵包屑
	echo '<div class="rs-breadcrumb-sc">';
	if ( ! empty( $parent_terms ) ) {
		foreach ( $parent_terms as $parent_term ) {
			$parent_link = get_term_link( $parent_term );
			if ( ! is_wp_error( $parent_link ) ) {
				echo '<a href="' . esc_url( $parent_link ) . '" class="parent-term">' . esc_html( $parent_term->name ) . '</a> > ';
			}
		}
	}
	// 當前分類（不是連結，黑色）
	echo '<span class="current-term">' . esc_html( $term->name ) . '</span>';
	echo '</div>';
}
