<?php
/**
 * 測試 shortcode 是否註冊
 * 將此內容貼入 Code Snippets，Run everywhere
 */

add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        $exists = shortcode_exists('rscat_categorylist');
        $status = $exists ? 'REGISTERED ✓' : 'NOT REGISTERED ✗';

        echo '<div style="position: fixed; bottom: 0; left: 0; background: #000; color: #fff; padding: 10px; z-index: 99999; font-family: monospace;">';
        echo 'Shortcode [rscat_categorylist]: ' . $status;
        echo '</div>';

        echo '<script>console.log("[Shortcode Test] rscat_categorylist is ' . ($exists ? 'REGISTERED' : 'NOT REGISTERED') . '");</script>';
    }
});
