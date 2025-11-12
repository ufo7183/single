# RS Smart Layout Shortcode

基於短代碼的彈性版型系統，支援所有分類法，可自由控制哪些分類使用智慧卡片排版。

## 功能特色

- ✅ **短代碼驅動** - 使用 `[rs_smart_layout]` 手動置入，完全自由控制
- ✅ **支援所有分類法** - WooCommerce 商品分類、一般分類、自訂分類法
- ✅ **智慧判定** - 自動判斷顯示子分類或文章
- ✅ **麵包屑導航** - 父分類可點擊，顏色區分（僅分類頁面）
- ✅ **響應式設計** - 桌面 4 欄、手機/平板 2 欄
- ✅ **ACF 整合** - 使用 ACF 自訂分類圖片
- ✅ **彈性參數** - 可自訂列數、每頁數量、最大寬度等

## 安裝需求

- WordPress 5.0+
- PHP 7.4+
- Advanced Custom Fields (ACF) 外掛

## 短代碼使用

### 基本使用

```
[rs_smart_layout]
```

在分類頁面使用，自動偵測當前分類。

### 參數說明

| 參數 | 說明 | 預設值 | 範例 |
|------|------|--------|------|
| `category_id` | 指定分類 ID（留空=自動偵測） | - | `25` |
| `taxonomy` | 分類法類型（留空=自動偵測） | - | `product_cat` |
| `show_breadcrumb` | 是否顯示麵包屑（僅分類頁面有效） | `true` | `false` |
| `columns` | 桌面列數（1-6） | `4` | `3` |
| `columns_mobile` | 手機列數（1-4） | `2` | `1` |
| `posts_per_page` | 每頁顯示數量 | `8` | `12` |
| `orderby` | 排序方式 | `menu_order` | `name` |
| `max_width` | 最大寬度（px） | `1280` | `1440` |

### 使用範例

#### 1. 自動偵測當前分類（在分類頁面）
```
[rs_smart_layout]
```

#### 2. 指定 WooCommerce 商品分類
```
[rs_smart_layout category_id="25" taxonomy="product_cat"]
```

#### 3. 指定一般 WordPress 分類
```
[rs_smart_layout category_id="10" taxonomy="category"]
```

#### 4. 不顯示麵包屑
```
[rs_smart_layout show_breadcrumb="false"]
```

#### 5. 自訂排列（3 欄，每頁 12 個）
```
[rs_smart_layout columns="3" posts_per_page="12"]
```

#### 6. 完整參數範例
```
[rs_smart_layout
    category_id="25"
    taxonomy="product_cat"
    columns="3"
    columns_mobile="1"
    posts_per_page="12"
    show_breadcrumb="true"
    max_width="1440"
]
```

## 使用場景

### 場景 1：在分類描述中使用

進入 WordPress 後台 → 商品 → 商品分類 → 編輯分類 → 描述欄位：

```
<h2>歡迎來到我的商品分類</h2>
[rs_smart_layout]
<p>更多資訊請聯繫我們</p>
```

### 場景 2：使用頁面建構器

在 Elementor、Gutenberg 或其他頁面建構器中：

1. 新增文字區塊
2. 輸入短代碼 `[rs_smart_layout]`
3. 可在上下左右放置其他元素

### 場景 3：在一般頁面顯示特定分類

創建「產品總覽」頁面：

```
<h2>熱門商品</h2>
[rs_smart_layout category_id="10" posts_per_page="4"]

<h2>最新商品</h2>
[rs_smart_layout category_id="20" posts_per_page="4"]
```

## 麵包屑顯示規則

- ✅ 在分類頁面：顯示麵包屑
- ✅ 在 Shop 頁面：顯示頁面標題
- ❌ 在其他頁面：即使設定 `show_breadcrumb="true"` 也不顯示

麵包屑格式：`父分類 > 子分類 > 當前分類`

- 父分類：灰色（#EEE），可點擊
- 當前分類：黑色（#000），不可點擊

## ACF 設定

外掛會自動註冊 ACF 欄位群組「分類圖片 (Shortcode)」。

### 上傳分類圖片

1. 進入 WordPress 後台
2. 選擇要編輯的分類（商品分類、一般分類等）
3. 找到「分類圖片」欄位
4. 上傳圖片（建議尺寸：800x600px）

## 技術資訊

### CSS Class 命名

所有 CSS class 都使用 `-sc` 後綴，避免與其他外掛衝突：

- `.rs-shortcode-wrapper-sc` - 容器
- `.rs-card-grid-sc` - Grid 容器
- `.rs-card-sc` - 卡片
- `.rs-card-image-sc` - 圖片容器
- `.rs-card-title-sc` - 標題
- `.rs-breadcrumb-sc` - 麵包屑

### JavaScript API

```javascript
// 重新初始化（用於 AJAX 載入後）
RSSmartLayoutShortcode.reinit();
```

## 常見問題

### Q: 圖片沒有顯示？

A: 請確認：
1. 已安裝並啟用 ACF 外掛
2. 已在分類編輯頁面上傳圖片
3. 圖片的欄位名稱是 `category_image`

### Q: 卡片排列錯誤？

A: 檢查瀏覽器寬度：
- 1080px 以上：預設 4 欄
- 1079px 以下：預設 2 欄
- 可用 `columns` 和 `columns_mobile` 參數調整

### Q: 可以在同一頁面使用多個短代碼嗎？

A: 目前僅支援單一短代碼，以避免分頁衝突。

## 更新日誌

### 1.0.0 (2025-01-12)
- 初始版本發布
- 支援所有分類法
- 短代碼參數系統
- 麵包屑導航
- 響應式 Grid 排列

## 授權

GPL v2 or later

## 作者

rs - https://rs.com
