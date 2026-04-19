<?php
/**
 * 商品详情页
 */
defined('EM_ROOT') || exit('access denied!');

$minQty = isset($goods['min_qty']) ? (int)$goods['min_qty'] : 1;
if ($minQty < 1) {
    $minQty = 1;
}
$maxQty = isset($goods['max_qty']) ? (int)$goods['max_qty'] : 0;
if ($maxQty < 1) {
    $maxQty = 0;
}
if ($maxQty > 0 && $maxQty < $minQty) {
    $maxQty = $minQty;
}
$maxQtyAttr = $maxQty > 0 ? 'max="' . $maxQty . '"' : '';
$fallbackCover = EM_URL . 'content/static/images/cover.svg';
?>

<article class="container goods-con goods-detail-page">
    <section class="goods-detail-card">
        <div class="goods-detail-layout">
            <div class="goods-detail-media">
                <img class="goods-cover" src="<?= $goods['cover'] ?>" alt="<?= htmlspecialchars($goods['title'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= $fallbackCover ?>'; this.onerror=null;">
            </div>

            <div class="goods-detail-main">
                <div class="goods-summary">
                    <h1 class="goods-detail-title"><?= $goods['title'] ?></h1>

                    <div class="goods-detail-stats">
                        <?php if(_g('stock_show') != 'n'): ?>
                            <span class="goods-stat-item stock-stat">
                                <span class="goods-stat-icon"><i class="fa fa-cubes" aria-hidden="true"></i></span>
                                <span class="goods-stat-label">库存</span>
                                <span id="stock" class="dynamic-stock goods-stat-value"><?= $goods['stock'] ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if(_g('sales_show') != 'n'): ?>
                            <span class="goods-stat-item sales-stat">
                                <span class="goods-stat-icon"><i class="fa fa-line-chart" aria-hidden="true"></i></span>
                                <span class="goods-stat-label">销量</span>
                                <span id="sales" class="dynamic-sales goods-stat-value"><?= $goods['sales'] ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($goods['is_auto'])): ?>
                            <span class="goods-stat-item delivery-stat">
                                <span class="goods-stat-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                                <span class="goods-stat-label">自动发货</span>
                            </span>
                        <?php else: ?>
                            <span class="goods-stat-item delivery-stat">
                                <span class="goods-stat-icon"><i class="fa fa-user-o" aria-hidden="true"></i></span>
                                <span class="goods-stat-label">人工发货</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="goods-price-block">
                        <span class="goods-price-currency">¥</span>
                        <span class="goods-price-value dynamic-price" id="price"><?= $goods['price'] ?></span>
                    </div>
                </div>

                <div class="goods-purchase-panel">
                    <form class="goods-purchase-form layui-form" id="buyForm">
                        <input type="hidden" name="goods_id" id="goods_id" value="<?= $goods['id'] ?>">

                        <?php foreach ($goods['skus']['option_name'] as $val): ?>
                            <div class="form-group spec-group">
                                <div class="form-title"><?= $val['title'] ?></div>
                                <div class="spec-options">
                                    <?php foreach ($val['sku_values'] as $v): ?>
                                        <div class="spec-option" data-id="<?= $v['option_id'] ?>"><?= $v['option_name'] ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-group">
                            <div class="form-title">
                                <i class="fa fa-calculator"></i>购买数量
                            </div>
                            <div class="quantity-selector">
                                <div class="quantity-btn minus" id="drawerMinusBtn"><i class="fa fa-minus"></i></div>
                                <input type="number" class="quantity-input" name="quantity" id="quantity" value="<?= $minQty ?>" min="<?= $minQty ?>" <?= $maxQtyAttr ?>>
                                <div class="quantity-btn plus" id="drawerPlusBtn"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>

                        <?php if (Option::get('coupon_switch') == 'y'): ?>
                            <div class="form-group">
                                <div class="form-title">
                                    <i class="fa fa-ticket"></i>优惠券
                                    <button type="button" class="coupon-change-link">【更换】</button>
                                </div>
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <div class="coupon-input-wrap">
                                            <input type="text" name="coupon_code" id="coupon_code" placeholder="请输入优惠券码（选填）" autocomplete="off" class="layui-input coupon-input">
                                            <button type="button" class="coupon-apply-btn layui-btn layui-btn-primary" id="coupon_apply_btn">使用</button>
                                        </div>
                                        <div class="coupon-tip" id="coupon_tip">
                                            已优惠金额：<span id="coupon_discount">0.00</span>元
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($goods['config']['input'])): ?>
                            <?php foreach ($goods['config']['input'] as $val): ?>
                                <div class="form-group">
                                    <div class="form-title"><?= $val['name'] ?></div>
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="text" name="config[input][<?= $val['name_en'] ?>]" placeholder="<?= $val['placeholder'] ?>" autocomplete="off" class="layui-input">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($visitor_input)): ?>
                            <?php foreach ($visitor_input as $field): ?>
                                <?php if ($field['type'] == 'contact'): ?>
                                    <div class="form-group">
                                        <div class="form-title">
                                            <i class="fa fa-address-book"></i> <?= $field['title'] ?>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="text" name="visitor_input[contact]" value="<?= htmlspecialchars($field['value']) ?>" placeholder="<?= $field['placeholder_order'] ?>" autocomplete="off" class="layui-input" required>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($field['type'] == 'password'): ?>
                                    <div class="form-group">
                                        <div class="form-title">
                                            <i class="fa fa-lock"></i>订单密码
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="text" name="visitor_input[password]" value="<?= htmlspecialchars($field['value']) ?>" placeholder="<?= $field['placeholder_order'] ?>" autocomplete="off" class="layui-input" required>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="form-group">
                            <div class="form-title">
                                <i class="fa fa-credit-card"></i>选择支付方式
                            </div>
                            <div class="payment-methods">
                                <?php if (empty($payment)): ?>
                                    <div class="payment-empty">
                                        <div class="payment-empty-icon"><i class="fa fa-info-circle"></i></div>
                                        <div class="payment-empty-title">暂未开启在线支付</div>
                                        <div class="payment-empty-desc">请先在站点根目录的 .env 文件中填写易付通支付参数，保存后这里会自动显示支付宝和微信支付。</div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($payment as $val): ?>
                                        <div class="payment-item" data-name="<?= $val['name'] ?>" data-method="<?= $val['plugin_name'] ?>">
                                            <div class="payment-icon">
                                                <img src="<?= $val['icon'] ?>" alt="<?= $val['title'] ?>">
                                            </div>
                                            <div class="payment-info">
                                                <div class="payment-name"><?= $val['title'] ?></div>
                                            </div>
                                            <div class="payment-checked layui-icon layui-icon-ok-circle"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="drawer-footer">
                            <div class="drawer-actions">
                                <a href="<?= EM_URL ?>" class="drawer-btn home-btn">
                                    <i class="fa fa-home"></i>
                                    <span>首页</span>
                                </a>
                                <button class="drawer-btn buy-btn-g<?= empty($payment) ? ' is-disabled' : '' ?>" <?= empty($payment) ? 'type="button" disabled' : 'lay-submit lay-filter="buy-submit"' ?>>
                                    <i class="fa fa-shopping-cart"></i>
                                    <span>立即购买</span>
                                    <span class="total-price">¥<span class="dynamic-price"><?= $goods['price'] ?></span></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="goods-content-card">
        <div class="markdown intro" id="eshopEchoLog"><?= $goods['content'] ?></div>
    </section>
</article>

<script>
    layui.use(['form', 'layer'], function() {
        var $ = layui.$;
        var form = layui.form;

        $('.intro img').each(function() {
            var img = this;
            img.onerror = function() {
                img.src = '<?= $fallbackCover ?>';
                img.onerror = null;
            };
            if (img.complete && img.naturalWidth === 0) {
                img.src = '<?= $fallbackCover ?>';
            }
        });

        form.on('submit(buy-submit)', function() {
            toBuy();
            return false;
        });

        initSku(<?= json_encode($goods) ?>);
    });
</script>

<?php include View::getCommonView('footer') ?>
