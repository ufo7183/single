<?php
/**
 * RS Smart Layout - 模組載入器
 *
 * @package RS_Smart_Layout
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 模組載入器類別
 */
class RS_Loader {

	/**
	 * 單例實例
	 *
	 * @var RS_Loader
	 */
	private static $instance = null;

	/**
	 * 獲取單例實例
	 *
	 * @return RS_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 建構函數
	 */
	private function __construct() {
		$this->load_modules();
		$this->init_hooks();
	}

	/**
	 * 載入所有模組
	 */
	private function load_modules() {
		// ACF 欄位註冊
		require_once RS_SMART_LAYOUT_PLUGIN_DIR . 'includes/rs-acf-fields.php';

		// 模板路由
		require_once RS_SMART_LAYOUT_PLUGIN_DIR . 'includes/rs-template-router.php';

		// 模板渲染
		require_once RS_SMART_LAYOUT_PLUGIN_DIR . 'includes/rs-template-render.php';

		// 資源載入
		require_once RS_SMART_LAYOUT_PLUGIN_DIR . 'includes/rs-assets.php';
	}

	/**
	 * 初始化 Hooks
	 */
	private function init_hooks() {
		// Init 階段執行
		add_action( 'init', array( $this, 'on_init' ), 0 );
	}

	/**
	 * Init 階段回調
	 */
	public function on_init() {
		// 可在此註冊自定義文章類型或分類法（若需要）
		// 目前規格移除了 rs_portfolio，所以此處保留空白供未來擴展
	}
}
