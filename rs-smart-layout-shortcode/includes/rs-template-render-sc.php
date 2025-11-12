<?php
/**
 * RS Smart Layout Shortcode - 模板渲染邏輯
 *
 * @package RS_Smart_Layout_Shortcode
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 獲取當前分類的子分類
 *
 * @param int    $term_id  分類 ID
 * @param string $taxonomy 分類法名稱
 * @param string $orderby  排序方式
 * @return array|false 子分類數組或 false
 */
function rs_get_child_terms_sc( $term_id, $taxonomy, $orderby = 'menu_order' ) {
	$args = array(
		'taxonomy'   => $taxonomy,
		'parent'     => $term_id,
		'hide_empty' => false,
		'orderby'    => $orderby,
		'order'      => 'ASC',
	);

	$child_terms = get_terms( $args );

	if ( is_wp_error( $child_terms ) || empty( $child_terms ) ) {
		return false;
	}

	return $child_terms;
}

/**
 * 獲取分類圖片 URL
 *
 * @param int    $term_id 分類 ID
 * @param string $size    圖片尺寸
 * @return string|false 圖片 URL 或 false
 */
function rs_get_term_image_sc( $term_id, $size = 'medium_large' ) {
	// 檢查 ACF 是否存在
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	$image = get_field( 'category_image', 'term_' . $term_id );

	if ( ! $image ) {
		return false;
	}

	// ACF 返回 array 格式
	if ( is_array( $image ) && isset( $image['sizes'][ $size ] ) ) {
		return $image['sizes'][ $size ];
	} elseif ( is_array( $image ) && isset( $image['url'] ) ) {
		return $image['url'];
	}

	return false;
}

/**
 * 渲染子分類卡片
 *
 * @param array  $child_terms 子分類數組
 * @param string $taxonomy    分類法名稱
 */
function rs_render_child_terms_sc( $child_terms, $taxonomy ) {
	if ( empty( $child_terms ) ) {
		return;
	}

	echo '<div class="rs-card-grid-sc rs-child-terms">';

	foreach ( $child_terms as $term ) {
		$term_link  = get_term_link( $term );
		$term_image = rs_get_term_image_sc( $term->term_id );

		if ( is_wp_error( $term_link ) ) {
			continue;
		}

		$card_class = 'rs-card-sc';
		if ( ! $term_image ) {
			$card_class .= ' no-image';
		}
		?>
		<a href="<?php echo esc_url( $term_link ); ?>" class="<?php echo esc_attr( $card_class ); ?>">
			<?php if ( $term_image ) : ?>
				<div class="rs-card-image-sc">
					<img src="<?php echo esc_url( $term_image ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy">
				</div>
			<?php endif; ?>
			<h3 class="rs-card-title-sc"><?php echo esc_html( $term->name ); ?></h3>
		</a>
		<?php
	}

	echo '</div>';
}

/**
 * 渲染文章卡片
 *
 * @param WP_Query $query 文章查詢對象
 */
function rs_render_posts_sc( $query ) {
	if ( ! $query || ! $query->have_posts() ) {
		echo '<p class="rs-no-results">' . esc_html__( '目前沒有內容。', 'rs-smart-layout-sc' ) . '</p>';
		return;
	}

	echo '<div class="rs-card-grid-sc rs-posts">';

	while ( $query->have_posts() ) {
		$query->the_post();

		$post_link      = get_permalink();
		$post_title     = get_the_title();
		$has_thumbnail  = has_post_thumbnail();
		$thumbnail_url  = $has_thumbnail ? get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) : false;

		$card_class = 'rs-card-sc';
		if ( ! $has_thumbnail ) {
			$card_class .= ' no-image';
		}
		?>
		<a href="<?php echo esc_url( $post_link ); ?>" class="<?php echo esc_attr( $card_class ); ?>">
			<?php if ( $thumbnail_url ) : ?>
				<div class="rs-card-image-sc">
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $post_title ); ?>" loading="lazy">
				</div>
			<?php endif; ?>
			<h3 class="rs-card-title-sc"><?php echo esc_html( $post_title ); ?></h3>
		</a>
		<?php
	}

	echo '</div>';

	wp_reset_postdata();
}

/**
 * 渲染分頁導航
 *
 * @param WP_Query $query 文章查詢對象
 */
function rs_render_pagination_sc( $query ) {
	if ( ! $query || $query->max_num_pages <= 1 ) {
		return;
	}

	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

	$pagination = paginate_links(
		array(
			'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, $paged ),
			'total'     => $query->max_num_pages,
			'prev_text' => '&laquo; ' . __( '上一頁', 'rs-smart-layout-sc' ),
			'next_text' => __( '下一頁', 'rs-smart-layout-sc' ) . ' &raquo;',
			'type'      => 'list',
		)
	);

	if ( $pagination ) {
		echo '<nav class="rs-pagination-sc">' . $pagination . '</nav>';
	}
}

/**
 * 偵測文章類型
 *
 * @param string $taxonomy 分類法名稱
 * @return string 文章類型
 */
function rs_detect_post_type_sc( $taxonomy ) {
	// WooCommerce 商品分類
	if ( 'product_cat' === $taxonomy || 'product_tag' === $taxonomy ) {
		return 'product';
	}

	// 一般分類
	if ( 'category' === $taxonomy || 'post_tag' === $taxonomy ) {
		return 'post';
	}

	// 嘗試從分類法獲取關聯的文章類型
	$tax_object = get_taxonomy( $taxonomy );
	if ( $tax_object && ! empty( $tax_object->object_type ) ) {
		return $tax_object->object_type[0];
	}

	// 預設為 post
	return 'post';
}

/**
 * 執行智慧判定並渲染內容
 *
 * @param array $args 參數陣列
 */
function rs_smart_render_sc( $args = array() ) {
	$defaults = array(
		'category_id'    => 0,
		'taxonomy'       => 'category',
		'posts_per_page' => 8,
		'orderby'        => 'menu_order',
	);

	$args = wp_parse_args( $args, $defaults );

	// Shop 頁面（category_id = 0）
	if ( 0 === $args['category_id'] && 'product_cat' === $args['taxonomy'] ) {
		// Shop 頁面：顯示所有第一層商品分類
		$top_level_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => $args['orderby'],
				'order'      => 'ASC',
			)
		);

		if ( ! is_wp_error( $top_level_categories ) && ! empty( $top_level_categories ) ) {
			rs_render_child_terms_sc( $top_level_categories, 'product_cat' );
		} else {
			echo '<p class="rs-no-results">' . esc_html__( '目前沒有商品分類。', 'rs-smart-layout-sc' ) . '</p>';
		}
		return;
	}

	// 檢查是否有子分類
	$child_terms = rs_get_child_terms_sc( $args['category_id'], $args['taxonomy'], $args['orderby'] );

	if ( $child_terms ) {
		// 有子分類：顯示子分類卡片
		rs_render_child_terms_sc( $child_terms, $args['taxonomy'] );
	} else {
		// 無子分類：顯示文章卡片
		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

		// 偵測文章類型
		$post_type = rs_detect_post_type_sc( $args['taxonomy'] );

		$query_args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $args['posts_per_page'],
			'paged'          => $paged,
			'tax_query'      => array(
				array(
					'taxonomy' => $args['taxonomy'],
					'field'    => 'term_id',
					'terms'    => $args['category_id'],
				),
			),
		);

		$query = new WP_Query( $query_args );

		rs_render_posts_sc( $query );
		rs_render_pagination_sc( $query );
	}
}
