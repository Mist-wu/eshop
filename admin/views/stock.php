<?php
defined('EM_ROOT') || exit('access denied!');
?>

<style>
</style>

<style>
    .stock-content{
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        word-wrap: break-word;
    }
    .custom-table{
        word-break: break-all;
    }
    .edit-stock-input{
        display: none;
    }
</style>


<div class="mt-3 panel bg-white">

    <div class="panel-body">
        <div class="table-container">
            <div style="">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= empty($action) ? 'active' : '' ?>" href="./stock.php">未售出</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action =='sales' ? 'active' : '' ?>" href="./stock.php?action=sales">已售出</a>
                    </li>
                </ul>
            </div>

            <div class="table-header justify-between flex mt-4">
                <div>
                    <button data-size="sm" class="btn primary mr-2" id="export" data-toggle="modal" data-target="#exportModal">
                        <i class="fa fa-external-link"></i> 导出卡密
                    </button>
                    <button class="btn btn-action btn-batch-delete danger" id="batchDeleteBtn" disabled>
                        <i class="fa fa-trash"></i> 删除选中
                    </button>
                </div>
                <div class="search-div">
                    <form action="" method="get" class="">
                        <div class="search-box">
                            <div id="singlePickerExample" class="search-input"></div>
                        </div>
                        <div class="search-box has-icon-right" id="searchboxExample">
                            <div id="searchBox"></div>
                        </div>
                        <div class="search-btn">
                            <input type="submit" class="btn primary" value="搜索" />
                            <a href="./stock.php" class="btn ml-2">重置</a>
                        </div>
                    </form>

                </div>
            </div>
            <form action="goods.php?action=operate_goods" method="post" name="form_log" id="form_log">
                <input type="hidden" name="draft" value="<?= $draft ?>">
                <div class="table-box">
                    <table class="table custom-table mt-4">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"/></th>
                            <th>商品名称</th>
                            <th>库存类型</th>
                            <th style="min-width: 200px; max-width: 300px;">库存内容</th>
                            <th>添加时间</th>
                            <th style="width: 120px;" class="text-center">操作</th>
                        </tr>
                        </thead>
                        <tbody class="checkboxContainer">
                        <?php foreach ($list as $key => $value): ?>
                            <tr>
                                <td style="width: 20px;">
                                    <input type="checkbox" name="ids[]" value="<?= $value['stock_id'] ?>" class="select-item" />
                                </td>
                                <td>
                                    <div style="min-width: 150px;min-width: 200px;word-break: break-all; white-space: normal; max-width: 300px;">
                                        <?= $value['title'] ?>
                                        <?= empty($value['sku_name']) ? '' : '<br><span class="text-muted" style="font-size: 14px;">' . $value['sku_name'] . '</span>' ?>
                                        <?= $value['delete_time'] ? '<br><span style="color: #ea644a;">商品已删除</span>' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="min-width: 150px;">
                                        <?= goodsTypeText($value['goods_type']) ?>
                                        <i class="fa fa-remove"></i>
                                        <?= $value['quantity'] ?>
                                    </div>

                                </td>
                                <td>
                                    <div class="break-all stock-content-<?= $value['stock_id'] ?>" style="max-width: 300px; word-break: break-all; max-height: 72px; overflow: auto;     white-space: normal;" ><?= $value['content'] ?></div>
                                </td>
                                <td>
                                    <div style="min-width: 180px;" class="text-center">
                                        <?= date('Y-m-d H:i:s', $value['create_time']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="min-width: 200px;">
                                    <span class="btn btn-info stock-show size-sm" data-id="<?= $value['stock_id'] ?>">
                                        <i class="fa fa-eye"></i> 查看
                                    </span>
                                        <a
                                                data-toggle="modal" data-target="#editModal"
                                                data-stock_id="<?= $value['stock_id'] ?>"
                                                data-token="<?= LoginAuth::genToken() ?>"
                                                class="btn size-sm primary edit-btn ml-1"
                                                data-goods_type="<?= $value['goods_type'] ?>"
                                                data-quantity="<?= $value['quantity'] ?>"
                                        >
                                            <i class="fa fa-edit"></i> 编辑
                                        </a>
                                        <a data-stock_id="<?= $value['stock_id'] ?>" data-token="<?= LoginAuth::genToken() ?>" class="btn danger del-stock-btn size-sm ml-1">
                                            <i class="fa fa-trash"></i> 删除
                                        </a>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            </form>
            <div class="pager mt-5 justify-center"><?= $pageurl ?> </div>
        </div>
    </div>


</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" >
    <div class="modal-dialog modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <form class="form" action="stock.php?action=edit" method="post" enctype="multipart/form-data" id="edit-form">
                <div class="modal-header" style="padding: 10px 1rem;">
                    <span class="modal-title" id="editModalLabel">编辑库存</span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="form-group edit-stock-input" id="edit-number">
                        <label>库存数量</label>
                        <input type="number" class="form-control" id="number-input" name="quantity">
                    </div>
                    <div class="form-group edit-stock-input" id="edit-content">
                        <label>库存内容</label>
                        <input type="text" class="form-control" id="content-input" name="content">
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" class="form-control" id="stock_id-input" name="stock_id">
                    <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn primary btn-sm">保存</button>
                </div>
            </form>

        </div>
    </div>
</div>

<div class="modal" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">导出卡密 <span class="text-muted text-sm">未售出</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div action="stock.php?action=export" method="get" target="_blank" class="panel bg-white">
                <div class="form">
                    <div class="modal-body  opacity-80"  style="max-height: 80vh; overflow: auto">
                        <div class="form-group">
                            <label class="form-label">商品分类</label>
                            <select name="sort_id" class="form-control">
                                <option value="-1">未分类</option>
                                <?php foreach($sorts as $val): ?>
                                    <option value="<?= $val['sid'] ?>"><?= $val['sortname'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">选择商品</label>
                            <select name="export_goods_id" class="form-control">
                                <option value="0">请选择商品</option>
                                <?php foreach($goods as $val): ?>
                                    <option style="display: none;" value="<?= $val['id'] ?>" class="goods-option sort-<?= $val['sort_id'] ?>"><?= $val['title'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">商品规格</label>
                            <select name="sku" class="form-control">
                                <option value="0">无规格</option>
                                <?php foreach($sku_list as $val): ?>
                                    <option style="display: none;" value="<?= $val['sku'] ?>" class="goods-sku sku-<?= $val['goods_id'] ?>"><?= $val['sku_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">导出范围</label>
                            <div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="export_range_all" name="export_range" class="custom-control-input" value="all" checked>
                                    <label class="custom-control-label" for="export_range_all">全部库存的卡密</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="export_range_num" name="export_range" class="custom-control-input" value="num">
                                    <label class="custom-control-label" for="export_range_num">
                                        导出指定的数量
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="export_range_time" name="export_range" class="custom-control-input" value="time">
                                    <label class="custom-control-label" for="export_range_time">
                                        按添加时间导出
                                    </label>
                                </div>

                            </div>
                        </div>
                        <div class="form-group export_num" style="display: none;">
                            <div class="custom-control">
                                <label class="form-label">导出数量</label>
                                <input name="export_num" value="" type="number" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group create_time" style="display: none;">
                            <label class="form-label">卡密添加时间</label>
                            <div class="row">
                                <div id="startTime" class="col-6 col-lg-6"></div>
                                <div id="endTime" class="col-6 col-lg-6"></div>
                            </div>

                        </div>
                        <div class="form-group">
                            <label class="form-label">是否删除</label>
                            <div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="is_delete_false" name="is_delete" class="custom-control-input" value="0" checked>
                                    <label class="custom-control-label" for="is_delete_false">仅导出不做删除</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="is_delete_true" name="is_delete" class="custom-control-input" value="1">
                                    <label class="custom-control-label" for="is_delete_true">
                                        导出并删除卡密
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">导出格式</label>
                            <div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="type_txt" name="type" class="custom-control-input" value="txt" checked>
                                    <label class="custom-control-label" for="type_txt">TXT</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" id="export_btn" class="btn primary">立即导出</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #stock-show-layer{
        max-width: 500px !important;
        max-height: 80vh !important;
        width: auto !important;
        margin: 0 auto !important; /* 水平居中 */
        left: 0 !important;       /* 重置left定位 */
        right: 0 !important;      /* 配合margin实现居中 */
    }
    #stock-show-layer .layui-layer-content{
        overflow: auto;
    }
</style>

<script>


    const searchBox = new zui.SearchBox('#searchBox', {
        name: 'keyword',
        className: 'search-input',
        placeholder: '规格名/卡密内容',
        defaultValue: '<?= $keyword ?>',
        onFocus: function(){

        }
    });
    setTimeout(function(){
        $('#searchBox input').attr('name', 'keyword');
    }, 50)


    const items = <?= $goods_json ?>;
    const picker = new zui.Picker('#singlePickerExample', {
        items,
        name: 'goods_id',
        placeholder: '选择商品',
        searchHint: '搜索商品',
        defaultValue: '<?= $goods_id ?>'
    });


    new zui.DatePicker('#startTime', {
        defaultValue: '',
        placeholder: '开始时间',
        name: 'start_time',
    });
    new zui.DatePicker('#endTime', {
        defaultValue: '',
        placeholder: '结束时间',
        name: 'end_time'
    });



    $('.edit-btn').click(function(){
        $('.edit-stock-input').hide();
        $('#number-input').val(-999);
        let stock_id = $(this).data('stock_id');
        let quantity = $(this).data('quantity');
        let goods_type = $(this).data('goods_type');
        let content = $('.stock-content-' + stock_id).html();
        $('#stock_id-input').val(stock_id);
        if(goods_type == 'duli'){
            $('#edit-content').show();
            $('#content-input').val(content);
            $('#number-input').val(quantity);
        }
        if(goods_type == 'guding'){
            $('#edit-content').show();
            $('#edit-number').show();
            $('#content-input').val(content);
            $('#number-input').val(quantity);
        }
        if(goods_type == 'xuni'){
            $('#edit-number').show();
            $('#number-input').val(quantity);
        }
        if(goods_type == 'post'){
            $('#edit-number').show();
            $('#number-input').val(quantity);
        }
    })

    $("#edit-form").submit(function (event) {

        event.preventDefault();
        $.ajax({
            type: "POST",
            url: $('#edit-form').attr('action'),
            data: $('#edit-form').serialize(),
            dataType: "json",
            success: function (e) {
                if(e.code == 0){
                    layer.msg('已保存');
                    location.reload();
                }else{
                    layer.msg('保存失败');
                }
            },
            error: function (xhr) {
                const errorMsg = JSON.parse(xhr.responseText).msg;
                layer.msg(errorMsg)
            }
        });
    });

    var sort_id = $('[name="sort_id"]').val();
    $('.sort-' + sort_id).show();

    $('[name="export_range"]').change(function(){
        var export_range = $('input[name="export_range"]:checked').val();
        if(export_range == 'all'){
            $('.export_num').hide();
            $('.create_time').hide();
        }
        if(export_range == 'num'){
            $('.export_num').show();
            $('.create_time').hide();
        }
        if(export_range == 'time'){
            $('.create_time').show();
            $('.export_num').hide();
        }
    })

    $('[name="export_goods_id"]').change(function(){
        $('.goods-sku').hide();
        $('[name="sku"]').val(0)
        var goods_id = $('[name="export_goods_id"]').val();
        $('.sku-' + goods_id).show();
    })

    $('[name="sort_id"]').change(function(){
        $('.goods-option').hide();
        $('[name="goods_id"]').val(0)
        $('[name="sku"]').val(0)
        var sort_id = $('[name="sort_id"]').val();
        $('.sort-' + sort_id).show();
    })

    $('#export_btn').click(function(){

        if(demo == 1){
            layer.msg('演示站点无法进行该操作');
            return;
        }

        var sort_id = $('[name="sort_id"]').val();
        var goods_id = $('[name="export_goods_id"]').val();
        var start_time = $('[name="start_time"]').val();
        var end_time = $('[name="end_time"]').val();
        var sku = $('[name="sku"]').val();
        var export_range = $('input[name="export_range"]:checked').val();
        var export_num = $('[name="export_num"]').val();
        var is_delete = $('input[name="is_delete"]:checked').val();
        if(goods_id == 0){
            zui.Messager.show({
                content: '请选择商品',
                type: 'danger',
            });
            return;
        }
        if(export_range == 'num'){
            if(export_num == '' || export_num <= 0){
                layer.msg("请输入导出卡密的数量");
                return;
            }
        }
        window.open("stock.php?action=export&export_num=" + export_num + "&goods_id=" + goods_id + "&sku=" + sku + "&export_range=" + export_range + "&is_delete=" + is_delete + "&start_time=" + start_time + "&end_time=" + end_time);
    })

    $('.stock-show').click(function(){
        var id = $(this).data('id');
        var content = $('.stock-content-' + id).html();
        layer.open({
            title: '查看卡密', content: content,
            success: function(layero, index) {
                // 通过layero参数获取弹出层DOM并设置ID
                // layero.attr('id', 'stock-show-layer');
            }
        });
    })

    $('#batchDeleteBtn').click(function(){
        var ids = [];
        $('.select-item:checked').each(function() {
            ids = $('.select-item:checked').map(function() {
                return $(this).val();
            }).get();
            ids = ids.join(',');
        });
        layer.confirm('确定要删除这些数据吗？', {
            btn: ['确认', '取消'], // 按钮
            icon: 3,             // 图标，3表示问号
            title: '温馨提示'
        }, function(index) {
            layer.close(index); // 关闭对话框
            $.ajax({
                url: '?action=delete_stock',
                type: 'POST',
                dataType: 'json',
                data: { ids: ids },
                success: function(res) {
                    location.reload();
                },
                error: function(err) {
                    layer.msg(err.responseJSON.msg);
                },
                complete: function() {

                }
            });
        }, function() {

        });

    })

</script>

<script>
    $(function () {
        $("#menu-goods").attr('class', 'admin-menu-item has-list in');
        $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-goods > .submenu").css('display', 'block');
        $('#menu-stock-list > a').attr('class', 'menu-link active');

        $('.del-stock-btn').click(function(){
            let stock_id = $(this).data('stock_id');

            layer.confirm('确定要删除这些数据吗？', {
                btn: ['确认', '取消'], // 按钮
                icon: 3,             // 图标，3表示问号
                title: '温馨提示'
            }, function(index) {
                layer.close(index); // 关闭对话框
                $.ajax({
                    url: '?action=delete_stock',
                    type: 'POST',
                    dataType: 'json',
                    data: { ids: stock_id },
                    success: function(res) {
                        location.reload();
                    },
                    error: function(err) {
                        layer.msg(err.responseJSON.msg);
                    },
                    complete: function() {

                    }
                });
            }, function() {

            });
        })

    });
</script>
