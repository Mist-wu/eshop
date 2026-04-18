<?php
defined('EM_ROOT') || exit('access denied!');
?>
<style>
.goods-search-wrapper {
    background: #fff;
    border-radius: 6px;
    margin-bottom: 12px;
    border: 1px solid #e6e6e6;
    transition: border-color 0.2s;
}
.goods-search-wrapper:hover {
    border-color: #d9d9d9;
}
.goods-search-wrapper .layui-colla-icon {
    left: auto;
    right: 15px;
    color: #666;
}
.goods-search-wrapper .layui-collapse {
    border: none;
}
.goods-search-wrapper .layui-colla-item {
    border: none;
}
.goods-search-wrapper .layui-colla-title {
    padding: 0px 16px;
    background: #fafafa;
    border: none;
    border-radius: 6px 6px 0 0;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}
.goods-search-wrapper .layui-colla-title:hover {
    background: #f5f5f5;
}
.goods-search-wrapper .layui-colla-title i.fa-filter {
    margin-right: 8px;
    color: #1890ff;
}
.goods-search-wrapper .layui-colla-content {
    padding: 16px;
    background: #fff;
    border: none;
}
.goods-search-form .layui-form-item {
    margin-bottom: 0px;
}
.goods-search-form .layui-form-label {
    width: auto;
    padding: 0 0 6px 0;
    text-align: left;
    font-size: 13px;
    color: #666;
}
.goods-search-form .layui-input-block {
    margin-left: 0;
}
.goods-search-form .layui-input {
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    padding: 6px 11px;
    font-size: 14px;
    transition: all 0.2s;
    height: 34px;
}
.goods-search-form .layui-input:hover {
    border-color: #40a9ff;
}
.goods-search-form .layui-input:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
}
.goods-search-form .search-btn-group {
    text-align: right;
}
.goods-search-form .layui-btn {
    border-radius: 4px;
    padding: 7px 18px;
    font-size: 14px;
    height: 34px;
    line-height: 1.5;
    transition: all 0.2s;
}
.goods-search-form .layui-btn i {
    margin-right: 4px;
}
</style>
<div class="goods-search-wrapper">
    <div class="layui-collapse" lay-filter="goods-search-collapse">
        <div class="layui-colla-item">
            <div class="layui-colla-title">
                <i class="fa fa-filter"></i>搜索条件
            </div>
            <div class="layui-colla-content">
                <form class="layui-form goods-search-form" id="goods-search-form-content">
                    <div class="grid-cols-xs-1 grid-cols-sm-2 grid-cols-md-3 grid-cols-lg-4 grid-cols-xl-5 grid-gap-15">
                        <div class="layui-form-item">
                            <label class="layui-form-label">商品分类</label>
                            <div class="layui-input-block">
                                <select name="category_id" lay-search="">
                                    <option value="">全部分类</option>
                                    <?php foreach($sorts as $val): ?>
                                        <option value="<?= $val['sid'] ?>">
                                            <?php
                                            // 如果是二级分类（有父级ID且不为0），添加缩进符号
                                            if(isset($val['pid']) && $val['pid'] != 0):
                                                echo '　├─ ';
                                            endif;
                                            echo $val['sortname'];
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">商品名称</label>
                            <div class="layui-input-block">
                                <input type="text" value="" name="keyword" placeholder="请输入商品名称" lay-affix="clear" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">上架状态</label>
                            <div class="layui-input-block">
                                <select name="is_on_shelf" lay-search="">
                                    <option value="">全部商品</option>
                                    <option value="y">上架中</option>
                                    <option value="n">已下架</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item search-btn-group" style="margin-top: 15px;">
                        <button class="layui-btn layui-btn-green" lay-submit lay-filter="index-search">
                            <i class="fa fa-search"></i>搜索
                        </button>
                        <button type="button" class="layui-btn layui-btn-primary" id="goods-reset-search">
                            <i class="fa fa-undo"></i>重置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mt-2"></i><span>刷新</span>
        </button>
        <button type="button" class="layui-btn layui-btn-green" lay-event="add">
            <i class="fa fa-plus mt-2"></i><span>添加</span>
        </button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            <i class="fa fa-trash-o mt-2"></i><span>删除</span>
        </button>
        <button id="toolbar-home-show" type="button" class="layui-btn layui-btn-purple layui-btn-disabled" lay-event="home_batch">
            <i class="fa fa-star mt-2"></i><span>首页展示批量</span>
        </button>
        <button type="button" class="layui-btn layui-btn-blue" lay-event="export_log">
            <i class="fa fa-download mt-2"></i><span>导出记录</span>
        </button>
    </div>
