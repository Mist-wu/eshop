<?php
defined('EM_ROOT') || exit('access denied!');
?>
<style>
    .template-page {
        padding: 0;
        background: transparent;
    }
    .template-page .template-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .template-page .template-toolbar-left,
    .template-page .template-toolbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .template-page .template-toolbar .layui-btn {
        height: 32px;
        line-height: 32px;
        border-radius: 6px;
        padding: 0 12px;
        font-size: 13px;
    }
    .template-page .template-count {
        color: #8c8c8c;
        font-size: 13px;
    }
    .template-page .template-table-wrap {
        background: #fff;
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        overflow-x: auto;
        box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    }
    .template-page .template-table-wrap .layui-table-view {
        border: 0;
        border-radius: 10px;
        box-shadow: none;
        margin: 0;
    }
    .template-page .template-table-wrap .layui-table {
        min-width: 980px;
        table-layout: fixed;
        border: 0;
    }
    .template-page .template-table-wrap .layui-table-header {
        background: linear-gradient(180deg, #f9fafb 0%, #f2f4f6 100%);
    }
    .template-page .template-table-wrap .layui-table-header table,
    .template-page .template-table-wrap .layui-table-header thead,
    .template-page .template-table-wrap .layui-table-header tr {
        background: linear-gradient(180deg, #f9fafb 0%, #f2f4f6 100%);
    }
    .template-page .template-table-wrap .layui-table-header th {
        background: transparent;
        color: #5f6b74;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.3px;
        border-bottom: 1px solid #e6e6e6;
        border-left: none;
        border-right: none;
    }
    .template-page .template-table-wrap .layui-table-header th .layui-table-cell {
        text-align: left;
    }
    .template-page .template-table-wrap .layui-table-body td {
        color: #262626;
        font-size: 13px;
        border-bottom: 1px solid #f0f0f0;
        border-left: none;
        border-right: none;
        vertical-align: middle;
    }
    .template-page .template-table-wrap .layui-table-body tr:nth-child(even) td {
        background: #fcfcfd;
    }
    .template-page .template-table-wrap .layui-table-body tr:hover td {
        background: #f3f8f6;
    }
    .template-page .template-table-wrap .layui-table-body tr:last-child td {
        border-bottom: none;
    }
    .template-page .template-table-wrap .layui-table-view .layui-table-cell {
        padding: 14px 16px;
        height: auto;
        line-height: 1.5;
        box-sizing: border-box;
    }
    .template-page .cover {
        width: 46px;
        height: 46px;
        border-radius: 6px;
        overflow: hidden;
        background: #f5f5f5;
        border: 1px solid #eee;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s;
    }
    .template-page .template-table-wrap .layui-table-body tr:hover .cover {
        transform: scale(1.05);
    }
    

        
</style>

<div class="template-page">
    <div class="template-toolbar">
        <div class="template-toolbar-left">
            <button type="button" class="layui-btn layui-btn-sm layui-btn" id="btn-store">应用商店</button>
        </div>
        <div class="template-toolbar-right">
            <span class="template-count">共 <strong id="template-count">0</strong> 个模板</span>
        </div>
    </div>
    <div id="checking-updates" style="display: none; padding: 10px; background-color: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 4px; margin-bottom: 10px;">
        <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 正在检测模板是否有更新...
    </div>
    <div class="template-table-wrap">
        <table class="layui-hide" id="index" lay-filter="index"></table>
    </div>
</div>
<script type="text/html" id="cover">
    <div class="layui-clear-space">
        <a href="javascript:;" data-id="{{ d.id }}" lay-event="img">
            <img onerror="this.onerror=null; this.src='./views/images/null.png'" class="cover" data-img="{{ d.preview }}" src="{{ d.preview }}" />
        </a>
    </div>
</script>

<script type="text/html" id="title">
    <div>
        {{#  if(d.update == 'n'){ }}
        <span>{{ d.tplname }}</span>
        {{#  } else { }}
        <span><span class="layui-badge">发现新版本</span> {{ d.tplname }}</span>
        {{#  } }}
    </div>
</script>

<script type="text/html" id="switch">
    <input type="checkbox" name="{{= d.tplfile }}" value="{{= d.tplfile }}" title=" ON |OFF " lay-skin="switch" lay-filter="switch" {{= d.switch == 'y' ? "checked" : "" }}>
</script>
<script type="text/html" id="tel_switch">
    <input type="checkbox" name="{{= d.tplfile }}" value="{{= d.tplfile }}" title=" ON |OFF " lay-skin="switch" lay-filter="tel_switch" {{= d.tel_switch == 'y' ? "checked" : "" }}>
</script>

<script type="text/html" id="operate">
    <div class="layui-clear-space">
        {{#  if(d.update == 'y'){ }}
        <a class="layui-btn layui-btn-blue" lay-event="update">更新版本</a>
        {{#  } }}
        <a class="layui-btn" lay-event="setting">配置</a>
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>

    </div>
</script>


<script>
    layui.use(['table'], function(){
        var table = layui.table;
        var form = layui.form;
        var $ = layui.$;

        $('#btn-store').on('click', function(){
            location.href = 'store.php';
        });
        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index', // 此处为静态模拟数据，实际使用时需换成真实接口
            toolbar: false,
            limits: [10,20,30,50,100],
            page: false,
            defaultToolbar: [],


            cols: [[
                {field:'name', title:'图片', width: 80, templet: '#cover', align: 'center'},
                {field:'title', title:'模板名称', minWidth: 170, templet: '#title'},
                {field:'version', title:'版本号', width: 130, align: 'center' },
                {field:'switch', title:'启用（电脑）', align: 'center', width: 180, templet: '#switch'},
                {field:'tel_switch', title:'启用（手机）', align: 'center', width: 180, templet: '#tel_switch'},
                {title:'操作', templet: '#operate', width: 250, align: 'left'}
            ]],
            done: function(res, curr, count){
                var total = typeof count !== 'undefined' ? count : (res.data ? res.data.length : 0);
                $('#template-count').text(total);
            },

            error: function(res, msg){
                console.log(res, msg)
            }
        });

        // 异步检测模板更新
        function checkTemplateUpdates() {
            // 淡入显示正在检测更新的提示
            $('#checking-updates').fadeIn();
            
            $.ajax({
                url: '?action=checkUpdates',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    // 更新表格中每个模板的更新状态
                    updateTableWithUpdates(res.data);
                },
                error: function(xhr, status, error) {
                    console.log('检测更新失败:', error);
                },
                complete: function() {
                    // 无论成功或失败，都使用高度过渡隐藏检测更新的提示，避免表格突然上移
                    $('#checking-updates').animate({
                        height: 0,
                        opacity: 0,
                        paddingTop: 0,
                        paddingBottom: 0,
                        marginTop: 0,
                        marginBottom: 0
                    }, 400, function() {
                        // 动画完成后完全隐藏元素
                        $(this).hide();
                    });
                }
            });
        }

        // 更新表格中模板的更新状态
        function updateTableWithUpdates(updateData) {
            // 获取当前表格数据
            var tableData = table.cache['index'];
            // 遍历更新数据，更新表格中对应模板的状态
            updateData.forEach(function(updateItem) {
                for(var i = 0; i < tableData.length; i++) {
                    if(tableData[i].tplfile === updateItem.tplfile) {
                        // 更新模板的更新状态
                        tableData[i].update = updateItem.update;
                        tableData[i].id = updateItem.id;
                        
                        // 更新指定行数据
                        table.updateRow('index', {
                            index: i,
                            data: tableData[i],
                            related: function(field, index){
                                return true;
                            }
                        });
                    }
                }
            });
        }

        // 表格渲染完成后，异步检测更新
        checkTemplateUpdates();

        // 状态 - 开关操作
        form.on('switch(switch)', function(obj){
            var active = obj.elem.checked == true ? 1 : 0;
            var tpl = this.name;
            if(active == 0){
                tpl = 'em_null_tpl';
            }
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=use',
                type: 'POST',
                dataType: 'json',
                data: { tpl: tpl, status: active, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    layer.msg('操作成功');
                    table.reload('index');
                },
                error: function(err) {
                    layer.msg(err.responseJSON.msg);
                },
                complete: function() {
                    layer.close(loadSwitch);
                }
            });
        });
        form.on('switch(tel_switch)', function(obj){
            var active = obj.elem.checked == true ? 1 : 0;
            var tpl = this.name;
            if(active == 0){
                tpl = 'em_null_tpl';
            }
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=use_tel',
                type: 'POST',
                dataType: 'json',
                data: { tpl: tpl, status: active, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    layer.msg('操作成功');
                    table.reload('index');
                },
                error: function(err) {
                    layer.msg(err.responseJSON.msg);
                },
                complete: function() {
                    layer.close(loadSwitch);
                }
            });
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
                        data: { ids: data.tplfile, token: '<?= LoginAuth::genToken() ?>' },
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

            if(obj.event === 'img'){
                layer.photos({
                    photos: {
                        "title": data.title,
                        "start": 0,
                        "data": [
                            {
                                "alt": data.tplname,
                                "pid": 1,
                                "src": data.preview,
                            }
                        ]
                    }
                });
            }
            if(obj.event === 'update'){
                var loadSwitch = layer.load(2);
                $.ajax({
                    url: '?action=upgrade',
                    type: 'POST',
                    dataType: 'json',
                    data: { plugin_id: data.id, alias: data.tplfile, token: '<?= LoginAuth::genToken() ?>' },
                    success: function(e) {
                        if(e.code == 400){
                            return layer.msg(e.msg)
                        }
                        layer.msg('更新成功');
                        var newRow = data;
                        newRow.update = 'n';
                        table.updateRow('index', {
                            index: obj.index,
                            data: newRow,
                            related: function(field, index){
                                return true;
                            }
                        });
                    },
                    error: function(err) {
                        layer.msg(err.responseJSON.msg);
                    },
                    complete: function() {
                        layer.close(loadSwitch);
                    }
                });

            }
            if(obj.event === 'setting'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '85%']  : ['1000px', '80%'];
                layer.open({
                    id: 'setting',
                    title: '配置',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    // skin: 'layui-layer-molv',
                    skin: 'em-modal',
                    content: '?action=setting_page&tpl=' + data.tplfile,
                    fixed: false, // 不固定
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                    }
                });
            }
        });

        // 触发排序事件
        table.on('sort(index)', function(obj){
            console.log(obj.field); // 当前排序的字段名
            console.log(obj.type); // 当前排序类型：desc（降序）、asc（升序）、null（空对象，默认排序）
            console.log(this); // 当前排序的 th 对象

            // 尽管我们的 table 自带排序功能，但并没有请求服务端。
            // 有些时候，你可能需要根据当前排序的字段，重新向后端发送请求，从而实现服务端排序，如：
            table.reload('index', {
                initSort: obj, // 记录初始排序，如果不设的话，将无法标记表头的排序状态。
                where: { // 请求参数（注意：这里面的参数可任意定义，并非下面固定的格式）
                    field: obj.field, // 排序字段
                    order: obj.type // 排序方式
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
            }else{
                $('#toolbar-del').removeClass('layui-btn-disabled');
            }
        });

        // 分页栏事件
        table.on('pagebar(index)', function(obj){
            alert()
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
    $("#menu-appearance").attr('class', 'admin-menu-item has-list in');
    $("#menu-appearance .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-appearance > .submenu").css('display', 'block');
    $('#menu-template > a').attr('class', 'menu-link active')


</script>
