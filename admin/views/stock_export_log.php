<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
</style>
<div style="padding: 20px 10px;" id="open-box">




    <table class="layui-hide" id="index" lay-filter="index"></table>
</div>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            删除
        </button>
    </div>
</script>


<script type="text/html" id="operate">
    <div class="layui-clear-space">
        <a class="layui-btn" lay-event="download">下载</a>
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
            url: 'stock.php?action=export_log_ajax', // 此处为静态模拟数据，实际使用时需换成真实接口
            toolbar: '#toolbar',
            limits: [10,20,30,50,100],
            page: true,
            defaultToolbar: [],
            maxHeight : 'full-78',
            cols: [[
                {type: 'checkbox'},
                {field:'filename', title:'文件名', minWidth: 230, align: 'center' },
                {field:'title', title:'商品名', minWidth: 230, align: 'center' },
                {field:'create_time', title:'导出时间', minWidth: 180, sort: true, align: 'center'},
                {title:'操作', templet: '#operate', width: 150, align: 'center'}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            }
        });






        // 搜索提交
        form.on('submit(index-search)', function(data){
            var field = data.field; // 获得表单字段
            // 执行搜索重载
            table.reload('index', {
                page: {
                    curr: 1 // 重新从第 1 页开始
                },
                where: field // 搜索的字段
            });
            return false; // 阻止默认 form 跳转
        });


        // 工具栏事件
        table.on('toolbar(index)', function(obj){
            var id = obj.config.id;
            var checkStatus = table.checkStatus(id);
            var othis = lay(this);
            switch(obj.event){
                case 'refresh':
                    table.reload(id);
                    break;
                case 'del':
                    var data = checkStatus.data;
                    if(data.length == 0){
                        break;
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
                            url: '?action=del_export_log',
                            type: 'POST',
                            dataType: 'json',
                            data: { ids: ids, token: '<?= LoginAuth::genToken() ?>' },
                            success: function(e) {
                                if(e.code == 400){
                                    layer.msg(e.msg)
                                }else{
                                    layer.msg('删除成功');
                                    table.reload(id);
                                }

                            },
                            error: function(err) {
                                layer.msg(err.responseJSON.msg);
                            }
                        });
                    });
                    break;
            };
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
                        url: '?action=del_export_log',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.id, token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                layer.msg(e.msg)
                            }else{
                                layer.msg('删除成功');
                                table.reload(id);
                            }
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }
            if(obj.event === 'download'){
                window.open("download.php?filename=" + data.filename);
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

    var maxHeight = $(window.parent).innerHeight() * 0.75;
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>
