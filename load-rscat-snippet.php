<?php
/**
 * Code Snippets 載入代碼
 *
 * 使用方式：
 * 1. 進入 WordPress 後台 > Snippets
 * 2. 新增 Snippet
 * 3. 複製以下代碼貼入
 * 4. 設定為 "Run everywhere" 或 "Only on site front-end"
 * 5. 啟用
 */

// 載入 RSCAT 分類列表功能
$rscat_file = __DIR__ . '/rscat-categorylist.php';

if (file_exists($rscat_file)) {
    require_once $rscat_file;
    error_log('[RSCAT Loader] File loaded successfully: ' . $rscat_file);
} else {
    error_log('[RSCAT Loader] ERROR - File not found: ' . $rscat_file);

    // 嘗試其他可能的路徑
    $alternative_paths = array(
        ABSPATH . 'rscat-categorylist.php',
        get_stylesheet_directory() . '/rscat-categorylist.php',
        get_template_directory() . '/rscat-categorylist.php',
        WP_CONTENT_DIR . '/rscat-categorylist.php',
    );

    foreach ($alternative_paths as $alt_path) {
        if (file_exists($alt_path)) {
            require_once $alt_path;
            error_log('[RSCAT Loader] File loaded from alternative path: ' . $alt_path);
            break;
        }
    }
}

// 驗證 shortcode 是否註冊成功
add_action('init', function() {
    if (shortcode_exists('rscat_categorylist')) {
        error_log('[RSCAT Loader] Shortcode [rscat_categorylist] registered successfully!');
    } else {
        error_log('[RSCAT Loader] ERROR - Shortcode [rscat_categorylist] NOT registered!');
    }
}, 999);

// 前端顯示 shortcode 狀態（僅管理員可見）
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        $status = shortcode_exists('rscat_categorylist') ? 'REGISTERED' : 'NOT REGISTERED';
        echo '<script>console.log("[RSCAT Loader] Shortcode status: ' . $status . '");</script>';
    }
});
