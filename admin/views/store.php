<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
/* 表格容器 */
.plugin-table-wrap {
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 10px;
    overflow-x: auto;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
}

.plugin-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    min-width: 980px;
}

.plugin-table thead {
    background: linear-gradient(180deg, #f9fafb 0%, #f2f4f6 100%);
}

.plugin-table th {
    text-align: left;
    font-size: 13px;
    color: #5f6b74;
    letter-spacing: 0.3px;
    font-weight: 600;
    padding: 14px 16px;
    border-bottom: 1px solid #e6e6e6;
}

.plugin-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
    color: #262626;
    font-size: 13px;
}

.plugin-table tbody tr:nth-child(even) {
    background: #fcfcfd;
}

.plugin-table tbody tr:hover {
    background: #f3f8f6;
}

.plugin-table tbody tr:last-child td {
    border-bottom: none;
}

.col-plugin { width: 26%; }
.col-version { width: 8%; }
.col-desc { width: 26%; }
.col-price { width: 8%; }
.col-action { width: 16%; }

.plugin-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.plugin-thumb {
    width: 46px;
    height: 46px;
    border-radius: 6px;
    overflow: hidden;
    background: #f5f5f5;
    border: 1px solid #eee;
    flex: 0 0 auto;
}

.plugin-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s;
}

.plugin-table tbody tr:hover .plugin-thumb img {
    transform: scale(1.05);
}

.plugin-meta {
    min-width: 0;
}

.plugin-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f1f1f;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.plugin-meta-sub {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 2px;
}

.plugin-desc {
    color: #7a7a7a;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.price-chip.price-free {
    background: #f0f9f0;
    color: #2f7a3e;
    border-color: #b7e3bf;
}

.price-chip.price-paid {
    background: #fff1f0;
    color: #cf1322;
    border-color: #ffa39e;
}


/* 加载和空状态 */
.plugin-loading td,
.plugin-empty td {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.plugin-loading i {
    font-size: 32px;
    color: #1890ff;
}

.plugin-empty i {
    font-size: 48px;
    color: #d9d9d9;
    margin-bottom: 16px;
}

/* 分页 */
.plugin-pagination {
    display: flex;
    justify-content: center;
    padding: 20px 0 40px;
}

/* 工具栏 */
.plugin-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 10px;
}

.plugin-toolbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.plugin-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 4px 8px;
}

.plugin-search input {
    border: none;
    outline: none;
    font-size: 13px;
    width: 220px;
}

.plugin-search .layui-icon {
    color: #8c8c8c;
}

.plugin-search .layui-btn {
    height: 28px;
    line-height: 28px;
    padding: 0 10px;
    font-size: 12px;
    border-radius: 4px;
}

.plugin-count {
    color: #8c8c8c;
    font-size: 13px;
}

/* 筛选栏 */
.store-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px 18px;
    padding: 14px 16px;
    border-bottom: 1px solid #eaecef;
    background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
}

.filter-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.filter-label {
    font-size: 13px;
    color: #2c2c2c;
    margin-right: 4px;
}

.filter-chip {
    border: 1px solid #d9e4e1;
    background: #fff;
    color: #39685f;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-chip:hover {
    background: #4C7D71;
    color: #fff;
    border-color: #4C7D71;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(76, 125, 113, 0.15);
}

.filter-chip.is-active {
    background: #4C7D71;
    color: #fff;
    border-color: #4C7D71;
    box-shadow: 0 4px 10px rgba(76, 125, 113, 0.18);
}

.filter-divider {
    width: 1px;
    height: 18px;
    background: #e6e6e6;
}

/* 响应式 */
@media (max-width: 900px) {
    .plugin-search input { width: 160px; }
}

@media (max-width: 600px) {
    .plugin-toolbar-right {
        width: 100%;
        justify-content: space-between;
    }

    .plugin-search {
        width: 100%;
        justify-content: space-between;
    }

    .plugin-search input {
        width: 100%;
    }
}
</style>

