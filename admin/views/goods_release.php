<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php
$payment = getPayment(false);
//d($payment);die;
$attachUserRaw = $goods['attach_user'] ?? '';
$attachUser = [];
if (!empty($attachUserRaw)) {
    $decoded = json_decode($attachUserRaw, true);
    if (is_array($decoded)) {
        $attachUser = $decoded;
    }
}

$goodsConfig = $goods['config'] ?? [];
if (is_string($goodsConfig)) {
    $decodedConfig = json_decode($goodsConfig, true);
    if (is_array($decodedConfig)) {
        $goodsConfig = $decodedConfig;
    }
}
if (empty($goodsConfig['tier_price']) && !empty($goodsConfig['member_price'])) {
    $goodsConfig['tier_price'] = $goodsConfig['member_price'];
}
$attachConfigInput = $goodsConfig['input'] ?? [];
$qtyDiscountConfig = $goodsConfig['qty_discount'] ?? [];
?>
<style>
    html, body {
        height: 100%;
        margin: 0;
    }

    .goods-form-shell {
        height: 100%;
        background: #f8fafb;
    }

    #form {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .goods-form-container {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .goods-form-footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e6e6e6;
        padding: 12px 16px;
        text-align: center;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }

    /* 自定义选项卡样式 */
    .custom-tabs {
        background: #fff;
        border-bottom: 2px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 50;
        padding: 12px 0 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .custom-tabs-nav {
        display: flex;
        padding: 0 20px;
        margin: 0;
        list-style: none;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        gap: 4px;
        background: transparent;
    }

    .custom-tabs-nav::-webkit-scrollbar {
        height: 4px;
    }

    .custom-tabs-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-tabs-nav::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .custom-tab-item {
        position: relative;
        padding: 10px 20px;
        cursor: pointer;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        user-select: none;
        border-radius: 8px 8px 0 0;
        background: transparent;
    }

    .custom-tab-item:hover {
        color: #0f766e;
        background: rgba(15, 118, 110, 0.05);
    }

    .custom-tab-item.active {
        color: #0f766e;
        background: rgba(15, 118, 110, 0.08);
        font-weight: 600;
        position: relative;
    }

    .custom-tab-item.active::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        bottom: 0;
        width: 60%;
        height: 3px;
        background: linear-gradient(90deg, #0f766e, #14b8a6);
        border-radius: 3px 3px 0 0;
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.3);
    }

    .custom-tabs-content {
        padding: 20px;
        min-height: 400px;
    }

    .custom-tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .custom-tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 表单项优化 */
    .layui-form-item {
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: box-shadow 0.3s ease;
    }

    .layui-form-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .layui-form-label {
        font-weight: 500;
        color: #374151;
    }

    .layui-input, .layui-textarea {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .layui-input:focus, .layui-textarea:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    /* 商品封面图预览 */
    #ID-upload-demo-img {
        max-width: 200px;
        max-height: 200px;
        object-fit: contain;
    }

    .page-goods-release .post-type{
        display: <?= $goods['type'] == 'post' ? 'block' : 'none' ?>
    }

    .upload-field {
        display: flex;
        align-items: center;
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
        min-height: 38px;
        padding: 4px 0;
        transition: all 0.2s ease;
    }

    .upload-field:focus-within {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .upload-field .upload-input {
        border: 0;
        box-shadow: none;
        height: 38px;
        line-height: 38px;
        flex: 1;
        padding-left: 0px;
    }

    .upload-field .upload-input:focus {
        box-shadow: none;
    }

    /* 封面图预览在输入框内 */
    .upload-preview-thumb {
        width: 38px;
        height: 38px;
        margin: 0 8px 0 8px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        flex-shrink: 0;
        background: #f9fafb;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .upload-preview-thumb:hover:not(.placeholder) {
        background: #f3f4f6;
        border-color: #0f766e;
    }

    /* 占位图样式 */
    .upload-preview-thumb.placeholder {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        cursor: default;
        border-color: #e5e7eb;
    }

    .upload-preview-thumb.placeholder::before {
        content: '📷';
        font-size: 18px;
        opacity: 0.6;
    }

    .upload-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px 3px 8px;
    }

    .upload-actions .layui-btn {
        height: 32px;
        line-height: 32px;
        margin: 0;
        padding: 0 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .upload-actions .layui-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }

    /* 隐藏原来的预览区域 */
    .goods-cover-preview {
        display: none;
    }

    /* 附加选项和批量优惠样式优化 */
    .attach-row, .qty-discount-row {
        background: #f9fafb;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 10px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .attach-row:hover, .qty-discount-row:hover {
        background: #f3f4f6;
        border-color: #cbd5e1;
    }
