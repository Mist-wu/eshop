<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    .info-container {
        display: flex;
        align-items: center;
    }

    .left-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        flex-shrink: 0; /* 防止图片被压缩 */
        border-radius: 4px;
    }

    .text-content {
        margin-left: 16px;
    }

    .first-line {
        font-size: 18px;
        font-weight: 500;
        color: #333333;
        margin: 0 0 4px 0;
    }

    .second-line {
        font-size: 14px;
        color: #666666;
        margin: 0;
    }
    .layui-table-view-1 .layui-table-body .layui-table tr .layui-table-cell{
        max-height: unset!important;
    }
</style>
<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn" lay-event="refresh">
            <i class="fa fa-refresh mr-3"></i>刷新
        </button>
        <button id="toolbar-upload" class="layui-btn layui-btn-sm layui-btn-normal" type="button">
            上传文件
        </button>
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            删除选中
        </button>
    </div>
</script>
<script type="text/html" id="info">
    <div class="layui-clear-space">
        <div class="info-container">
            <img src="{{ d.media_icon }}" alt="" class="left-image">
            <div class="text-content">
                <p class="second-line">{{ d.filename }}</p>
                <p class="second-line">源文件：{{ d.file_url }}</p>
                {{#  if(d.width > 0){ }}
                <p class="second-line">图片尺寸：{{ d.width }}x{{ d.height }}</p>
                {{#  } }}
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="operate">
    <div class="layui-clear-space">
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
    </div>
</script>


<script>
    layui.use(['table', 'upload'], function(){
        var table = layui.table;
        var form = layui.form;
        var upload = layui.upload;
        var layer = layui.layer;
        function initUpload() {
            var $btn = $('#toolbar-upload');
            if (!$btn.length || $btn.data('uploadInited')) {
                return;
            }
            $btn.data('uploadInited', true);
            upload.render({
                elem: '#toolbar-upload',
                url: './media.php?action=upload&editor=1',
                field: 'editormd-image-file',
                accept: 'file',
                multiple: true,
                done: function(res){
                    if (!res || res.success !== 1) {
                        var msg = (res && res.message) ? res.message : '上传失败';
                        layer.msg(msg);
                    }
                },
                allDone: function(obj){
                    if (obj.total > 0) {
                        layer.msg('上传完成');
                    }
                    table.reload('index');
                },
                error: function(){
                    layer.msg('上传失败');
                }
            });
        }

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
            lineStyle: 'height: 100px;',

            cols: [[
                {type: 'checkbox'},
                {field:'info', title:'资源信息', minWidth: 350, templet: '#info'},
                {field:'attsize', title:'文件大小', width: 150},
                {field:'addtime', title:'添加时间', sort: true, width: 180},
                {title:'操作', templet: '#operate', width: 100}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            },
            done: function(){
                initUpload();
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
                        return item.aid; // 提取每个对象的uid
                    }).join(',');
                    layer.confirm('确定要删除选中的数据吗？', {
                        btn: ['确认', '取消'], // 按钮
                        icon: 3,             // 图标，3表示问号
                        title: '温馨提示'
                    }, function(index) {
                        layer.close(index); // 关闭对话框
                        $.ajax({
                            url: '?action=operate_media',
                            type: 'POST',
                            dataType: 'json',
                            data: { aids: ids, operate: 'del', token: '<?= LoginAuth::genToken() ?>' },
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
                    break;
            };
        });

        // 触发单元格工具事件
        table.on('tool(index)', function(obj){ // 双击 toolDouble
            var data = obj.data; // 获得当前行数据
            var id = obj.config.id;
            if(obj.event == 'del'){
                layer.confirm('确认删除？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示',
                }, function(index) {
                    layer.close(index); // 关闭对话框
                    $.ajax({
                        url: '?action=operate_media',
                        type: 'POST',
                        dataType: 'json',
                        data: { aids: data.aid, operate: 'del', token: '<?= LoginAuth::genToken() ?>' },
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
        $("#menu-system").attr('class', 'admin-menu-item has-list in');
        $("#menu-system .fa-angle-right").attr('class', 'fas arrow iconfont icon-you active');
        $("#menu-system > .submenu").css('display', 'block');
        $('#menu-media > a').attr('class', 'menu-link active')

    });

</script>
