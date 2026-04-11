<?php defined('EM_ROOT') || exit('access denied!'); ?>

<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button type="button" class="layui-btn layui-btn-green" lay-event="add">
            <i class="fa fa-plus mr-3"></i>添加
        </button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            <i class="fa fa-trash mr-3"></i>删除
        </button>
        <button id="toggle-expand" class="layui-btn layui-btn-blue" lay-event="toggleExpand" data-expanded="true">
            <i class="fa fa-minus-square-o mr-3"></i>全部合并
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
    layui.use(function(){
        var treeTable = layui.treeTable;
        // 创建渲染实例
        window.table = treeTable.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index&type=<?= $type ?>', // 此处为静态模拟数据，实际使用时需换成真实接口
            tree: {
                customName: {
                    id: 'sid',
                    pid: 'pid',
                    name: 'sortname',
                    children: 'children'
                },
                view: {
                    showIcon: false,
                    expandAllDefault: true,
                    indent: 20
                }
            },
            toolbar: '#toolbar',
            limits: [],
            page: false,
            lineStyle: 'height: 30px;',
            defaultToolbar: ['filter', 'exports'],


            cols: [[
                {type: 'checkbox'},
                {field:'sortimg', title:'图片', width: 80, templet: '#cover', align: 'center'},
                {field:'sortname', title:'分类名称', minWidth: 170 },
                {field:'taxis', title:'排序', width: 70, align: 'center' },
                {title:'操作', templet: '#operate', width: 150, align: 'center'}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            }
        });


        // 工具栏事件
        treeTable.on('toolbar(index)', function(obj){
            var id = obj.config.id;
            var checkStatus = treeTable.checkStatus(id);
            var othis = lay(this);
            if(obj.event == 'refresh'){
                treeTable.reload(id);
            }
            if(obj.event == 'add'){
                AdminModal.open({
                    title: '添加分类',
                    url: '?action=add&type=<?= $type ?>',
                    width: 860,
                    height: '85vh'
                });
            }
            if(obj.event == 'del'){
                var data = checkStatus.data;
                if(data.length == 0){
                    return;
                }
                var ids = $.map(data, function(item) {
                    return item.sid; // 提取每个对象的uid
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
                        data: { ids: ids, type: 'goods', token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            treeTable.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }
            if(obj.event == 'toggleExpand'){
                var btn = $('#toggle-expand');
                var isExpanded = btn.attr('data-expanded') === 'true';

                if(isExpanded){
                    // 当前是展开状态，执行合并
                    treeTable.expandAll(id, false);
                    btn.attr('data-expanded', 'false');
                    btn.html('<i class="fa fa-plus-square-o mr-3"></i>全部展开');
                }else{
                    // 当前是合并状态，执行展开
                    treeTable.expandAll(id, true);
                    btn.attr('data-expanded', 'true');
                    btn.html('<i class="fa fa-minus-square-o mr-3"></i>全部合并');
                }
            }

        });

        // 触发单元格工具事件
        treeTable.on('tool(index)', function(obj){ // 双击 toolDouble
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
                        data: { ids: data.sid, type: 'goods', token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            treeTable.reload(id);
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
                                "src": data.sortimg,
                            }
                        ]
                    }
                });
            }
            if(obj.event === 'edit'){
                AdminModal.open({
                    title: '编辑分类',
                    url: '?action=edit&type=<?= $type ?>&id=' + data.sid,
                    width: 860,
                    height: '85vh'
                });
            }
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



    });
</script>

<?php if($type == 'goods'): ?>
<script>
    $("#menu-goods").attr('class', 'admin-menu-item has-list in');
    $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-goods > .submenu").css('display', 'block');
    $('#menu-sort-list > a').attr('class', 'menu-link active')
</script>
<?php else: ?>
    <script>
        $("#menu-blog").attr('class', 'admin-menu-item has-list in');
        $("#menu-blog .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-blog > .submenu").css('display', 'block');
        $('#menu-blog-sort > a').attr('class', 'menu-link active')
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../../../components/modal.php'; ?>