</style>
<div class="goods-form-shell">
    <form class="layui-form" id="form" method="post" action="goods_save.php">
        <input type="hidden" name="goods_id" value="<?= $goods['id'] ?? '' ?>" />
        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>

        <div class="goods-form-container">
            <!-- 自定义选项卡 -->
            <div class="custom-tabs">
                <ul class="custom-tabs-nav">
                    <li class="custom-tab-item active" data-tab="tab1">基础信息</li>
                    <li class="custom-tab-item" data-tab="tab2">规格信息</li>
                    <li class="custom-tab-item" data-tab="tab3">商品详情</li>
                    <li class="custom-tab-item" data-tab="tab4">附加选项</li>
                    <li class="custom-tab-item" data-tab="tab5">营销设置</li>
                    <li class="custom-tab-item" data-tab="tab6">其他设置</li>
                </ul>
            </div>

            <div class="custom-tabs-content">
                <!-- 选项卡1: 基础信息 -->
                <div class="custom-tab-pane active" id="tab1">
                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            商品类型
                        </label>
                        <?php if($action == 'edit'): ?>
                            <input type="hidden" name="type" value="<?= $goods['type'] ?>" />
                        <?php endif; ?>
                        <div class="layui-input-block">
                            <?php
                            // 对接商品（group_id = -1）特殊处理
                            $isRemoteGoods = ($action == 'edit' && ($goods['group_id'] ?? 0) == -1);

                            if ($isRemoteGoods):
                                // 通过 Hook 获取对接来源信息
                                $remoteSourceInfo = '';
                                doMultiAction('get_remote_goods_source', ['goods' => $goods], $remoteSourceInfo);
                            ?>
                            <div style="line-height: 38px; color: #666;">
                                <span class="layui-badge layui-btn-blue" style="margin-right: 8px;">对接商品</span>
                                <?= $remoteSourceInfo ?: '来源未知' ?>
                            </div>
                            <?php else: ?>
                            <?php foreach($goods['goods_type_all'] as $val):
                                // 非对接商品时，始终隐藏对接类型（is_remote = true）
                                if (!$isRemoteGoods && !empty($val['is_remote'])) continue;
                            ?>
                            <input <?= $action == 'edit' ? 'disabled' : '' ?> lay-filter="goods-type-radio" type="radio" name="type" value="<?= $val['value'] ?>" <?= $goods['type'] == $val['value'] ? 'checked' : '' ?> title="<?= $val['name'] ?>">
                            <?php endforeach; ?>
                            <?php if(empty($goods['goods_type_all'])): ?>
                            <span class="form-tips">
                                当前站点未启用任何商品类型扩展，请先恢复对应模块后再配置更多类型
                            </span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">商品分类</label>
                        <div class="layui-input-block">
                            <select name="sort_id">
                                <option value="">选择分类</option>
                                <?php
                                    foreach ($sorts as $key => $value):
                                        if ($value['pid'] != 0) {
                                            continue;
                                        }
                                        $flg = $value['sid'] == $goods['sort_id'] ? 'selected' : '';
                                ?>
                                    <option value="<?= $value['sid'] ?>" <?= $flg ?>><?= $value['sortname'] ?></option>
                                    <?php
                                        $children = $value['children'];
                                        foreach ($children as $key):
                                            $value = $sorts[$key];
                                            $flg = $value['sid'] == $goods['sort_id'] ? 'selected' : '';
                                    ?>
                                        <option value="<?= $value['sid'] ?>" <?= $flg ?>>&nbsp; &nbsp; &nbsp; <?= $value['sortname'] ?></option>
                                    <?php
                                        endforeach;
                                        endforeach;
                                    ?>
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">商品名称</label>
                        <div class="layui-input-block">
                            <input type="text" name="title" class="layui-input" value="<?= $goods['title'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">封面图</label>
                        <div class="layui-input-block">
                            <div class="upload-field">
                                <div id="cover-preview-wrapper" class="upload-preview-thumb placeholder" title="点击预览封面图">
                                    <img id="ID-upload-demo-img" src="<?= $goods['cover'] ?? '' ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; display: <?= !empty($goods['cover']) ? 'block' : 'none' ?>;" />
                                </div>
                                <input type="text" value="<?= $goods['cover'] ?? '' ?>" placeholder="封面图" class="layui-input upload-input" name="cover" id="sortimg">
                                <div class="upload-actions">
                                    <button type="button" class="layui-btn layui-btn-blue media-history-btn" data-target="#sortimg">选择</button>
                                    <button type="button" id="ID-upload-demo-btn" class="layui-btn layui-btn-purple">上传图片</button>
                                    <button type="button" class="layui-btn layui-btn-red upload-clear" data-target="#sortimg">清空</button>
                                </div>
                            </div>
                            <div class="goods-cover-preview">
                                <div class="goods-cover-meta">
                                    <div class="layui-progress" lay-filter="upload-progress">
                                        <div class="layui-progress-bar" lay-percent="0%"></div>
                                    </div>
                                    <div id="ID-upload-demo-text" class="upload-tip"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- 选项卡2: 规格信息 -->
                <div class="custom-tab-pane" id="tab2">
                    <!-- SKU Component -->
                    <?php
                    $sku_goods_id = $goods['id'] ?? 0;
                    $sku_type_id = $goods['group_id'] ?? 0;
                    $sku_goods_type = $goods['type'] ?? '';
                    include View::getAdmView('components/sku/sku_widget');
                    ?>
                </div>
                <!-- 选项卡3: 商品详情 -->
                <div class="custom-tab-pane" id="tab3">
                    <div class="layui-form-item">
                        <label class="layui-form-label">简介内容</label>
                        <div class="layui-input-block">
                            <textarea class="layui-textarea" name="des"><?= $goods['des'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">商品详情</label>
                        <div class="layui-input-block">
                            <textarea class="basic-example" name="content"><?= $goods['content'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">订单详情页额外显示内容</label>
                        <div class="layui-input-block">
                            <textarea class="basic-example" name="pay_content"><?= $goods['pay_content'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>
                <!-- 选项卡4: 附加选项 -->
                <div class="custom-tab-pane" id="tab4">
                    <div class="layui-form-item" id="user_attach_box">
                        <label class="layui-form-label">设置附加选项</label>
                        <div class="layui-input-block">
                            <div id="attachRows" class="attach-rows"></div>
                        </div>
                    </div>
                </div>
                <!-- 选项卡5: 营销设置 -->
                <div class="custom-tab-pane" id="tab5">
                    <div class="layui-form-item">
                        <label class="layui-form-label">配置批量购买优惠</label>
                        <div id="qtyDiscountRows">
                            <?php if(empty($qtyDiscountConfig)): ?>
                                <div class="kv-item qty-discount-row">
                                    <div class="layui-input-inline">
                                        <input step="1" type="number" class="kv-input layui-input qty-discount-qty" placeholder="购买数量" />
                                    </div>
                                    <div class="layui-input-inline">
                                        <select class="layui-input qty-discount-type" lay-filter="qty-discount-type">
                                            <option value="per_item">每件优惠</option>
                                            <option value="order_off">订单优惠</option>
                                            <option value="order_discount">订单折扣</option>
                                        </select>
                                    </div>
                                    <div class="layui-input-inline qty-discount-value-wrap">
                                        <input step="0.01" type="number" class="kv-input layui-input qty-discount-value" placeholder="优惠值" />
                                        <span class="qty-discount-unit">元</span>
                                    </div>
                                    <div class="layui-input-inline">
                                        <select class="layui-input qty-discount-scope">
                                            <option value="all">全部生效</option>
                                            <option value="login">登录生效</option>
                                        </select>
                                    </div>
                                    <button type="button" class="layui-btn qty-discount-add" title="添加"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="layui-btn qty-discount-remove" title="删除"><i class="fa fa-trash-o"></i></button>
                                </div>
                            <?php else: ?>
                                <?php foreach($qtyDiscountConfig as $val): ?>
                                <?php
                                    $qtyType = $val['type'] ?? 'per_item';
                                    $qtyScope = $val['scope'] ?? 'all';
                                ?>
                                <div class="kv-item qty-discount-row">
                                    <div class="layui-input-inline">
                                        <input value="<?= $val['qty'] ?? '' ?>" step="1" type="number" class="kv-input layui-input qty-discount-qty" placeholder="购买数量" />
                                    </div>
                                    <div class="layui-input-inline">
                                        <select class="layui-input qty-discount-type" lay-filter="qty-discount-type">
                                            <option value="per_item" <?= $qtyType === 'per_item' ? 'selected' : '' ?>>每件优惠</option>
                                            <option value="order_off" <?= $qtyType === 'order_off' ? 'selected' : '' ?>>订单优惠</option>
                                            <option value="order_discount" <?= $qtyType === 'order_discount' ? 'selected' : '' ?>>订单折扣</option>
                                        </select>
                                    </div>
                                    <div class="layui-input-inline qty-discount-value-wrap">
                                        <input value="<?= $val['value'] ?? '' ?>" step="0.01" type="number" class="kv-input layui-input qty-discount-value" placeholder="优惠值" />
                                        <span class="qty-discount-unit">元</span>
                                    </div>
                                    <div class="layui-input-inline">
                                        <select class="layui-input qty-discount-scope">
                                            <option value="all" <?= $qtyScope === 'all' ? 'selected' : '' ?>>全部生效</option>
                                            <option value="login" <?= $qtyScope === 'login' ? 'selected' : '' ?>>登录生效</option>
                                        </select>
                                    </div>
                                    <button type="button" class="layui-btn qty-discount-add" title="添加"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="layui-btn qty-discount-remove" title="删除"><i class="fa fa-trash-o"></i></button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 选项卡6: 其他设置 -->
                <div class="custom-tab-pane" id="tab6">
                    <div class="layui-form-item post-type">
                        <label class="layui-form-label">跳转链接</label>
                        <div class="layui-input-block">
                            <input name="link" type="text" class="layui-input" placeholder="点击商品直接跳转链接，无商品功能" value="<?= $goods['link'] ?? '' ?>">
                        </div>

                    </div>
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <input type="checkbox" value="1" name="is_on_shelf" title="上架" <?= $goods['is_on_shelf'] == 1 ? 'checked' : '' ?>>
                        </div>
                        <div class="layui-input-block">
                            <input type="checkbox" value="1" name="index_top" title="首页置顶" <?= $goods['index_top'] == 1 ? 'checked' : '' ?>>
                        </div>
                        <div class="layui-input-block">
                            <input type="checkbox" value="1" name="sort_top" title="分类置顶" <?= $goods['sort_top'] == 1 ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">排序</label>
                        <div class="layui-input-block">
                            <input type="number" name="sort_num" class="layui-input" value="<?= $goods['sort_num'] ?? '' ?>">
                        </div>
                    </div>
                    <?php
                        $minQty = isset($goods['min_qty']) ? (int)$goods['min_qty'] : 1;
                        if ($minQty < 1) {
                            $minQty = 1;
                        }
                        $maxQty = isset($goods['max_qty']) ? (int)$goods['max_qty'] : 0;
                        $maxQtyValue = $maxQty > 0 ? $maxQty : '';
                    ?>
                    <div class="layui-form-item">
                        <label class="layui-form-label">最小购买数量</label>
                        <div class="layui-input-block">
                            <input type="number" min="1" name="min_qty" class="layui-input" value="<?= $minQty ?>">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">最大购买数量</label>
                        <div class="layui-input-block">
                            <input type="number" min="0" name="max_qty" class="layui-input" placeholder="不限制" value="<?= $maxQtyValue ?>">
                        </div>
                        <div class="form-tips">留空或 0 表示不限制</div>
                    </div>
                    <!-- 设置可用支付类型 -->
                    <div class="layui-form-item">
                        <label class="layui-form-label">可用支付类型</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="payment[]" value="all" title="不限制" <?= empty($goods['payment']) || in_array('all', $goods['payment']) ? 'checked' : '' ?>>
                            <?php foreach($payment as $val): ?>
                                <input type="checkbox" <?= in_array($val['unique'], $goods['payment']) ? 'checked' : '' ?> name="payment[]" value="<?= $val['unique'] ?>" title="<?= $val['title'] ?>">
                            <?php endforeach; ?>
                        </div>
                        <!-- <span class="form-tips"></span> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 底部按钮 -->
        <div class="goods-form-footer">
            <button type="submit" class="layui-btn layui-btn-green layui-btn-disabled" lay-submit lay-filter="submit" id="goods-submit-btn" data-ready-text="保存" data-loading-text="数据加载中..." disabled>数据加载中...</button>
            <button type="button" class="layui-btn" id="btn-cancel">取消</button>
        </div>
        <?= doAction('goods_eidt_form_foot') ?>
    </form>
</div>




<script src="./tinymce/tinymce.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

<script src="./views/js/views/goods.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>


<script>
    // 自定义选项卡切换
    $(document).ready(function(){
        $('.custom-tab-item').on('click', function(){
            var tabId = $(this).data('tab');

            // 切换激活状态
            $('.custom-tab-item').removeClass('active');
            $(this).addClass('active');

            // 切换内容
            $('.custom-tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
        });

        // 取消按钮关闭弹窗
        $('#btn-cancel').on('click', function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        });
    });

    layui.use(['form', 'laydate', 'util'], function(){
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;
        var loadIndex = null;
        var submitBtn = $('#goods-submit-btn');
        var submitReadyText = submitBtn.data('ready-text') || '保存';
        var submitLoadingText = submitBtn.data('loading-text') || '数据加载中...';
        var skuStatus = {
            busy: true,
            ready: false,
            message: '规格信息加载中，请稍候...'
        };

        function syncSubmitButton(status) {
            if (status && typeof status === 'object') {
                skuStatus = $.extend({}, skuStatus, status);
            }
            var disabled = !!skuStatus.busy;
            submitBtn.prop('disabled', disabled);
            submitBtn.toggleClass('layui-btn-disabled', disabled);
            submitBtn.text(disabled ? submitLoadingText : submitReadyText);
        }

        $(document).on('emSku:status.goodsRelease', function (event, status) {
            syncSubmitButton(status);
        });

        if (typeof EmSku !== 'undefined' && typeof EmSku.getStatus === 'function') {
            syncSubmitButton(EmSku.getStatus());
        } else {
            syncSubmitButton();
        }

        form.on('submit(submit)', function(){
            if (typeof EmSku !== 'undefined' && typeof EmSku.getStatus === 'function') {
                syncSubmitButton(EmSku.getStatus());
            }
            if (skuStatus.busy) {
                layer.msg(skuStatus.message || '规格信息加载中，请稍候再提交');
                return false;
            }
            if (typeof syncAttachNames === 'function') {
                syncAttachNames();
            }
            if (typeof syncQtyDiscountNames === 'function') {
                syncQtyDiscountNames();
            }
            var field = $('#form').serialize(); // 使用表单序列化，支持数组字段
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 400){
                        layer.msg(e.msg)
                    }else{
                        // 在父页面显示提示
                        parent.layer.msg(e.msg || '保存成功');
                        // 立即关闭弹窗
                        var index = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(index);
                        // 刷新父页面表格
                        parent.window.table.reload('index');
                    }

                },
                error: function (xhr) {
                    var msg = '请求失败，请稍后重试';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp && resp.msg) msg = resp.msg;
                    } catch (e) {
                        // ignore json parse error
                    }
                    layer.msg(msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });


        var uploadInst = upload.render({
            elem: '#ID-upload-demo-btn',
            field: 'image',
            url: './article.php?action=upload_cover', // 实际使用时改成您自己的上传接口即可。
            before: function(obj){
                // 预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result){
                    $('#ID-upload-demo-img').attr('src', result); // 图片链接（base64）
                });

                element.progress('upload-progress', '0%'); // 进度条复位
                loadIndex = layer.load(2);
            },
            done: function(res){
                // 若上传失败
                if(res.code == 400){
                    return layer.msg(res.msg);
                }
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                // 上传成功的一些操作
                if(res.code == 0){
                    $('#sortimg').val(res.data).trigger('input');
                }
                $('#ID-upload-demo-text').html(''); // 置空上传失败的状态
            },
            error: function(){
                // 演示失败状态，并实现重传
                var demoText = $('#ID-upload-demo-text');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    uploadInst.upload();
                });
                if (loadIndex !== null) {
                    layer.close(loadIndex);
                    loadIndex = null;
                }
            },
            // 进度条
            progress: function(n, elem, e){
                element.progress('upload-progress', n + '%'); // 可配合 layui 进度条元素使用
                if(n == 100){
                    layer.close(loadIndex)
                    loadIndex = null;
                }
            }
        });

        function openHistoryModal(target) {
            var targetId = (target || '').replace('#', '');
            var isMobile = window.innerWidth < 1200;
            var area = isMobile ? ['98%', '85%'] : ['1000px', '800px'];
            layer.open({
                id: 'media_history_select',
                title: '选择历史图片',
                type: 2,
                area: area,
                skin: 'em-modal',
                content: 'media.php?action=history&target=' + encodeURIComponent(targetId),
                fixed: false,
                scrollbar: false,
                maxmin: true,
                shadeClose: true
            });
        }

        $('.media-history-btn').on('click', function () {
            openHistoryModal($(this).data('target'));
        });

        $('.upload-clear').on('click', function () {
            var target = $(this).data('target');
            $(target).val('').trigger('input');
        });

        // 更新封面图预览
        function updateCoverPreview(url) {
            var $img = $('#ID-upload-demo-img');
            var $wrapper = $('#cover-preview-wrapper');

            if (url && url.trim()) {
                $img.attr('src', url).show();
                $wrapper.removeClass('placeholder').css('cursor', 'pointer');
            } else {
                $img.attr('src', '').hide();
                $wrapper.addClass('placeholder').css('cursor', 'default');
            }
        }

        $('#sortimg').on('input', function () {
            updateCoverPreview($(this).val());
        });

        // 点击封面图预览
        $('#cover-preview-wrapper').on('click', function () {
            var url = $('#sortimg').val();
            if (url && url.trim()) {
                layer.photos({
                    photos: {
                        "title": "商品封面预览",
                        "start": 0,
                        "data": [{
                            "alt": "商品封面",
                            "pid": 1,
                            "src": url
                        }]
                    },
                    anim: 5
                });
            }
        });

        // 页面加载时初始化
        $(document).ready(function(){
            updateCoverPreview($('#sortimg').val());
        });


    })