<!-- 工具栏 -->
<div class="plugin-toolbar">
    <div class="plugin-toolbar-left">
        <button class="layui-btn" id="refresh-btn">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
    </div>
    <div class="plugin-toolbar-right">
        <div class="plugin-search">
            <i class="layui-icon layui-icon-search"></i>
            <input type="text" id="plugin-search" placeholder="搜索插件名称或描述">
            <button class="layui-btn layui-btn-sm" id="search-btn">搜索</button>
        </div>
        <span class="plugin-count">共 <strong id="total-count">0</strong> 个应用</span>
    </div>
</div>

<!-- 表格容器 -->
<div class="plugin-table-wrap">
    <div class="store-filter">
        <div class="filter-group" data-filter-group="type">
            <span class="filter-label">类型</span>
            <button class="filter-chip is-active" type="button" data-filter-type="all">全部</button>
            <button class="filter-chip" type="button" data-filter-type="tpl">模板主题</button>
        </div>
        <div class="filter-divider"></div>
        <div class="filter-group" data-filter-group="plugin">
            <span class="filter-label">插件分类</span>
            <?php foreach ($plugin_type_arr as $item): ?>
                <button class="filter-chip" type="button" data-filter-type="plu" data-plugin-type="<?= $item['id'] ?>"><?= $item['title'] ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <table class="plugin-table" id="plugin-table">
        <thead>
            <tr>
                <th class="col-plugin">应用</th>
                <th class="col-version">版本</th>
                <th class="col-desc">简介</th>
                <th class="col-price">至尊</th>
                <th class="col-price">SVIP</th>
                <th class="col-price">VIP</th>
                <th class="col-action">操作</th>
            </tr>
        </thead>
        <tbody id="plugin-rows">
            <tr class="plugin-loading">
                <td colspan="7">
                    <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>
                    <p style="margin-top: 12px;">加载中...</p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 分页 -->
<div class="plugin-pagination" id="plugin-pagination"></div>

