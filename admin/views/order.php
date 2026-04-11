<?php
defined('EM_ROOT') || exit('access denied!');
?>
<style>
.order-search-wrapper {
    background: #fff;
    border-radius: 6px;
    margin-bottom: 12px;
    border: 1px solid #e6e6e6;
    transition: border-color 0.2s;
}
.order-search-wrapper:hover {
    border-color: #d9d9d9;
}
.order-search-wrapper .layui-colla-icon {
    left: auto;
    right: 15px;
    color: #666;
}
.order-search-wrapper .layui-collapse {
    border: none;
}
.order-search-wrapper .layui-colla-item {
    border: none;
}
.order-search-wrapper .layui-colla-title {
    padding: 0px 16px;
    background: #fafafa;
    border: none;
    border-radius: 6px 6px 0 0;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}
.order-search-wrapper .layui-colla-title:hover {
    background: #f5f5f5;
}
.order-search-wrapper .layui-colla-title i.fa-filter {
    margin-right: 8px;
    color: #1890ff;
}
.order-search-wrapper .layui-colla-content {
    padding: 16px;
    background: #fff;
    border: none;
}
.order-search-form .layui-form-item {
    margin-bottom: 0px;
}
.order-search-form .layui-form-label {
    width: auto;
    padding: 0 0 6px 0;
    text-align: left;
    font-size: 13px;
    color: #666;
}
.order-search-form .layui-input-block {
    margin-left: 0;
}
.order-search-form .layui-input {
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    padding: 6px 11px;
    font-size: 14px;
    transition: all 0.2s;
    height: 34px;
}
.order-search-form .layui-input:hover {
    border-color: #40a9ff;
}
.order-search-form .layui-input:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
}
.order-search-form .search-btn-group {
    text-align: right;
}
.order-search-form .layui-btn {
    border-radius: 4px;
    padding: 7px 18px;
    font-size: 14px;
    height: 34px;
    line-height: 1.5;
    transition: all 0.2s;
}
.order-search-form .layui-btn i {
    margin-right: 4px;
}
.order-stats-item {
    display: inline-block;
    padding: 0 10px;
    background: rgba(15, 118, 110, 0.08);
    border: 1px solid rgba(15, 118, 110, 0.2);
    border-radius: 6px;
    font-size: 12px;
    margin-right: 10px;
    margin-bottom: 10px;
    height: 30px;
    line-height: 30px;
    vertical-align: middle;
}
.order-stats-label {
    color: #4C7D71;
    margin-right: 4px;
}
.order-stats-value {
    color: #4C7D71;
    font-weight: 600;
}
</style>
<div class="order-search-wrapper">
    <div class="layui-collapse" lay-filter="search-collapse">
        <div class="layui-colla-item">
            <div class="layui-colla-title">
                <i class="fa fa-filter"></i>搜索条件
            </div>
            <div class="layui-colla-content">
                <form class="layui-form order-search-form" id="search-form-content">
                    <div class="grid-cols-xs-1 grid-cols-sm-2 grid-cols-md-3 grid-cols-lg-4 grid-cols-xl-5 grid-gap-15">
                        <div class="layui-form-item">
                            <label class="layui-form-label">订单号</label>
                            <div class="layui-input-block">
                                <input type="text" value="" name="out_trade_no" placeholder="请输入订单号" lay-affix="clear" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">商品名称</label>
                            <div class="layui-input-block">
                                <input type="text" value="" name="goods_title" placeholder="请输入商品名" lay-affix="clear" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">用户信息</label>
                            <div class="layui-input-block">
                                <input type="text" value="" name="email_username" placeholder="邮箱/昵称/手机" lay-affix="clear" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">下单IP</label>
                            <div class="layui-input-block">
                                <input type="text" value="" name="client_ip" placeholder="请输入IP地址" lay-affix="clear" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">支付状态</label>
                            <div class="layui-input-block">
                                <select name="pay_status" lay-search="">
                                    <option value="">全部订单</option>
                                    <option value="y">已支付</option>
                                    <option value="n">未支付</option>
                                </select>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">下单时间</label>
                            <div class="layui-input-block">
                                <input type="text" name="create_time" placeholder="选择时间范围" id="create-time-range" class="layui-input">
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item search-btn-group" style="margin-top: 15px;">
                        <button class="layui-btn layui-btn-green" lay-submit lay-filter="index-search">
                            <i class="fa fa-search"></i>搜索
                        </button>
                        <button type="button" class="layui-btn layui-btn-primary" id="reset-search">
                            <i class="fa fa-undo"></i>重置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<table class="layui-hide" id="index" lay-filter="index"></table>
