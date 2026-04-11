<?php
defined('EM_ROOT') || exit('access denied!');
?>
<form class="layui-form" style="float: right;">
    <div class="layui-form-item">
        <div class="layui-inline">
            <div class="layui-input-inline layui-input-wrap">
                <input type="text" value="" name="keyword" placeholder="搜索标题..." lay-affix="clear" class="layui-input">
            </div>
            <div class="layui-form-mid" style="padding: 0!important;">
                <button class="layui-btn" lay-submit lay-filter="index-search">搜索</button>
                <button type="reset" class="layui-btn layui-btn">重置</button>
            </div>
        </div>
    </div>
</form>
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
            <img onerror="this.onerror=null; this.src='./views/images/null.png'" class="cover" data-img="{{ d.cover }}" src="{{ d.cover }}" style="width: 40px; border-radius: 3px;" />
        </a>
    </div>
</script>

<script type="text/html" id="title">
    <div class="layui-clear-space">

        <span style="">{{ d.title }}</span>
    </div>
</script>
<script type="text/html" id="is_sku">
    <div class="layui-clear-space">

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
            limits: [10,20,30,50,100],
            page: true,
            lineStyle: 'height: 30px;',
            defaultToolbar: ['filter', 'exports', 'print', { // 右上角工具图标
                title: '提示',
                layEvent: 'LAYTABLE_TIPS',
                icon: 'layui-icon-tips',
                onClick: function(obj) { // 2.9.12+
                    layer.alert('自定义工具栏图标按钮');
                }
            }],


            cols: [[
                {type: 'checkbox'},
                {field:'name', title:'封面图', width: 80, templet: '#cover', align: 'center'},
                {field:'title', title:'文章标题', minWidth: 200, templet: '#title'},
                {field:'views', title:'浏览数', width: 130, align: 'center', sort: true },
                {field:'sortname', title:'分类', width: 130, sort: true, align: 'center'},
                {field:'date', title:'添加时间', sort: true, width: 150, align: 'center'},
                {title:'操作', templet: '#operate', width: 150, align: 'center'}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            }
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
                success: function(res) {
                    layer.msg('操作成功');
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
                let area = isMobile ? ['98%', '85%'] : ['1200px', '90%'];
                layer.open({
                    id: 'article_add',
                    title: '添加文章',
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: '?action=write',
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
                    return item.gid; // 提取每个对象的uid
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
                let area = isMobile ? ['98%', '85%'] : ['1200px', '90%'];
                layer.open({
                    id: 'article_edit',
                    title: '编辑文章 - ' + data.title,
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: '?action=edit&gid=' + data.gid,
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: false
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
    $("#menu-blog").attr('class', 'admin-menu-item has-list in');
    $("#menu-blog .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-blog > .submenu").css('display', 'block');
    $('#menu-blog-list > a').attr('class', 'menu-link active')
</script>
