<?php defined('EM_ROOT') || exit('access denied!'); ?>
<form class="layui-form" style="float: right;">
    <div class="layui-form-item">
        <div class="layui-inline">
            <div class="layui-input-inline layui-input-wrap">
                <select name="member_id">
                    <option value="">会员等级</option>
                    <?php foreach($member_list as $val): ?>
                    <option value="<?= $val['id'] ?>"><?= $val['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="layui-input-inline layui-input-wrap">
                <input type="text" value="" name="keyword" placeholder="昵称/手机/邮箱/ID" lay-affix="clear" class="layui-input">
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
        <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
            删除选中
        </button>
    </div>
</script>
<script type="text/html" id="operate">
    <div class="layui-clear-space">
        <a class="layui-btn" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
        {{#  if(d.state == 0){ }}
        <a class="layui-btn layui-btn-yellow" lay-event="forbid">封禁</a>
        {{# }else{  }}
        <a class="layui-btn layui-btn-blue" lay-event="unforbid">解封</a>
        {{#  } }}
    </div>
</script>


<script>
    layui.use(['table'], function(){
        var table = layui.table;
        var form = layui.form;
        // 记住每页条数
        var pageSize = localStorage.getItem('user_limit') || 10;
        pageSize = parseInt(pageSize, 10);
        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index', // 此处为静态模拟数据，实际使用时需换成真实接口
            toolbar: '#toolbar',
            limits: [10,20,30,50,100],
            page: true,
            limit: pageSize,
            defaultToolbar: ['filter', 'exports', 'print'],


            cols: [[
                {type: 'checkbox'},
                {field:'uid', title: 'ID', sort: true, width: 80, align: 'center'},
                {field:'nickname', title: '用户昵称', minWidth: 130, maxWidth: 170},
                {field:'tel', title:'手机号码', minWidth: 130},
                {field:'email', title:'邮箱', minWidth: 180},
                {field:'level_name', title:'会员等级', minWidth: 100, maxWidth: 120},
                {field:'expend', title:'总消费', minWidth: 90, maxWidth: 120, sort: true},
                {field:'reg_ip', title:'注册IP', minWidth: 120, maxWidth: 125},
                {field:'create_time', title:'注册时间', sort: true, width: 140},
                {title:'操作', templet: '#operate', width: 200}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            },
            done: function(res, curr, count) {
                var that = this;
                setTimeout(function () {
                    var limitSelect = document.querySelector('.layui-laypage-limits select');
                    if (!limitSelect || limitSelect.dataset.bound) {
                        return;
                    }
                    limitSelect.dataset.bound = '1';
                    limitSelect.addEventListener('change', function () {
                        var newSize = parseInt(this.value, 10) || 10;
                        localStorage.setItem('user_limit', newSize);
                        table.reload(that.config.id, {
                            page: {
                                curr: curr
                            },
                            limit: newSize
                        });
                    });
                }, 0);
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
                        return item.uid; // 提取每个对象的uid
                    }).join(',');
                    layer.confirm('确定要删除选中的用户吗？', {
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
                    break;
            };
        });

        // 触发单元格工具事件
        table.on('tool(index)', function(obj){ // 双击 toolDouble
            var data = obj.data; // 获得当前行数据
            var id = obj.config.id;
            if(obj.event == 'del'){
                layer.confirm('确定要删除该用户吗？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index); // 关闭对话框
                    $.ajax({
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { ids: data.uid, token: '<?= LoginAuth::genToken() ?>' },
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
            if(obj.event === 'forbid'){
                $.ajax({
                    url: '?action=forbid',
                    type: 'POST',
                    dataType: 'json',
                    data: { ids: data.uid, token: '<?= LoginAuth::genToken() ?>' },
                    success: function(e) {
                        if(e.code == 400){
                            return layer.msg(e.msg)
                        }
                        layer.msg('已封禁用户');
                        table.reload(id);
                    },
                    error: function(err) {
                        layer.msg(err.responseJSON.msg);
                    }
                });
            }
            if(obj.event === 'unforbid'){
                $.ajax({
                    url: '?action=unforbid',
                    type: 'POST',
                    dataType: 'json',
                    data: { ids: data.uid, token: '<?= LoginAuth::genToken() ?>' },
                    success: function(e) {
                        if(e.code == 400){
                            return layer.msg(e.msg)
                        }
                        layer.msg('已解除封禁');
                        table.reload(id);
                    },
                    error: function(err) {
                        layer.msg(err.responseJSON.msg);
                    }
                });
            }
            if(obj.event === 'edit'){
                let isMobile = window.innerWidth < 768;
                AdminModal.open({
                    title: '编辑用户',
                    url: 'user.php?action=edit&uid=' + data.uid,
                    width: isMobile ? '98vw' : 720,
                    height: isMobile ? '88vh' : '78vh'
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
    $("#menu-user").attr('class', 'admin-menu-item has-list in');
    $("#menu-user .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-user > .submenu").css('display', 'block');
    $('#menu-user-default > a').attr('class', 'menu-link active')
</script>

<?php include __DIR__ . '/components/modal.php'; ?>
