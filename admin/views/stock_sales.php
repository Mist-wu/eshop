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
</style>


<div class="mt-3 panel bg-white">
    <div class="panel-body">
        <div class="table-container">
            <div>
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
                    <button class="btn btn-action btn-batch-delete danger" id="batchDeleteBtn" disabled>
                        <i class="fa fa-trash"></i> 删除选中
                    </button>
                </div>
                <div class="search-div">
                    <form action="" method="get" class="">
                        <input type="hidden" name="action" value="sales" />
                        <div class="search-box">
                            <div id="singlePickerExample" class="search-input"></div>
                        </div>
                        <div class="search-box has-icon-right" id="searchboxExample">
                            <div id="searchBox"></div>
                        </div>
                        <div class="search-btn">
                            <input type="submit" class="btn primary" value="搜索" />
                            <a href="./stock.php?action=sales" class="btn ml-2">重置</a>
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
                            <th>出售时间</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                        </thead>
                        <tbody class="checkboxContainer">
                        <?php foreach ($list as $key => $value): ?>
                            <tr>
                                <td style="width: 20px;">
                                    <input type="checkbox" name="ids[]" value="<?= $value['id'] ?>" class="select-item" />
                                </td>
                                <td>
                                    <div style="min-width: 150px; min-width: 200px;word-break: break-all; white-space: normal; max-width: 300px;">
                                        <?= $value['title'] ?>
                                        <?= empty($value['sku_name']) ? '' : '<br><span class="text-muted" style="font-size: 14px;">' . $value['sku_name'] . '</span>' ?>
                                        <?= $value['goods_delete_time'] ? '<br><span style="color: #ea644a;">商品已删除</span>' : '' ?>
                                    </div>

                                </td>
                                <td>
                                    <div style="min-width: 150px;">
                                        <?= goodsTypeText($value['goods_type']) ?>
                                    </div>

                                </td>
                                <td>
                                    <div class="stock-content-<?= $value['id'] ?>" style="max-width: 300px; word-break: break-all; max-height: 75px; overflow: auto">
                                        <?= $value['content'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="min-width: 180px;" class="text-center">
                                        <?= date('Y-m-d H:i:s', $value['create_time']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="min-width: 160px;">
                                        <!--                                    <a data-stock_id="--><?php //= $value['id'] ?><!--" data-token="--><?php //= LoginAuth::genToken() ?><!--" class="btn btn-sm btn-primary">-->
                                        <!--                                        <i class="fa fa-edit"></i> 编辑-->
                                        <!--                                    </a>-->
                                        <span class="btn btn-info stock-show size-sm" data-id="<?= $value['id'] ?>">
                                        <i class="fa fa-eye"></i> 查看
                                    </span>
                                        <a data-stock_id="<?= $value['id'] ?>" data-token="<?= LoginAuth::genToken() ?>" class="btn danger del-stock-btn size-sm ml-1">
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
            console.log(ids)
        });
        layer.confirm('确定要删除这些数据吗？', {
            btn: ['确认', '取消'], // 按钮
            icon: 3,             // 图标，3表示问号
            title: '温馨提示'
        }, function(index) {
            layer.close(index); // 关闭对话框
            $.ajax({
                url: '?action=delete_sales',
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
                    url: '?action=delete_sales',
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