<script type="text/html" id="toolbar">
    <div class="layui-btn-container" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button class="layui-btn" lay-event="refresh">
                <i class="fa fa-refresh mr-3"></i>刷新
            </button>
            <button id="toolbar-del" class="layui-btn layui-btn-sm layui-btn-red layui-btn-disabled" lay-event="del">
                <i class="fa fa-trash-o mr-3"></i>删除选中
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-yellow" lay-event="del_unpaid">
                <i class="fa fa-trash mr-3"></i>删除未支付订单
            </button>
        </div>
        <div>
            <span class="order-stats-item">
                <span class="order-stats-label">订单数量</span>
                <span class="order-stats-value" id="stats-count">0</span>
            </span>
            <span class="order-stats-item">
                <span class="order-stats-label">订单金额</span>
                <span class="order-stats-value" id="stats-amount">¥0.00</span>
            </span>
        </div>
    </div>
</script>
<script type="text/html" id="goods">
    <div class="goods-info">
        <div class="goods-title row-1-hidden">{{ d.list[0].title }}</div>
        <div class="goods-spec row-1-hidden">{{ d.list[0].attr_spec }}</div>
    </div>
</script>

<script type="text/html" id="quantity">
    <div class="goods-info">
        <div class="qty-badge">{{ d.list[0].quantity }}</div>
    </div>
