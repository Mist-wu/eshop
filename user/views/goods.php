<?php
defined('EM_ROOT') || exit('access denied!');
$isdraft = $draft ? '&draft=1' : '';

?>
<?php if (isset($_GET['active_del'])): ?>
    <div class="alert alert-success">删除成功</div><?php endif ?>
<?php if (isset($_GET['active_up'])): ?>
    <div class="alert alert-success">置顶成功</div><?php endif ?>
<?php if (isset($_GET['active_down'])): ?>
    <div class="alert alert-success">取消置顶成功</div><?php endif ?>
<?php if (isset($_GET['error_a'])): ?>
    <div class="alert alert-danger">请选择要处理的文章</div><?php endif ?>
<?php if (isset($_GET['error_b'])): ?>
    <div class="alert alert-danger">请选择要执行的操作</div><?php endif ?>
<?php if (isset($_GET['active_post'])): ?>
    <div class="alert alert-success">发布成功</div><?php endif ?>
<?php if (isset($_GET['active_move'])): ?>
    <div class="alert alert-success">移动成功</div><?php endif ?>
<?php if (isset($_GET['active_change_author'])): ?>
    <div class="alert alert-success">更改作者成功</div><?php endif ?>
<?php if (isset($_GET['active_hide'])): ?>
    <div class="alert alert-success">转入草稿箱成功</div><?php endif ?>
<?php if (isset($_GET['active_savedraft'])): ?>
    <div class="alert alert-success">草稿保存成功</div><?php endif ?>
<?php if (isset($_GET['active_savelog'])): ?>
    <div class="alert alert-success">保存成功</div><?php endif ?>
<?php if (isset($_GET['active_ck'])): ?>
    <div class="alert alert-success">文章审核成功</div><?php endif ?>
<?php if (isset($_GET['active_unck'])): ?>
    <div class="alert alert-success">文章驳回成功</div><?php endif ?>
<?php if (isset($_GET['error_post_per_day'])): ?>
    <div class="alert alert-danger">超出每日发文数量</div><?php endif ?>


<ol class="breadcrumb" style="padding-left: 10px; padding-top: 10px;">
    <li><a href="./">控制台</a></li>
    <li><a href="./goods.php">商品管理</a></li>
    <li class="active">商品列表</li>
</ol>


<div class="card shadow mb-4">

    <a href="./goods.php?action=release" class="btn btn-sm btn-success shadow-sm mt-4"><i class="icofont-pencil-alt-5"></i> 发布商品</a>
    <div class="card-body">
        <form action="goods.php?action=operate_goods" method="post" name="form_log" id="form_log">
            <input type="hidden" name="draft" value="<?= $draft ?>">
            <div>
                <table class="table table-bordered table-striped table-hover datatable no-footer">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAllItem"/></th>
                        <th>商品名称</th>
                        <th>商品类型</th>
                        <th>库存</th>
                        <th>分类</th>
                        <th><a href="goods.php?sortDate=<?= $sortDate . $sorturl ?>">添加时间</a></th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody class="checkboxContainer">
                    <?php
                    $multiCheckBtn = false; // 是否显示批量审核驳回按钮
                    foreach ($logs as $key => $value):
                        $sortName = isset($sorts[$value['sortid']]['sortname']) ? $sorts[$value['sortid']]['sortname'] : '未知分类';
                        $sortName = $value['sortid'] == -1 ? '未分类' : $sortName;
                        ?>
                        <tr>
                            <td style="width: 20px;"><input type="checkbox" name="blog[]" value="<?= $value['id'] ?>" class="ids"/></td>
                            <td>
                                <a href="goods.php?action=edit&id=<?= $value['id'] ?>"><?= $value['title'] ?></a>
                                <a href="<?= Url::log($value['id']) ?>" target="_blank" class="text-muted ml-2"><i class="icofont-external-link"></i></a>
                                <br>
                                <?php if ($value['top'] == 'y'): ?><span class="badge small badge-success">首页置顶</span><?php endif ?>
                                <?php if ($value['sortop'] == 'y'): ?><span class="badge small badge-info">分类置顶</span><?php endif ?>
                                <?php if (!$draft && $value['timestamp'] > time()): ?><span class="badge small badge-warning">定时发布</span><?php endif ?>
                                <?php if ($value['password']): ?><span class="small">??</span><?php endif ?>
                                <?php if ($value['link']): ?><span class="small">??</span><?php endif ?>
                                <?php if (!$draft && $value['checked'] == 'n'): ?>
                                    <span class="badge small badge-secondary">待审核</span><br>
                                    <small class="text-secondary"><?= $value['feedback'] ? '审核反馈：' . $value['feedback'] : '' ?></small>
                                <?php endif ?>
                            </td>
                            <td><?= goodsTypeText($value['goods_type']) ?></td>
                            <td><?= $value['stock_quantity'] ?></td>
                            <td class="small"><a href="goods.php?sid=<?= $value['sortid'] . $isdraft ?>"><?= $sortName ?></a></td>
                            <td class="small"><?= $value['date'] ?></td>
                            <td>
                                <?php if($value['goods_type'] == 0): ?>
                                    <a href="stock.php?action=add&goods_id=<?= $value['id'] ?>">添加库存</a>
                                <?php endif ?>
                                <?php if(in_array($value['goods_type'], [1,2])): ?>
                                    <a href="stock.php?action=add&goods_id=<?= $value['id'] ?>">编辑库存</a>
                                <?php endif ?>
                                <a href="javascript: eb_confirm(<?= $value['id'] ?>, 'goods', '<?= LoginAuth::genToken() ?>');" class="badge badge-danger">删除</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>

        </form>
        <div class="page"><?= $pageurl ?> </div>
        <div class="text-center small">(有 <?= $logNum ?> 件<?= $draft ? '草稿' : '商品' ?>)</div>
    </div>
</div>

<script>


    $(function () {
        $("#menu-goods").attr('class', 'has-list open in');
        $("#menu-goods-list").addClass('active');
        setTimeout(hideActived, 3600);
    });
</script>
