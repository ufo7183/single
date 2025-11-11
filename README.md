# rsmeg 置入式輪播模組 v1.1

> 一個可嵌入的純 HTML 輪播模組，使用 Swiper 實現，具備自動播放、首尾相連、響應式設計與桌機 overlay 特效。

## ✨ 特性

- ✅ **零全域污染**：所有 class/id 以 `rsmeg` 前綴命名，不影響嵌入頁面
- ✅ **響應式設計**：自動適配手機/平板/桌機三種斷點
- ✅ **自動輪播**：5 秒自動切換，支援互動暫停
- ✅ **首尾相連**：無縫循環播放
- ✅ **桌機特效**：中間卡片左右兩側自動加上 overlay 效果
- ✅ **無障礙支援**：鍵盤導航、ARIA 標籤、減少動畫偏好
- ✅ **純原生技術**：HTML + CSS + JS，無框架依賴

---

## 📦 快速開始

### 1. 直接使用

開啟 `rsmeg-carousel.html` 即可在瀏覽器中查看完整效果。

### 2. 嵌入到現有頁面

將 `rsmeg-carousel.html` 中的以下部分複製到您的頁面：

```html
<!-- 1. 引入 Swiper CSS（在 <head> 中） -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<!-- 2. 引入 rsmeg 樣式（在 <head> 中） -->
<style>
  /* 複製 rsmeg-carousel.html 中的 <style> 內容 */
</style>

<!-- 3. 放置輪播 HTML（在 <body> 中您想要的位置） -->
<div class="rsmeg-root" role="region" aria-label="rsmeg 置入式輪播">
  <!-- ... 完整 DOM 結構 ... -->
</div>

<!-- 4. 在 </body> 前引入腳本 -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  /* 複製 rsmeg-carousel.html 中的 <script> 初始化代碼 */
</script>
```

---

## 🎨 自訂圖片

### 替換圖片 URL

找到 `.rsmeg-slide` 中的 `<img>` 標籤，修改 `src` 屬性：

```html
<div class="rsmeg-slide swiper-slide">
  <img class="rsmeg-img"
       alt="您的圖片描述"
       src="https://your-image-url.com/image.jpg"
       loading="lazy">
</div>
```

### 建議圖片尺寸

| 裝置類型 | 建議尺寸 | 比例 |
|---------|---------|------|
| 桌機/平板 | 380 × 214 px | 16:9 |
| 手機 | 322 × 182 px | ~16:9 |

> 💡 **提示**：使用相同比例的圖片可確保最佳視覺效果。模組會自動使用 `object-fit: cover` 裁切圖片。

### 新增或刪除卡片

```html
<!-- 新增卡片：複製整個 .rsmeg-slide 區塊 -->
<div class="rsmeg-slide swiper-slide">
  <img class="rsmeg-img"
       alt="新圖片"
       src="https://placehold.co/380x214"
       loading="lazy">
</div>

<!-- 刪除卡片：直接移除不需要的 .rsmeg-slide 區塊 -->
```

---

## ⚙️ 配置選項

### 可用的 data 屬性

在 `.rsmeg-swiper` 元素上修改配置：

```html
<div class="rsmeg-swiper swiper"
     data-rsmeg-autoplay="true"    <!-- 啟用/停用自動播放 -->
     data-rsmeg-delay="5000"       <!-- 自動播放間隔（毫秒） -->
     data-rsmeg-loop="true">       <!-- 啟用/停用首尾相連 -->
```

| 屬性 | 預設值 | 說明 |
|-----|-------|------|
| `data-rsmeg-autoplay` | `true` | 是否啟用自動播放 |
| `data-rsmeg-delay` | `5000` | 自動播放間隔（毫秒） |
| `data-rsmeg-loop` | `true` | 是否啟用首尾相連 |

### 停用自動播放範例

```html
<div class="rsmeg-swiper swiper"
     data-rsmeg-autoplay="false"
     data-rsmeg-loop="true">
```

---

## 📱 響應式行為

| 斷點 | 螢幕寬度 | 顯示張數 | 箭頭 | 特效 |
|-----|---------|---------|------|------|
| 手機 | < 768px | 1 張 | 隱藏 | 邊緣預覽 |
| 平板 | 768px - 1279px | 2 張 | 顯示 | 無 |
| 桌機 | ≥ 1280px | 3 張 | 顯示 | 左右卡片 overlay |

### 互動行為

- **滑鼠懸停**（桌機）：暫停自動播放
- **鍵盤焦點**：箭頭獲得焦點時暫停自動播放
- **觸控滑動**（手機/平板）：滑動時暫停，結束後 2 秒恢復
- **減少動畫偏好**：自動偵測系統設定，停用動畫與自動播放

---

## 🎯 桌機 Overlay 效果

在桌機模式（≥1280px）下，中間卡片左右兩側的卡片會自動加上暗色 overlay，凸顯中央焦點。

- **觸發條件**：螢幕寬度 ≥ 1280px
- **效果**：半透明黑色遮罩（`rgba(0,0,0,0.35)`）
- **動態更新**：切換卡片時自動重新計算

---

## 🛡️ 安全與相容性

### 無全域污染保證