</script>
<script type="text/html" id="cover">
    <div class="layui-clear-space">
        <a href="javascript:;" data-id="{{ d.id }}" lay-event="img">
            <img onerror="this.onerror=null; this.src='./views/images/null.png'" class="cover" data-img="{{ d.cover }}" src="{{ d.cover }}" style="width: 40px; border-radius: 3px;" />
        </a>
    </div>
</script>

<script type="text/html" id="title">
    <div>
        {{#  if(d.is_sku == 'y'){ }}
        <span class="layui-badge-rim layui-border-blue">多规格</span>
        {{#  } }}
        {{#  if(d.is_sku == 'n'){ }}
        <span class="layui-badge-rim layui-border-cyan">单规格</span>
        {{#  } }}
        <span><?php doAction('adm_goods_list_type', "{{ d.type }}"); ?></span>
        <span>{{ d.title }}</span>
    </div>
</script>
<script type="text/html" id="is_sku">
    <div class="layui-clear-space">

    </div>
</script>
<script type="text/html" id="home">
    <input type="checkbox" name="{{= d.id }}" value="{{= d.id }}" title=" ON |OFF " lay-skin="switch" lay-filter="home_switch" {{= d.home == 'y' ? "checked" : "" }}>
</script>
<script type="text/html" id="is_on_shelf">
    <input type="checkbox" name="{{= d.id }}" value="{{= d.id }}" title=" ON |OFF " lay-skin="switch" lay-filter="switch" {{= d.is_on_shelf == 1 ? "checked" : "" }}>
</script>
<script type="text/html" id="type">
    <div class="layui-clear-space">
        <span>{{ d.type_text }}</span>
    </div>
</script>
<script type="text/html" id="stock">
    <div class="layui-clear-space">
        {{ d.stock }}
    </div>
</script>

<script type="text/html" id="operate">
    <div class="layui-clear-space">
        <a class="layui-btn" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-blue em-modal" data-modal="stock-modal" data-id="{{ d.id }}" lay-event="stock">库存</a>
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
    </div>
</script>


<script>
    layui.use(['table', 'element'], function(){
        var table = layui.table;
        var form = layui.form;
        var element = layui.element;
        var tableId = 'index';
        var currentWhere = {};
        var currentSort = {};

        function reloadGoodsTable(options){
            options = options || {};

            if (options.where) {
                currentWhere = $.extend({}, currentWhere, options.where);
            }

            options.where = $.extend({}, currentWhere);

            if (typeof options.initSort === 'undefined' && currentSort.field && currentSort.type) {
                options.initSort = currentSort;
            }

            table.reload(tableId, options);
        }

        // 从本地存储读取每页条数（默认10）
        var pageSize = localStorage.getItem('goods_limit') || 10;
        pageSize = parseInt(pageSize); // 确保是数字类型
        // 从本地存储读取当前页码（默认1）
        var pageCurr = localStorage.getItem('goods_page') || 1;
        pageCurr = parseInt(pageCurr) || 1;

        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            id: tableId,
            url: '?action=index', // 此处为静态模拟数据,实际使用时需换成真实接口
            toolbar: '#toolbar',
            limits: [10,20,30,50,100],
            page: {
                curr: pageCurr
            },
            limit: pageSize,
            lineStyle: 'height: 30px;',
            defaultToolbar: false,


            cols: [[
                {type: 'checkbox'},
                // {field:'id', title:'ID', width: 80},
                {field:'name', title:'封面图', width: 80, templet: '#cover', align: 'center'},
                {field:'title', title:'商品标题', minWidth: 170, templet: '#title'},
                {field:'sort_name', title:'商品分类', width: 130, align: 'center' },
                // {field:'is_sku', title:'规格类型', width: 90, align: 'center', templet: '#is_sku' },
                {field:'stock', title:'库存', sort: true, width: 100, templet: '#stock', align: 'center' },
                {field:'sales', title:'销量', width: 100, sort: true, align: 'center'},
                {field:'home', title:'首页展示', align: 'center', width: 110, templet: '#home'},
                {field:'is_on_shelf', title:'上架', align: 'center', width: 100, templet: '#is_on_shelf'},
                {field:'create_time', title:'添加时间', sort: true, width: 150, align: 'center'},
                {title:'操作', templet: '#operate', width: 210, align: 'center'}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            },
            done: function(res, curr, count) {
                // 记住当前页码
                localStorage.setItem('goods_page', curr);
                // 绑定分页下拉框的change事件（注意：需在done回调中绑定，确保元素已渲染）
                setTimeout(function () {
                    // 找到分页控件中的下拉框（Layui分页的class固定为 layui-laypage-limits）
                    document.querySelector('.layui-laypage-limits select').addEventListener('change', function () {
                        var newSize = this.value; // 获取用户选择的新每页条数
                        // 存储到本地存储
                        localStorage.setItem('goods_limit', newSize);
                        // 重新渲染表格（保持当前页码，应用新的每页条数）
                        reloadGoodsTable({
                            page: {
                                curr: curr // 保持当前页码
                            },
                            limit: newSize // 应用新的每页条数
                        });
                    });
                }, 0);
            }
        });

        // 首页展示 - 开关操作
        form.on('switch(home_switch)', function(obj){
            var active = obj.elem.checked == true ? 'y' : 'n';
            var id = this.name;
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=home_switch',
                type: 'POST',
                dataType: 'json',
                data: { goods_id: id, home: active, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        layer.msg(e.msg)
                    }else{
                        layer.msg('操作成功');
                    }
                },
                error: function(err) {
                    layer.msg(err.responseJSON.msg);
                },
                complete: function() {
                    layer.close(loadSwitch);
                }
            });
        });

        // 状态 - 开关操作
        form.on('switch(switch)', function(obj){
            var active = obj.elem.checked == true ? 1 : 0;
            var id = this.name;
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=shelf',
                type: 'POST',
                dataType: 'json',
                data: { goods_id: id, status: active, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        layer.msg(e.msg)
                    }else{
                        layer.msg('操作成功');
                    }

                },
                error: function(err) {
                    layer.msg(err.responseJSON.msg);
                },
                complete: function() {
                    layer.close(loadSwitch);
                }
            });
        });

        // 搜索提交
        form.on('submit(index-search)', function(data){
            var field = data.field; // 获得表单字段
            // 执行搜索重载
            reloadGoodsTable({
                page: {
                    curr: 1 // 重新从第 1 页开始
                },
                where: field // 搜索的字段
            });
            return false; // 阻止默认 form 跳转
        });

        // 重置按钮事件
        $('#goods-reset-search').on('click', function(){
            $('.goods-search-form')[0].reset(); // 重置表单
            layui.form.render(); // 重新渲染表单
            // 清空缓存的搜索条件
            currentWhere = {};
            // 重新加载表格，清空搜索条件
            reloadGoodsTable({
                page: {
                    curr: 1
                },
                where: {} // 清空搜索条件
            });
        });

        // 初始化折叠状态 - 默认折叠
        var searchExpanded = localStorage.getItem('goods-search-expanded');
        if (searchExpanded === 'true') {
            // 如果之前是展开状态，则添加展开相关的类
            $('.goods-search-wrapper .layui-colla-item').addClass('layui-show');
            $('.goods-search-wrapper .layui-colla-item').addClass('layui-colla-active');
            $('.goods-search-wrapper .layui-colla-title').addClass('layui-colla-active');
        }

        // 监听折叠面板展开/折叠事件
        element.on('collapse(goods-search-collapse)', function(data){
            // data.show 为 true 表示展开，false 表示折叠
            localStorage.setItem('goods-search-expanded', data.show);
        });


        // 工具栏事件
        table.on('toolbar(index)', function(obj){
            var id = obj.config.id;
            var checkStatus = table.checkStatus(id);
            if(obj.event == 'refresh'){
                reloadGoodsTable();
            }
            if(obj.event == 'add'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '85%']  : ['1200px', '90%'];
                layer.open({
                    id: 'goods_add',
                    title: '添加商品',
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: 'goods.php?action=release',
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: false
                });
            }
            if(obj.event == 'del'){
                var data = checkStatus.data;
                if(data.length == 0){
                    return;
                }
                var ids = $.map(data, function(item) {
                    return item.id; // 提取每个对象的uid
                }).join(',');
                layer.confirm('确定要删除选中的数据？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index); // 关闭对话框
                    $.ajax({
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: ids, token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                layer.msg(e.msg)
                            }else{
                                layer.msg('删除成功');
                                reloadGoodsTable();
                            }

                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }
            if(obj.event == 'home_batch'){
                var data = checkStatus.data;
                if(data.length == 0){
                    return;
                }
                var ids = $.map(data, function(item) {
                    return item.id;
                }).join(',');

                var dialogHtml = '' +
                    '<div style="padding: 12px 20px 0;">' +
                    '  <div class="layui-form" id="home-batch-form">' +
                    '    <input type="radio" name="home" value="y" title="显示" checked>' +
                    '    <input type="radio" name="home" value="n" title="隐藏">' +
                    '  </div>' +
                    '</div>';

                layer.open({
                    title: '批量设置首页展示',
                    content: dialogHtml,
                    btn: ['确定', '取消'],
                    success: function(){
                        form.render('radio');
                    },
                    yes: function(index){
                        var home = $('#home-batch-form input[name="home"]:checked').val() || 'y';
                        var loadSwitch = layer.load(2);
                        $.ajax({
                            url: '?action=home_batch',
                            type: 'POST',
                            dataType: 'json',
                            data: { ids: ids, home: home, token: '<?= LoginAuth::genToken() ?>' },
                            success: function(e) {
                                if(e.code == 400){
                                    layer.msg(e.msg)
                                }else{
                                    layer.msg('操作成功');
                                    reloadGoodsTable();
                                }
                            },
                            error: function(err) {
                                layer.msg(err.responseJSON.msg);
                            },
                            complete: function() {
                                layer.close(loadSwitch);
                            }
                        });
                        layer.close(index);
                    }
                });
            }
            if(obj.event === 'export_log'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '85%']  : ['1000px', '800px'];
                layer.open({
                    id: 'export_log',
                    title: '卡密导出记录',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    skin: 'layui-layer-molv',
                    content: 'stock.php?action=export_log',
                    fixed: false, // 不固定
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                    }
                });
            }

        });

        // 触发单元格工具事件
        table.on('tool(index)', function(obj){ // 双击 toolDouble
            var data = obj.data; // 获得当前行数据
            var id = obj.config.id;
            if(obj.event == 'del'){
                layer.confirm('确定删除？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index); // 关闭对话框
                    $.ajax({
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.id, token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                layer.msg(e.msg)
                            }else{
                                layer.msg('删除成功');
                                reloadGoodsTable();
                            }

                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }

            if(obj.event === 'img'){
                layer.photos({
                    photos: {
                        "title": data.title,
                        "start": 0,
                        "data": [
                            {
                                "alt": data.title,
                                "pid": 1,
                                "src": data.cover,
                            }
                        ]
                    }
                });
            }
            if(obj.event === 'edit'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '85%']  : ['1200px', '90%'];
                layer.open({
                    id: 'goods_edit',
                    title: '编辑商品 - ' + data.title,
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: 'goods.php?action=edit&id=' + data.id,
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: false
                });
            }
            if(obj.event === 'stock'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '85%']  : ['1000px', '800px'];
                layer.open({
                    id: 'stock_manage',
                    title: '库存管理 - ' + data.title,
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: 'stock.php?action=index&goods_id=' + data.id,
                    fixed: false, // 不固定
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true
                });
            }

        });

        // 触发排序事件
        table.on('sort(index)', function(obj){
            console.log(obj.field); // 当前排序的字段名
            console.log(obj.type); // 当前排序类型：desc（降序）、asc（升序）、null（空对象，默认排序）
            console.log(this); // 当前排序的 th 对象
            currentSort = obj.type ? {field: obj.field, type: obj.type} : {};

            // 尽管我们的 table 自带排序功能，但并没有请求服务端。
            // 有些时候，你可能需要根据当前排序的字段，重新向后端发送请求，从而实现服务端排序，如：
            reloadGoodsTable({
                initSort: obj, // 记录初始排序，如果不设的话，将无法标记表头的排序状态。
                where: { // 请求参数（注意：这里面的参数可任意定义，并非下面固定的格式）
                    field: obj.type ? obj.field : '', // 排序字段
                    order: obj.type || '' // 排序方式
                }
            });
        });

        // 触发表格复选框选择
        table.on('checkbox(index)', function(obj){
            var id = obj.config.id;
            var checkData = table.checkStatus(id).data;
            console.log(checkData)
            if(checkData.length == 0){
                $('#toolbar-del').addClass('layui-btn-disabled');
                $('#toolbar-home-show').addClass('layui-btn-disabled');
            }else{
                $('#toolbar-del').removeClass('layui-btn-disabled');
                $('#toolbar-home-show').removeClass('layui-btn-disabled');
            }
        });

        // 分页栏事件
        table.on('pagebar(index)', function(obj){
            console.log(obj); // 查看对象所有成员
            console.log(obj.config); // 当前实例的配置信息
            console.log(obj.event); // 属性 lay-event 对应的值
        });


        // 表头自定义元素工具事件 --- 2.8.8+
        table.on('colTool(test)', function(obj){
            var event = obj.event;
            console.log(obj);
            if(event === 'email-tips'){
                layer.alert(layui.util.escape(JSON.stringify(obj.col)), {
                    title: '当前列属性选项'
                });
            }
        });


    });
</script>



<script>
    $("#menu-goods").attr('class', 'admin-menu-item has-list in');
    $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-goods > .submenu").css('display', 'block');
    $('#menu-goods-list > a').attr('class', 'menu-link active');
</script>

<?php include __DIR__ . '/components/modal.php'; ?>
