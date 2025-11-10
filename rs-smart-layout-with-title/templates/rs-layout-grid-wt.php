<?php
/**
 * RS Smart Layout WT - 統一 Grid 版型（含標題與面包屑）
 *
 * @package RS_Smart_Layout_WT
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="primary" class="content-area rs-full-width-wt">
	<main id="main" class="site-main rs-smart-layout-wt-main">

		<?php
		$queried_object = get_queried_object();

		// 處理 Shop 頁面或分類頁面
		if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( $queried_object && ( $queried_object instanceof WP_Term ) ) ) :
			?>

			<!-- 分類標題與面包屑 -->
			<header class="rs-archive-header-wt">
				<?php
				// 顯示面包屑
				if ( function_exists( 'is_shop' ) && is_shop() ) :
					// Shop 頁面：顯示頁面標題
					echo '<div class="rs-breadcrumb"><span class="current-term">' . esc_html( woocommerce_page_title( false ) ) . '</span></div>';
				elseif ( $queried_object ) :
					// 獲取父分類
					$parent_terms = array();
					$current_term = $queried_object;

					while ( $current_term->parent != 0 ) {
						$parent = get_term( $current_term->parent, $current_term->taxonomy );
						if ( ! is_wp_error( $parent ) ) {
							array_unshift( $parent_terms, $parent );
							$current_term = $parent;
						} else {
							break;
						}
					}

					// 輸出面包屑
					echo '<div class="rs-breadcrumb">';
					if ( ! empty( $parent_terms ) ) {
						foreach ( $parent_terms as $parent_term ) {
							$parent_link = get_term_link( $parent_term );
							if ( ! is_wp_error( $parent_link ) ) {
								echo '<a href="' . esc_url( $parent_link ) . '" class="parent-term">' . esc_html( $parent_term->name ) . '</a> > ';
							}
						}
					}
					// 當前分類（不是連結，黑色）
					echo '<span class="current-term">' . esc_html( $queried_object->name ) . '</span>';
					echo '</div>';
				endif;
				?>
			</header>

			<div class="rs-layout-container-wt">
				<?php
				// 執行智慧判定並渲染內容
				rs_smart_render_wt();
				?>
			</div>

		<?php else : ?>

			<p><?php esc_html_e( '找不到內容。', 'rs-smart-layout-wt' ); ?></p>

		<?php endif; ?>

	</main>
</div>

<?php
get_footer();