</script>


<script>
    $("#menu-goods").attr('class', 'admin-menu-item has-list in');
    $("#menu-goods .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-goods > .submenu").css('display', 'block');
    $('#menu-goods-list > a').attr('class', 'menu-link active')
</script>

<!--附加选项-->
<script>
    let attachItems = <?= json_encode(!empty($attachConfigInput) ? $attachConfigInput : $attachUser, JSON_UNESCAPED_UNICODE) ?>;
    const attachRows = $('#attachRows');

    function normalizeAttachItems(raw) {
        if (!raw || typeof raw !== 'object') return [];
        if (Array.isArray(raw)) {
            return raw.map((item) => {
                if (!item || typeof item !== 'object') return null;
                return {
                    name: (item.name || '').toString(),
                    name_en: (item.name_en || '').toString(),
                    type: (item.type || 'string').toString(),
                    placeholder: (item.placeholder || '').toString(),
                    required: (item.required === 0 || item.required === '0') ? 0 : 1
                };
            }).filter(Boolean);
        }
        return Object.keys(raw).map((key) => ({
            name: key,
            name_en: '',
            type: 'string',
            placeholder: (raw[key] || '').toString(),
            required: 1
        }));
    }

    function addRowAfter($afterRow, item) {
        const data = item || {name: '', name_en: '', type: 'string', placeholder: '', required: 1};
        const row = $(`
            <div class="attach-row">
                <div class="layui-input-inline attach-col attach-type">
                    <select class="attach-type-select">
                        <option value="string">字符串</option>
                        <option value="phone">手机号码</option>
                        <option value="email">邮箱</option>
                        <option value="number">纯数字</option>
                    </select>
                </div>
                <div class="layui-input-inline attach-col">
                    <input type="text" class="layui-input attach-name" placeholder="请输入名称">
                </div>
                <div class="layui-input-inline attach-col">
                    <input type="text" class="layui-input attach-name-en" placeholder="请输入英文名称">
                </div>
                <div class="layui-input-inline attach-col attach-placeholder">
                    <input type="text" class="layui-input attach-placeholder-input" placeholder="请输入提示内容">
                </div>
                <div class="layui-input-inline attach-col attach-required">
                    <select class="attach-required-select">
                        <option value="1">必填</option>
                        <option value="0">非必填</option>
                    </select>
                </div>
                <div class="attach-actions">
                    <button type="button" class="layui-btn layui-btn-blue attach-add" title="在下方插入"><i class="fa fa-plus"></i></button>
                    <button type="button" class="layui-btn layui-btn attach-remove" title="删除"><i class="fa fa-trash-o"></i></button>
                </div>
            </div>
        `);

        row.find('.attach-name').val(data.name || '');
        row.find('.attach-name-en').val(data.name_en || '');
        row.find('.attach-type-select').val(data.type || 'string');
        row.find('.attach-required-select').val((data.required === 0 || data.required === '0') ? '0' : '1');
        row.find('.attach-placeholder-input').val(data.placeholder || '');

        if ($afterRow && $afterRow.length) {
            $afterRow.after(row);
        } else {
            attachRows.append(row);
        }

        row.on('input change', 'input, select', syncAttachNames);
        row.find('.attach-add').on('click', function () {
            addRowAfter(row, {name: '', name_en: '', type: 'string', placeholder: '', required: 1});
            syncAttachNames();
            if (window.layui && layui.form) {
                layui.form.render('select');
            }
            row.next('.attach-row').find('.attach-name').focus();
        });
        row.find('.attach-remove').on('click', function () {
            if (attachRows.children('.attach-row').length <= 1) {
                row.find('input').val('');
                row.find('.attach-type-select').val('string');
                row.find('.attach-required-select').val('1');
                if (window.layui && layui.form) {
                    layui.form.render('select');
                }
                syncAttachNames();
                return;
            }
            row.remove();
            syncAttachNames();
        });

        if (window.layui && layui.form) {
            layui.form.render('select');
        }
    }

    function syncAttachNames() {
        let index = 0;
        attachRows.children('.attach-row').each(function () {
            const $row = $(this);
            const name = $row.find('.attach-name').val().trim();
            const required = $row.find('.attach-required-select').val() || '1';
            if (!name) {
                $row.find('.attach-name').removeAttr('name');
                $row.find('.attach-name-en').removeAttr('name');
                $row.find('.attach-type-select').removeAttr('name');
                $row.find('.attach-required-select').removeAttr('name');
                $row.find('.attach-placeholder-input').removeAttr('name');
                return;
            }
            $row.find('.attach-name').attr('name', `config[input][${index}][name]`);
            $row.find('.attach-name-en').attr('name', `config[input][${index}][name_en]`);
            $row.find('.attach-type-select').attr('name', `config[input][${index}][type]`);
            $row.find('.attach-required-select').attr('name', `config[input][${index}][required]`).val(required);
            $row.find('.attach-placeholder-input').attr('name', `config[input][${index}][placeholder]`);
            index += 1;
        });
    }

    const normalizedItems = normalizeAttachItems(attachItems);
    if (normalizedItems.length) {
        normalizedItems.forEach((item) => addRowAfter(null, item));
    } else {
        addRowAfter(null, {name: '', name_en: '', type: 'string', placeholder: '', required: 1});
    }
    syncAttachNames();
