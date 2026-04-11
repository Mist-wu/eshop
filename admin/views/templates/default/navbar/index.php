<?php defined('EM_ROOT') || exit('access denied!'); ?>

<table class="layui-hide" id="index" lay-filter="index"></table>

<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            删除
        </button>
        <button class="layui-btn layui-btn-green" lay-event="add_custom">
            <i class="fa fa-plus"></i> 添加自定义导航
        </button>
        <!-- <button class="layui-btn layui-btn-blue" lay-event="add_sort">
            <i class="fa fa-plus"></i> 添加商品分类导航
        </button> -->
        <button class="layui-btn layui-btn-purple" lay-event="add_blogsort">
            <i class="fa fa-plus"></i> 添加文章分类导航
        </button>
        <button class="layui-btn layui-btn-yellow" lay-event="add_page">
            <i class="fa fa-plus"></i> 添加页面导航
        </button>
    </div>
</script>
<script type="text/html" id="operate">
    <div class="layui-clear-space">
        <a class="layui-btn" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
    </div>
</script>
<script type="text/html" id="show">
    <input type="checkbox" name="{{= d.id }}" value="{{= d.id }}" title=" ON |OFF " lay-skin="switch" lay-filter="switch" {{= d.hide == 'n' ? "checked" : "" }}>
</script>


<script>
    layui.use(function(){
        var $ = layui.$;
        var treeTable = layui.treeTable;
        var form = layui.form;
        // 创建渲染实例
        window.table = treeTable.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index',
            tree: {
                customName: {
                    children: 'children',
                    pid: 'pid',
                    name: 'naviname',
                    icon: null
                },
                view: {
                    showIcon: false,
                    expandAllDefault: true
                }

            },
            toolbar: '#toolbar',
            limits: [10,20,30,50,100],
            page: false,
            defaultToolbar: false,
            lineStyle: 'height: 50px;',

            cols: [[
                {type: 'checkbox'},
                {field:'naviname', title: '导航名称', minWidth: 130},
                {field:'type_name', title:'类型', minWidth: 170},
                {field:'url', title:'地址', minWidth: 100},
                {field:'show', title:'显示', minWidth: 100, templet: '#show'},
                {field:'taxis', title:'排序', minWidth: 100},
                {title:'操作', templet: '#operate', width: 140}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            }
        });

        // 状态 - 开关操作
        form.on('switch(switch)', function(obj){
            var active = obj.elem.checked == true ? 'show' : 'hide';
            var id = this.name;
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=' + active,
                type: 'POST',
                dataType: 'json',
                data: { id: id, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    layer.msg('操作成功');
                },
                error: function(xhr) {
                    var msg = '操作失败';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp && resp.msg) msg = resp.msg;
                    } catch (e) {}
                    layer.msg(msg);
                },
                complete: function() {
                    layer.close(loadSwitch);
                }
            });
        });

        // 搜索提交
        form.on('submit(index-search)', function(data){
            var field = data.field;
            treeTable.reload('index', {
                page: {
                    curr: 1
                },
                where: field
            });
            return false;
        });


        // 工具栏事件
        treeTable.on('toolbar(index)', function(obj){
            var id = obj.config.id;
            var checkStatus = treeTable.checkStatus(id);
            var othis = lay(this);

            // 打开弹窗的通用函数
            function openModal(title, url) {
                let isMobile = window.innerWidth < 768;
                let area = isMobile ? ['98%', '85%'] : ['600px', '700px'];
                layer.open({
                    title: title,
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: url,
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        layer.iframeAuto(index);
                        that.offset();
                    }
                });
            }

            switch(obj.event){
                case 'refresh':
                    treeTable.reload(id);
                    break;
                case 'add_custom':
                    openModal('添加自定义导航', '?action=add_custom');
                    break;
                case 'add_sort':
                    openModal('添加商品分类到导航', '?action=add_sort');
                    break;
                case 'add_blogsort':
                    openModal('添加文章分类到导航', '?action=add_blogsort');
                    break;
                case 'add_page':
                    openModal('添加页面到导航', '?action=add_page');
                    break;
                case 'del':
                    var data = checkStatus.data;
                    if(data.length == 0){
                        break;
                    }
                    var ids = $.map(data, function(item) {
                        return item.id;
                    }).join(',');
                    layer.confirm('确定要删除选中的数据吗？', {
                        btn: ['确认', '取消'],
                        icon: 3,
                        title: '温馨提示'
                    }, function(index) {
                        layer.close(index);
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
                                treeTable.reload(id);
                            },
                            error: function(xhr) {
                                var msg = '删除失败';
                                try {
                                    var resp = JSON.parse(xhr.responseText);
                                    if (resp && resp.msg) msg = resp.msg;
                                } catch (e) {}
                                layer.msg(msg);
                            }
                        });
                    });
                    break;
            };
        });

        // 触发单元格工具事件
        treeTable.on('tool(index)', function(obj){ // 双击 toolDouble
            var data = obj.data; // 获得当前行数据
            var id = obj.config.id;
            if(obj.event == 'del'){
                layer.confirm('确定删除？', {
                    btn: ['确认', '取消'],
                    icon: 3,
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index);
                    $.ajax({
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.id, token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            treeTable.reload(id);
                        },
                        error: function(xhr) {
                            var msg = '删除失败';
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                if (resp && resp.msg) msg = resp.msg;
                            } catch (e) {}
                            layer.msg(msg);
                        }
                    });
                });
            }

            if(obj.event === 'edit'){
                let isMobile = window.innerWidth < 768;
                let area = isMobile ? ['98%', 'auto']  : ['700px', 'auto'];
                layer.open({
                    id: 'edit',
                    title: '编辑',
                    type: 2,
                    area: area,
                    // skin: 'layui-layer-win10',
                    skin: 'layui-layer-molv',
                    content: '?action=edit&id=' + data.id,
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    success: function(layero, index, that){
                        layer.iframeAuto(index); // 让 iframe 高度自适应
                        that.offset(); // 重新自适应弹层坐标
                    }
                });
            }

        });

        // 触发排序事件
        treeTable.on('sort(index)', function(obj){
            console.log(obj.field); // 当前排序的字段名
            console.log(obj.type); // 当前排序类型：desc（降序）、asc（升序）、null（空对象，默认排序）
            console.log(this); // 当前排序的 th 对象

            // 尽管我们的 table 自带排序功能，但并没有请求服务端。
            // 有些时候，你可能需要根据当前排序的字段，重新向后端发送请求，从而实现服务端排序，如：
            treeTable.reload('index', {
                initSort: obj, // 记录初始排序，如果不设的话，将无法标记表头的排序状态。
                where: { // 请求参数（注意：这里面的参数可任意定义，并非下面固定的格式）
                    field: obj.field, // 排序字段
                    order: obj.type // 排序方式
                }
            });
        });

        // 触发表格复选框选择
        treeTable.on('checkbox(index)', function(obj){
            var id = obj.config.id;
            var checkData = treeTable.checkStatus(id).data;
            console.log(checkData)
            if(checkData.length == 0){
                $('#toolbar-del').addClass('layui-btn-disabled');
            }else{
                $('#toolbar-del').removeClass('layui-btn-disabled');
            }
        });

        // 分页栏事件
        treeTable.on('pagebar(index)', function(obj){
            console.log(obj);
            console.log(obj.config);
            console.log(obj.event);
        });


        // 表头自定义元素工具事件 --- 2.8.8+
        treeTable.on('colTool(test)', function(obj){
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
        $('#menu-navi > a').attr('class', 'menu-link active')
    });
</script>
