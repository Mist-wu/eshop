<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    table tr:last-child td{
        border-bottom: none;
    }
</style>

<div class="panel" style="">
    <div class="panel-body no-padding">

        <div style="padding: 12px 10px;">
            <div class="btn-group">
                <a href="javascript:;" id="add-sku-attr" class="btn btn-primary btn-sm"><i class="icon icon-plus"></i> 添加属性</a>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>属性</th>
                            <th>规格</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($list as $key => $val):
                        ?>
                            <tr>
                                <td>
                                    <a href="javascript:;" data-content="<?= $val['name'] ?>" data-id="<?= $val['id'] ?>" class="update-sku-attr"><?= $val['name'] ?></a>
                                    <a href="javascript:;" data-id="<?= $val['id'] ?>" class="text-danger del-sku-attr"><i class="fa fa-trash"></i></a>
                                </td>
                                <td>
                                    <?php
                                    foreach($val['value'] as $v):
                                    ?>
                                    <a href="javascript:;" data-content="<?= $v['name'] ?>" data-id="<?= $v['id'] ?>" class="delete-sku-value"><?= $v['name'] ?></a>
                                    <a href="javascript: eb_confirm(<?= $v['id'] ?>, 'attr_value', '<?= LoginAuth::genToken() ?>');" class="text-danger"><i class="fa fa-trash"></i></a>
                                        &nbsp;&nbsp;&nbsp;
                                    <?php endforeach; ?>
                                    <a href="javascript:;" data-id="<?= $val['id'] ?>" class="add-sku-value">添加规格</a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>




<script>
    $(function () {
        $("#menu-goods").attr('class', 'admin-menu-item has-list in');
        $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-goods > .submenu").css('display', 'block');
        $('#menu-sku-list > a').attr('class', 'menu-link active')


        $('.update-sku-attr').click(function(){
            var id = $(this).data('id');
            var content = $(this).data('content');


            layer.prompt({
                title: '修改属性值',
                formType: 0, // 0表示文本框
                value: content
            }, function(value, index, elem){
                window.location = 'sku.php?action=update_sku_attr&id=' + id + '&type_id=<?= $cate['id'] ?>&content=' + value;
                layer.close(index); // 关闭提示框
            });


        })

        $('.delete-sku-value').click(function(){
            var id = $(this).data('id');
            var content = $(this).data('content');

            layer.prompt({
                title: '修改规格值',
                formType: 0, // 0表示文本框
                value: content
            }, function(value, index, elem){
                window.location = 'sku.php?action=edit_sku_value&id=' + id + '&type_id=<?= $cate['id'] ?>&content=' + value;
                layer.close(index); // 关闭提示框
            });
        })

        $('.add-sku-value').click(function(){
            var id = $(this).data('id');


            layer.prompt({
                title: '添加规格',
                formType: 0 // 0表示文本框
            }, function(value, index, elem){
                window.location = 'sku.php?action=add_sku_value&id=' + id + '&type_id=<?= $cate['id'] ?>&content=' + value;
                layer.close(index); // 关闭提示框
            });

        })

        $('#add-sku-attr').click(function(){

            layer.prompt({
                title: '添加属性',
                formType: 0 // 0表示文本框
            }, function(value, index, elem){
                window.location = 'sku.php?action=add_sku_attr&type_id=<?= $cate['id'] ?>&content=' + value;
                layer.close(index); // 关闭提示框
            });


        })

        $('.del-sku-attr').click(function() {
            var id = $(this).data('id');


            layer.confirm('确定要删除这个属性吗？', {icon: 3, title: '温馨提示', skin: 'class-layer-danger', btn: ['确定', '取消']}, function (index) {
                window.location = 'sku.php?action=del_sku_attr&id=' + id + '&type_id=<?= $cate['id'] ?>';
                layer.close(index);
            });
        })

    });
</script>