- ✅ 所有 CSS 選擇器以 `.rsmeg-root` 開頭
- ✅ 未使用全域標籤選擇器（如 `img`、`button`）
- ✅ 未注入全域 CSS 變數或重置樣式
- ✅ JS 僅在 `.rsmeg-root` 範圍內操作

### 瀏覽器支援

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- iOS Safari 14+
- Android Chrome 90+

### 已知限制

- 需要支援 CSS `aspect-ratio`（Safari 15+）
- 低於 IE11 的瀏覽器不支援

---

## 🚀 效能優化

- **懶載入**：圖片使用 `loading="lazy"` 屬性
- **固定比例**：使用 `aspect-ratio` 避免 layout shift
- **最小化資源**：僅引入 Swiper 必要功能
- **事件節流**：resize 事件自動由 Swiper 處理

---

## ♿ 無障礙功能

- ✅ 語意化 HTML（`role="region"`）
- ✅ ARIA 標籤（`aria-label`）
- ✅ 鍵盤導航（Tab、Enter、Space）
- ✅ 焦點可見性（outline 樣式）
- ✅ 圖片 alt 文本
- ✅ 減少動畫偏好支援

---

## 🔧 進階自訂

### 修改品牌色

在 CSS 中搜尋 `#E83743`（卡片邊框色）並替換：

```css
.rsmeg-root .rsmeg-img {
  border: 2px solid #YOUR_COLOR; /* 改成您的品牌色 */
}
```

### 調整卡片間距

在 JS 初始化中修改 `spaceBetween`：

```javascript
breakpoints: {
  0: { slidesPerView: 1, spaceBetween: 16 },    // 手機間距
  768: { slidesPerView: 2, spaceBetween: 20 },  // 平板間距
  1280: { slidesPerView: 3, spaceBetween: 32 } // 桌機間距
}
```

### 修改卡片圓角

```css
.rsmeg-root .rsmeg-img {
  border-radius: 20px; /* 改成您想要的圓角大小 */
}
```

---

## 📋 驗收檢查清單

### 視覺/排版
- [x] 最大寬度 1280px，置中顯示
- [x] 手機可見邊緣預覽（peeking）
- [x] 卡片圓角 16px、邊框 2px (#E83743)
- [x] 底部漸層效果正確
- [x] 桌機顯示箭頭，手機隱藏
- [x] 桌機 overlay 效果正確切換

### 互動/功能
- [x] 斷點 slidesPerView 正確（1/2/3）
- [x] 箭頭可操作切換
- [x] 自動播放每 5 秒切換
- [x] 互動時暫停，離開後恢復
- [x] 首尾相連無縫循環
- [x] 6+ 張圖片正常運作

### 無全域污染
- [x] 所有選擇器以 `.rsmeg-root` 開頭
- [x] 未影響嵌入頁其他元素
- [x] JS 僅在範圍內操作

### 無障礙
- [x] role/aria-label 完整
- [x] 鍵盤可操作
- [x] focus 樣式可見

---

## 📝 注意事項

### ⚠️ 重要規則

1. **不要修改 `rsmeg` 前綴**：所有 class/id 必須保持 `rsmeg-` 開頭
2. **不要移除範疇化選擇器**：所有 CSS 必須以 `.rsmeg-root` 開頭
3. **不要使用全域選擇器**：避免 `img {}`、`button {}` 等無前綴選擇器
4. **保持 DOM 結構**：不要更改 `.rsmeg-carousel` → `.rsmeg-swiper` → `.rsmeg-wrapper` → `.rsmeg-slide` 的層級關係

### 💡 最佳實踐

- 使用相同比例的圖片以獲得最佳視覺效果
- 圖片檔案大小建議 < 200KB（已壓縮）
- 提供有意義的 `alt` 文本
- 測試多種裝置與螢幕尺寸
- 確認在減少動畫模式下仍可操作

---

## 🐛 疑難排解

### Q: 輪播沒有顯示
A: 確認已正確引入 Swiper CSS 和 JS，並檢查瀏覽器控制台是否有錯誤。

### Q: 圖片變形或裁切不正確
A: 使用建議尺寸比例（16:9）的圖片，模組會使用 `object-fit: cover` 自動處理。

### Q: 箭頭位置不對
A: 檢查 `.rsmeg-container` 的 `max-width` 設定，箭頭位置是根據容器寬度計算的。

### Q: 自動播放不會暫停
A: 確認瀏覽器支援 `pauseOnMouseEnter` 功能，並檢查是否有 JS 錯誤阻止事件監聽。

### Q: 在嵌入頁面中樣式錯亂
A: 確認沒有其他 CSS 使用相同的 class 名稱，所有樣式都應該以 `.rsmeg-root` 範疇化。

---

## 📄 授權

本專案依照 SDD v1.1 規格實作，供專案內部使用。

---

## 🔗 相關資源

- [Swiper 官方文件](https://swiperjs.com/)
- [Web Accessibility Initiative (WAI)](https://www.w3.org/WAI/)
- [Responsive Images](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images)

---

**版本**：v1.1
**最後更新**：2025-11-11
**作者**：依據 rsmeg SDD v1.1 規格實作
