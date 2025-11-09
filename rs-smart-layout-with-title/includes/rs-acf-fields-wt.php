<?php
/**
 * RS Smart Layout - ACF 欄位註冊
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 註冊 ACF 欄位群組
 */
function rs_register_acf_fields() {
	// 檢查 ACF 是否存在
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_rs_taxonomy_image',
			'title'                 => '分類圖片',
			'fields'                => array(
				array(
					'key'               => 'field_rs_category_image',
					'label'             => '分類圖片',
					'name'              => 'category_image',
					'type'              => 'image',
					'instructions'      => '上傳分類的特色圖片（建議尺寸：800x600px）',
					'required'          => 0,
					'conditional_logic' => 0,
					'return_format'     => 'array',
					'preview_size'      => 'medium',
					'library'           => 'all',
				),
			),
			'location'              => array(
				// 套用到所有分類法
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'all',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
		)
	);
}

add_action( 'acf/init', 'rs_register_acf_fields' );

/**
 * 顯示 ACF 未啟用的管理員通知
 */
function rs_acf_admin_notice() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong>RS Smart Layout:</strong>
				<?php esc_html_e( '此外掛需要 Advanced Custom Fields (ACF) 外掛才能完整運作。請安裝並啟用 ACF。', 'rs-smart-layout-wt' ); ?>
			</p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'rs_acf_admin_notice' );
