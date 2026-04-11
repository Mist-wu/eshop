<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php
$coupon = $coupon ?? [];
$isEdit = !empty($coupon);
$categoryId = (int)($coupon['category_id'] ?? 0);
$goodsId = (int)($coupon['goods_id'] ?? 0);
$remark = $coupon['remark'] ?? '';
$expireValue = !empty($coupon['end_time']) ? date('Y-m-d H:i:s', $coupon['end_time']) : '';
$discountType = $coupon['discount_type'] ?? 'amount';
$thresholdType = $coupon['threshold_type'] ?? 'none';
$sorts = $sorts ?? [];
$sortTree = [];
foreach ($sorts as $sid => $sort) {
    $pid = (int)($sort['pid'] ?? 0);
    if (!isset($sortTree[$pid])) {
        $sortTree[$pid] = [];
    }
    $sortTree[$pid][] = (int)$sid;
}

function renderCouponSortOptions($sorts, $sortTree, $pid, $depth, $currentId) {
    if (empty($sortTree[$pid])) {
        return;
    }
    foreach ($sortTree[$pid] as $sid) {
        if (empty($sorts[$sid])) {
            continue;
        }
        $sort = $sorts[$sid];
        $label = str_repeat('—', $depth) . $sort['sortname'];
        $selected = ($currentId === (int)$sid) ? 'selected' : '';
        echo '<option value="' . $sid . '" ' . $selected . '>' . $label . '</option>';
        renderCouponSortOptions($sorts, $sortTree, $sid, $depth + 1, $currentId);
    }
}
?>

