<?php
/**
 * Plugin Name: RS Smart Layout Shortcode
 * Plugin URI: https://rs.com
 * Description: 基於短代碼的彈性版型系統，支援所有分類法，可自由控制哪些分類使用智慧卡片排版
 * Version: 1.0.0
 * Author: rs
 * Author URI: https://rs.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rs-smart-layout-sc
 * Domain Path: /languages
 *
 * @package RS_Smart_Layout_Shortcode
 */

// 防止直接訪問
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定義常數
define( 'RS_SMART_LAYOUT_SC_VERSION', '1.0.0' );
define( 'RS_SMART_LAYOUT_SC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RS_SMART_LAYOUT_SC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RS_SMART_LAYOUT_SC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * 主外掛類別
 */
class RS_Smart_Layout_Shortcode {
	/**
	 * 單例實例
	 *
	 * @var RS_Smart_Layout_Shortcode
	 */
	private static $instance = null;

	/**
	 * 取得單例實例
	 *
	 * @return RS_Smart_Layout_Shortcode
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 建構函式
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * 載入相依檔案
	 */
	private function load_dependencies() {
		// 載入 ACF 欄位註冊
		require_once RS_SMART_LAYOUT_SC_PLUGIN_DIR . 'includes/rs-acf-fields-sc.php';

		// 載入短代碼處理
		require_once RS_SMART_LAYOUT_SC_PLUGIN_DIR . 'includes/rs-shortcode-sc.php';

		// 載入渲染邏輯
		require_once RS_SMART_LAYOUT_SC_PLUGIN_DIR . 'includes/rs-template-render-sc.php';

		// 載入資源載入
		require_once RS_SMART_LAYOUT_SC_PLUGIN_DIR . 'includes/rs-assets-sc.php';
	}

	/**
	 * 初始化鉤子
	 */
	private function init_hooks() {
		// 載入文本域
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * 載入翻譯
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'rs-smart-layout-sc',
			false,
			dirname( RS_SMART_LAYOUT_SC_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * 啟動外掛
 *
 * @return RS_Smart_Layout_Shortcode
 */
function rs_smart_layout_shortcode() {
	return RS_Smart_Layout_Shortcode::get_instance();
}

// 初始化外掛
rs_smart_layout_shortcode();
