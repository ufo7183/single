<?php
/**
 * RS Smart Layout - 統一 Grid 版型
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="primary" class="content-area rs-full-width">
	<main id="main" class="site-main rs-smart-layout-main">

		<?php
		$queried_object = get_queried_object();

		// 處理 Shop 頁面或分類頁面
		if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( $queried_object && ( $queried_object instanceof WP_Term ) ) ) :
			?>

			<div class="rs-layout-container">
				<?php
				// 執行智慧判定並渲染內容
				rs_smart_render();
				?>
			</div>

		<?php else : ?>

			<p><?php esc_html_e( '找不到內容。', 'rs-smart-layout' ); ?></p>

		<?php endif; ?>

	</main>
</div>

<?php
get_footer();
