/**
 * RS Search - 前端互動腳本
 *
 * 功能：
 * - 第一層分類點擊事件處理
 * - AJAX 載入第二層分類
 * - 鍵盤導航支援
 * - 無障礙功能
 *
 * @version 1.0.0
 */

(function () {
    'use strict';

    /**
     * 初始化所有 rs-search 元件
     */
    function initRSSearch() {
        var containers = document.querySelectorAll('.rs-search');

        if (!containers.length) {
            return;
        }

        containers.forEach(function (container) {
            new RSSearchComponent(container);
        });
    }

    /**
     * RS Search 元件類別
     */
    function RSSearchComponent(container) {
        this.container = container;
        this.level1Container = container.querySelector('.rs-search__level1');
        this.level2Container = container.querySelector('.rs-search__level2');
        this.currentActiveButton = null;
        this.isLoading = false;

        this.init();
    }

    RSSearchComponent.prototype = {
        /**
         * 初始化元件
         */
        init: function () {
            this.bindEvents();
            this.setupKeyboardNavigation();
        },

        /**
         * 綁定事件
         */
        bindEvents: function () {
            var self = this;

            // 事件代理：點擊第一層分類
            this.level1Container.addEventListener('click', function (e) {
                var button = e.target.closest('.rs-search__l1-tag');
                if (!button) return;

                e.preventDefault();
                self.handleLevel1Click(button);
            });
        },

        /**
         * 設定鍵盤導航
         */
        setupKeyboardNavigation: function () {
            var self = this;
            var buttons = this.level1Container.querySelectorAll('.rs-search__l1-tag');

            buttons.forEach(function (button, index) {
                button.addEventListener('keydown', function (e) {
                    // Enter 或 Space：觸發點擊
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        self.handleLevel1Click(button);
                    }
                    // 左鍵：上一個
                    else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        var prevButton = buttons[index - 1] || buttons[buttons.length - 1];
                        prevButton.focus();
                    }
                    // 右鍵：下一個
                    else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        var nextButton = buttons[index + 1] || buttons[0];
                        nextButton.focus();
                    }
                });
            });
        },

        /**
         * 處理第一層分類點擊
         */
        handleLevel1Click: function (button) {
            if (this.isLoading) {
                return;
            }

            var termId = button.getAttribute('data-term-id');

            if (!termId) {
                return;
            }

            // 更新視覺狀態
            this.setActiveButton(button);

            // 載入第二層分類
            this.loadLevel2(termId);
        },

        /**
         * 設定啟用的按鈕
         */
        setActiveButton: function (button) {
            // 移除所有啟用狀態
            var allButtons = this.level1Container.querySelectorAll('.rs-search__l1-tag');
            allButtons.forEach(function (btn) {
                btn.classList.remove('is-active');
                btn.setAttribute('aria-selected', 'false');
            });

            // 設定當前啟用
            button.classList.add('is-active');
            button.setAttribute('aria-selected', 'true');
            this.currentActiveButton = button;
        },

        /**
         * 載入第二層分類
         */
        loadLevel2: function (parentId) {
            var self = this;

            // 設定載入狀態
            this.setLoadingState(true);

            // 準備請求資料
            var formData = new URLSearchParams();
            formData.append('action', 'rs_search_load_children');
            formData.append('nonce', RS_SEARCH.nonce);
            formData.append('parent', parentId);
            formData.append('taxonomy', RS_SEARCH.taxonomy);

            // 發送 AJAX 請求
            fetch(RS_SEARCH.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    self.handleLevel2Response(data);
                })
                .catch(function (error) {
                    self.handleLevel2Error(error);
                })
                .finally(function () {
                    self.setLoadingState(false);
                });
        },

        /**
         * 處理第二層回應
         */
        handleLevel2Response: function (data) {
            if (!data.ok) {
                this.showMessage(data.message || '載入失敗', 'error');
                return;
            }

            if (!data.children || data.children.length === 0) {
                this.showMessage('尚無第二層分類', 'empty');
                return;
            }

            this.renderLevel2(data.children);
        },

        /**
         * 處理載入錯誤
         */
        handleLevel2Error: function (error) {
            console.error('RS Search AJAX Error:', error);
            this.showMessage('載入失敗，請重試', 'error');
        },

        /**
         * 渲染第二層分類
         */
        renderLevel2: function (children) {
            var self = this;
            var fragment = document.createDocumentFragment();

            children.forEach(function (term) {
                var link = document.createElement('a');
                link.className = 'rs-search__l2-tag';
                link.href = term.url;
                link.setAttribute('role', 'listitem');

                var span = document.createElement('span');
                span.className = 'rs-search__l2-text';

                var text = term.name;
                if (RS_SEARCH.show_count && term.count > 0) {
                    text += ' (' + term.count + ')';
                }
                span.textContent = text;

                link.appendChild(span);
                fragment.appendChild(link);
            });

            // 清空並插入
            this.level2Container.innerHTML = '';
            this.level2Container.appendChild(fragment);
        },

        /**
         * 顯示訊息
         */
        showMessage: function (message, type) {
            var className = 'rs-search__' + type;
            this.level2Container.innerHTML = '<div class="' + className + '">' + this.escapeHtml(message) + '</div>';
        },

        /**
         * 設定載入狀態
         */
        setLoadingState: function (isLoading) {
            this.isLoading = isLoading;
            this.level2Container.setAttribute('aria-busy', isLoading ? 'true' : 'false');

            if (isLoading) {
                this.level2Container.classList.add('rs-search__l2--loading');
            } else {
                this.level2Container.classList.remove('rs-search__l2--loading');
            }
        },

        /**
         * 轉義 HTML
         */
        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
    };

    /**
     * DOM Ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRSSearch);
    } else {
        initRSSearch();
    }

    // 支援動態載入的元件（例如 AJAX 載入的 Pop）
    window.RSSearchComponent = RSSearchComponent;
    window.initRSSearch = initRSSearch;
})();
