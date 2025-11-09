# RS Smart Layout｜智慧分類與文章版型外掛

**版本**: 1.0.0
**作者**: 米米專案團隊
**需求**: WordPress 5.8+, PHP 7.4+, ACF (Advanced Custom Fields)

---

## 📖 功能說明

RS Smart Layout 是一個智慧化分類版型判定系統，可讓所有分類頁面（包含 WooCommerce 商品分類）自動套用統一的「圖片＋標題卡片樣式」。

### 核心特色

✅ **智慧判定** - 自動辨識分類是否有子分類，並決定顯示內容
✅ **統一樣式** - 所有卡片採用一致的 Grid 佈局
✅ **響應式設計** - 手機/平板 2 欄、桌面 4 欄
✅ **WooCommerce 支援** - 完整支援商品分類頁
✅ **無圖彈性** - 未設定圖片時優雅降級顯示
✅ **內建分頁** - 每頁顯示 8 個項目，自動分頁

---

## 🎯 智慧判定邏輯

```
當用戶訪問分類頁面時：

1. 檢查該分類是否有子分類
   ├─ 有子分類 → 顯示子分類卡片（使用 ACF 圖片 + 分類名稱）
   └─ 無子分類 → 顯示文章卡片（使用精選圖片 + 文章標題）

2. 卡片圖片來源
   ├─ 分類卡片：ACF 欄位「category_image」
   └─ 文章卡片：WordPress 精選圖片

3. 無圖片處理
   └─ 不顯示圖片區域，僅顯示標題（純文字卡片）
```

---

## 📦 安裝步驟

### 1. 安裝 ACF 外掛

本外掛**必須**搭配 Advanced Custom Fields (ACF) 使用：

- 前往 WordPress 後台 → 外掛 → 安裝外掛
- 搜尋「Advanced Custom Fields」
- 安裝並啟用（免費版或 Pro 版皆可）

### 2. 安裝本外掛

#### 方法 A：上傳安裝
1. 將 `rs-smart-layout` 整個資料夾上傳至 `/wp-content/plugins/`
2. 前往 WordPress 後台 → 外掛
3. 找到「RS Smart Layout」並啟用

#### 方法 B：FTP 安裝
1. 透過 FTP 將資料夾上傳至 `/wp-content/plugins/`
2. 登入 WordPress 後台啟用外掛

### 3. 設定分類圖片

啟用外掛後，所有分類編輯頁面會自動新增「分類圖片」欄位：

1. 前往 文章 → 分類（或任何自訂分類法）
2. 編輯要設定的分類
3. 上傳「分類圖片」（建議尺寸：800x600px）
4. 儲存

---

## 🎨 前端呈現

### 響應式斷點

| 裝置類型 | 螢幕寬度 | 欄數 | 間距 |
|---------|---------|------|------|
| 手機 / 平板 | < 1024px | 2 欄 | 16px |
| 桌面 | ≥ 1024px | 4 欄 | 24px |

### Hover 效果

- 背景變色：`#F2EFEA`
- 卡片上浮：`translateY(-4px)`
- 圖片縮放：`scale(1.05)`
- 陰影加深：`0 6px 20px rgba(0,0,0,0.12)`

---

## 🔧 適用頁面

本外掛會自動套用於以下頁面：

- ✅ 一般分類頁 (`is_category()`)
- ✅ 自訂分類法頁 (`is_tax()`)
- ✅ WooCommerce 商品分類 (`is_product_category()`)
- ✅ WooCommerce 商品標籤 (`is_product_tag()`)

**不會套用於**：
- ❌ 單一文章 / 單一商品頁面
- ❌ 搜尋結果頁
- ❌ 作者頁面
- ❌ 日期歸檔頁面

---

## 🛠 開發者擴展

### Filter Hooks

#### 1. 控制外掛套用範圍

```php
add_filter( 'rs_smart_layout_should_apply', function( $should_apply ) {
    // 例如：在特定分類 ID 停用
    if ( is_category( 5 ) ) {
        return false;
    }
    return $should_apply;
} );
```

#### 2. 控制資源載入

```php
add_filter( 'rs_smart_layout_should_enqueue', function( $should_enqueue ) {
    // 自訂邏輯
    return $should_enqueue;
} );
```

### 自訂樣式

如需覆蓋預設樣式，在佈景主題的 `style.css` 或 `custom.css` 中加入：

```css
/* 修改卡片間距 */
.rs-card-grid {
    gap: 32px;
}

/* 修改 Hover 背景色 */
.rs-card:hover {
    background: #your-color;
}

/* 修改標題字體 */
.rs-card-title {
    font-family: 'Your Font', sans-serif;
}
```

---

## 📁 檔案結構

```
rs-smart-layout/
│
├── rs-smart-layout.php              # 主插件文件
├── README.md                        # 說明文檔
│
├── includes/                        # 核心模組
│   ├── class-rs-loader.php          # 模組載入器
│   ├── rs-acf-fields.php            # ACF 欄位註冊
│   ├── rs-template-router.php       # 智慧路由
│   ├── rs-template-render.php       # 渲染邏輯
│   └── rs-assets.php                # 資源載入
│
├── templates/                       # 模板文件
│   └── rs-layout-grid.php           # 統一 Grid 模板
│
└── assets/                          # 前端資源
    ├── css/
    │   └── rs-smart-layout.css      # 樣式表
    └── js/
        └── rs-smart-layout.js       # 前端腳本
```

---

## ❓ 常見問題

### Q1: 為什麼分類頁面沒有套用新樣式？

**A**: 請檢查：
1. 外掛是否已啟用
2. ACF 外掛是否已安裝並啟用
3. 清除瀏覽器快取和 WordPress 快取
4. 檢查是否有其他外掛或佈景主題衝突

### Q2: 可以修改每頁顯示的項目數量嗎？

**A**: 可以，在 `includes/rs-template-render.php` 的 `rs_smart_render()` 函數中，修改：

```php
'posts_per_page' => 8,  // 改成你想要的數量
```

### Q3: 如何修改圖片尺寸？

**A**: 在 `includes/rs-template-render.php` 中修改：

```php
// 分類圖片
rs_get_term_image( $term_id, 'medium_large' );  // 改成 'large', 'full' 等

// 文章精選圖片
get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
```

### Q4: 子分類沒有圖片怎麼辦？

**A**: 外掛會自動以「純標題卡片」方式顯示，不會強制要求圖片。

### Q5: 支援多層級分類嗎？

**A**: 是的，外掛會遞迴判定：
- 第一層分類 → 顯示第二層子分類
- 第二層分類 → 顯示第三層子分類（如有），否則顯示文章
- 以此類推

---

## 📞 技術支援

如遇問題或需要客製化開發，請聯絡：

**米米專案團隊**
Email: support@example.com

---

## 📄 授權

GPL v2 或更新版本

---

## 🔄 更新日誌

### v1.0.0 (2025-11-09)
- ✨ 初始版本發布
- ✅ 智慧分類判定功能
- ✅ 響應式 Grid 佈局
- ✅ WooCommerce 支援
- ✅ ACF 欄位自動註冊
- ✅ 內建分頁功能
