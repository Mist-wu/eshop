<?php
/**
 * Admin Modal 组件
 *
 * 使用方法:
 * AdminModal.open({
 *     title: '标题',
 *     url: 'xxx.php',
 *     width: 1000,    // 可选，默认自适应
 *     height: '80vh'  // 可选，默认自适应
 * });
 * AdminModal.close();
 */
?>
<style>
/* Admin Modal 样式 */
.admin-modal-mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0);
    backdrop-filter: blur(0px);
    z-index: 99999;
    display: flex;
    justify-content: center;
    align-items: center;
    visibility: hidden;
    transition: all 0.3s ease;
}
.admin-modal-mask.active {
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(1px);
    visibility: visible;
}
.admin-modal {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    /* 默认自适应大小 */
    width: 92vw;
    height: 88vh;
    max-width: 1100px;
    max-height: 90vh;
    /* 动画 */
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.admin-modal-mask.active .admin-modal {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.admin-modal-header {
    height: 56px;
    padding: 0 20px;
    background: #fff;
    border-bottom: 1px solid #eef2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    position: relative;
}
/* 顶部装饰线 */
.admin-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #0f766e;
}
.admin-modal-title {
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.admin-modal-title::before {
    content: '';
    width: 4px;
    height: 18px;
    background: #0f766e;
    border-radius: 2px;
}
.admin-modal-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.admin-modal-mask.locked .admin-modal-close {
    display: none;
}
.admin-modal-btn {
    width: 32px;
    height: 32px;
    background: #f5f7f9;
    border: none;
    border-radius: 16px;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.admin-modal-btn:hover {
    background: #e8edf2;
    color: #111;
}
.admin-modal-close {
    width: 32px;
    height: 32px;
    background: #f5f7f9;
    border: none;
    border-radius: 16px;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.admin-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
    transform: rotate(90deg);
}
.admin-modal-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    background: #f8fafb;
}
.admin-modal-body iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
/* 最大化与最小化 */
.admin-modal-mask.maximized .admin-modal {
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw;
    max-height: 100vh;
    border-radius: 0;
    box-shadow: none;
}
.admin-modal-mask.minimized {
    background: rgba(0, 0, 0, 0);
    backdrop-filter: none;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 0 16px 16px 0;
}
.admin-modal-mask.minimized .admin-modal {
    width: 320px !important;
    height: 56px !important;
    max-width: 92vw;
    max-height: 56px;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.admin-modal-mask.minimized .admin-modal-body {
    display: none;
}
.admin-modal-mask.minimized .admin-modal-header {
    border-bottom: none;
}
/* 响应式 */
@media (max-width: 768px) {
    .admin-modal {
        width: 95vw;
        height: 90vh;
        max-width: 100vw;
        max-height: 90vh;
    }
    .admin-modal-header {
        height: 50px;
        padding: 0 16px;
    }
    .admin-modal-title {
        font-size: 15px;
    }
    .admin-modal-mask.minimized .admin-modal {
        width: 92vw !important;
    }
}
</style>

<div class="admin-modal-mask" id="adminModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <div class="admin-modal-title">
                <span id="adminModalTitle">弹窗</span>
            </div>
            <div class="admin-modal-actions">
                <button class="admin-modal-btn admin-modal-minimize" type="button" title="最小化" aria-label="最小化" onclick="AdminModal.minimize()"></button>
                <button class="admin-modal-btn admin-modal-maximize" type="button" title="最大化" aria-label="最大化" onclick="AdminModal.maximize()"></button>
                <button class="admin-modal-btn admin-modal-close" type="button" title="关闭" aria-label="关闭" onclick="AdminModal.close()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="admin-modal-body">
            <iframe id="adminModalIframe" src="about:blank"></iframe>
        </div>
    </div>
</div>

<script>
var AdminModal = (function() {
    var $mask = null;
    var $modal = null;
    var $title = null;
    var $iframe = null;
    var $closeBtn = null;
    var $minBtn = null;
    var $maxBtn = null;
    var allowClose = true;
    var state = 'normal';
    var icons = {
        minimize: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg>',
        maximize: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>',
        restore: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="7" y="7" width="12" height="12" rx="1"/><path d="M5 13V5h8"/></svg>'
    };

    function init() {
        $mask = document.getElementById('adminModal');
        $modal = $mask.querySelector('.admin-modal');
        $title = document.getElementById('adminModalTitle');
        $iframe = document.getElementById('adminModalIframe');
        $closeBtn = $mask.querySelector('.admin-modal-close');
        $minBtn = $mask.querySelector('.admin-modal-minimize');
        $maxBtn = $mask.querySelector('.admin-modal-maximize');

        if ($minBtn) $minBtn.innerHTML = icons.minimize;
        if ($maxBtn) $maxBtn.innerHTML = icons.maximize;

        // 点击遮罩关闭
        $mask.addEventListener('click', function(e) {
            if (!allowClose) return;
            if (e.target === $mask) close();
        });

        // ESC 关闭
        document.addEventListener('keydown', function(e) {
            if (!allowClose) return;
            if (e.key === 'Escape' && $mask.classList.contains('active')) {
                close();
            }
        });
    }

    function setState(nextState) {
        state = nextState || 'normal';
        $mask.classList.remove('minimized', 'maximized');
        if (state === 'minimized') $mask.classList.add('minimized');
        if (state === 'maximized') $mask.classList.add('maximized');
        updateControls();
    }

    function updateControls() {
        if (!$minBtn || !$maxBtn) return;
        if (state === 'minimized') {
            $minBtn.innerHTML = icons.restore;
            $minBtn.title = '还原';
            $minBtn.setAttribute('aria-label', '还原');
        } else {
            $minBtn.innerHTML = icons.minimize;
            $minBtn.title = '最小化';
            $minBtn.setAttribute('aria-label', '最小化');
        }

        if (state === 'maximized') {
            $maxBtn.innerHTML = icons.restore;
            $maxBtn.title = '还原';
            $maxBtn.setAttribute('aria-label', '还原');
        } else {
            $maxBtn.innerHTML = icons.maximize;
            $maxBtn.title = '最大化';
            $maxBtn.setAttribute('aria-label', '最大化');
        }
    }

    function setClosable(flag) {
        allowClose = flag !== false;
        if (!$mask) return;
        if (allowClose) {
            $mask.classList.remove('locked');
        } else {
            $mask.classList.add('locked');
        }
    }

    function open(options) {
        if (!$mask) init();

        var opts = options || {};
        var title = opts.title || '弹窗';
        var url = opts.url || 'about:blank';
        var width = opts.width;
        var height = opts.height;
        var closable = opts.closable;

        // 设置标题
        $title.textContent = title;
        setClosable(closable);
        setState('normal');

        // 设置尺寸
        if (width) {
            $modal.style.width = typeof width === 'number' ? width + 'px' : width;
        } else {
            $modal.style.width = '';
        }
        if (height) {
            $modal.style.height = typeof height === 'number' ? height + 'px' : height;
        } else {
            $modal.style.height = '';
        }

        // 加载页面
        $iframe.src = url;

        // 显示弹窗
        $mask.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        if (!allowClose) return;
        if (!$mask) return;
        setState('normal');
        $mask.classList.remove('active');
        document.body.style.overflow = '';
        // 等动画结束后清空 iframe
        setTimeout(function() {
            $iframe.src = 'about:blank';
        }, 300);
    }

    function minimize() {
        if (!$mask || !$mask.classList.contains('active')) return;
        if (state === 'minimized') {
            setState('normal');
        } else {
            setState('minimized');
        }
    }

    function maximize() {
        if (!$mask || !$mask.classList.contains('active')) return;
        if (state === 'maximized') {
            setState('normal');
        } else {
            setState('maximized');
        }
    }

    // DOM ready 后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { open: open, close: close, minimize: minimize, maximize: maximize };
})();
</script>