</script>

<script>
    const qtyDiscountRows = $('#qtyDiscountRows');

    function updateQtyDiscountUnit($row) {
        const type = $row.find('.qty-discount-type').val();
        const isDiscount = type === 'order_discount';
        const unitText = isDiscount ? '折' : '元';
        const placeholderText = isDiscount ? '折扣值' : '优惠值';
        $row.find('.qty-discount-unit').text(unitText);
        $row.find('.qty-discount-value').attr('placeholder', placeholderText);
    }

    function syncQtyDiscountNames() {
        let index = 0;
        qtyDiscountRows.children('.qty-discount-row').each(function () {
            const $row = $(this);
            const qty = $row.find('.qty-discount-qty').val().trim();
            if (!qty) {
                $row.find('.qty-discount-qty').removeAttr('name');
                $row.find('.qty-discount-type').removeAttr('name');
                $row.find('.qty-discount-value').removeAttr('name');
                $row.find('.qty-discount-scope').removeAttr('name');
                return;
            }
            $row.find('.qty-discount-qty').attr('name', `config[qty_discount][${index}][qty]`);
            $row.find('.qty-discount-type').attr('name', `config[qty_discount][${index}][type]`);
            $row.find('.qty-discount-value').attr('name', `config[qty_discount][${index}][value]`);
            $row.find('.qty-discount-scope').attr('name', `config[qty_discount][${index}][scope]`);
            index += 1;
        });
    }

    function addQtyDiscountRow(afterRow) {
        const row = $(`
            <div class="kv-item qty-discount-row">
                <div class="layui-input-inline">
                    <input step="1" type="number" class="kv-input layui-input qty-discount-qty" placeholder="购买数量" />
                </div>
                <div class="layui-input-inline">
                    <select class="layui-input qty-discount-type" lay-filter="qty-discount-type">
                        <option value="per_item">每件优惠</option>
                        <option value="order_off">订单优惠</option>
                        <option value="order_discount">订单折扣</option>
                    </select>
                </div>
                <div class="layui-input-inline qty-discount-value-wrap">
                    <input step="0.01" type="number" class="kv-input layui-input qty-discount-value" placeholder="优惠值" />
                    <span class="qty-discount-unit">元</span>
                </div>
                <div class="layui-input-inline">
                    <select class="layui-input qty-discount-scope">
                        <option value="all">全部生效</option>
                        <option value="login">登录生效</option>
                    </select>
                </div>
                <button type="button" class="layui-btn qty-discount-add" title="添加"><i class="fa fa-plus"></i></button>
                <button type="button" class="layui-btn qty-discount-remove" title="删除"><i class="fa fa-trash-o"></i></button>
            </div>
        `);

        if (afterRow && afterRow.length) {
            afterRow.after(row);
        } else {
            qtyDiscountRows.append(row);
        }

        if (window.layui && layui.form) {
            layui.form.render('select');
        }
        updateQtyDiscountUnit(row);
        syncQtyDiscountNames();
        return row;
    }

    qtyDiscountRows.on('input change', 'input, select', syncQtyDiscountNames);

    qtyDiscountRows.on('change', '.qty-discount-type', function () {
        updateQtyDiscountUnit($(this).closest('.qty-discount-row'));
    });
    if (window.layui && layui.form) {
        layui.form.on('select(qty-discount-type)', function (data) {
            updateQtyDiscountUnit($(data.elem).closest('.qty-discount-row'));
            syncQtyDiscountNames();
        });
    }

    qtyDiscountRows.on('click', '.qty-discount-add', function () {
        const row = $(this).closest('.qty-discount-row');
        const newRow = addQtyDiscountRow(row);
        newRow.find('.qty-discount-qty').focus();
    });

    qtyDiscountRows.on('click', '.qty-discount-remove', function () {
        const row = $(this).closest('.qty-discount-row');
        if (qtyDiscountRows.children('.qty-discount-row').length <= 1) {
            row.find('input').val('');
            row.find('.qty-discount-type').val('per_item');
            row.find('.qty-discount-scope').val('all');
            if (window.layui && layui.form) {
                layui.form.render('select');
            }
            syncQtyDiscountNames();
            return;
        }
        row.remove();
        syncQtyDiscountNames();
    });

    qtyDiscountRows.children('.qty-discount-row').each(function () {
        updateQtyDiscountUnit($(this));
    });
    syncQtyDiscountNames();
</script>