</script>
<script type="text/html" id="userinfo">
    <div class="user-info">
        <div class="user-name">
            {{# if(d.user_id == 0){ }}
            <span class="user-badge">游客</span>
            {{#  }else{ }}
            <span class="user-badge">{{ d.user_nickname }}</span>
            {{#  } }}
        </div>
    </div>
</script>
<script type="text/html" id="orderNo">
    <div class="order-no" style="font-size: 13px;">{{ d.out_trade_no }}</div>
</script>
<script type="text/html" id="amountTpl">
    <div class="order-amount"><span class="currency">¥</span>{{ d.amount }}</div>
</script>
<script type="text/html" id="statusTpl">
    {{# if(d.status == 0){ }}
    <span class="order-status is-unpaid">未支付</span>
    {{#  }else if(d.status == 1){ }}
    <span class="order-status is-pending">待发货</span>
    {{#  }else if(d.status == 2){ }}
    <span class="order-status is-done">已完成</span>
    {{#  }else if(d.status == -1){ }}
    <span class="order-status is-partial">部分发货</span>
    {{#  }else{ }}
    <span class="order-status is-unknown">{{ d.status_text || '未知状态' }}</span>
    {{#  } }}
</script>
<script type="text/html" id="paymentTpl">
    <span class="order-tag">{{ d.payment || '-' }}</span>
</script>
<script type="text/html" id="createTimeTpl">
    <span class="order-time">{{ d.create_time || '-' }}</span>
</script>
<script type="text/html" id="payTimeTpl">
    {{# if(d.pay_time == ''){ }}
    <span class="order-time is-empty">未付款</span>
    {{#  }else{ }}
    <span class="order-time">{{ d.pay_time }}</span>
    {{#  } }}
</script>
<script type="text/html" id="operate">
    <div class="layui-clear-space order-actions">
        <a class="layui-btn layui-btn layui-btn-sm" lay-event="detail">详情</a>
        {{# if(d.status == 1){ }}
        <a class="layui-btn layui-btn-sm layui-btn-blue" lay-event="deliver">发货</a>
        {{#  } }}
        {{#  if(d.pay_time == ''){ }}
        <a class="layui-btn layui-btn-sm layui-btn-yellow" lay-event="budan">补单</a>
        {{#  } }}
        <a class="layui-btn layui-btn-sm layui-btn-red" lay-event="del">删除</a>
    </div>
</script>




<script>
    layui.use(['table', 'element', 'laydate'], function(){
        var table = layui.table;
        var form = layui.form;
        var element = layui.element;
        var laydate = layui.laydate;

        // 初始化日期范围选择器
        laydate.render({
            elem: '#create-time-range',
            type: 'datetime',
            range: true,
            rangeLinked: true,
            format: 'yyyy-MM-dd HH:mm:ss'
        });

        // 创建渲染实例
        window.table = table.render({
            elem: '#index',
            autoSort: false,
            url: '?action=index', // 此处为静态模拟数据，实际使用时需换成真实接口
            toolbar: '#toolbar',
            limits: [10,20,30,50,100],
            lineStyle: 'height: 69px;',
            page: true,
            defaultToolbar: [],


            cols: [[
                {type: 'checkbox'},

                {field:'out_trade_no', title: '订单号', width: 175, templet: '#orderNo'},
                {field:'goods', title:'商品信息',templet: '#goods', minWidth: 200, maxWidth: 500},
                {field:'id', title: '数量', width: 90, templet: '#quantity', align: 'center'},
                {field:'amount', title:'订单金额', width: 110, templet: '#amountTpl', align: 'center'},
                {field:'user_email', title:'用户', minWidth: 108, maxWidth: 160, templet: '#userinfo'},
                {field:'status_text', title:'订单状态', width: 110, templet: '#statusTpl', align: 'center'},
                {field:'payment', title:'支付方式', width: 110, templet: '#paymentTpl', align: 'center'},
                {field:'create_time', title:'下单时间', width: 150, templet: '#createTimeTpl'},
                {field:'pay_time', title:'支付时间', width: 150, templet: '#payTimeTpl'},
                {title:'操作', templet: '#operate', minWidth: 215}
            ]],

            error: function(res, msg){
                console.log(res, msg)
            },
            done: function(res, curr, count){
                // 更新统计数据
                if(res.stats){
                    $('#stats-count').text(res.stats.total_count);
                    $('#stats-amount').text('¥' + res.stats.total_amount);
                }
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

        // 重置按钮事件
        $('#reset-search').on('click', function(){
            $('.order-search-form')[0].reset(); // 重置表单
            layui.form.render(); // 重新渲染表单
            // 重新加载表格，清空搜索条件
            table.reload('index', {
                page: {
                    curr: 1
                },
                where: {} // 清空搜索条件
            });
        });

        // 初始化折叠状态 - 默认折叠
        var searchExpanded = localStorage.getItem('order-search-expanded');
        if (searchExpanded === 'true') {
            // 如果之前是展开状态，则添加展开相关的类
            $('.order-search-wrapper .layui-colla-item').addClass('layui-show');
            $('.order-search-wrapper .layui-colla-item').addClass('layui-colla-active');
            $('.order-search-wrapper .layui-colla-title').addClass('layui-colla-active');
        }

        // 监听折叠面板展开/折叠事件
        element.on('collapse(search-collapse)', function(data){
            // data.show 为 true 表示展开，false 表示折叠
            localStorage.setItem('order-search-expanded', data.show);
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
                case 'del_unpaid':
                    layer.confirm('确定要删除所有未支付订单吗？此操作不可恢复！', {
                        btn: ['确认', '取消'],
                        icon: 3,
                        title: '温馨提示'
                    }, function(index) {
                        layer.close(index);
                        var loadIndex = layer.load(1, {shade: [0.3, '#000']});
                        $.ajax({
                            url: '?action=del_unpaid',
                            type: 'POST',
                            dataType: 'json',
                            data: { token: '<?= LoginAuth::genToken() ?>' },
                            success: function(e) {
                                layer.close(loadIndex);
                                if(e.code == 400){
                                    return layer.msg(e.msg);
                                }
                                layer.alert(e.data.message, {
                                    icon: 1,
                                    title: '删除成功',
                                    btn: ['确定'],
                                    yes: function(index) {
                                        layer.close(index);
                                        table.reload(id);
                                    }
                                });
                            },
                            error: function(err) {
                                layer.close(loadIndex);
                                layer.msg(err.responseJSON ? err.responseJSON.msg : '删除失败');
                            }
                        });
                    });
                    break;
                case 'del':
                    var data = checkStatus.data;
                    if(data.length == 0){
                        break;
                    }
                    var ids = $.map(data, function(item) {
                        return item.id; // 提取每个对象的uid
                    }).join(',');
                    layer.confirm('确定要删除选中的数据吗？', {
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
                layer.confirm('确定要删除这条数据吗？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index); // 关闭对话框
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
                            table.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                });
            }
            if(obj.event === 'budan'){
                layer.confirm('将该订单改为已支付状态？', {
                    btn: ['确认', '取消'], // 按钮
                    icon: 3,             // 图标，3表示问号
                    title: '温馨提示'
                }, function(index) {
                    layer.close(index); // 关闭对话框
                    $.ajax({
                        url: '?action=repay',
                        type: 'POST',
                        dataType: 'json',
                        data: { out_trade_no: data.out_trade_no },
                        success: function(e) {
                            if(e.code == 400){
                                return layer.msg(e.msg)
                            }
                            layer.msg('补单成功');
                            table.reload(id);
                        },
                        error: function(err) {
                            layer.msg(err.responseJSON.msg);
                        }
                    });
                }, function() {
                });
            }

            if(obj.event === 'deliver'){
                let isMobile = window.innerWidth < 768;
                AdminModal.open({
                    title: '发货',
                    url: 'order.php?action=deliver&order_id=' + data.id,
                    width: isMobile ? '98vw' : 520,
                    height: isMobile ? '85vh' : '70vh'
                });
            }
            if(obj.event === 'detail'){
                let isMobile = window.innerWidth < 1200;
                let area = isMobile ? ['98%', '88%'] : ['900px', '80%'];
                layer.open({
                    id: 'order-detail-' + data.id,
                    title: '订单详情',
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: 'order.php?action=detail&order_id=' + data.id,
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true
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
    $("#menu-order").attr('class', 'admin-menu-item has-list in');
    $("#menu-order .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-order > .submenu").css('display', 'block');
    $('#menu-order-goods > a').attr('class', 'menu-link active')
</script>

<?php include __DIR__ . '/components/modal.php'; ?>
