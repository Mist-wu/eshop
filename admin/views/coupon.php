<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .coupon-status-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .coupon-remark {
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="page-goods-release">
    <div class="layui-form-item">
        <div class="layui-input-block">
            <div class="table-box">
                <table class="layui-hide" id="coupon-table" lay-filter="coupon-table"></table>
            </div>
            <script type="text/html" id="coupon-toolbar">
                <div class="layui-btn-container">
                    <button class="layui-btn" lay-event="refresh">
                        <i class="fa fa-refresh mr-3"></i>刷新
                    </button>
                    <button type="button" class="layui-btn layui-btn-green" lay-event="add">
                        <i class="fa fa-plus"></i> 新增优惠券
                    </button>
                    <button id="coupon-toolbar-del" class="layui-btn layui-btn-red layui-btn-disabled" lay-event="del">
                        删除选中
                    </button>
                </div>
            </script>
            <script type="text/html" id="coupon-remark">
                <span class="coupon-remark" title="{{ d.remark_title }}">{{ d.remark_text }}</span>
            </script>
            <script type="text/html" id="coupon-status">
                <div class="coupon-status-cell">
                    <input type="checkbox"
                            class="layui-switch"
                            data-id="{{ d.id }}"
                            lay-skin="switch"
                            lay-text="启用|禁用"
                            lay-filter="coupon-status"
                        {{# if(d.status == 1){ }}checked{{# } }}>
                </div>
            </script>
            <script type="text/html" id="coupon-operate">
                <div class="layui-clear-space">
                    <a class="layui-btn layui-btn" lay-event="edit">编辑</a>
                    <a class="layui-btn layui-btn-red" lay-event="del">删除</a>
                </div>
            </script>
        </div>
    </div>
</div>

<script>
    $(function(){
        var couponToken = '<?= LoginAuth::genToken() ?>';

        layui.use(['table', 'form', 'layer'], function(){
            var table = layui.table;
            var form = layui.form;
            var layer = layui.layer;

            function openCouponModal(title, url) {
                var isMobile = window.innerWidth < 1200;
                var area = isMobile ? ['98%', '85%']  : ['1000px', '800px'];
                layer.open({
                    id: 'coupon_modal',
                    title: title,
                    type: 2,
                    area: area,
                    skin: 'em-modal',
                    content: url,
                    fixed: false,
                    scrollbar: false,
                    maxmin: true,
                    shadeClose: true
                });
            }

            function confirmAction(message, onConfirm) {
                if (typeof layer !== 'undefined' && layer.confirm) {
                    layer.confirm(message, {
                        btn: ['确认', '取消'],
                        icon: 3,
                        title: '温馨提示'
                    }, function(index){
                        layer.close(index);
                        onConfirm();
                    });
                } else if (confirm(message)) {
                    onConfirm();
                }
            }

            var pageSize = parseInt(localStorage.getItem('coupon_limit'), 10) || 30;
            var pageCurr = parseInt(localStorage.getItem('coupon_page'), 10) || 1;

            window.couponTable = table.render({
                elem: '#coupon-table',
                id: 'coupon-table',
                autoSort: false,
                url: 'coupon.php?action=index',
                toolbar: '#coupon-toolbar',
                limits: [10,20,30,50,100],
                page: {
                    curr: pageCurr
                },
                limit: pageSize,
                lineStyle: 'height: 32px;',
                cols: [[
                    {type: 'checkbox'},
                    {field:'code', title:'券码', minWidth: 160},
                    {field:'scope_text', title:'适用范围', minWidth: 180},
                    {field:'threshold_text', title:'门槛', width: 110, align: 'center'},
                    {field:'discount_text', title:'优惠', width: 120, align: 'center'},
                    {field:'status', title:'启用', width: 88, templet: '#coupon-status'},
                    {field:'expire_text', title:'过期时间', width: 150, align: 'center'},
                    {field:'use_limit_text', title:'可用次数', width: 90, align: 'center'},
                    {field:'used_times', title:'已用次数', width: 90, align: 'center'},
                    {field:'remark_text', title:'备注信息', minWidth: 118, templet: '#coupon-remark'},
                    {title:'操作', templet: '#coupon-operate', width: 160, align: 'center'}
                ]],
                done: function(res, curr, count){
                    localStorage.setItem('coupon_page', curr);
                    form.render('checkbox');
                    $('#coupon-toolbar-del').addClass('layui-btn-disabled');
                    var that = this;
                    setTimeout(function () {
                        var limitSelect = document.querySelector('.layui-laypage-limits select');
                        if (!limitSelect || limitSelect.dataset.bound) {
                            return;
                        }
                        limitSelect.dataset.bound = '1';
                        limitSelect.addEventListener('change', function () {
                            var newSize = parseInt(this.value, 10) || 30;
                            localStorage.setItem('coupon_limit', newSize);
                            table.reload(that.config.id, {
                                page: {
                                    curr: curr
                                },
                                limit: newSize
                            });
                        });
                    }, 0);
                },
                error: function(res, msg){
                    console.log(res, msg);
                }
            });

            table.on('toolbar(coupon-table)', function(obj){
                var id = obj.config.id;
                var checkStatus = table.checkStatus(id);
                if (obj.event === 'refresh') {
                    table.reload(id);
                }
                if (obj.event === 'add') {
                    openCouponModal('新增优惠券', 'coupon.php?action=form');
                }
                if (obj.event === 'del') {
                    var data = checkStatus.data;
                    if (data.length == 0) {
                        return layer.msg('请选择要删除的优惠券');
                    }
                    var ids = $.map(data, function(item) {
                        return item.id;
                    }).join(',');
                    confirmAction('确认删除选中的优惠券？', function(){
                        $.ajax({
                            type: 'POST',
                            url: 'coupon.php?action=delete',
                            data: { ids: ids, token: couponToken },
                            dataType: 'json',
                            success: function(resp){
                                if (resp.code === 0) {
                                    layer.msg('删除成功');
                                    table.reload(id);
                                } else {
                                    layer.msg(resp.msg || '删除失败');
                                }
                            },
                            error: function(xhr){
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
            });

            form.on('switch(coupon-status)', function(obj){
                var id = $(obj.elem).data('id');
                var status = obj.elem.checked ? 1 : 0;
                $.ajax({
                    type: 'POST',
                    url: 'coupon.php?action=toggle',
                    data: { id: id, status: status, token: couponToken },
                    dataType: 'json',
                    success: function(resp){
                        if (resp.code === 0) {
                            table.reload('coupon-table');
                            return;
                        }
                        obj.elem.checked = !obj.elem.checked;
                        form.render('checkbox');
                        layer.msg(resp.msg || '操作失败');
                    },
                    error: function(xhr){
                        obj.elem.checked = !obj.elem.checked;
                        form.render('checkbox');
                        var msg = '操作失败';
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp && resp.msg) msg = resp.msg;
                        } catch (e) {}
                        layer.msg(msg);
                    }
                });
            });

            table.on('tool(coupon-table)', function(obj){
                var data = obj.data;
                if (obj.event === 'edit') {
                    openCouponModal('编辑优惠券', 'coupon.php?action=form&id=' + data.id);
                }
                if (obj.event === 'del') {
                    confirmAction('确认删除该优惠券？', function(){
                        $.ajax({
                            type: 'POST',
                            url: 'coupon.php?action=delete',
                            data: { ids: data.id, token: couponToken },
                            dataType: 'json',
                            success: function(resp){
                                if (resp.code === 0) {
                                    table.reload('coupon-table');
                                } else {
                                    layer.msg(resp.msg || '删除失败');
                                }
                            },
                            error: function(xhr){
                                var msg = '删除失败';
                                try {
                                    var resp = JSON.parse(xhr.responseText);
                                    if (resp && resp.msg) msg = resp.msg;
                                } catch (e) {}
                                if (typeof layer !== 'undefined') {
                                    layer.msg(msg);
                                } else {
                                    alert(msg);
                                }
                            }
                        });
                    });
                }
            });

            table.on('checkbox(coupon-table)', function(obj){
                var id = obj.config.id;
                var checkData = table.checkStatus(id).data;
                if (checkData.length == 0) {
                    $('#coupon-toolbar-del').addClass('layui-btn-disabled');
                } else {
                    $('#coupon-toolbar-del').removeClass('layui-btn-disabled');
                }
            });
        });
    });
</script>

<script>
    $("#menu-goods").attr('class', 'admin-menu-item has-list in');
    $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-goods > .submenu").css('display', 'block');
    $('#menu-coupon-index > a').attr('class', 'menu-link active');
</script>
