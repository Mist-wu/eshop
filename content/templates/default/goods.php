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


?>

<style>
    .stat-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f5f7ff;
        border-radius: 20px;
        margin-right: 10px;
        font-size: 13px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        box-shadow: 0 8px 18px rgba(95, 132, 255, 0.08);
    }
    
    .stat-item:hover {
        background: var(--brand-soft);
        border-color: rgba(95, 132, 255, 0.3);
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(95, 132, 255, 0.14);
    }
    
    .stat-icon {
        font-size: 14px;
    }
    
    .stat-label {
        color: var(--muted);
        font-weight: 500;
    }
    
    .stat-value {
        color: var(--text);
        font-weight: 600;
    }
    
    .stock-stat .stat-value {
        color: var(--brand);
    }
    
    .sales-stat .stat-value {
        color: var(--brand-dark);
    }
    
    .delivery-type {
        margin: 10px 0;
    }
    
    .delivery-tag {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        margin-right: 8px;
    }
    
    .auto-delivery {
        background: var(--brand-soft);
        color: var(--brand);
        border: 1px solid rgba(95, 132, 255, 0.28);
    }
    
    .manual-delivery {
        background: #f3f6fd;
        color: #62708c;
        border: 1px solid var(--border);
    }
</style>

<article class="container goods-con">

    <div class="card">
        <div class="row no-gutters">
            <div class="col-md-4" style="padding: 10px;">
                <img class="goods-cover" src="<?= $goods['cover'] ?>" alt="" style="" onerror="this.src='<?= EM_URL ?>admin/views/images/cover.svg'; this.onerror=null;" />
            </div>
            <div class="col-md-8" style="padding-bottom: 1rem;">
                <div class="card-body goods-attr" style="padding-bottom: 0;">
                    <h3 class="card-title"><?= $goods['title'] ?></h3>
                    <p class="card-text">
                        <?php if(_g('stock_show') != 'n'): ?>
                            <span class="stat-item stock-stat">
                                <span class="stat-icon">📦</span>
                                <span class="stat-label">库存</span>
                                <span id="stock" class="dynamic-stock stat-value"><?= $goods['stock'] ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if(_g('sales_show') != 'n'): ?>
                            <span class="stat-item sales-stat">
                                <span class="stat-icon">📈</span>
                                <span class="stat-label">销量</span>
                                <span id="sales" class="dynamic-sales stat-value"><?= $goods['sales'] ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if (isset($goods['is_auto']) && $goods['is_auto']): ?>
                            <span class="stat-item sales-stat">
                                <span class="stat-icon">🤖</span>
                                <span class="stat-label">自动发货</span>
                            </span>
                        <?php else: ?>
                            <span class="stat-item sales-stat">
                                <span class="stat-icon">👤</span>
                                <span class="stat-label">人工发货</span>
                            </span>
                        <?php endif; ?>
                    </p>
                    <div style="margin-bottom: 15px;">
                        <span class="currency">¥</span>
                        <span class="price dynamic-price" id="price"><?= $goods['price'] ?></span>
                    </div>

                    <div class="col-lg-12" style="padding: 0;">
                        <!-- 开发模板，可直接复用此处 -->
                        <form class="drawer-content layui-form" id="buyForm">
                            <!-- 规格选择 -->
                            <input type="hidden" name="goods_id" id="goods_id" value="<?= $goods['id'] ?>">
                            <?php foreach($goods['skus']['option_name'] as $val): ?>
                                <div class="form-group spec-group">
                                    <div class="form-title">
                                        <?= $val['title'] ?>
                                    </div>
                                    <div class="spec-options">
                                        <?php foreach($val['sku_values'] as $v): ?>
                                            <div class="spec-option" data-id="<?= $v['option_id'] ?>"><?= $v['option_name'] ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <!-- 购买数量 -->
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
                                    <a href="javascript:;" class="coupon-change-link">【更换】</a>
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
                            <!-- 自定义输入信息 -->
                            <?php if(!empty($goods['config']['input'])): ?>
                            <?php foreach($goods['config']['input'] as $val): ?>
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
                            <?php if(!empty($visitor_input)): ?>
                            <?php foreach($visitor_input as $field): ?>
                                <?php if($field['type'] == 'contact'): ?>
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
                                <?php elseif($field['type'] == 'password'): ?>
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
                            <!-- 支付方式选择 -->
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
                                    <?php foreach($payment as $val): ?>
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
                            <!-- 提交订单 -->
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
        </div>
    </div>



    <div class="card" style="padding: 15px;">
        <div class="markdown intro" id="eshopEchoLog"><?= $goods['content'] ?></div>
    </div>





    <div style="clear:both;"></div>
</article>


<script>
    layui.use(['carousel', 'layer'], function() {
        var $ = layui.$;
        var form = layui.form;
        var layer = layui.layer;
        // 内容图片错误处理
        $('.intro img').each(function() {
            var img = this;
            img.onerror = function() {
                img.src = '<?= EM_URL ?>admin/views/images/cover.svg';
                img.onerror = null;
            };
            // 如果图片已经加载失败（针对缓存或快速加载的情况）
            if (img.complete && img.naturalWidth === 0) {
                img.src = '<?= EM_URL ?>admin/views/images/cover.svg';
            }
        });
        form.on('submit(buy-submit)', function(data){
            toBuy();
            return false; // 阻止默认 form 跳转
        });
        var goods = <?= json_encode($goods) ?>;
        initSku(goods)
    })
</script>

<?php include View::getCommonView('footer') ?>
