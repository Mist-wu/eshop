<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .promotion-page {
        display: grid;
        gap: 18px;
    }

    .promotion-hero {
        background: linear-gradient(135deg, #0f766e 0%, #0ea5a2 48%, #22c55e 140%);
        color: #fff;
        border-radius: 20px;
        padding: 20px 22px;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.16);
    }

    .promotion-hero h2 {
        margin: 0 0 8px;
        font-size: 24px;
        font-weight: 700;
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    }

    .promotion-hero p {
        margin: 0;
        opacity: 0.9;
        font-size: 13px;
    }

    .promotion-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .promotion-stat {
        background: var(--panel);
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        padding: 14px 16px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .promotion-stat .label {
        font-size: 12px;
        color: var(--muted);
        margin-bottom: 6px;
    }

    .promotion-stat .value {
        font-size: 22px;
        font-weight: 700;
        color: var(--text);
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    }

    .promotion-table {
        background: var(--panel);
        border-radius: 18px;
        border: 1px solid var(--border-soft);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        padding: 14px;
    }

    .promotion-note {
        font-size: 12px;
        color: var(--muted);
        margin: 8px 4px 2px;
    }

    .coupon-code-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .coupon-code {
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
        font-weight: 700;
        letter-spacing: 0.04em;
    }
</style>

<main class="main-content">
    <div class="promotion-page">
        <section class="promotion-hero">
            <h2>推广中心</h2>
            <p>您的用户ID：<?= UID ?>。每次创建邀请码默认优惠 <?= htmlspecialchars($defaultDiscount, ENT_QUOTES) ?> 元，管理员可在后台单独调整。</p>
        </section>

        <section class="promotion-stats">
            <div class="promotion-stat">
                <div class="label">邀请码总数</div>
                <div class="value"><?= (int)($promoterStats['coupon_total'] ?? 0) ?></div>
            </div>
            <div class="promotion-stat">
                <div class="label">已支付使用次数</div>
                <div class="value"><?= (int)($promoterStats['used_total'] ?? 0) ?></div>
            </div>
            <div class="promotion-stat">
                <div class="label">可用邀请码</div>
                <div class="value"><?= (int)($promoterStats['active_total'] ?? 0) ?></div>
            </div>
        </section>

        <section class="promotion-table">
            <table class="layui-hide" id="promotion-table" lay-filter="promotion-table"></table>
            <script type="text/html" id="promotion-toolbar">
                <div class="layui-btn-container">
                    <button class="layui-btn layui-btn-primary" lay-event="refresh"><i class="fa fa-refresh mr-3"></i>刷新</button>
                    <button class="layui-btn layui-btn-green" lay-event="create"><i class="fa fa-plus"></i>创建邀请码</button>
                </div>
            </script>
            <script type="text/html" id="promotion-code">
                <div class="coupon-code-wrap">
                    <span class="coupon-code">{{ d.code }}</span>
                    <button class="layui-btn layui-btn-xs layui-btn-primary copy-coupon" data-clipboard-text="{{ d.code }}">复制</button>
                </div>
            </script>
            <div class="promotion-note">统计口径：仅统计已支付订单中的邀请码使用次数。</div>
        </section>
    </div>
</main>

<script>
    $('#menu-promotion').addClass('open menu-current');

    layui.use(['table', 'layer'], function () {
        var table = layui.table;
        var layer = layui.layer;
        var $ = layui.$;
        var token = '<?= LoginAuth::genToken() ?>';
        var defaultDiscount = '<?= htmlspecialchars($defaultDiscount, ENT_QUOTES) ?>';

        table.render({
            elem: '#promotion-table',
            id: 'promotion-table',
            url: '/user/promotion.php?action=index',
            toolbar: '#promotion-toolbar',
            limits: [10, 20, 30, 50],
            page: true,
            limit: 10,
            cols: [[
                {field:'code', title:'邀请码', minWidth: 180, templet: '#promotion-code'},
                {field:'discount_text', title:'优惠额度', width: 120, align: 'center'},
                {field:'used_times', title:'已使用', width: 90, align: 'center'},
                {field:'use_limit_text', title:'可用次数', width: 100, align: 'center'},
                {field:'state_text', title:'状态', width: 90, align: 'center'},
                {field:'create_time_text', title:'创建时间', minWidth: 160, align: 'center'}
            ]]
        });

        var clipboard = new ClipboardJS('.copy-coupon');
        clipboard.on('success', function() {
            layer.msg('邀请码已复制');
        });
        clipboard.on('error', function() {
            layer.msg('复制失败，请手动复制');
        });

        table.on('toolbar(promotion-table)', function(obj){
            if (obj.event === 'refresh') {
                table.reload('promotion-table');
                return;
            }
            if (obj.event === 'create') {
                layer.prompt({
                    title: '输入邀请码前缀（可选）',
                    formType: 0,
                    value: 'U<?= UID ?>',
                    maxlength: 8
                }, function(value, index){
                    layer.close(index);
                    $.ajax({
                        type: 'POST',
                        url: '/user/promotion.php?action=create',
                        dataType: 'json',
                        data: {
                            token: token,
                            prefix: $.trim(value)
                        },
                        success: function(resp) {
                            if (resp.code === 0) {
                                var code = (resp.data && resp.data.code) ? resp.data.code : '';
                                layer.msg('邀请码创建成功，默认优惠 ' + defaultDiscount + ' 元');
                                if (code) {
                                    layer.alert('新邀请码：' + code, {title: '创建成功'});
                                }
                                table.reload('promotion-table', {page: {curr: 1}});
                            } else {
                                layer.msg(resp.msg || '创建失败');
                            }
                        },
                        error: function(xhr) {
                            var msg = '创建失败';
                            try {
                                var err = JSON.parse(xhr.responseText);
                                if (err && err.msg) msg = err.msg;
                            } catch (e) {}
                            layer.msg(msg);
                        }
                    });
                });
            }
        });
    });
</script>

