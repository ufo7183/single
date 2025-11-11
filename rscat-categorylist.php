<?php
/**
 * ========================================
 * RSCAT 分類列表 Shortcode (動態設定版)
 * ========================================
 * Shortcode 使用方式：
 * [rscat_categorylist] (使用後台第1組設定)
 * [rscat_categorylist mapping="2"] (使用後台第2組設定)
 * [rscat_categorylist mapping="3" parent_id="5"]
 * [rscat_categorylist post_type="product" taxonomy="product_category"] (覆寫)
 *
 * 支援多種文章類型的階層式分類列表顯示
 * 水平排列，支援水平滾動
 * 後台可動態設定文章類型對應與CSS樣式
 * ========================================
 */

// 註冊後台設定頁面
if (!function_exists('rscat_categorylist_add_admin_menu')) {
    function rscat_categorylist_add_admin_menu() {
        add_options_page(
            'RSCAT 分類列表設定',
            'RSCAT 分類列表',
            'manage_options',
            'rscat-categorylist-settings',
            'rscat_categorylist_settings_page'
        );
    }
    add_action('admin_menu', 'rscat_categorylist_add_admin_menu');
}

// 後台設定頁面
if (!function_exists('rscat_categorylist_settings_page')) {
    function rscat_categorylist_settings_page() {
        // 儲存設定
        if (isset($_POST['rscat_save_settings']) && check_admin_referer('rscat_categorylist_settings')) {
            $settings = array(
                'post_type_mappings' => array(),
                'css_styles' => array(
                    'button_normal' => sanitize_textarea_field($_POST['css_button_normal']),
                    'text_normal' => sanitize_textarea_field($_POST['css_text_normal']),
                    'button_active' => sanitize_textarea_field($_POST['css_button_active']),
                    'text_active' => sanitize_textarea_field($_POST['css_text_active']),
                )
            );

            // 處理10組文章類型對應
            for ($i = 1; $i <= 10; $i++) {
                $post_type = isset($_POST['post_type_' . $i]) ? sanitize_text_field($_POST['post_type_' . $i]) : '';
                $taxonomy = isset($_POST['taxonomy_' . $i]) ? sanitize_text_field($_POST['taxonomy_' . $i]) : '';

                $settings['post_type_mappings'][] = array(
                    'post_type' => $post_type,
                    'taxonomy' => $taxonomy
                );
            }

            update_option('rscat_categorylist_settings', $settings);
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }

        // 讀取現有設定
        $settings = get_option('rscat_categorylist_settings', array());
        $mappings = isset($settings['post_type_mappings']) ? $settings['post_type_mappings'] : array();
        $css = isset($settings['css_styles']) ? $settings['css_styles'] : array();

        // 預設 CSS 值
        $default_css = rscat_categorylist_get_default_css();

        ?>
        <div class="wrap">
            <h1>RSCAT 分類列表設定</h1>

            <form method="post" action="">
                <?php wp_nonce_field('rscat_categorylist_settings'); ?>

                <h2>文章類型與分類法對應</h2>
                <p class="description">不填寫則預設使用 post + category</p>

                <table class="form-table">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <?php
                        $post_type = isset($mappings[$i-1]['post_type']) ? $mappings[$i-1]['post_type'] : '';
                        $taxonomy = isset($mappings[$i-1]['taxonomy']) ? $mappings[$i-1]['taxonomy'] : '';
                        ?>
                        <tr>
                            <th scope="row">第 <?php echo $i; ?> 組</th>
                            <td>
                                <label>
                                    文章類型：
                                    <input type="text" name="post_type_<?php echo $i; ?>" value="<?php echo esc_attr($post_type); ?>" class="regular-text" placeholder="例如: post, product, portfolio">
                                </label>
                                <br><br>
                                <label>
                                    自訂分類：
                                    <input type="text" name="taxonomy_<?php echo $i; ?>" value="<?php echo esc_attr($taxonomy); ?>" class="regular-text" placeholder="例如: category, product_category">
                                </label>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table>

                <hr>

                <h2>CSS 樣式設定</h2>
                <p class="description">請輸入完整的 CSS 屬性，以分號結尾</p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="css_button_normal">Normal 狀態 - 按鈕樣式</label></th>
                        <td>
                            <textarea name="css_button_normal" id="css_button_normal" rows="8" class="large-text code"><?php
                                echo esc_textarea(isset($css['button_normal']) && !empty($css['button_normal']) ? $css['button_normal'] : $default_css['button_normal']);
                            ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="css_text_normal">Normal 狀態 - 文字樣式</label></th>
                        <td>
                            <textarea name="css_text_normal" id="css_text_normal" rows="8" class="large-text code"><?php
                                echo esc_textarea(isset($css['text_normal']) && !empty($css['text_normal']) ? $css['text_normal'] : $default_css['text_normal']);
                            ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="css_button_active">Active/Hover 狀態 - 按鈕樣式</label></th>
                        <td>
                            <textarea name="css_button_active" id="css_button_active" rows="8" class="large-text code"><?php
                                echo esc_textarea(isset($css['button_active']) && !empty($css['button_active']) ? $css['button_active'] : $default_css['button_active']);
                            ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="css_text_active">Active/Hover 狀態 - 文字樣式</label></th>
                        <td>
                            <textarea name="css_text_active" id="css_text_active" rows="8" class="large-text code"><?php
                                echo esc_textarea(isset($css['text_active']) && !empty($css['text_active']) ? $css['text_active'] : $default_css['text_active']);
                            ?></textarea>
                        </td>
                    </tr>
                </table>

                <?php submit_button('儲存設定', 'primary', 'rscat_save_settings'); ?>
            </form>
        </div>

        <style>
        .form-table th {
            width: 200px;
        }
        .form-table textarea.code {
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }
        </style>
        <?php
    }
}

