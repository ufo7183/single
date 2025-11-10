<?php
/**
 * RS Smart Layout - 模板渲染邏輯
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 獲取當前分類的子分類
 *
 * @param int    $term_id 分類 ID
 * @param string $taxonomy 分類法名稱
 * @return array|false 子分類數組或 false
 */
function rs_get_child_terms_wt( $term_id, $taxonomy ) {
	$args = array(
		'taxonomy'   => $taxonomy,
		'parent'     => $term_id,
		'hide_empty' => false,
		'orderby'    => 'menu_order',
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
 * @param string $size 圖片尺寸
 * @return string|false 圖片 URL 或 false
 */
function rs_get_term_image_wt( $term_id, $size = 'medium_large' ) {
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
 * @param string $taxonomy 分類法名稱
 */
function rs_render_child_terms_wt( $child_terms, $taxonomy ) {
	if ( empty( $child_terms ) ) {
		return;
	}

	echo '<div class="rs-card-wt-grid-wt rs-child-terms">';

	foreach ( $child_terms as $term ) {
		$term_link  = get_term_link( $term );
		$term_image = rs_get_term_image_wt( $term->term_id );

		if ( is_wp_error( $term_link ) ) {
			continue;
		}

		$card_class = 'rs-card';
		if ( ! $term_image ) {
			$card_class .= ' no-image';
		}
		?>
		<a href="<?php echo esc_url( $term_link ); ?>" class="<?php echo esc_attr( $card_class ); ?>">
			<?php if ( $term_image ) : ?>
				<div class="rs-card-wt-image">
					<img src="<?php echo esc_url( $term_image ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy">
				</div>
			<?php endif; ?>
			<h3 class="rs-card-wt-title"><?php echo esc_html( $term->name ); ?></h3>
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
function rs_render_posts_wt( $query ) {
	if ( ! $query || ! $query->have_posts() ) {
		echo '<p class="rs-no-results">' . esc_html__( '目前沒有內容。', 'rs-smart-layout-wt' ) . '</p>';
		return;
	}

	echo '<div class="rs-card-wt-grid-wt rs-posts">';

	while ( $query->have_posts() ) {
		$query->the_post();

		$post_link      = get_permalink();
		$post_title     = get_the_title();
		$has_thumbnail  = has_post_thumbnail();
		$thumbnail_url  = $has_thumbnail ? get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) : false;

		$card_class = 'rs-card';
		if ( ! $has_thumbnail ) {
			$card_class .= ' no-image';
		}
		?>
		<a href="<?php echo esc_url( $post_link ); ?>" class="<?php echo esc_attr( $card_class ); ?>">
			<?php if ( $thumbnail_url ) : ?>
				<div class="rs-card-wt-image">
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $post_title ); ?>" loading="lazy">
				</div>
			<?php endif; ?>
			<h3 class="rs-card-wt-title"><?php echo esc_html( $post_title ); ?></h3>
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
function rs_render_pagination_wt( $query ) {
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
			'prev_text' => '&laquo; ' . __( '上一頁', 'rs-smart-layout-wt' ),
			'next_text' => __( '下一頁', 'rs-smart-layout-wt' ) . ' &raquo;',
			'type'      => 'list',
		)
	);

	if ( $pagination ) {
		echo '<nav class="rs-pagination">' . $pagination . '</nav>';
	}
}

/**
 * 執行智慧判定並渲染內容
 */
function rs_smart_render_wt() {
	// 檢查是否為 Shop 頁面
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		// Shop 頁面：顯示所有第一層商品分類
		$top_level_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
			)
		);

		if ( ! is_wp_error( $top_level_categories ) && ! empty( $top_level_categories ) ) {
			rs_render_child_terms_wt( $top_level_categories, 'product_cat' );
		} else {
			echo '<p class="rs-no-results">' . esc_html__( '目前沒有商品分類。', 'rs-smart-layout-wt' ) . '</p>';
		}
		return;
	}

	$queried_object = get_queried_object();

	// 確保是分類頁面
	if ( ! $queried_object || ! ( $queried_object instanceof WP_Term ) ) {
		return;
	}

	$term_id  = $queried_object->term_id;
	$taxonomy = $queried_object->taxonomy;

	// 檢查是否有子分類
	$child_terms = rs_get_child_terms_wt( $term_id, $taxonomy );

	if ( $child_terms ) {
		// 有子分類：顯示子分類卡片
		rs_render_child_terms_wt( $child_terms, $taxonomy );
	} else {
		// 無子分類：顯示文章卡片
		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

		// 判斷文章類型
		$post_type = 'post'; // 預設為一般文章

		// 如果是 WooCommerce 商品分類
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$post_type = 'product';
		} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			$post_type = 'product';
		}

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => 8,
			'paged'          => $paged,
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);

		$query = new WP_Query( $args );

		rs_render_posts_wt( $query );
		rs_render_pagination_wt( $query );
	}
}
