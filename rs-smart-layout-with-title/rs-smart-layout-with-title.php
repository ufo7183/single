<?php
/**
 * Plugin Name: RS Smart Layout With Title
 * Plugin URI: https://example.com/rs-smart-layout-with-title
 * Description: 智慧分類與文章版型外掛（含標題與面包屑） - 自動套用統一的圖片+標題卡片樣式
 * Version: 1.0.1
 * Author: rs
 * Author URI: https://example.com
 * Text Domain: rs-smart-layout-wt
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定義插件常數
define( 'RS_SMART_LAYOUT_WT_VERSION', '1.0.1' );
define( 'RS_SMART_LAYOUT_WT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RS_SMART_LAYOUT_WT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RS_SMART_LAYOUT_WT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * 主插件類別
 */
class RS_Smart_Layout_WT {

	/**
	 * 單例實例
	 *
	 * @var RS_Smart_Layout_WT
	 */
	private static $instance = null;

	/**
	 * 獲取單例實例
	 *
	 * @return RS_Smart_Layout_WT
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * 載入依賴文件
	 */
	private function load_dependencies() {
		require_once RS_SMART_LAYOUT_WT_PLUGIN_DIR . 'includes/class-rs-loader-wt.php';
	}

	/**
	 * 初始化 Hooks
	 */
	private function init_hooks() {
		// 在 plugins_loaded 階段初始化
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

		// 註冊啟用/停用鉤子
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * plugins_loaded 回調
	 */
	public function on_plugins_loaded() {
		// 載入文本域
		load_plugin_textdomain( 'rs-smart-layout-wt', false, dirname( RS_SMART_LAYOUT_WT_PLUGIN_BASENAME ) . '/languages' );

		// 初始化 Loader
		RS_Loader_WT::get_instance();
	}

	/**
	 * 插件啟用
	 */
	public function activate() {
		// 刷新重寫規則
		flush_rewrite_rules();
	}

	/**
	 * 插件停用
	 */
	public function deactivate() {
		// 刷新重寫規則
		flush_rewrite_rules();
	}
}

/**
 * 啟動插件
 *
 * @return RS_Smart_Layout_WT
 */
function rs_smart_layout_wt() {
	return RS_Smart_Layout_WT::get_instance();
}

// 初始化插件
rs_smart_layout_wt();
