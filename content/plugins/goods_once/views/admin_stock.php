<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    html.in-layer,
    html.in-layer body {
        height: 100%;
        overflow: hidden;
    }
    html.in-layer body {
        margin: 0;
    }
    html.in-layer .stock-container {
        height: 100%;
        overflow: auto;
        box-sizing: border-box;
    }

    /* 页面容器 */
    .stock-container {
        padding: 24px;
        background: #f8fafb;
        box-sizing: border-box;
    }

    /* 库存统计卡片 */
    .stock-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #e5e8eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s;
    }
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .stat-card.total {
        background: linear-gradient(135deg, #4C7D71, #6BA596);
        border: none;
    }
    .stat-card.total .stat-label,
    .stat-card.total .stat-value,
    .stat-card.total .stat-sub {
        color: #fff;
    }
    .stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .stat-label i {
        font-size: 14px;
    }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.2;
    }
    .stat-value.danger {
        color: #dc2626;
    }
    .stat-value.warning {
        color: #d97706;
    }
    .stat-sub {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }
    .stat-sub span {
        font-weight: 500;
    }

    /* 页面标题 */
    .stock-header {
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e8eb;
    }
    .stock-title {
        font-size: 18px;
        font-weight: 600;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .stock-title::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, #4C7D71, #6BA596);
        border-radius: 2px;
    }

    /* Tab 样式优化 */
    .layui-tab-brief > .layui-tab-title {
        border-bottom: 2px solid #e5e8eb;
    }
    .layui-tab-brief > .layui-tab-title .layui-this {
        color: #4C7D71;
    }
    .layui-tab-brief > .layui-tab-title .layui-this::after {
        background: linear-gradient(90deg, #4C7D71, #6BA596);
        height: 3px;
        border-radius: 3px 3px 0 0;
    }
    .layui-tab-brief > .layui-tab-title li {
        font-size: 14px;
        padding: 0 20px;
    }
    .layui-tab-content {
        padding: 16px 0 0;
    }

    /* 表格样式优化 */
    .layui-table-view {
        border-radius: 10px;
        border: 1px solid #e6e6e6;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    }
    .layui-table-box {
        border-radius: 10px;
    }
    .layui-table-tool {
        background: #fff;
        border-bottom: 1px solid #eaecef;
        padding: 12px 16px;
    }
    .layui-table-header {
        background: linear-gradient(180deg, #f9fafb 0%, #f2f4f6 100%);
    }
    .layui-table-header th {
        background: transparent;
        color: #5f6b74;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.3px;
        border-bottom: 1px solid #e6e6e6;
    }
    .layui-table-body td {
        color: #262626;
        font-size: 13px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    .layui-table-body tr:nth-child(even) td {
        background: #fcfcfd;
    }
    .layui-table-body tr:hover td {
        background: #f3f8f6;
    }
    .layui-table-body tr:last-child td {
        border-bottom: none;
    }
    .layui-table-view .layui-table-cell {
        padding: 12px 16px;
        height: auto;
        line-height: 1.5;
        box-sizing: border-box;
    }
    .layui-table-view .layui-table-body .layui-table tr .layui-table-cell {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 1;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .layui-table-tool-temp {
        padding-right: 0;
    }
    /* 分页样式 */
    .layui-table-page {
        border-top: 1px solid #eef2f5;
        padding: 12px 16px;
    }
    .layui-laypage .layui-laypage-curr .layui-laypage-em {
        background: linear-gradient(90deg, #4C7D71, #6BA596);
        border-radius: 6px;
    }
    .layui-laypage a:hover {
        color: #4C7D71;
    }

    /* 工具栏按钮优化 */
    .layui-table-tool .layui-btn {
        border-radius: 6px;
    }
    .layui-table-tool .layui-input {
        border-radius: 6px;
        border-color: #d1d5db;
    }
    .layui-table-tool .layui-input:focus {
        border-color: #4C7D71;
    }

    /* 自定义弹窗样式 */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .modal-overlay.active {
        background: rgba(0, 0, 0, 0.4);
        visibility: visible;
    }

    /* 弹窗盒子 */
    .modal-box {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
        width: 90%;
        max-width: 520px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
        transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .modal-overlay.active .modal-box {
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    /* 弹窗头部 */
    .modal-header {
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
    .modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #4C7D71, #6BA596);
    }
    .modal-title {
        color: #1a1a1a;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .modal-title::before {
        content: '';
        width: 4px;
        height: 18px;
        background: linear-gradient(180deg, #4C7D71, #6BA596);
        border-radius: 2px;
    }
    .modal-close {
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
    .modal-close:hover {
        background: #fee2e2;
        color: #dc2626;
        transform: rotate(90deg);
    }

    /* 弹窗内容 */
    .modal-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        background: #fff;
    }

    /* 弹窗底部 */
    .modal-footer {
        padding: 16px 24px;
        background: #fafbfc;
        border-top: 1px solid #eef2f5;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        flex-shrink: 0;
    }

    /* 表单样式 */
    .form-group {
        margin-bottom: 20px;
    }
    .form-group:last-child {
        margin-bottom: 0;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: #fff;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #4C7D71;
        box-shadow: 0 0 0 3px rgba(76, 125, 113, 0.15);
    }
    .form-textarea {
        resize: vertical;
        min-height: 180px;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        line-height: 1.6;
    }
    .form-hint {
        font-size: 12px;
        color: #6b7280;
        margin-top: 8px;
        padding-left: 2px;
    }

    /* 按钮样式 */
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-primary {
        background: linear-gradient(90deg, #4C7D71 0%, #6BA596 100%);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(90deg, #3D6A5F 0%, #5A9485 100%);
        box-shadow: 0 4px 12px rgba(76, 125, 113, 0.35);
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .btn .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* 响应式 */
    @media (max-width: 768px) {
        .stock-container {
            padding: 16px;
        }
        .modal-box {
            width: 95%;
            max-height: 90vh;
        }
    }
</style>

<div class="stock-container" id="open-box">
    <!-- 库存统计卡片 -->
    <div class="stock-stats">
        <!-- 总计卡片 -->
        <div class="stat-card total">
            <div class="stat-label"><i class="fa fa-cubes"></i> 总可用库存</div>
            <div class="stat-value"><?= $total_available ?></div>
            <div class="stat-sub">已售出 <span><?= $total_sold ?></span> 条</div>
        </div>
        <!-- 各规格卡片 -->
        <?php foreach($sku_stock_stats as $stat): ?>
        <div class="stat-card">
            <div class="stat-label"><i class="fa fa-tag"></i> <?= htmlspecialchars($stat['sku_name']) ?></div>
            <div class="stat-value <?= $stat['available'] <= 0 ? 'danger' : ($stat['available'] <= 10 ? 'warning' : '') ?>">
                <?= $stat['available'] ?>
            </div>
            <div class="stat-sub">已售 <span><?= $stat['sold'] ?></span></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tab 切换 -->
    <div class="layui-tab layui-tab-brief" lay-filter="stockTab">
        <ul class="layui-tab-title">
            <li class="layui-this">未售出</li>
            <li>已售出</li>
            <li>标记售出</li>
        </ul>
        <div class="layui-tab-content">
            <!-- 未售出 -->
            <div class="layui-tab-item layui-show">
                <table class="layui-hide" id="stock_unsold" lay-filter="stock_unsold"></table>
            </div>
            <!-- 已售出 -->
            <div class="layui-tab-item">
                <table class="layui-hide" id="stock_sold" lay-filter="stock_sold"></table>
            </div>
            <!-- 标记售出 -->
            <div class="layui-tab-item">
                <table class="layui-hide" id="stock_marked" lay-filter="stock_marked"></table>
            </div>
        </div>
    </div>
</div>

<!-- 添加库存弹窗 -->
<div class="modal-overlay" id="modal-add">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">添加库存</h3>
            <button class="modal-close" onclick="closeModal('modal-add')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <?php if($goods['is_sku'] == 'y'): ?>
            <div class="form-group">
                <label class="form-label">选择规格</label>
                <select class="form-select" id="add-sku-id">
                    <option value="">请选择规格</option>
                    <?php foreach($skus as $sku): ?>
                    <option value="<?= $sku['id'] ?>"><?= getSkuName($sku['option_ids']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" id="add-sku-id" value="<?= $skus[0]['id'] ?? 0 ?>">
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">卡密内容</label>
                <textarea class="form-textarea" id="add-content" placeholder="每行输入一个卡密&#10;支持批量添加"></textarea>
                <div class="form-hint">提示：多个卡密请换行分隔，系统将自动按行拆分</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add')">取消</button>
            <button class="btn btn-primary" id="btn-add-submit">
                <span class="btn-text">确认添加</span>
            </button>
        </div>
    </div>
</div>

<!-- 编辑库存弹窗 -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">编辑库存</h3>
            <button class="modal-close" onclick="closeModal('modal-edit')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <?php if($goods['is_sku'] == 'y'): ?>
            <div class="form-group">
                <label class="form-label">所属规格</label>
                <select class="form-select" id="edit-sku-id">
                    <?php foreach($skus as $sku): ?>
                    <option value="<?= $sku['id'] ?>"><?= getSkuName($sku['option_ids']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">卡密内容</label>
                <textarea class="form-textarea" id="edit-content" style="min-height: 120px;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit')">取消</button>
            <button class="btn btn-primary" id="btn-edit-submit">
                <span class="btn-text">保存修改</span>
            </button>
        </div>
    </div>
</div>

<!-- 查看卡密弹窗 -->
<div class="modal-overlay" id="modal-view">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">卡密内容</h3>
            <button class="modal-close" onclick="closeModal('modal-view')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-textarea" id="view-content" style="min-height: 100px; background: #f9fafb; cursor: text; user-select: all;"></div>
        </div>
    <div class="modal-footer">
            <button class="btn btn-secondary" type="button" onclick="closeModal('modal-view')">关闭窗口</button>
            <button class="btn btn-primary btn-copy" type="button" data-clipboard-target="#view-content">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                </svg>
                复制卡密
            </button>
        </div>
    </div>
</div>

<!-- 未售出工具栏 -->
<script type="text/html" id="toolbar_unsold">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-primary layui-border-green layui-btn-sm" lay-event="refresh">
            <i class="fa fa-refresh"></i>
        </button>
        <button type="button" class="layui-btn layui-btn-sm" lay-event="add">添加库存</button>
        <button class="layui-btn layui-btn-primary layui-border-blue layui-btn-disabled" id="btn-batch-mark" lay-event="batchMark">
            批量标记售出
        </button>
        <button class="layui-btn layui-btn-sm layui-bg-red layui-btn-disabled" id="btn-batch-del" lay-event="batchDel">
            删除选中
        </button>
        <button class="layui-btn layui-btn-sm layui-bg-red" lay-event="clearStock">
            清空库存
        </button>
        <button type="button" class="layui-btn layui-btn-sm layui-btn-primary priority-menu">
            优先选项 <i class="layui-icon layui-icon-down layui-font-12"></i>
        </button>
        <button class="layui-btn layui-btn-sm layui-btn-warm" lay-event="dedupe">
            库存去重
        </button>
        <button type="button" class="layui-btn layui-btn-sm layui-bg-blue" lay-event="export">导出</button>
        <?php if($goods['is_sku'] == 'y'): ?>
        <div class="layui-inline" style="margin-left: 20px;">
            <select name="sku_id" lay-filter="filter-sku">
                <option value="">全部规格</option>
                <?php foreach($skus as $sku): ?>
                <option value="<?= $sku['id'] ?>"><?= getSkuName($sku['option_ids']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="layui-inline">
            <input type="text" name="keyword" placeholder="搜索卡密内容" class="layui-input layui-input-sm" style="width: 150px;">
        </div>
        <button class="layui-btn layui-btn-sm" lay-event="search">搜索</button>
    </div>
</script>

<!-- 已售出工具栏 -->
<script type="text/html" id="toolbar_sold">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-primary layui-border-green layui-btn-sm" lay-event="refresh">
            <i class="fa fa-refresh"></i>
        </button>
        <div class="layui-inline">
            <input type="text" name="keyword" placeholder="搜索卡密内容" class="layui-input layui-input-sm" style="width: 150px;">
        </div>
        <button class="layui-btn layui-btn-sm" lay-event="search">搜索</button>
    </div>
</script>

<!-- 标记售出工具栏 -->
<script type="text/html" id="toolbar_marked">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-primary layui-border-green layui-btn-sm" lay-event="refresh">
            <i class="fa fa-refresh"></i>
        </button>
        <button class="layui-btn layui-btn-sm layui-bg-blue layui-btn-disabled" id="btn-batch-unmark" lay-event="batchUnmark">
            批量取消标记
        </button>
        <div class="layui-inline">
            <input type="text" name="keyword" placeholder="搜索卡密内容" class="layui-input layui-input-sm" style="width: 150px;">
        </div>
        <button class="layui-btn layui-btn-sm" lay-event="search">搜索</button>
    </div>
</script>

<!-- 未售出操作列 -->
<script type="text/html" id="operate_unsold">
    <div class="layui-clear-space">
        <a class="layui-btn layui-btn-primary" lay-event="view">查看</a>
        <a class="layui-btn layui-btn-normal btn-copy-row" data-index="{{ d.LAY_INDEX }}">复制</a>
        <a class="layui-btn" lay-event="edit">编辑</a>
        <a class="layui-btn layui-bg-red" lay-event="del">删除</a>
        {{#  if(!d.weight || d.weight <= 0){ }}
        <a class="layui-btn layui-bg-purple" lay-event="priority">优先销售</a>
        {{#  } else { }}
        <a class="layui-btn layui-btn-primary" lay-event="unpriority">取消优先</a>
        {{#  } }}
        <a class="layui-btn layui-btn-primary layui-border-blue" lay-event="mark">标记售出</a>
    </div>
</script>

<!-- 已售出操作列 -->
<script type="text/html" id="operate_sold">
    <div class="layui-clear-space">
        <a class="layui-btn layui-btn-xs" lay-event="view">查看</a>
    </div>
</script>

<!-- 标记售出操作列 -->
<script type="text/html" id="operate_marked">
    <div class="layui-clear-space">
        <a class="layui-btn layui-btn-primary" lay-event="view">查看</a>
        <a class="layui-btn layui-bg-blue" lay-event="unmark">取消标记</a>
    </div>
</script>

<script>
// 弹窗控制
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
// 点击遮罩关闭
document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
// ESC 关闭弹窗
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(modal) {
            modal.classList.remove('active');
        });
    }
});

if (window.top !== window.self) {
    document.documentElement.classList.add('in-layer');
}

layui.use(['table', 'element', 'form', 'layer', 'dropdown'], function(){
    var table = layui.table;
    var element = layui.element;
    var form = layui.form;
    var layer = layui.layer;
    var dropdown = layui.dropdown;
    var $ = layui.$;

    var goods_id = <?= $goods['id'] ?>;
    var token = '<?= LoginAuth::genToken() ?>';
    var currentEditData = null;
    var modalClipboard = new ClipboardJS('.btn-copy');
    var rowClipboard = new ClipboardJS('.btn-copy-row', {
        text: function(trigger){
            var index = $(trigger).data('index');
            var cache = table.cache && table.cache['stock_unsold'] ? table.cache['stock_unsold'] : [];
            var row = cache[index] || {};
            console.log(index)
            return row.content || '';
        }
    });

    modalClipboard.on('success', function(e){
        layer.msg('已复制到剪贴板');
        e.clearSelection();
    });

    modalClipboard.on('error', function(){
        layer.msg('复制失败，请手动复制');
    });

    rowClipboard.on('success', function(e){
        layer.msg('已复制到剪贴板');
        e.clearSelection();
    });

    rowClipboard.on('error', function(){
        layer.msg('复制失败，请手动复制');
    });

    // 搜索函数 - 未售出
    function searchUnsold() {
        var $toolbar = $('#stock_unsold').next('.layui-table-view').find('.layui-table-tool-temp');
        var keyword = $toolbar.find('input[name="keyword"]').val() || '';
        var sku_id = $toolbar.find('select[name="sku_id"]').val() || '';
        table.reload('stock_unsold', {
            page: {curr: 1},
            where: {keyword: keyword, sku_id: sku_id}
        });
    }

    // 搜索函数 - 已售出
    function searchSold() {
        var $toolbar = $('#stock_sold').next('.layui-table-view').find('.layui-table-tool-temp');
        var keyword = $toolbar.find('input[name="keyword"]').val() || '';
        table.reload('stock_sold', {
            page: {curr: 1},
            where: {keyword: keyword}
        });
    }

    // 搜索函数 - 标记售出
    function searchMarked() {
        var $toolbar = $('#stock_marked').next('.layui-table-view').find('.layui-table-tool-temp');
        var keyword = $toolbar.find('input[name="keyword"]').val() || '';
        table.reload('stock_marked', {
            page: {curr: 1},
            where: {keyword: keyword}
        });
    }

    // 未售出表格
    table.render({
        elem: '#stock_unsold',
        url: '<?= PLUGIN_URL ?>goods_once/api.php?action=stock_list&goods_id=' + goods_id,
        toolbar: '#toolbar_unsold',
        page: true,
        limit: 10,
        limits: [10, 20, 50, 100],
        defaultToolbar: [],
        cols: [[
            {type: 'checkbox'},
            <?php if($goods['is_sku'] == 'y'): ?>
            {field: 'sku_name', title: '规格', align: 'center'},
            <?php endif; ?>
            {field: 'content', title: '卡密内容'},
            {field: 'create_time_fmt', title: '添加时间', width: 160, align: 'center', sort: true},
            {title: '操作', width: 450, align: 'center', templet: '#operate_unsold'}
        ]],
        done: function(){
            var $toolbar = $('#stock_unsold').next('.layui-table-view').find('.layui-table-tool-temp');
            // 渲染工具栏中的 layui 组件
            form.render('select', 'stock-filter');
            renderPriorityMenu($toolbar);
            // 绑定搜索框回车事件
            $toolbar.find('input[name="keyword"]').off('keydown').on('keydown', function(e){
                if(e.keyCode === 13){
                    e.preventDefault();
                    searchUnsold();
                }
            });
        }
    });

    // 已售出表格
    table.render({
        elem: '#stock_sold',
        url: '<?= PLUGIN_URL ?>goods_once/api.php?action=sold_list&goods_id=' + goods_id,
        toolbar: '#toolbar_sold',
        page: true,
        limit: 10,
        limits: [10, 20, 50, 100],
        defaultToolbar: [],
        cols: [[
            <?php if($goods['is_sku'] == 'y'): ?>
            {field: 'sku_name', title: '规格', align: 'center'},
            <?php endif; ?>
            {field: 'content', title: '卡密内容'},
            {field: 'order_id', title: '订单ID', width: 100, align: 'center'},
            {field: 'sale_time_fmt', title: '售出时间', width: 160, align: 'center', sort: true},
            {title: '操作', width: 80, align: 'center', templet: '#operate_sold'}
        ]],
        done: function(){
            var $toolbar = $('#stock_sold').next('.layui-table-view').find('.layui-table-tool-temp');
            // 绑定搜索框回车事件
            $toolbar.find('input[name="keyword"]').off('keydown').on('keydown', function(e){
                if(e.keyCode === 13){
                    e.preventDefault();
                    searchSold();
                }
            });
        }
    });

    // 标记售出表格
    table.render({
        elem: '#stock_marked',
        url: '<?= PLUGIN_URL ?>goods_once/api.php?action=marked_list&goods_id=' + goods_id,
        toolbar: '#toolbar_marked',
        page: true,
        limit: 10,
        limits: [10, 20, 50, 100],
        defaultToolbar: [],
        cols: [[
            {type: 'checkbox'},
            <?php if($goods['is_sku'] == 'y'): ?>
            {field: 'sku_name', title: '规格', align: 'center'},
            <?php endif; ?>
            {field: 'content', title: '卡密内容'},
            {field: 'create_time_fmt', title: '添加时间', width: 160, align: 'center', sort: true},
            {title: '操作', width: 160, align: 'center', templet: '#operate_marked'}
        ]],
        done: function(){
            var $toolbar = $('#stock_marked').next('.layui-table-view').find('.layui-table-tool-temp');
            // 绑定搜索框回车事件
            $toolbar.find('input[name="keyword"]').off('keydown').on('keydown', function(e){
                if(e.keyCode === 13){
                    e.preventDefault();
                    searchMarked();
                }
            });
        }
    });

    // Tab 切换
    element.on('tab(stockTab)', function(data){
        if(data.index === 1){
            table.reload('stock_sold');
        }
        if(data.index === 2){
            table.reload('stock_marked');
        }
    });

    // 未售出工具栏事件
    table.on('toolbar(stock_unsold)', function(obj){
        var checkStatus = table.checkStatus('stock_unsold');
        switch(obj.event){
            case 'refresh':
                table.reload('stock_unsold');
                break;
            case 'add':
                $('#add-content').val('');
                $('#add-sku-id').val('');
                openModal('modal-add');
                break;
            case 'batchMark':
                var markData = checkStatus.data;
                if(markData.length === 0){
                    layer.msg('请先选择要标记的数据');
                    return;
                }
                var markIds = markData.map(function(item){ return item.id; });
                markSold(markIds);
                break;
            case 'batchDel':
                var data = checkStatus.data;
                if(data.length === 0){
                    layer.msg('请先选择要删除的数据');
                    return;
                }
                var ids = data.map(function(item){ return item.id; });
                deleteStock(ids);
                break;
            case 'dedupe':
                dedupeStock();
                break;
            case 'export':
                exportStock();
                break;
            case 'clearStock':
                clearStock();
                break;
            case 'search':
                searchUnsold();
                break;
        }
    });

    // 已售出工具栏事件
    table.on('toolbar(stock_sold)', function(obj){
        switch(obj.event){
            case 'refresh':
                table.reload('stock_sold');
                break;
            case 'search':
                searchSold();
                break;
        }
    });

    // 标记售出工具栏事件
    table.on('toolbar(stock_marked)', function(obj){
        var checkStatus = table.checkStatus('stock_marked');
        switch(obj.event){
            case 'refresh':
                table.reload('stock_marked');
                break;
            case 'batchUnmark':
                var data = checkStatus.data;
                if(data.length === 0){
                    layer.msg('请先选择要取消标记的数据');
                    return;
                }
                var ids = data.map(function(item){ return item.id; });
                unmarkSold(ids);
                break;
            case 'search':
                searchMarked();
                break;
        }
    });

    // 未售出行操作
    table.on('tool(stock_unsold)', function(obj){
        var data = obj.data;
        switch(obj.event){
            case 'view':
                $('#view-content').text(data.content);
                openModal('modal-view');
                break;
            case 'edit':
                currentEditData = data;
                $('#edit-id').val(data.id);
                $('#edit-content').val(data.content);
                $('#edit-sku-id').val(data.sku_id);
                openModal('modal-edit');
                break;
            case 'del':
                deleteStock([data.id]);
                break;
            case 'priority':
                updatePriority([data.id], true);
                break;
            case 'unpriority':
                updatePriority([data.id], false);
                break;
            case 'mark':
                markSold([data.id]);
                break;
        }
    });

    // 已售出行操作
    table.on('tool(stock_sold)', function(obj){
        if(obj.event === 'view'){
            $('#view-content').text(obj.data.content);
            openModal('modal-view');
        }
    });

    // 标记售出行操作
    table.on('tool(stock_marked)', function(obj){
        var data = obj.data;
        switch(obj.event){
            case 'view':
                $('#view-content').text(data.content);
                openModal('modal-view');
                break;
            case 'unmark':
                unmarkSold([data.id]);
                break;
        }
    });

    // 复选框事件
    table.on('checkbox(stock_unsold)', function(obj){
        var checkData = table.checkStatus('stock_unsold').data;
        if(checkData.length > 0){
            $('#btn-batch-del').removeClass('layui-btn-disabled');
            $('#btn-batch-mark').removeClass('layui-btn-disabled');
        } else {
            $('#btn-batch-del').addClass('layui-btn-disabled');
            $('#btn-batch-mark').addClass('layui-btn-disabled');
        }
    });

    table.on('checkbox(stock_marked)', function(obj){
        var checkData = table.checkStatus('stock_marked').data;
        if(checkData.length > 0){
            $('#btn-batch-unmark').removeClass('layui-btn-disabled');
        } else {
            $('#btn-batch-unmark').addClass('layui-btn-disabled');
        }
    });

    // 规格筛选
    form.on('select(filter-sku)', function(data){
        var keyword = $('#search-keyword').val();
        table.reload('stock_unsold', {
            page: {curr: 1},
            where: {keyword: keyword, sku_id: data.value}
        });
    });

    // 添加库存提交
    $('#btn-add-submit').on('click', function(){
        var btn = $(this);
        var sku_id = $('#add-sku-id').val();
        var content = $('#add-content').val();

        if(!content.trim()){
            layer.msg('请输入卡密内容');
            return;
        }

        btn.prop('disabled', true).find('.btn-text').text('提交中...');

        $.ajax({
            url: '<?= PLUGIN_URL ?>goods_once/api.php?action=add_stock',
            type: 'POST',
            dataType: 'json',
            data: {
                goods_id: goods_id,
                sku_id: sku_id,
                content: content,
                token: token
            },
            success: function(res){
                btn.prop('disabled', false).find('.btn-text').text('确认添加');
                if(res.code === 0){
                    layer.msg('添加成功，共添加 ' + res.data.count + ' 条');
                    closeModal('modal-add');
                    table.reload('stock_unsold');
                } else {
                    layer.msg(res.msg || '添加失败');
                }
            },
            error: function(){
                btn.prop('disabled', false).find('.btn-text').text('确认添加');
                layer.msg('请求失败');
            }
        });
    });

    // 编辑库存提交
    $('#btn-edit-submit').on('click', function(){
        var btn = $(this);
        var id = $('#edit-id').val();
        var sku_id = $('#edit-sku-id').val() || (currentEditData ? currentEditData.sku_id : 0);
        var content = $('#edit-content').val();

        if(!content.trim()){
            layer.msg('卡密内容不能为空');
            return;
        }

        btn.prop('disabled', true).find('.btn-text').text('保存中...');

        $.ajax({
            url: '<?= PLUGIN_URL ?>goods_once/api.php?action=edit_stock',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                goods_id: goods_id,
                sku_id: sku_id,
                content: content,
                token: token
            },
            success: function(res){
                btn.prop('disabled', false).find('.btn-text').text('保存修改');
                if(res.code === 0){
                    layer.msg('修改成功');
                    closeModal('modal-edit');
                    table.reload('stock_unsold');
                } else {
                    layer.msg(res.msg || '修改失败');
                }
            },
            error: function(){
                btn.prop('disabled', false).find('.btn-text').text('保存修改');
                layer.msg('请求失败');
            }
        });
    });

    // 删除库存
    function deleteStock(ids){
        layer.confirm('确定要删除选中的 ' + ids.length + ' 条数据吗？', {icon: 3}, function(index){
            layer.close(index);
            var loading = layer.load();
            $.ajax({
                url: '<?= PLUGIN_URL ?>goods_once/api.php?action=delete_stock',
                type: 'POST',
                dataType: 'json',
                data: {
                    ids: ids.join(','),
                    goods_id: goods_id,
                    token: token
                },
                success: function(res){
                    layer.close(loading);
                    if(res.code === 0){
                        layer.msg('删除成功');
                        table.reload('stock_unsold');
                    } else {
                        layer.msg(res.msg || '删除失败');
                    }
                },
                error: function(){
                    layer.close(loading);
                    layer.msg('请求失败');
                }
            });
        });
    }

    // 清空库存（需要二次确认）
    function clearStock(){
        layer.confirm('确定要清空当前商品所有库存吗？', {icon: 3, title: '温馨提示'}, function(index){
            layer.close(index);
            layer.confirm('清空后无法恢复，确认继续？', {icon: 3, title: '再次确认'}, function(index2){
                layer.close(index2);
                var loading = layer.load();
                $.ajax({
                    url: '<?= PLUGIN_URL ?>goods_once/api.php?action=clear_stock',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        goods_id: goods_id,
                        token: token
                    },
                    success: function(res){
                        layer.close(loading);
                        if(res.code === 0){
                            layer.msg('清空成功');
                            setTimeout(function(){
                                location.reload();
                            }, 600);
                        } else {
                            layer.msg(res.msg || '清空失败');
                        }
                    },
                    error: function(){
                        layer.close(loading);
                        layer.msg('请求失败');
                    }
                });
            });
        });
    }

    function renderPriorityMenu($toolbar){
        var btn = $toolbar.find('.priority-menu')[0];
        if (!btn) {
            return;
        }
        dropdown.render({
            elem: btn,
            data: [
                {title: '批量优先', id: 'batch_priority'},
                {title: '批量取消优先', id: 'batch_unpriority'}
            ],
            click: function(obj){
                var data = table.checkStatus('stock_unsold').data || [];
                if (data.length === 0) {
                    layer.msg('请先选择要操作的数据');
                    return;
                }
                var ids = data.map(function(item){ return item.id; });
                if (obj.id === 'batch_priority') {
                    updatePriority(ids, true);
                } else if (obj.id === 'batch_unpriority') {
                    updatePriority(ids, false);
                }
            }
        });
    }

    function updatePriority(ids, isPriority){
        if (!ids || ids.length === 0) {
            layer.msg('请先选择要操作的数据');
            return;
        }
        var action = isPriority ? 'priority_stock' : 'unpriority_stock';
        var loading = layer.load();
        $.ajax({
            url: '<?= PLUGIN_URL ?>goods_once/api.php?action=' + action,
            type: 'POST',
            dataType: 'json',
            data: {
                ids: ids.join(','),
                goods_id: goods_id,
                token: token
            },
            success: function(res){
                layer.close(loading);
                if(res.code === 0){
                    layer.msg(isPriority ? '已设为优先销售' : '已取消优先');
                    table.reload('stock_unsold');
                } else {
                    layer.msg(res.msg || '操作失败');
                }
            },
            error: function(){
                layer.close(loading);
                layer.msg('请求失败');
            }
        });
    }

    // 标记售出
    function markSold(ids){
        if (!ids || ids.length === 0) {
            layer.msg('请先选择要标记的数据');
            return;
        }
        layer.confirm('确定要标记选中的 ' + ids.length + ' 条卡密为已售出吗？', {icon: 3}, function(index){
            layer.close(index);
            var loading = layer.load();
            $.ajax({
                url: '<?= PLUGIN_URL ?>goods_once/api.php?action=mark_sold',
                type: 'POST',
                dataType: 'json',
                data: {
                    ids: ids.join(','),
                    goods_id: goods_id,
                    token: token
                },
                success: function(res){
                    layer.close(loading);
                    if(res.code === 0){
                        layer.msg('标记成功');
                        table.reload('stock_unsold');
                        table.reload('stock_marked');
                    } else {
                        layer.msg(res.msg || '操作失败');
                    }
                },
                error: function(){
                    layer.close(loading);
                    layer.msg('请求失败');
                }
            });
        });
    }

    // 取消标记
    function unmarkSold(ids){
        if (!ids || ids.length === 0) {
            layer.msg('请先选择要取消标记的数据');
            return;
        }
        layer.confirm('确定要取消选中 ' + ids.length + ' 条卡密的标记吗？', {icon: 3}, function(index){
            layer.close(index);
            var loading = layer.load();
            $.ajax({
                url: '<?= PLUGIN_URL ?>goods_once/api.php?action=unmark_sold',
                type: 'POST',
                dataType: 'json',
                data: {
                    ids: ids.join(','),
                    goods_id: goods_id,
                    token: token
                },
                success: function(res){
                    layer.close(loading);
                    if(res.code === 0){
                        layer.msg('已取消标记');
                        table.reload('stock_marked');
                        table.reload('stock_unsold');
                    } else {
                        layer.msg(res.msg || '操作失败');
                    }
                },
                error: function(){
                    layer.close(loading);
                    layer.msg('请求失败');
                }
            });
        });
    }

    // 导出库存
    function exportStock(){
        var sku_id = $('#filter-sku').val() || '';
        window.open('<?= PLUGIN_URL ?>goods_once/api.php?action=export_stock&goods_id=' + goods_id + '&sku_id=' + sku_id + '&token=' + token);
    }

    // 库存去重
    function dedupeStock(){
        layer.confirm('确定要对当前商品库存进行去重吗？将删除重复的卡密，仅保留一条。', {icon: 3}, function(index){
            layer.close(index);
            var loading = layer.load();
            $.ajax({
                url: '<?= PLUGIN_URL ?>goods_once/api.php?action=dedupe_stock',
                type: 'POST',
                dataType: 'json',
                data: {
                    goods_id: goods_id,
                    token: token
                },
                success: function(res){
                    layer.close(loading);
                    if(res.code === 0){
                        var deleted = res.data && typeof res.data.deleted !== 'undefined' ? res.data.deleted : 0;
                        layer.msg('去重完成，删除重复 ' + deleted + ' 条');
                        setTimeout(function(){
                            location.reload();
                        }, 600);
                    } else {
                        layer.msg(res.msg || '去重失败');
                    }
                },
                error: function(){
                    layer.close(loading);
                    layer.msg('请求失败');
                }
            });
        });
    }

});
</script>
