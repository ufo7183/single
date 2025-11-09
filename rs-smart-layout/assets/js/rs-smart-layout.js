/**
 * RS Smart Layout - 前端腳本
 *
 * @package RS_Smart_Layout
 * @version 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * 初始化
	 */
	$(document).ready(function() {
		// 圖片懶載入增強（如瀏覽器不支持原生 loading="lazy"）
		initLazyLoadFallback();

		// 鍵盤導航增強
		initKeyboardNavigation();

		// 圖片載入錯誤處理
		initImageErrorHandling();
	});

	/**
	 * 懶載入備用方案
	 */
	function initLazyLoadFallback() {
		// 檢查瀏覽器是否支持原生 lazy loading
		if ('loading' in HTMLImageElement.prototype) {
			return;
		}

		// 如不支持，可在此加入 Intersection Observer 邏輯
		// 目前保持簡單，依賴原生支持
	}

	/**
	 * 鍵盤導航增強
	 */
	function initKeyboardNavigation() {
		$('.rs-card').on('keydown', function(e) {
			// Enter 或 Space 鍵觸發點擊
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				$(this)[0].click();
			}
		});
	}

	/**
	 * 圖片載入錯誤處理
	 */
	function initImageErrorHandling() {
		$('.rs-card-image img').on('error', function() {
			var $card = $(this).closest('.rs-card');

			// 移除圖片區域
			$(this).closest('.rs-card-image').remove();

			// 添加 no-image 類別
			$card.addClass('no-image');

			console.warn('RS Smart Layout: 圖片載入失敗', $(this).attr('src'));
		});
	}

	/**
	 * 允許外部擴展
	 */
	window.RSSmartLayout = {
		version: '1.0.0',

		// 重新初始化（用於 AJAX 載入後）
		reinit: function() {
			initKeyboardNavigation();
			initImageErrorHandling();
		}
	};

})(jQuery);
