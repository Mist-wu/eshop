<?php defined('EM_ROOT') || exit('access denied!'); ?>

<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li>
            <a href="./store.php"><i class="fa fa-one fa-shopping-cart"></i> 应用商店</a>
        </li>
        <li class="<?= $filter == '' ? 'layui-this' : '' ?>"><a href="./plugin.php">已安装</a></li>
        <li class="<?= $filter == 'on' ? 'layui-this' : '' ?>"><a href="./plugin.php?filter=on">启用中</a></li>
        <li class="<?= $filter == 'off' ? 'layui-this' : '' ?>"><a href="./plugin.php?filter=off">已关闭</a></li>
    </ul>
</div>
<div id="checking-updates" style="display: none; padding: 10px; background-color: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 4px; margin-bottom: 10px;">
    <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 正在检测插件是否有更新...
</div>
<table class="layui-hide" id="index" lay-filter="index"></table>

<script type="text/html" id="switch">
    <input type="checkbox" name="{{= d.alias }}" value="{{= d.active }}" title=" ON |OFF " lay-skin="switch" lay-filter="switch" {{= d.active == 1 ? "checked" : "" }}>
</script>

<script type="text/html" id="cover">
    <div class="layui-clear-space">
        <a href="javascript:;" data-id="{{ d.id }}" lay-event="img">
            <img onerror="this.onerror=null; this.src='./views/images/null.png'" class="cover" data-img="{{ d.preview }}" src="{{ d.preview }}" style="width: 50px; border-radius: 3px;" />
        </a>
    </div>
</script>

<script type="text/html" id="name">
    <div class="layui-clear-space">
        {{#  if(d.update == 1){ }}
        <div><span class="layui-badge">发现新版本</span> {{ d.Name }}</div>
        {{#  } else { }}
        <div><strong>{{ d.Name }}</strong></div>
        {{#  } }}
        <div>{{ d.Description }}</div>
    </div>
</script>
<script type="text/html" id="operate">
    <div class="layui-clear-space">
        {{#  if(d.Setting == true){ }}
        <button class="layui-btn layui-btn-blue" lay-event="setting">配置</button>
        {{#  } }}
        <button class="layui-btn layui-btn-red" lay-event="del">卸载</button>
        {{#  if(d.update == 1){ }}
        <button class="layui-btn layui-btn-green" lay-event="update">更新</button>
        {{#  } }}

    </div>
</script>


<script>
    layui.use(['table'], function(){
        var table = layui.table;
        var form = layui.form;
        var dropdown = layui.dropdown;


        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            url: './plugin.php?action=index&filter=<?= $filter ?>', // 此处为静态模拟数据，实际使用时需换成真实接口
            // limits: [10,20,30,50,100],
            limits: [10],
            lineStyle: 'height: 69px;',
            page: false,
            cols: [[
                {field:'preview', title:'演示图', width: 80, templet: '#cover', align: 'center'},
                {field:'name', title:'插件名', minWidth: 520, templet: '#name'},
                {field:'active', title:'开关', width: 100, templet: '#switch'},
                {field:'Author', title:'作者', width: 100},
                {field:'Version', title:'版本', width: 100},
                {title:'操作', templet: '#operate', width: 250}
            ]],

            done: function(res, curr, count){
                // 表格渲染完成后，异步检测更新
                checkPluginUpdates();
            },

            error: function(res, msg){
                console.log(res, msg)
            }
        });

        // 异步检测插件更新
        function checkPluginUpdates() {
            // 淡入显示正在检测更新的提示
            $('#checking-updates').fadeIn();
            
            $.ajax({
                url: './plugin.php?action=checkUpdates&filter=<?= $filter ?>',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    // 更新表格中每个插件的更新状态
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

        // 更新表格中插件的更新状态
        function updateTableWithUpdates(updateData) {
            // 获取当前表格数据
            var tableData = table.cache['index'];
            // 遍历更新数据，更新表格中对应插件的状态
            updateData.forEach(function(updateItem) {
                console.log(tableData)
                for(var i = 0; i < tableData.length; i++) {
                    if(tableData[i].Plugin === updateItem.plugin) {
                        // 更新插件的更新状态
                        tableData[i].update = updateItem.update;

                        tableData[i].id = updateItem.id;
                        
                        // 更新指定行数据
                        console.log(i)
                        console.log(tableData[i]);
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

        // 状态 - 开关操作
        form.on('switch(switch)', function(obj){
            var active = obj.elem.checked == true ? 1 : 0;
            var alias = this.name;
            console.log('active: ' + active)
            console.log('alias: ' + alias)
            var loadSwitch = layer.load(2);
            $.ajax({
                url: '?action=switch',
                type: 'POST',
                dataType: 'json',
                data: { plugin: alias, status: active, token: '<?= LoginAuth::genToken() ?>' },
                success: function(e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
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



        // 触发单元格工具事件
        table.on('tool(index)', function(obj){ // 双击 toolDouble
            var data = obj.data; // 获得当前行数据
            var id = obj.config.id;
            if(obj.event == 'del'){
                layer.confirm('删除该插件？', {
                    btn: ['删除插件', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    var loadSwitch = layer.load(2);
                    $.ajax({
                        url: '?action=del',
                        type: 'POST',
                        dataType: 'json',
                        data: { plugin: data.alias, token: '<?= LoginAuth::genToken() ?>' },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('删除成功');
                            table.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        },
                        complete: function() {
                            layer.close(loadSwitch);
                        }
                    });
                });
            }
            if(obj.event == 'update'){
                console.log(obj.index)
                var loadSwitch = layer.load(2);
                $.ajax({
                    url: '?action=upgrade',
                    type: 'POST',
                    dataType: 'json',
                    data: { plugin_id: data.id, alias: data.Plugin, token: '<?= LoginAuth::genToken() ?>' },
                    success: function(e) {
                        if(e.code == 400){
                            return layer.msg(e.msg)
                        }
                        layer.msg('更新成功');
                       
                        var newRow = data;
                        newRow.update = 0;
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
                if(data.Ui == 'Layui'){
                    let isMobile = window.innerWidth < 1200;
                    let area = isMobile ? ['98%', '85%']  : ['1000px', '80%'];
                    layer.open({
                        id: 'edit',
                        title: data.Name,
                        type: 2,
                        area: area,
                        // skin: 'layui-layer-win10',
                        // skin: 'layui-layer-molv',
                        skin: 'em-modal',
                        content: 'plugin.php?action=setting_page&plugin=' + data.Plugin,
                        fixed: false, // 不固定
                        maxmin: true,
                        shadeClose: true,
                        success: function(layero, index, that){
                            // layer.iframeAuto(index); // 让 iframe 高度自适应
                            // that.offset(); // 重新自适应弹层坐标
                        }
                    });
                }else{
                    location.href = "./plugin.php?plugin=" + data.Plugin;
                }

            }
            if(obj.event === 'img'){
                layer.photos({
                    photos: {
                        "title": data.Name,
                        "start": 0,
                        "data": [
                            {
                                "alt": data.Name,
                                "pid": 1,
                                "src": data.preview,
                            }
                        ]
                    }
                });
            }
        });



    });

</script>


<script>
    $(function () {
        $("#menu-plugin").addClass('active');
    });
</script>