// 取得預設 CSS
if (!function_exists('rscat_categorylist_get_default_css')) {
    function rscat_categorylist_get_default_css() {
        return array(
            'button_normal' => "background: #F5F3F3;\npadding: 10px 25px;\njustify-content: center;\nalign-items: center;\ngap: 10px;\nborder-radius: 23px;\nborder: none;",
            'text_normal' => "color: #292929;\nfont-feature-settings: 'case' on;\nfont-family: \"Noto Sans CJK TC\", sans-serif;\nfont-size: 16px;\nfont-style: normal;\nfont-weight: 400;\nline-height: 160%;\nletter-spacing: 1.6px;\ntext-align: center;",
            'button_active' => "background: #E83743;\npadding: 10px 25px;\njustify-content: center;\nalign-items: center;\ngap: 10px;\nborder-radius: 23px;\nborder: none;",
            'text_active' => "color: #FFF;\nfont-feature-settings: 'case' on;\nfont-family: \"Noto Sans CJK TC\", sans-serif;\nfont-size: 16px;\nfont-style: normal;\nfont-weight: 400;\nline-height: 160%;\nletter-spacing: 1.6px;\ntext-align: center;",
        );
    }
}

// 註冊 shortcode
if (!function_exists('rscat_categorylist_shortcode_handler')) {
    function rscat_categorylist_shortcode_handler($atts) {
        // 解析 shortcode 參數
        $atts = shortcode_atts(array(
            'mapping' => '1',
            'post_type' => '',
            'taxonomy' => '',
            'parent_id' => '',
            'auto_current_children' => false,
            'current_level' => 0,
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'include' => '',
            'exclude' => '',
        ), $atts, 'rscat_categorylist');

        // 讀取後台設定
        $settings = get_option('rscat_categorylist_settings', array());
        $mappings = isset($settings['post_type_mappings']) ? $settings['post_type_mappings'] : array();
        $css_styles = isset($settings['css_styles']) ? $settings['css_styles'] : array();

        // 決定使用哪一組設定
        $mapping_index = intval($atts['mapping']) - 1;

        // 如果 shortcode 有指定 post_type 或 taxonomy，優先使用
        if (empty($atts['post_type']) && isset($mappings[$mapping_index]['post_type']) && !empty($mappings[$mapping_index]['post_type'])) {
            $post_type = $mappings[$mapping_index]['post_type'];
        } elseif (!empty($atts['post_type'])) {
            $post_type = $atts['post_type'];
        } else {
            // 找第一組有值的
            $post_type = 'post';
            foreach ($mappings as $map) {
                if (!empty($map['post_type'])) {
                    $post_type = $map['post_type'];
                    break;
                }
            }
        }

        if (empty($atts['taxonomy']) && isset($mappings[$mapping_index]['taxonomy']) && !empty($mappings[$mapping_index]['taxonomy'])) {
            $taxonomy = $mappings[$mapping_index]['taxonomy'];
        } elseif (!empty($atts['taxonomy'])) {
            $taxonomy = $atts['taxonomy'];
        } else {
            // 找第一組有值的，或根據 post_type 自動匹配
            $taxonomy = '';
            foreach ($mappings as $map) {
                if (!empty($map['taxonomy'])) {
                    $taxonomy = $map['taxonomy'];
                    break;
                }
            }

            // 如果還是空的，使用自動匹配
            if (empty($taxonomy)) {
                $taxonomy_mapping = array(
                    'post' => 'category',
                    'teach' => 'teachcat',
                    'portfolio' => 'portfolio_category',
                    'event' => 'event_category',
                    'project' => 'project_category',
                );
                $taxonomy = isset($taxonomy_mapping[$post_type]) ? $taxonomy_mapping[$post_type] : 'category';
            }
        }

        $post_type = sanitize_text_field($post_type);
        $taxonomy = sanitize_text_field($taxonomy);
        $manual_parent_id = $atts['parent_id'] !== '' ? intval($atts['parent_id']) : null;
        $auto_current_children = filter_var($atts['auto_current_children'], FILTER_VALIDATE_BOOLEAN);
        $current_level = intval($atts['current_level']);
        $hide_empty = filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN);
        $orderby = sanitize_text_field($atts['orderby']);
        $order = strtoupper(sanitize_text_field($atts['order']));
        $include = sanitize_text_field($atts['include']);
        $exclude = sanitize_text_field($atts['exclude']);

        // 驗證 order 參數
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'ASC';
        }

        // 驗證 post_type 是否存在
        if (!post_type_exists($post_type)) {
            return '<p>指定的文章類型不存在。</p>';
        }

        // 驗證 taxonomy 是否存在
        if (!taxonomy_exists($taxonomy)) {
            return '<p>指定的分類法不存在。</p>';
        }

        // 如果指定了 manual parent_id，驗證父分類是否存在
        if ($manual_parent_id !== null && $manual_parent_id > 0) {
            $parent_term = get_term($manual_parent_id, $taxonomy);
            if (is_wp_error($parent_term) || !$parent_term) {
                return '<p>指定的父分類 ID 不存在。</p>';
            }
        }

        // 自動偵測當前分類層級邏輯
        $parent_id = 0;
        $current_term = null;
        $is_current_tax_page = is_tax($taxonomy) || is_category() || is_tag();

        if ($is_current_tax_page) {
            $queried_object = get_queried_object();
            if ($queried_object instanceof WP_Term && $queried_object->taxonomy === $taxonomy) {
                $current_term = $queried_object;
            }
        }

        // 參數優先級：manual parent_id > auto_current_children > current_level > 預設
        if ($manual_parent_id !== null) {
            $parent_id = $manual_parent_id;
        } elseif ($auto_current_children && $current_term) {
            $parent_id = $current_term->term_id;
        } elseif ($current_level > 0 && $current_term) {
            $ancestors = get_ancestors($current_term->term_id, $taxonomy);
            $current_term_level = count($ancestors) + 1;

            if ($current_level > $current_term_level) {
                $parent_id = $current_term->term_id;
            } elseif ($current_level === $current_term_level) {
                $parent_id = $current_term->parent;
            } elseif ($current_level < $current_term_level && $current_level > 0) {
                $target_parent_index = count($ancestors) - $current_level;
                if ($target_parent_index >= 0 && isset($ancestors[$target_parent_index])) {
                    $parent_id = $ancestors[$target_parent_index];
                } else {
                    $parent_id = 0;
                }
            }
        } else {
            $parent_id = 0;
        }

        ob_start();

        // 建立查詢參數
        $query_args = array(
            'taxonomy' => $taxonomy,
            'parent' => $parent_id,
            'hide_empty' => $hide_empty,
            'orderby' => $orderby,
            'order' => $order,
        );

        if (!empty($include)) {
            $include_ids = array_map('intval', explode(',', $include));
            $query_args['include'] = $include_ids;
        }

        if (!empty($exclude)) {
            $exclude_ids = array_map('intval', explode(',', $exclude));
            $query_args['exclude'] = $exclude_ids;
        }

        if ($orderby === 'menu_order') {
            global $wpdb;
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->terms}");
            if (!in_array('menu_order', $columns)) {
                $query_args['orderby'] = 'name';
            }
        }

        // 執行查詢
        $terms = get_terms($query_args);

        if (is_wp_error($terms)) {
            echo '<p>查詢分類時發生錯誤：' . esc_html($terms->get_error_message()) . '</p>';
            return ob_get_clean();
        }

        if (empty($terms)) {
            echo '<p>沒有找到相關分類。</p>';
            return ob_get_clean();
        }

        // 取得當前頁面資訊用於 active 狀態判斷
        $active_term_id = 0;
        $active_term_ancestors = array();

        if ($is_current_tax_page && $current_term) {
            $active_term_id = $current_term->term_id;
            $active_term_ancestors = get_ancestors($active_term_id, $taxonomy);
        }

        $active_link_class = 'rscat-category-link-active';

        // 輸出樣式（僅輸出一次）
        static $rscat_categorylist_styles_loaded = false;
        if (!$rscat_categorylist_styles_loaded) {
            // 取得 CSS 設定
            $default_css = rscat_categorylist_get_default_css();
            $button_normal_css = !empty($css_styles['button_normal']) ? $css_styles['button_normal'] : $default_css['button_normal'];
            $text_normal_css = !empty($css_styles['text_normal']) ? $css_styles['text_normal'] : $default_css['text_normal'];
            $button_active_css = !empty($css_styles['button_active']) ? $css_styles['button_active'] : $default_css['button_active'];
            $text_active_css = !empty($css_styles['text_active']) ? $css_styles['text_active'] : $default_css['text_active'];

            echo '<style>';
            echo '.rscat-category-list-container { width: 100%; overflow: hidden; position: relative; }';
            echo '.rscat-category-list { list-style: none; padding: 0; margin: 0; display: flex; align-items: center; gap: 10px; overflow-x: auto; overflow-y: hidden; white-space: nowrap; scrollbar-width: none; -ms-overflow-style: none; }';
            echo '.rscat-category-list::-webkit-scrollbar { display: none; }';
            echo '.rscat-category-item { margin: 0; padding: 0; flex-shrink: 0; }';
            echo '.rscat-category-link { display: flex; ' . $button_normal_css . ' ' . $text_normal_css . ' text-decoration: none; white-space: nowrap; cursor: pointer; }';
            echo '.rscat-category-link:hover, .rscat-category-link:focus, .rscat-category-link.rscat-category-link-active { display: flex; ' . $button_active_css . ' ' . $text_active_css . ' }';
            echo '</style>';

            $rscat_categorylist_styles_loaded = true;
        }

        echo '<div class="rscat-category-list-container">';
        echo '<ul class="rscat-category-list">';

        // 添加「全部」按鈕
        $all_button_url = '';
        $all_button_is_active = false;

        if ($parent_id > 0) {
            $all_button_url = get_term_link($parent_id, $taxonomy);
            if ($is_current_tax_page && $current_term && $current_term->term_id === $parent_id) {
                $all_button_is_active = true;
            }
        } else {
            if ($post_type === 'post') {
                $page_for_posts = get_option('page_for_posts');
                if ($page_for_posts) {
                    $all_button_url = get_permalink($page_for_posts);
                } else {
                    $all_button_url = home_url('/');
                }
            } else {
                $all_button_url = get_post_type_archive_link($post_type);
                if (!$all_button_url) {
                    $all_button_url = home_url('/');
                }
            }

            if (!$is_current_tax_page || !$current_term) {
                $all_button_is_active = true;
            } elseif (is_post_type_archive($post_type)) {
                $all_button_is_active = true;
            }
        }

        $all_button_classes = ['rscat-category-link'];
        if ($all_button_is_active) {
            $all_button_classes[] = $active_link_class;
        }

        $all_escaped_classes = esc_attr(implode(' ', $all_button_classes));
        $all_li_active_class = $all_button_is_active ? ' current-category' : '';

        echo '<li class="rscat-category-item' . esc_attr($all_li_active_class) . '">';
        echo '<a href="' . esc_url($all_button_url) . '" class="' . $all_escaped_classes . '">全部</a>';
        echo '</li>';

        // 輸出分類按鈕
        foreach ($terms as $term) {
            $term_id = $term->term_id;
            $term_link = get_term_link($term);
            $term_name = $term->name;

            $is_current_term_active = false;

            if ($is_current_tax_page && $current_term) {
                if ($active_term_id === $term_id) {
                    $is_current_term_active = true;
                } elseif (in_array($term_id, $active_term_ancestors)) {
                    $is_current_term_active = true;
                }
            }

            $link_classes = ['rscat-category-link'];
            if ($is_current_term_active) {
                $link_classes[] = $active_link_class;
            }

            $escaped_classes = esc_attr(implode(' ', $link_classes));
            $li_active_class = $is_current_term_active ? ' current-category' : '';

            if (is_wp_error($term_link)) {
                continue;
            }

            echo '<li class="rscat-category-item' . esc_attr($li_active_class) . '">';
            echo '<a href="' . esc_url($term_link) . '" class="' . $escaped_classes . '">' . esc_html($term_name) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';

        return ob_get_clean();
    }
}

if (!shortcode_exists('rscat_categorylist')) {
    add_shortcode('rscat_categorylist', 'rscat_categorylist_shortcode_handler');
}
