<?php defined('EM_ROOT') || exit('access denied!'); ?>


<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button type="button" class="layui-btn layui-btn-green" lay-event="add">添加</button>
        <button id="toolbar-del" class="layui-btn layui-btn-red layui-btn-disabled" lay-event="del">
            删除
        </button>
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
            limits: [10,20,30,50,100],
            page: true,
            defaultToolbar: ['filter', 'exports', 'print', { // 右上角工具图标
                title: '提示',
                layEvent: 'LAYTABLE_TIPS',
                icon: 'layui-icon-tips',
                onClick: function(obj) { // 2.9.12+
                    layer.alert('自定义工具栏图标按钮');
                }
            }],
            lineStyle: 'height: 20px;',

            cols: [[
                {type: 'checkbox'},
                {field:'title', title: '标题', minWidth: 130},
                {field:'views', title:'浏览数', minWidth: 90},
                // {field:'alias', title:'别名', minWidth: 100},
                {field:'template', title:'模板', minWidth: 90},
                {field:'date', title:'添加时间', sort: true, width: 150},
                {title:'操作', templet: '#operate', width: 173}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            },
            complete: function(xhr, ts) {
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
            if(obj.event == 'refresh'){
                table.reload(id);
            }
            if(obj.event == 'add'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', 'auto']  : ['1000px', 'auto'];
                layer.open({
                    id: 'edit',
                    title: '添加',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    skin: 'layui-layer-molv',
                    content: 'page.php?action=new',
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        setTimeout(function(){
                            layer.iframeAuto(index); // 让 iframe 高度自适应
                            that.offset(); // 重新自适应弹层坐标
                        }, 50)

                    }
                });
            }
            if(obj.event == 'del'){
                var data = checkStatus.data;
                if(data.length == 0){
                    return false;
                }
                var ids = $.map(data, function(item) {
                    return item.gid; // 提取每个对象的uid
                }).join(',');
                layer.confirm('删除选中的数据？', {
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
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.gid, token: '<?= LoginAuth::genToken() ?>' },
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
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', 'auto']  : ['1000px', 'auto'];
                layer.open({
                    id: 'edit',
                    title: '编辑',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    skin: 'layui-layer-molv',
                    content: 'page.php?action=mod&id=' + data.gid,
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        setTimeout(function(){
                            layer.iframeAuto(index); // 让 iframe 高度自适应
                            that.offset(); // 重新自适应弹层坐标
                        }, 50)
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
    $(function () {
        $("#menu-appearance").attr('class', 'admin-menu-item has-list in');
        $("#menu-appearance .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-appearance > .submenu").css('display', 'block');
        $('#menu-page > a').attr('class', 'menu-link active')
    });
</script>
