<?php defined('EM_ROOT') || exit('access denied!'); ?>

<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button type="button" class="layui-btn layui-btn-green" lay-event="add">添加</button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            删除
        </button>
    </div>
</script>
<script type="text/html" id="cover">
    <div class="layui-clear-space">
        <a href="javascript:;" data-id="{{ d.id }}" lay-event="img">
            <img onerror="this.onerror=null; this.src='./views/images/null.png'" class="cover" data-img="{{ d.sortimg }}" src="{{ d.sortimg }}" style="width: 40px; border-radius: 3px;" />
        </a>
    </div>
</script>

<script type="text/html" id="sku_attr_text">
    <div class="layui-clear-space">
        {{ d.sku_attrs_text }}
    </div>
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
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
    </div>
</script>


<script>
    layui.use(['table'], function(){
        var table = layui.table;
        var form = layui.form;
        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index', // 此处为静态模拟数据，实际使用时需换成真实接口
            toolbar: '#toolbar',
            limits: [],
            page: false,
            lineStyle: 'height: 30px;',
            defaultToolbar: ['filter', 'exports'],


            cols: [[
                {type: 'checkbox'},
                {field:'name', title:'规格模板', minWidth: 170 },
                {field:'sku_attr_text', title:'商品规格', minWidth: 170, templet: '#sku_attr_text' },
                {title:'操作', templet: '#operate', width: 210, align: 'center'}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            }
        });


        // 工具栏事件
        table.on('toolbar(index)', function(obj){
            var id = obj.config.id;
            var checkStatus = table.checkStatus(id);
            var othis = lay(this);
            if(obj.event == 'refresh'){
                table.reload(id);
            }
            if(obj.event == 'add'){
                let isMobile = window.innerWidth < 768;
                let area = isMobile ? ['98%', '75%']  : ['700px', '90%'];
                layer.open({
                    id: 'add',
                    title: '添加',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    skin: 'layui-layer-molv',
                    content: '?action=add',
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        // layer.iframeAuto(index); // 让 iframe 高度自适应
                        // that.offset(); // 重新自适应弹层坐标
                    }
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
                        url: '?action=del_sku_cate',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: ids, type: 'goods', token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            table.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
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
                        url: '?action=del_sku_cate',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.id, type: 'goods', token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            table.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }


            if(obj.event === 'edit'){
                let isMobile = window.innerWidth < 768;
                let area = isMobile ? ['98%', '75%']  : ['700px', '90%'];
                layer.open({
                    id: 'edit',
                    title: '编辑',
                    type: 2,
                    area: area,
                    skin: 'layui-layer-molv',
                    content: '?action=edit&id=' + data.id,
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        // layer.iframeAuto(index); // 让 iframe 高度自适应
                        // that.offset(); // 重新自适应弹层坐标
                    }
                });
            }
        });

        // 触发表格复选框选择
        table.on('checkbox(index)', function(obj){
            var id = obj.config.id;
            var checkData = table.checkStatus(id).data;
            console.log(checkData)
            if(checkData.length == 0){
                $('#toolbar-del').addClass('layui-btn-disabled');
            }else{
                $('#toolbar-del').removeClass('layui-btn-disabled');
            }
        });



    });
</script>


<script>
    $("#menu-goods").attr('class', 'admin-menu-item has-list in');
    $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-goods > .submenu").css('display', 'block');
    $('#menu-sku-list > a').attr('class', 'menu-link active')
</script>