<style>
    html, body { height: 100%; }
    body { margin: 0; }
    .coupon-form-shell { height: 100%; background: #f8fafb; }
    .coupon-form { height: 100%; display: flex; flex-direction: column; }
    .coupon-form-body { flex: 1; overflow-y: auto; padding: 16px; }
    .coupon-form-footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e6e6e6;
        padding: 12px 16px;
        text-align: center;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }
    .coupon-discount-value-wrap { position: relative; display: inline-flex; align-items: center; }
    .coupon-discount-value { padding-right: 60px; }
    .coupon-discount-unit {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: #f1f5f9;
        color: #111827;
        border-radius: 12px;
        padding: 2px 10px;
        font-weight: 600;
        font-size: 12px;
        border: 1px solid #d0d5dd;
    }
    .coupon-hint { color: #7b8794; font-size: 12px; margin-top: 6px; }
</style>

<div class="coupon-form-shell">
    <form class="layui-form coupon-form" id="couponForm" novalidate>
        <input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input name="id" value="<?= $coupon['id'] ?? '' ?>" type="hidden"/>
        <div class="coupon-form-body">
            <div class="layui-form-item">
                <label class="layui-form-label">商品分类</label>
                <div class="layui-input-block">
                    <select name="category_id" id="category_id" lay-filter="category_id" lay-search>
                        <option value="0">全场通用</option>
                        <?php renderCouponSortOptions($sorts, $sortTree, 0, 0, $categoryId); ?>
                    </select>
                    <div class="coupon-hint">不选分类则全场可用，选分类则该分类商品可用</div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选择商品</label>
                <div class="layui-input-block">
                    <select name="goods_id" id="goods_id" lay-search>
                        <option value="0">不指定商品</option>
                    </select>
                    <div class="coupon-hint">选择商品后，仅该商品可用</div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">备注信息</label>
                <div class="layui-input-block">
                    <textarea name="remark" id="remark" class="layui-textarea" placeholder="备注信息（选填）"><?= htmlspecialchars($remark, ENT_QUOTES) ?></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">使用门槛</label>
                <div class="layui-input-block">
                    <div class="layui-input-inline">
                        <select name="threshold_type" id="threshold_type" lay-filter="threshold_type">
                            <option value="none" <?= $thresholdType === 'none' ? 'selected' : '' ?>>无门槛</option>
                            <option value="min" <?= $thresholdType === 'min' ? 'selected' : '' ?>>有门槛</option>
                        </select>
                    </div>
                    <div class="layui-input-inline">
                        <input type="number" step="0.01" name="min_amount" id="min_amount" class="layui-input" placeholder="最低消费金额" value="<?= $coupon['min_amount'] ?? '' ?>">
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">抵扣模式</label>
                <div class="layui-input-block">
                    <input type="radio" name="discount_type" value="amount" title="金额抵扣" lay-filter="discount_type" <?= $discountType === 'amount' ? 'checked' : '' ?>>
                    <input type="radio" name="discount_type" value="percent" title="百分比抵扣" lay-filter="discount_type" <?= $discountType === 'percent' ? 'checked' : '' ?>>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">优惠数值</label>
                <div class="layui-input-block">
                    <div class="coupon-discount-value-wrap">
                        <input type="number" step="0.01" name="discount_value" id="discount_value" class="layui-input coupon-discount-value" placeholder="请输入优惠数值" value="<?= $coupon['discount_value'] ?? '' ?>">
                        <span class="coupon-discount-unit">元</span>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">过期时间</label>
                <div class="layui-input-block">
                    <input type="text" name="expire_time" id="expire_time" class="layui-input" placeholder="选择过期时间（不选则不限）" value="<?= $expireValue ?>" autocomplete="off">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">可用次数</label>
                <div class="layui-input-block">
                    <input type="number" name="use_limit" id="use_limit" class="layui-input" placeholder="可用次数（0为不限）" value="<?= $coupon['use_limit'] ?? 1 ?>">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">优惠券前缀</label>
                <div class="layui-input-block">
                    <input type="text" name="prefix" id="prefix" class="layui-input" placeholder="例如：EM" value="<?= $coupon['prefix'] ?? '' ?>">
                </div>
            </div>
            <?php if (!$isEdit): ?>
            <div class="layui-form-item" id="quantity_row">
                <label class="layui-form-label">生成数量</label>
                <div class="layui-input-block">
                    <input type="number" name="quantity" id="quantity" class="layui-input" value="1">
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="coupon-form-footer">
            <button type="button" class="layui-btn layui-btn-green" id="btn-coupon-save">保存</button>
            <button type="button" class="layui-btn" id="btn-coupon-cancel">取消</button>
        </div>
    </form>
</div>

<script>
    layui.use(['form', 'laydate'], function(){
        var form = layui.form;
        var laydate = layui.laydate;
        var $ = layui.$;
        form.render();

        function toggleMinAmount() {
            var type = $('#threshold_type').val();
            if (type === 'min') {
                $('#min_amount').prop('disabled', false);
            } else {
                $('#min_amount').prop('disabled', true).val('');
            }
        }

        function updateDiscountUnit(type) {
            var isPercent = type === 'percent';
            var unitText = isPercent ? '%' : '￥';
            var placeholderText = isPercent ? '请输入百分比' : '请输入金额';
            $('.coupon-discount-unit').text(unitText);
            $('#discount_value').attr('placeholder', placeholderText);
        }

        function renderGoodsOptions(list, selectedId) {
            var $select = $('#goods_id');
            $select.empty();
            $select.append('<option value="0">不指定商品</option>');
            if (Array.isArray(list)) {
                list.forEach(function(item){
                    var text = item.title ? item.title : ('商品#' + item.id);
                    var $opt = $('<option></option>').val(item.id).text(text);
                    $select.append($opt);
                });
            }
            if (selectedId && selectedId > 0) {
                $select.val(String(selectedId));
            }
            form.render('select');
        }

        function loadGoodsOptions(categoryId, selectedId) {
            $.ajax({
                type: 'GET',
                url: 'coupon.php?action=goods',
                data: { category_id: categoryId },
                dataType: 'json',
                success: function(resp){
                    if (resp.code === 0) {
                        renderGoodsOptions((resp.data && resp.data.list) ? resp.data.list : [], selectedId);
                    } else {
                        renderGoodsOptions([], 0);
                        layer.msg(resp.msg || '加载商品失败');
                    }
                },
                error: function(){
                    renderGoodsOptions([], 0);
                }
            });
        }

        laydate.render({
            elem: '#expire_time',
            type: 'datetime',
            trigger: 'click'
        });

        toggleMinAmount();
        updateDiscountUnit($('input[name="discount_type"]:checked').val() || 'amount');

        var currentCategoryId = <?= $categoryId ?>;
        var currentGoodsId = <?= $goodsId ?>;
        loadGoodsOptions(currentCategoryId, currentGoodsId);

        form.on('select(threshold_type)', function(){
            toggleMinAmount();
        });
        form.on('select(category_id)', function(data){
            loadGoodsOptions(data.value, 0);
        });
        form.on('radio(discount_type)', function(data){
            updateDiscountUnit(data.value);
        });

        $('#btn-coupon-cancel').on('click', function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        });

        $('#btn-coupon-save').on('click', function(){
            $.ajax({
                type: 'POST',
                url: 'coupon.php?action=save',
                data: $('#couponForm').serialize(),
                dataType: 'json',
                success: function(resp){
                    if (resp.code === 0) {
                        if (window.parent) {
                            window.parent.location.reload();
                            if (window.parent.AdminModal) {
                                window.parent.AdminModal.close();
                            }
                        }
                    } else {
                        layer.msg(resp.msg || '保存失败');
                    }
                },
                error: function(xhr){
                    var msg = '保存失败';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp && resp.msg) msg = resp.msg;
                    } catch (e) {}
                    layer.msg(msg);
                }
            });
        });
    });
</script>
