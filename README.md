# WordPress Shortcode: `rs-search` (階層式自定義分類快篩)

> 雙層階層分類快篩系統，專為 WordPress 自定義分類 `searchtag` 設計。

---

## 📋 目錄

- [功能簡介](#功能簡介)
- [系統需求](#系統需求)
- [安裝方式](#安裝方式)
- [使用方法](#使用方法)
- [參數說明](#參數說明)
- [檔案結構](#檔案結構)
- [技術規格](#技術規格)
- [安全性](#安全性)
- [瀏覽器支援](#瀏覽器支援)
- [無障礙功能](#無障礙功能)
- [常見問題](#常見問題)
- [開發與除錯](#開發與除錯)
- [版本歷史](#版本歷史)
- [授權資訊](#授權資訊)

---

## 🎯 功能簡介

**RS Search** 提供一個輕量級、高效能的雙層階層分類瀏覽系統：

### 核心功能

✅ **雙層階層導覽**
- 第一層：顯示父分類標籤，點擊後以 AJAX 載入子分類
- 第二層：顯示子分類標籤，點擊後導向該分類頁面
- **顯示所有分類**：包含空分類（沒有文章的分類也會顯示）

✅ **AJAX 動態載入**
- 無需頁面刷新
- 支援未登入使用者 (`wp_ajax_nopriv_`)
- 錯誤處理與空狀態提示

✅ **無障礙支援**
- ARIA 屬性完整
- 鍵盤導航（左右鍵、Enter、Space）
- 螢幕閱讀器友善

✅ **響應式設計**
- 手機、平板、桌面完全適配
- 觸控裝置優化（目標大小 ≥ 44px）
- 支援深色模式與高對比模式

✅ **高度可配置**
- 自定義根節點
- 排序選項
- 包含/排除特定分類
- 顯示文章計數（可選）

---

## 💻 系統需求

### 必要條件

| 項目 | 需求 |
|------|------|
| **WordPress** | 5.0+ |
| **PHP** | 7.0+ |
| **Taxonomy** | 自定義階層分類 `searchtag` 必須已存在 |
| **Post Type** | `post` 或其他已關聯 `searchtag` 的文章類型 |

### 建議環境

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ 或 MariaDB 10.3+
- 支援 `fetch` API 的瀏覽器（現代瀏覽器）

---

## 📦 安裝方式

### 方法 1：透過 Code Snippets 外掛（推薦）

1. 安裝並啟用 [Code Snippets](https://wordpress.org/plugins/code-snippets/) 外掛
2. 前往 **Snippets** → **Add New**
3. 複製 `rs-search.php` 的完整內容貼入
4. 設定：
   - **Name**: RS Search Shortcode
   - **Run snippet**: Only run in site front-end
5. 點擊 **Save Changes and Activate**

### 方法 2：主題 functions.php

```php
// 在主題或子主題的 functions.php 中引入
require_once get_stylesheet_directory() . '/rs-search/rs-search.php';
```

### 方法 3：MU Plugin（多站點推薦）

1. 將完整資料夾上傳至 `wp-content/mu-plugins/rs-search/`
2. 在 `wp-content/mu-plugins/` 建立 `rs-search-loader.php`：

```php
<?php
/**
 * Plugin Name: RS Search Loader
 */
require_once WPMU_PLUGIN_DIR . '/rs-search/rs-search.php';
```

### 檔案結構部署

確保以下檔案結構正確：

```
rs-search/
├── rs-search.php          # 主程式（必要）
├── assets/
│   ├── rs-search.css      # 樣式表（必要）
│   └── rs-search.js       # 前端腳本（必要）
├── README.md              # 說明文件
└── LICENSE                # 授權文件（可選）
```

---

## 🚀 使用方法

### 基本用法

在任何頁面、文章或小工具中插入：

```
[rs-search]
```

### 進階用法

```
[rs-search root="123" orderby="name" order="ASC" show_count="true" class="custom-class"]
```

### 在 PHP 範本中使用

```php
<?php echo do_shortcode('[rs-search]'); ?>
```

### 在彈窗（Pop/Modal）中使用

```html
<!-- 彈窗容器 -->
<div id="searchModal" class="modal">
    <div class="modal-content">
        <h2>分類快篩</h2>
        [rs-search orderby="term_id" order="ASC"]
    </div>
</div>
```

---

## ⚙️ 參數說明

### 完整參數列表

| 參數 | 型別 | 預設值 | 說明 |
|------|------|--------|------|
| `root` | int | `0` | 第一層分類的根節點 term_id（0 = 所有頂層） |
| `include` | string | `''` | 僅顯示這些 term_id（逗號分隔，例如 `"12,34,56"`） |
| `exclude` | string | `''` | 排除這些 term_id（逗號分隔） |
| `orderby` | string | `'name'` | 排序欄位（`name`, `slug`, `term_id`, `count`） |
| `order` | string | `'ASC'` | 排序方向（`ASC`, `DESC`） |
| `show_count` | bool | `false` | 是否在第二層顯示文章數 |
| `class` | string | `''` | 額外的 CSS 類別名稱 |

### 參數範例

#### 1. 指定根分類

僅顯示分類 ID 為 `50` 的子分類：

```
[rs-search root="50"]
```

#### 2. 排序設定

按文章數量降序排列：

```
[rs-search orderby="count" order="DESC"]
```

#### 3. 包含特定分類

僅顯示 ID 為 10、20、30 的分類：

```
[rs-search include="10,20,30"]
```

#### 4. 排除特定分類

排除 ID 為 5、15 的分類：

```
[rs-search exclude="5,15"]
```

#### 5. 顯示文章計數

```
[rs-search show_count="true"]
```

輸出範例：`子分類 A (8)`

#### 6. 自定義樣式類別

```
[rs-search class="my-custom-search"]
```

可在 CSS 中針對 `.my-custom-search` 覆寫樣式。

---

## 📁 檔案結構

### v1.0.2+ 內嵌版本（推薦）

```
rs-search/
│
├── rs-search.php                 # ⭐ 唯一必要檔案（內含 CSS 與 JS）
│   ├── RS_Search_Shortcode       # 主類別
│   │   ├── register_shortcode()  # 註冊 shortcode
│   │   ├── shortcode_handler()   # 渲染 HTML
│   │   ├── ajax_load_children()  # AJAX 處理器
│   │   ├── get_inline_styles()   # 內嵌 CSS
│   │   └── get_inline_script()   # 內嵌 JavaScript
│   └── 初始化單例
│
└── README.md                     # 說明文件（本檔案）
```

**✨ 優勢**：
- 只需要一個 `rs-search.php` 檔案
- 完美支援 Code Snippets 外掛
- 無需處理檔案路徑問題
- 即插即用，複製程式碼即可使用

### v1.0.1 舊版本結構（已棄用）

```
rs-search/
│
├── rs-search.php                 # 主程式檔案
├── assets/rs-search.css          # 外部樣式表（已棄用）
├── assets/rs-search.js           # 外部腳本（已棄用）
└── README.md                     # 說明文件
```

**⚠️ 注意**：v1.0.0 和 v1.0.1 版本需要 assets 資料夾，在 Code Snippets 中會有路徑問題，建議升級至 v1.0.2+。

---

## 🔧 技術規格

### PHP 架構

- **設計模式**: Singleton（單例）
- **命名空間**: 無（避免相容性問題）
- **Hook**: `init`, `wp_ajax_*`（v1.0.2+ 移除了 `wp_enqueue_scripts`）
- **資源載入**: 內嵌 CSS 與 JavaScript（無需外部檔案）
- **安全性**: Nonce 驗證、資料清理、輸出轉義

### JavaScript 架構

- **模式**: 原生 JavaScript（無依賴）
- **API**: Fetch API（非 jQuery）
- **相容性**: ES5+ (可透過 Babel 轉譯支援 IE11)
- **事件**: 事件代理、鍵盤事件

### CSS 架構

- **方法論**: BEM (Block Element Modifier)
- **字型**: Noto Sans TC（需自行載入或使用系統字型）
- **單位**: px, rem（可調整）
- **特性**: Flexbox、媒體查詢、CSS 變數（可選）

### AJAX 端點

#### 請求

```
POST /wp-admin/admin-ajax.php
Content-Type: application/x-www-form-urlencoded

action=rs_search_load_children
nonce={nonce_value}
parent={term_id}
taxonomy=searchtag
```

#### 回應（成功）

```json
{
  "ok": true,
  "children": [
    {
      "id": 12,
      "name": "子分類 A",
      "slug": "child-a",
      "url": "https://example.com/searchtag/child-a/",
      "count": 8
    },
    {
      "id": 13,
      "name": "子分類 B",
      "slug": "child-b",
      "url": "https://example.com/searchtag/child-b/",
      "count": 3
    }
  ]
}
```

#### 回應（失敗）

```json
{
  "ok": false,
  "message": "參數錯誤：無效的父分類 ID"
}
```

---

## 🔒 安全性

### 已實作的安全措施

✅ **Nonce 驗證**
- 所有 AJAX 請求皆透過 `wp_create_nonce()` 與 `check_ajax_referer()` 驗證
- Nonce 名稱: `rs-search`

✅ **資料清理**
- `absint()`: 整數參數
- `sanitize_key()`: taxonomy 名稱
- `sanitize_html_class()`: CSS 類別

✅ **輸出轉義**
- `esc_html()`: 一般文字
- `esc_attr()`: HTML 屬性
- `esc_url()`: URL 連結

✅ **權限控制**
- 只讀取公開分類
- 支援未登入使用者（`wp_ajax_nopriv_`）
- 不回傳敏感資訊

✅ **Taxonomy 白名單**
- 僅允許查詢 `searchtag`
- 拒絕任意 taxonomy 查詢

### 建議的額外措施

🔐 **內容安全政策 (CSP)**

```php
add_action('send_headers', function() {
    header("Content-Security-Policy: default-src 'self';");
});
```

🔐 **速率限制**

可搭配外掛如 [WP Limit Login Attempts](https://wordpress.org/plugins/wp-limit-login-attempts/)。

🔐 **HTTPS 強制**

```php
add_action('template_redirect', function() {
    if (!is_ssl()) {
        wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
        exit();
    }
});
```

---

## 🌐 瀏覽器支援

### 完全支援

| 瀏覽器 | 最低版本 |
|--------|----------|
| Chrome | 42+ |
| Firefox | 39+ |
| Safari | 10.1+ |
| Edge | 14+ |
| Opera | 29+ |
| iOS Safari | 10.3+ |
| Android Chrome | 42+ |

### 部分支援

| 瀏覽器 | 版本 | 限制 |
|--------|------|------|
| IE 11 | - | 需要 Fetch Polyfill |

### Polyfill 建議

對於舊瀏覽器，可引入：

```html
<script src="https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.js"></script>
```

---

## ♿ 無障礙功能

### ARIA 屬性

```html
<!-- 第一層 -->
<div role="tablist" aria-label="第一層分類">
    <button role="tab" aria-selected="true" aria-controls="level2-container">
        分類名稱
    </button>
</div>

<!-- 第二層 -->
<div role="list" aria-live="polite" aria-busy="false">
    <a role="listitem">子分類</a>
</div>
```

### 鍵盤操作

| 按鍵 | 功能 |
|------|------|
| `Tab` | 切換焦點 |
| `Enter` / `Space` | 啟動第一層分類 |
| `←` | 上一個第一層分類 |
| `→` | 下一個第一層分類 |

### 螢幕閱讀器

- 使用 `aria-live="polite"` 通知第二層內容更新
- 使用 `aria-busy="true"` 標示載入狀態
- 提供有意義的標籤文字

---

## ❓ 常見問題

### Q1: Taxonomy `searchtag` 不存在怎麼辦？

**A**: 確保已註冊該 taxonomy。可在 `functions.php` 中新增：

```php
add_action('init', function() {
    register_taxonomy('searchtag', 'post', array(
        'hierarchical' => true,
        'label' => '搜尋標籤',
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'searchtag'),
    ));
});
```

### Q2: 第二層無法載入（AJAX 錯誤）

**A**: 檢查以下項目：

1. 瀏覽器控制台是否有錯誤訊息
2. 確認 `admin-ajax.php` 路徑正確
3. 檢查 Nonce 是否有效（24 小時有效期）
4. 確認伺服器允許 POST 請求

### Q3: 樣式沒有生效

**A**: 確保：

1. 檔案路徑正確（`assets/rs-search.css`）
2. 主題沒有覆寫相同的 CSS 選擇器
3. 使用瀏覽器開發者工具檢查 CSS 載入

### Q4: 如何更改顏色？

**A**: 在主題的 `style.css` 或自訂 CSS 中覆寫：

```css
.rs-search__l1-tag:hover,
.rs-search__l1-tag.is-active {
    background: #your-color !important;
}

.rs-search__l2-tag:hover {
    background: #your-color !important;
}
```

### Q5: 支援多個 Shortcode 在同一頁面嗎？

**A**: 是的，完全支援。每個元件獨立運作。

### Q6: 可以只顯示第一層，不要 AJAX 嗎?

**A**: 目前設計為雙層互動。若需要單層靜態，建議使用原生 `wp_list_categories()`。

### Q7: 如何偵錯 AJAX 請求？

**A**: 開啟瀏覽器開發者工具 → Network 標籤 → 篩選 XHR → 檢查回應內容。

---

## 🛠️ 開發與除錯

### 開發模式

在 `wp-config.php` 啟用除錯：

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 測試資料建立

```php
// 建立測試分類
wp_insert_term('父分類 A', 'searchtag', array('parent' => 0));
wp_insert_term('子分類 A1', 'searchtag', array('parent' => {父分類A的ID}));
wp_insert_term('子分類 A2', 'searchtag', array('parent' => {父分類A的ID}));
```

### 常見除錯指令

```php
// 檢查 taxonomy 是否存在
var_dump(taxonomy_exists('searchtag'));

// 列出所有分類
$terms = get_terms(array('taxonomy' => 'searchtag', 'hide_empty' => false));
print_r($terms);

// 檢查 Nonce
var_dump(wp_verify_nonce($_POST['nonce'], 'rs-search'));
```

---

## 📝 版本歷史

### v1.0.2 (2025-01-12)

**重大更新 - 修正 CSS 與 JS 載入問題**

🐛 **問題修正**
- 修正透過 Code Snippets 使用時 CSS 完全無效的問題
- 修正 JavaScript 無法載入導致 AJAX 功能失效的問題
- 修正樣式錯誤導致第一層分類顯示不正確的問題

🔧 **技術變更**
- **改用內嵌方式**：將 CSS 和 JavaScript 直接嵌入 PHP 檔案中
- 移除對外部 assets 資料夾的依賴
- 優化程式碼，使用壓縮版 CSS 和 JS
- 加入唯一 ID 機制，支援同一頁面多個 shortcode 實例
- 加入 `$styles_printed` 靜態變數，避免重複輸出 CSS

💡 **使用優勢**
- **單檔案部署**：只需要 `rs-search.php` 一個檔案即可運作
- **完美支援 Code Snippets**：無需處理檔案路徑問題
- **即插即用**：複製程式碼即可使用，無需額外設定

---

### v1.0.1 (2025-01-12)

**功能更新**

✨ **新功能**
- 顯示空分類：即使分類下沒有文章，也會在第一層和第二層顯示
- 改進使用者體驗：使用者可以看到完整的分類結構

🔧 **技術變更**
- 將 `hide_empty` 參數從 `true` 改為 `false`
- 適用於第一層分類查詢和 AJAX 子分類查詢

---

### v1.0.0 (2025-01-12)

**初始版本**

✨ **新功能**
- 雙層階層分類瀏覽
- AJAX 動態載入
- 鍵盤導航支援
- 完整無障礙功能
- 響應式設計
- 支援未登入使用者

🔒 **安全性**
- Nonce 驗證
- 資料清理與轉義
- Taxonomy 白名單

📚 **文件**
- 完整 README
- 程式碼註解
- 使用範例

---

## 📄 授權資訊

**RS Search Shortcode**
Copyright (c) 2025

本專案採用 **MIT License** 授權。

```
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🤝 貢獻與支援

### 回報問題

如發現錯誤或有功能建議，請提交 Issue。

### 技術支援

- 📧 Email: support@example.com
- 📚 文件: [https://docs.example.com](https://docs.example.com)
- 💬 社群: [WordPress.org 論壇](https://wordpress.org/support/)

---

## 🔗 相關資源

- [WordPress Shortcode API](https://developer.wordpress.org/plugins/shortcodes/)
- [WordPress AJAX](https://codex.wordpress.org/AJAX_in_Plugins)
- [BEM 命名規範](http://getbem.com/)
- [ARIA 無障礙指南](https://www.w3.org/WAI/ARIA/apg/)
- [Noto Sans TC 字型](https://fonts.google.com/specimen/Noto+Sans+TC)

---

**製作**: RS Search Team
**最後更新**: 2025-01-12
**版本**: 1.0.2
