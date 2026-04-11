<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php if (isset($_GET['activated'])): ?>
    <div class="alert alert-success">保存成功</div><?php endif ?>

<style>
    /* 页面整体容器 */
    .widget-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    /* 卡片基础样式 */
    .em-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        margin-bottom: 20px;
        transition: all 0.3s;
        border: 1px solid #f0f0f0;
    }
    .em-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .em-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
        border-radius: 8px 8px 0 0;
    }
    .em-card-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    .em-card-body {
        padding: 20px;
    }

    /* 左侧组件库样式 */
    .widget-item {
        border: 1px solid #eee;
        border-radius: 6px;
        margin-bottom: 10px;
        background: #fff;
        overflow: hidden;
    }
    .widget-header {
        padding: 12px 15px;
        background: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.2s;
    }
    .widget-header:hover {
        background: #f9f9f9;
    }
    .widget-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #666;
    }
    .widget-name {
        font-weight: 500;
        color: #333;
        flex: 1;
    }
    .widget-status-badge {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #eee;
        color: #999;
    }
    .widget-status-badge.active {
        background: #EDF2F1;
        color: #4C7D71;
    }
    .widget-content {
        display: none;
        padding: 15px;
        border-top: 1px dashed #eee;
        background: #fafafa;
    }
    
    /* 右侧已启用组件样式 */
    .active-widget-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 6px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .active-widget-item:hover {
        border-color: #4C7D71;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(76, 125, 113, 0.15);
    }
    .active-widget-info {
        flex: 1;
        margin-left: 12px;
    }
    .active-widget-title {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    .active-widget-desc {
        font-size: 12px;
        color: #999;
        margin-top: 2px;
    }
    
    /* 响应式布局 */
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: -10px;
    }
    .col-md-6 {
        width: 50%;
        padding: 10px;
        box-sizing: border-box;
    }
    @media (max-width: 768px) {
        .col-md-6 {
            width: 100%;
        }
        .widget-container {
            padding: 10px;
        }
    }

    /* 按钮样式优化 */
    .btn-action {
        padding: 4px 12px;
        font-size: 12px;
        border-radius: 4px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .btn-add {
        background: #EDF2F1;
        color: #4C7D71;
    }
    .btn-add:hover {
        background: #4C7D71;
        color: #fff;
    }
    .btn-del {
        background: #fff1f0;
        color: #ff4d4f;
    }
    .btn-del:hover {
        background: #ff4d4f;
        color: #fff;
    }

    /* 弹窗自定义样式 */
    .em-modal-skin .layui-layer-title {
        background: #fff;
        border-bottom: none;
        padding: 20px 20px 0 20px;
        height: auto;
        line-height: normal;
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    .em-modal-skin .layui-layer-setwin {
        right: 20px;
        top: 20px;
    }
    .em-modal-skin .layui-layer-setwin .layui-layer-close1 {
        background: none;
        width: 24px;
        height: 24px;
        margin-left: 0;
        font-size: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
        cursor: pointer;
    }
    .em-modal-skin .layui-layer-setwin .layui-layer-close1::before,
    .em-modal-skin .layui-layer-setwin .layui-layer-close1::after {
        content: '';
        position: absolute;
        width: 14px;
        height: 2px;
        background-color: #999;
        transform: rotate(45deg);
        border-radius: 1px;
    }
    .em-modal-skin .layui-layer-setwin .layui-layer-close1::after {
        transform: rotate(-45deg);
    }
    .em-modal-skin .layui-layer-setwin .layui-layer-close1:hover {
        background-color: #f5f5f5;
    }
    .em-modal-skin .layui-layer-setwin .layui-layer-close1:hover::before,
    .em-modal-skin .layui-layer-setwin .layui-layer-close1:hover::after {
        background-color: #666;
    }
    
    /* 弹窗圆角 */
    body .em-modal-skin {
        border-radius: 20px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        overflow: hidden;
    }
</style>

<div class="widget-container">
    <div class="row">
        <!-- 左侧：组件库 -->
        <div class="col-md-6">
            <div class="em-card">
                <div class="em-card-header">
                    <h3 class="em-card-title">
                        <i class="fa fa-cubes" style="color: #1890ff; margin-right: 8px;"></i>组件库
                    </h3>
                    <button type="button" class="layui-btn layui-btn-sm layui-btn" id="btnAddWidget">
                        <i class="layui-icon layui-icon-add-1"></i> 自定义组件
                    </button>
                </div>
                <div class="em-card-body" id="adm_widget_list">
                    <!-- 系统组件 -->
                    <?php 
                    $sys_widgets = [
                        'blogger' => ['icon' => 'fa-user', 'name' => '个人资料'],
                        'calendar' => ['icon' => 'fa-calendar', 'name' => '日历'],
                        'sort' => ['icon' => 'fa-folder-open', 'name' => '分类'],
                        'archive' => ['icon' => 'fa-archive', 'name' => '存档'],
                        'newcomm' => ['icon' => 'fa-comments', 'name' => '最新评论'],
                        'newlog' => ['icon' => 'fa-file-text', 'name' => '最新文章'],
                        'hotlog' => ['icon' => 'fa-fire', 'name' => '热门文章'],
                        'link' => ['icon' => 'fa-link', 'name' => '友情链接'],
                        'search' => ['icon' => 'fa-search', 'name' => '搜索'],
                    ];
                    
                    foreach ($sys_widgets as $id => $info): 
                    ?>
                    <div class="widget-item" id="<?= $id ?>">
                        <div class="widget-header">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="widget-icon"><i class="fa <?= $info['icon'] ?>"></i></div>
                                <span class="widget-name"><?= $info['name'] ?></span>
                            </div>
                            <div class="widget-actions">
                                <button class="btn-action btn-add widget-act-add">添加</button>
                                <button class="btn-action btn-del widget-act-del" style="display: none;">已添加</button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <form action="widgets.php?action=setwg&wg=<?= $id ?>" method="post" class="layui-form">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="title" class="layui-input" value="<?= $customWgTitle[$id] ?>" />
                                    </div>
                                </div>
                                <?php if($id == 'newcomm'): ?>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">显示数量</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="index_comnum" class="layui-input" value="<?= Option::get('index_comnum') ?>" />
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">截取字数</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="comment_subnum" class="layui-input" value="<?= Option::get('comment_subnum') ?>" />
                                    </div>
                                </div>
                                <?php elseif(in_array($id, ['newlog', 'hotlog'])): ?>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">显示数量</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="index_<?= $id == 'newlog' ? 'newlog' : 'hotlognum' ?>" class="layui-input" value="<?= Option::get($id == 'newlog' ? 'index_newlognum' : 'index_hotlognum') ?>" />
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="layui-form-item mb-0 text-right">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit>保存配置</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- 自定义组件 -->
                    <?php
                    foreach ($custom_widget as $key => $val):
                        preg_match("/^custom_wg_(\d+)/", $key, $matches);
                        $custom_wg_title = empty($val['title']) ? '未命名组件(' . $matches[1] . ')' : $val['title'];
                    ?>
                    <div class="widget-item" id="<?= $key ?>">
                        <div class="widget-header">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="widget-icon" style="color: #eb2f96; background: #fff0f6;"><i class="fa fa-code"></i></div>
                                <span class="widget-name"><?= $custom_wg_title ?></span>
                                <span class="layui-badge-rim">自定义</span>
                            </div>
                            <div class="widget-actions">
                                <button class="btn-action btn-add widget-act-add">添加</button>
                                <button class="btn-action btn-del widget-act-del" style="display: none;">已添加</button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <form action="widgets.php?action=setwg&wg=custom_text" method="post" class="layui-form">
                                <input type="hidden" name="custom_wg_id" value="<?= $key ?>" />
                                <div class="layui-form-item">
                                    <label class="layui-form-label">标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="title" class="layui-input" value="<?= $val['title'] ?>" />
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">内容</label>
                                    <div class="layui-input-block">
                                        <textarea name="content" class="layui-textarea" style="height:150px;"><?= $val['content'] ?></textarea>
                                    </div>
                                </div>
                                <div class="layui-form-item mb-0 text-right">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit>保存</button>
                                    <a class="layui-btn layui-btn-sm layui-btn-danger" href="widgets.php?action=setwg&wg=custom_text&rmwg=<?= $key ?>">删除</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- 右侧：已启用组件 -->
        <div class="col-md-6">
            <div class="em-card">
                <div class="em-card-header">
                    <h3 class="em-card-title">
                        <i class="fa fa-check-circle" style="color: #4C7D71; margin-right: 8px;"></i>已启用组件
                    </h3>
                    <span class="text-muted" style="font-size: 12px;">按保存顺序显示</span>
                </div>
                <div class="em-card-body">
                    <form action="widgets.php?action=compages" method="post">
                        <div class="adm_widget_box" style="min-height: 100px;">
                            <?php
                            foreach ($widgets as $widget):
                                $flg = strpos($widget, 'custom_wg_') === 0;
                                $title = ($flg && isset($custom_widget[$widget]['title'])) ? $custom_widget[$widget]['title'] : '';
                                if ($flg && empty($title)) {
                                    preg_match("/^custom_wg_(\d+)/", $widget, $matches);
                                    $title = '未命名组件(' . $matches[1] . ')';
                                }
                                // 获取对应图标
                                $icon = 'fa-cube';
                                $icon_color = '#4C7D71';
                                $bg_color = '#EDF2F1';
                                
                                if(!$flg && isset($sys_widgets[$widget])) {
                                    $icon = $sys_widgets[$widget]['icon'];
                                    $title = $sys_widgets[$widget]['name']; // 使用标准名称
                                } elseif($flg) {
                                    $icon = 'fa-code';
                                    $icon_color = '#4C7D71';
                                    $bg_color = '#EDF2F1';
                                }
                            ?>
                            <div class="active-widget-item" id="em_<?= $widget ?>">
                                <div class="widget-icon" style="color: <?= $icon_color ?>; background: <?= $bg_color ?>;">
                                    <i class="fa <?= $icon ?>"></i>
                                </div>
                                <div class="active-widget-info">
                                    <div class="active-widget-title"><?= $flg ? $title : $widgetTitle[$widget] ?></div>
                                    <div class="active-widget-desc">ID: <?= $widget ?></div>
                                </div>
                                <input type="hidden" name="widgets[]" value="<?= $widget ?>" />
                                <button type="button" class="layui-btn layui-btn-xs layui-btn-danger remove-active-widget">移除</button>
                            </div>
                            <?php endforeach ?>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="submit" class="layui-btn layui-btn-normal" style="width: 100%;">保存组件</button>
                            <div class="mt-2">
                                <a href="javascript:eb_confirm(0, 'reset_widget', '<?= LoginAuth::genToken() ?>');" class="text-muted" style="font-size: 12px;">重置默认组件</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加自定义组件弹窗 -->
<div id="add-widget-modal" style="display: none;">
    <div class="em-modal-box">
        <div class="em-modal-close-btn" onclick="layer.close(layer.index)">
            <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
        <div class="em-modal-header">
            <div class="em-modal-icon-wrapper"><i class="layui-icon layui-icon-component"></i></div>
            <div class="em-modal-title">添加自定义组件</div>
            <div class="em-modal-desc">创建一个新的侧边栏组件，支持 HTML 代码</div>
        </div>
        <div class="em-modal-body">
            <form action="widgets.php?action=setwg&wg=custom_text" method="post" class="layui-form">
                <div class="layui-form-item">
                    <label class="layui-form-label" style="padding-left: 0; color: #4C7D71; font-weight: 500;">组件名</label>
                    <div class="layui-input-block" style="margin-left: 0;">
                        <input type="text" name="new_title" required placeholder="请输入组件名称" class="layui-input" 
                               style="border-color: #EDF2F1; background-color: #FAFAFA; border-radius: 4px; height: 42px;">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label" style="padding-left: 0; color: #4C7D71; font-weight: 500;">内容</label>
                    <div class="layui-input-block" style="margin-left: 0;">
                        <textarea name="new_content" required placeholder="支持HTML代码" class="layui-textarea" rows="8"
                                  style="border-color: #EDF2F1; background-color: #FAFAFA; border-radius: 4px; min-height: 150px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="layui-form-item text-center" style="margin-top: 30px; margin-bottom: 0;">
                    <button class="layui-btn" lay-submit style="background-color: #4C7D71; width: 100%; height: 42px; line-height: 42px; font-size: 15px; border-radius: 4px; letter-spacing: 1px;">保存组件</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    layui.use(['element', 'form', 'layer'], function(){
        var element = layui.element;
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.$;

        // 折叠面板逻辑
        $('.widget-header').click(function(e) {
            if ($(e.target).closest('.widget-actions').length > 0) return;
            $(this).next('.widget-content').slideToggle(200);
        });

        // 初始化状态：检查已启用的组件，隐藏左侧添加按钮
        var widgets = $(".active-widget-item").map(function() {
            return $(this).attr("id").substring(3); // remove 'em_'
        }).get();
        
        $.each(widgets, function(i, widget_id) {
            $("#" + widget_id + " .widget-act-add").hide();
            $("#" + widget_id + " .widget-act-del").show().text('已添加').addClass('layui-btn-disabled').prop('disabled', true);
        });

        // 左侧点击添加
        $(document).on('click', '.widget-act-add', function(e) {
            e.stopPropagation();
            var widget_item = $(this).closest('.widget-item');
            var title = widget_item.find('.widget-name').text();
            var widget_id = widget_item.attr('id');
            var icon_class = widget_item.find('.widget-icon i').attr('class');
            var icon_style = widget_item.find('.widget-icon').attr('style') || '';
            
            var widget_html = `
                <div class="active-widget-item" id="em_${widget_id}">
                    <div class="widget-icon" style="${icon_style}">
                        <i class="${icon_class}"></i>
                    </div>
                    <div class="active-widget-info">
                        <div class="active-widget-title">${title}</div>
                        <div class="active-widget-desc">ID: ${widget_id}</div>
                    </div>
                    <input type="hidden" name="widgets[]" value="${widget_id}" />
                    <button type="button" class="layui-btn layui-btn-xs layui-btn-danger remove-active-widget">移除</button>
                </div>
            `;
            
            $(".adm_widget_box").append(widget_html);
            $(this).hide();
            $(this).next(".widget-act-del").show().text('已添加').addClass('layui-btn-disabled').prop('disabled', true);
            layer.msg('已添加到启用列表', {icon: 1, time: 1000});
        });

        // 右侧点击移除
        $(document).on('click', '.remove-active-widget', function() {
            var widget_div = $(this).closest('.active-widget-item');
            var widget_id = widget_div.attr('id').substring(3);
            
            widget_div.remove();
            
            // 恢复左侧按钮状态
            $("#" + widget_id + " .widget-act-del").hide();
            $("#" + widget_id + " .widget-act-add").show();
            layer.msg('已移除', {icon: 1, time: 1000});
        });

        // 添加自定义组件弹窗
        $("#btnAddWidget").click(function(){
            var isMobile = window.innerWidth < 640;
            var area = isMobile ? '90%' : '500px';
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: area, // 宽度自适应
                content: $('#add-widget-modal').html(), // 直接引用 DOM
                shadeClose: true,
                skin: 'em-modal-skin', // 应用自定义皮肤类名
                success: function(layero, index){
                    // 弹窗打开后不需要特殊处理，只需正常显示
                }
            });
        });
    });
</script>

<script>
    $("#menu-blog").attr('class', 'admin-menu-item has-list in');
    $("#menu-blog .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-blog > .submenu").css('display', 'block');
    $('#menu-blog-widgets > a').attr('class', 'menu-link active')
</script>