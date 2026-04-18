<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php
$promoterUser = $promoterUser ?? [];
$promoterStats = $promoterStats ?? ['coupon_total' => 0, 'used_total' => 0, 'active_total' => 0];
$promoterCoupons = $promoterCoupons ?? [];

$uid = (int)($promoterUser['uid'] ?? 0);
$nickname = htmlspecialchars((string)($promoterUser['nickname'] ?? ''), ENT_QUOTES);
$email = htmlspecialchars((string)($promoterUser['email'] ?? ''), ENT_QUOTES);
$tel = htmlspecialchars((string)($promoterUser['tel'] ?? ''), ENT_QUOTES);
$regIp = htmlspecialchars((string)($promoterUser['reg_ip'] ?? ''), ENT_QUOTES);
$roleText = User::getRoleName((string)($promoterUser['role'] ?? ''), $uid);
$createTimeText = !empty($promoterUser['create_time']) ? date('Y-m-d H:i:s', (int)$promoterUser['create_time']) : '-';
$updateTimeText = !empty($promoterUser['update_time']) ? date('Y-m-d H:i:s', (int)$promoterUser['update_time']) : '-';
?>

<style>
    .promoter-detail {
        padding: 16px;
        background: #f8fafc;
        min-height: 100vh;
    }

    .promoter-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 14px;
    }

    .promoter-head {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .promoter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }

    .promoter-item {
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 10px 12px;
    }

    .promoter-item .label {
        color: #64748b;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .promoter-item .value {
        color: #0f172a;
        font-size: 14px;
        font-weight: 600;
        word-break: break-all;
    }

    .promoter-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
        margin-top: 12px;
    }

    .promoter-stat {
        background: #ecfeff;
        border: 1px solid #a5f3fc;
        border-radius: 10px;
        padding: 12px;
    }

    .promoter-stat .label {
        font-size: 12px;
        color: #0f766e;
        margin-bottom: 4px;
    }

    .promoter-stat .value {
        font-size: 22px;
        color: #0f172a;
        font-weight: 700;
    }

    .promoter-actions {
        margin-top: 12px;
    }

    .promoter-table {
        width: 100%;
        border-collapse: collapse;
    }

    .promoter-table th,
    .promoter-table td {
        border: 1px solid #e5e7eb;
        padding: 8px 10px;
        font-size: 13px;
        text-align: center;
    }

    .promoter-table th {
        background: #f1f5f9;
        font-weight: 600;
        color: #334155;
    }

    .promoter-table td.code {
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
    }
</style>

<div class="promoter-detail">
    <div class="promoter-card">
        <div class="promoter-head">推广者账号信息</div>
        <div class="promoter-grid">
            <div class="promoter-item">
                <div class="label">用户ID</div>
                <div class="value"><?= $uid ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">昵称</div>
                <div class="value"><?= $nickname !== '' ? $nickname : '-' ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">角色</div>
                <div class="value"><?= htmlspecialchars($roleText, ENT_QUOTES) ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">邮箱</div>
                <div class="value"><?= $email !== '' ? $email : '-' ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">手机</div>
                <div class="value"><?= $tel !== '' ? $tel : '-' ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">注册IP</div>
                <div class="value"><?= $regIp !== '' ? $regIp : '-' ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">注册时间</div>
                <div class="value"><?= htmlspecialchars($createTimeText, ENT_QUOTES) ?></div>
            </div>
            <div class="promoter-item">
                <div class="label">最近更新</div>
                <div class="value"><?= htmlspecialchars($updateTimeText, ENT_QUOTES) ?></div>
            </div>
        </div>

        <div class="promoter-stats">
            <div class="promoter-stat">
                <div class="label">邀请码总数</div>
                <div class="value"><?= (int)($promoterStats['coupon_total'] ?? 0) ?></div>
            </div>
            <div class="promoter-stat">
                <div class="label">已支付使用次数</div>
                <div class="value"><?= (int)($promoterStats['used_total'] ?? 0) ?></div>
            </div>
            <div class="promoter-stat">
                <div class="label">可用邀请码</div>
                <div class="value"><?= (int)($promoterStats['active_total'] ?? 0) ?></div>
            </div>
        </div>
        <div class="promoter-actions">
            <button type="button" class="layui-btn layui-btn-normal" id="view-promoter-orders">一键筛选该推广者订单</button>
        </div>
    </div>

    <div class="promoter-card">
        <div class="promoter-head">邀请码明细</div>
        <div class="layui-word-aux" style="margin-bottom: 10px;">管理员可在“优惠券管理”中编辑某张邀请码的优惠额度。</div>
        <div class="table-box">
            <table class="promoter-table">
                <thead>
                <tr>
                    <th>邀请码</th>
                    <th>优惠额度</th>
                    <th>已使用</th>
                    <th>可用次数</th>
                    <th>状态</th>
                    <th>创建时间</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($promoterCoupons)): ?>
                    <tr>
                        <td colspan="6">暂无邀请码数据</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($promoterCoupons as $row): ?>
                        <tr>
                            <td class="code"><?= htmlspecialchars((string)($row['code'] ?? ''), ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)($row['discount_text'] ?? ''), ENT_QUOTES) ?></td>
                            <td><?= (int)($row['used_times'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($row['use_limit_text'] ?? ''), ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)($row['state_text'] ?? ''), ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)($row['create_time_text'] ?? ''), ENT_QUOTES) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        var uid = <?= $uid ?>;
        var btn = document.getElementById('view-promoter-orders');
        if (!btn || uid <= 0) {
            return;
        }

        btn.addEventListener('click', function () {
            var targetUrl = 'order.php?promoter_uid=' + uid;
            if (window.parent && window.parent !== window && window.parent.location) {
                window.parent.location.href = targetUrl;
            } else {
                window.location.href = targetUrl;
            }
        });
    })();
</script>
