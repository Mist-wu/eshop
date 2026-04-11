<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    #fairy-sku-table th, #fairy-sku-table td, #fairy-sku-table td input.layui-input{
        text-align: center;
    }
    #fairy-sku-table td input.layui-input{
        padding-left: 0;
    }
</style>
<div id="msg" class="fixed-top alert" style="display: none"></div>




<form action="stock.php?action=add" method="post" enctype="multipart/form-data" id="addgoods" name="addgoods" class="mt-3">
    <?php if($goods['is_sku'] == 'n' && $goods['type'] == 'duli'): ?>
        <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
        <textarea name="content" class="form-control" rows="13" placeholder="不同卡密之间使用回车键换行"></textarea>
    <?php endif ?>
    <?php if($goods['is_sku'] == 'n' && $goods['type'] == 'xuni'): ?>
        <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
        <div class="form-group">
            <label for="exampleInputAccount1">库存数量</label>
            <input type="number" class="form-control" name="quantity" value="<?= empty($stock['quantity']) ? '' : $stock['quantity'] ?>">
        </div>
    <?php endif ?>
    <?php if($goods['is_sku'] == 'n' && $goods['type'] == 'post'): ?>
        <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
        <div class="form-group">
            <label for="exampleInputAccount1">库存数量</label>
            <input type="number" class="form-control" name="quantity" value="<?= empty($stock['quantity']) ? '' : $stock['quantity'] ?>">
        </div>
    <?php endif ?>
    <?php if($goods['is_sku'] == 'n' && $goods['type'] == 'guding'): ?>
        <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
        <div class="form-group">
            <label for="exampleInputAccount1">库存数量</label>
            <input type="number" class="form-control" name="quantity" value="<?= empty($stock['quantity']) ? '' : $stock['quantity'] ?>">
        </div>
        <div class="form-group">
            <label for="exampleInputPassword1">固定通用卡密</label>
            <input type="type" class="form-control" name="content" value="<?= empty($stock['content']) ? '' : $stock['content'] ?>">
        </div>
    <?php endif ?>

    <?php if($goods['is_sku'] == 'y'): ?>
        <?php if(empty($get_sku)): ?>
            <div class="card">
                <div class="card-body">
                    <p class="mb-4">请选择您要添加库存的规格【<?= $goods['title'] ?>】</p>
                    <?php foreach($data as $val): ?>
                        <a href="?action=add&get_sku=<?= $val['sku_value'] ?>&goods_id=<?= $goods['id'] ?>" type="button" class="btn btn-outline-primary mr-3"><?= $val['sku_name'] ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif ?>
        <?php if(!empty($get_sku)): ?>
            <?php if($goods['type'] == 'duli'): ?>
                <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
                <input type="hidden" name="sku" value="<?= $get_sku ?>" />
                <textarea name="content" class="form-control" rows="13" placeholder="不同卡密之间使用回车键换行"></textarea>
            <?php endif ?>

            <?php if($goods['type'] == 'xuni'): ?>
                <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
                <input type="hidden" name="sku" value="<?= $get_sku ?>" />
                <div class="form-group">
                    <label for="exampleInputAccount1">库存数量</label>
                    <input type="number" class="form-control" name="quantity" value="<?= empty($stock['quantity']) ? '' : $stock['quantity'] ?>">
                </div>
            <?php endif ?>

            <?php if($goods['type'] == 'guding'): ?>
                <input type="hidden" name="goods_id" value="<?= $goods_id ?>" />
                <input type="hidden" name="sku" value="<?= $get_sku ?>" />
                <div class="form-group">
                    <label for="exampleInputAccount1">库存数量</label>
                    <input type="number" class="form-control" name="quantity" value="<?= empty($stock['quantity']) ? '' : $stock['quantity'] ?>">
                </div>
                <div class="form-group">
                    <label for="exampleInputPassword1">固定通用卡密</label>
                    <input type="type" class="form-control" name="content" value="<?= empty($stock['content']) ? '' : $stock['content'] ?>">
                </div>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>


    <?php if($goods['is_sku'] == 'n' || !empty($get_sku)): ?>
        <div class="col-xs-12 col-md-12">
            <div id="post_button">
                <input type="submit" value="保存" class="btn btn-success btn-sm"/>
            </div>
        </div>
    <?php endif; ?>
</form>



<?php if(isset($_GET['save'])){ ?>
<script>
    $(function(){
        layer.msg('库存添加成功');
    })

</script>
<?php } ?>
<script>


    $('#btn-save').click(function(){

        let content = $('#stock-content').val();
        let id = $(this).data('id')
        $('textarea[name="skus[' + id + '][stock_sdk]"]').html(content);
        $('#stock-content').val(null);
        $('#addStock').modal('hide')
    })

    var goods_type = "<?= $goods_type ?>"




</script>
<script>

    $(function () {
        $("#menu-goods").attr('class', 'admin-menu-item has-list in');
        $("#menu-goods .fa-angle-right").attr('class', 'fas arrow iconfont icon-you active');
        $("#menu-goods > .submenu").css('display', 'block');
        setTimeout(hideActived, 3600);



    });



</script>