<script>
layui.use(['laypage', 'layer'], function(){
    var laypage = layui.laypage;
    var layer = layui.layer;

    var currentPage = 1;
    var pageSize = 12;
    var currentKeyword = '';
    var currentSid = new URLSearchParams(window.location.search).get('sid') || '';
    var currentType = 'all';
    var currentPluginType = 0;

    function setActiveFilter(type, pluginType) {
        $('.filter-chip').removeClass('is-active');
        if (type === 'all') {
            $('.filter-chip[data-filter-type="all"]').addClass('is-active');
            return;
        }
        if (type === 'tpl') {
            $('.filter-chip[data-filter-type="tpl"]').addClass('is-active');
            return;
        }
        if (type === 'plu') {
            $('.filter-chip[data-filter-type="plu"][data-plugin-type="' + pluginType + '"]').addClass('is-active');
        }
    }

    function resetAllFilters() {
        currentType = 'all';
        currentPluginType = 0;
        currentKeyword = '';
        currentSid = '';
        $('#plugin-search').val('');
        setActiveFilter('all');
        loadApps(1, '');
    }

    // 加载数据
    function loadApps(page, keyword) {
        currentPage = page || 1;
        if (typeof keyword !== 'undefined') {
            currentKeyword = keyword;
        }

        $('#plugin-rows').html(`
            <tr class="plugin-loading">
                <td colspan="7">
                    <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>
                    <p style="margin-top: 12px;">加载中...</p>
                </td>
            </tr>
        `);

        var url = '?action=index';
        var data = { page: currentPage, limit: pageSize, keyword: currentKeyword };

        if (currentType === 'tpl') {
            url = '?action=tpl_ajax';
        } else if (currentType === 'plu') {
            url = '?action=plu_ajax';
            data.plugin_type = currentPluginType;
        } else {
            data.sid = currentSid;
        }

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.code === 0 && res.data && res.data.length > 0) {
                    renderRows(res.data);
                    renderPagination(res.count || res.data.length);
                    $('#total-count').text(res.count || res.data.length);
                } else {
                    $('#plugin-rows').html(`
                        <tr class="plugin-empty">
                            <td colspan="7">
                                <i class="layui-icon layui-icon-face-surprised"></i>
                                <p>暂无应用数据</p>
                            </td>
                        </tr>
                    `);
                    $('#total-count').text(0);
                }
            },
            error: function() {
                $('#plugin-rows').html(`
                    <tr class="plugin-empty">
                        <td colspan="7">
                            <i class="layui-icon layui-icon-face-cry"></i>
                            <p>加载失败，请重试</p>
                        </td>
                    </tr>
                `);
            }
        });
    }

    // 渲染表格行
    function renderRows(data) {
        var html = '';

        data.forEach(function(item) {
            var actionBtn = '';
            if (item.is_install === 'y') {
                actionBtn = '<button class="layui-btn btn-installed layui-btn-disabled"><i class="layui-icon layui-icon-ok"></i> 已安装</button>';
            } else if (item.reg_type == '0' && item.my_price > 0) {
                actionBtn = '<button class="layui-btn btn-auth layui-btn-yellow" data-action="auth"><i class="layui-icon layui-icon-vercode"></i> 限授权用户</button>';
            } else if (item.is_buy === 'y' && item.reg_type > 0) {
                actionBtn = '<button class="layui-btn btn-install layui-btn-green" data-action="install" data-id="' + item.id + '" data-type="' + item.type + '" data-name="' + item.name + '"><i class="layui-icon layui-icon-download-circle"></i> 立即安装</button>';
            } else if (item.is_buy === 'n' && item.my_price > 0 && item.reg_type > 0) {
                actionBtn = '<a href="<?= EM_LINE[0]['value'] ?>api/emshop.php?action=buy&emkey=<?= $emkey ?>&plugin=' + item.id + '" target="_blank" class="layui-btn btn-buy layui-btn-blue">' + item.my_price + ' 立即购买</a>';
            } else if (item.my_price == 0) {
                actionBtn = '<button class="layui-btn btn-install layui-btn-green" data-action="install" data-id="' + item.id + '" data-type="' + item.type + '" data-name="' + item.name + '"><i class="layui-icon layui-icon-download-circle"></i> 免费安装</button>';
            }

            html += `
                <tr data-id="${item.id}">
                    <td class="col-plugin">
                        <div class="plugin-cell">
                            <div class="plugin-thumb">
                                <img src="${item.cover || './views/images/null.png'}" onerror="this.onerror=null; this.src='./views/images/null.png'" alt="${item.name}">
                            </div>
                            <div class="plugin-meta">
                                <div class="plugin-name" title="${item.name}">${item.name}</div>
                                <div class="plugin-meta-sub">作者: ${item.author}</div>
                            </div>
                        </div>
                    </td>
                    <td class="col-version">v${item.version || '1.0'}</td>
                    <td class="col-desc">
                        <div class="plugin-desc" title="${item.description || '暂无描述'}">${item.description || '暂无描述'}</div>
                    </td>
                    <td class="col-price">
                        <span class="price-chip price-free">免费</span>
                    </td>
                    <td class="col-price">
                        <span class="price-chip ${item.svip_price == 0 ? 'price-free' : 'price-paid'}">${item.svip_price == 0 ? '免费' : '¥' + item.svip_price}</span>
                    </td>
                    <td class="col-price">
                        <span class="price-chip ${item.vip_price == 0 ? 'price-free' : 'price-paid'}">${item.vip_price == 0 ? '免费' : '¥' + item.vip_price}</span>
                    </td>
                    <td class="col-action">
                        <div class="plugin-action">${actionBtn}</div>
                    </td>
                </tr>
            `;
        });

        $('#plugin-rows').html(html);
    }

    // 渲染分页
    function renderPagination(total) {
        if (total <= pageSize) {
            $('#plugin-pagination').hide();
            return;
        }

        $('#plugin-pagination').show();
        laypage.render({
            elem: 'plugin-pagination',
            count: total,
            limit: pageSize,
            curr: currentPage,
            theme: '#1890ff',
            jump: function(obj, first) {
                if (!first) {
                    loadApps(obj.curr);
                    $('html, body').animate({ scrollTop: $('.plugin-table-wrap').offset().top - 80 }, 300);
                }
            }
        });
    }

    // 安装事件
    $(document).on('click', '[data-action="install"]', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var type = $btn.data('type') === 'template' ? 'tpl' : 'plugin';

        var loadIndex = layer.load(2);
        layer.msg('正在安装，请稍后');
        $btn.prop('disabled', true).html('<i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 安装中...');

        $.ajax({
            url: './store.php?action=install',
            type: 'POST',
            dataType: 'json',
            data: { type: type, plugin_id: id },
            success: function(e) {
                layer.close(loadIndex);
                if (e.code === 400) {
                    $btn.prop('disabled', false).html('<i class="layui-icon layui-icon-download-circle"></i> 重试');
                    return layer.msg(e.msg, {icon: 2});
                }
                layer.msg('安装成功！', {icon: 1});
                setTimeout(function() {
                    loadApps(currentPage);
                }, 500);
            },
            error: function(err) {
                layer.close(loadIndex);
                $btn.prop('disabled', false).html('<i class="layui-icon layui-icon-download-circle"></i> 重试');
                layer.alert(err.responseJSON ? err.responseJSON.msg : '安装失败');
            }
        });
    });

    // 授权事件
    $(document).on('click', '[data-action="auth"]', function() {
        layer.confirm('此插件仅授权用户可安装，是否前往授权页面？', {
            btn: ['立即前往', '取消'],
            icon: 3,
            title: '温馨提示'
        }, function(index) {
            layer.close(index);
            location.href = "./auth.php";
        });
    });

    // 图片预览
    $(document).on('click', '.plugin-thumb img', function(e) {
        e.stopPropagation();
        var src = $(this).attr('src');
        var name = $(this).closest('tr').find('.plugin-name').text();
        layer.photos({
            photos: {
                "title": name,
                "start": 0,
                "data": [{ "alt": name, "pid": 1, "src": src }]
            }
        });
    });

    // 刷新
    $('#refresh-btn').on('click', function() {
        loadApps(currentPage);
        layer.msg('刷新成功', {icon: 1, time: 1000});
    });

    // 筛选
    $(document).on('click', '.filter-chip', function() {
        var type = $(this).data('filter-type');
        if (type === 'all') {
            resetAllFilters();
            return;
        }
        if (type === 'tpl') {
            currentType = 'tpl';
            currentPluginType = 0;
            currentKeyword = '';
            $('#plugin-search').val('');
            setActiveFilter('tpl');
            loadApps(1, '');
            return;
        }
        if (type === 'plu') {
            currentType = 'plu';
            currentPluginType = $(this).data('plugin-type') || 0;
            currentKeyword = '';
            $('#plugin-search').val('');
            setActiveFilter('plu', currentPluginType);
            loadApps(1, '');
        }
    });

    // 搜索
    $('#search-btn').on('click', function() {
        var keyword = $('#plugin-search').val().trim();
        loadApps(1, keyword);
    });

    $('#plugin-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('#search-btn').trigger('click');
        }
    });

    // 初始加载
    loadApps(1);
});
</script>

<script>
$(function () {
    $("#menu-store").addClass('active');
    setTimeout(hideActived, 3600);

    $('.category').on('change', function () {
        var selectedCategory = $(this).val();
        if (selectedCategory) {
            window.location.href = './store.php?sid=' + selectedCategory;
        }
    });
});
</script>
