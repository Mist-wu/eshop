<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .profile-page {
        display: grid;
        gap: 20px;
    }

    .profile-hero {
        position: relative;
        padding: 26px 28px;
        border-radius: var(--radius-lg);
        color: #ffffff;
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 48%, #f59e0b 140%);
        overflow: hidden;
    }

    .profile-hero::before,
    .profile-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.16);
    }

    .profile-hero::before {
        width: 320px;
        height: 320px;
        left: -120px;
        bottom: -180px;
    }

    .profile-hero::after {
        width: 240px;
        height: 240px;
        right: -80px;
        top: -140px;
    }

    .profile-hero > * {
        position: relative;
        z-index: 1;
    }

    .hero-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .hero-user {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .hero-avatar {
        width: 68px;
        height: 68px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 700;
        text-transform: uppercase;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
    }

    .hero-greeting {
        font-size: 13px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        opacity: 0.85;
    }

    .hero-name {
        margin: 6px 0 4px;
        font-size: 28px;
        font-weight: 700;
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    }

    .hero-meta {
        font-size: 13px;
        opacity: 0.85;
    }

    .status-pill {
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.2);
        letter-spacing: 0.04em;
    }

    .status-ok {
        background: rgba(16, 185, 129, 0.3);
        border-color: rgba(16, 185, 129, 0.5);
    }

    .status-warn {
        background: rgba(245, 158, 11, 0.3);
        border-color: rgba(245, 158, 11, 0.5);
    }

    .hero-actions {
        margin-top: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        background: #ffffff;
        color: var(--primary-strong);
        font-size: 13px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.6);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hero-btn.ghost {
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.35);
    }

    .hero-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .stat-tile {
        background: var(--panel);
        border: 1px solid var(--border-soft);
        border-radius: 18px;
        padding: 18px 20px;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        display: grid;
        gap: 6px;
    }

    .stat-label {
        font-size: 12px;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--text);
    }

    .stat-meta {
        font-size: 12px;
        color: var(--muted);
    }

    .info-card {
        background: var(--panel);
        border-radius: 20px;
        border: 1px solid var(--border-soft);
        padding: 20px 22px;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        display: grid;
        gap: 16px;
    }

    .card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .card-head h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
    }

    .card-head p {
        margin: 6px 0 0;
        font-size: 13px;
        color: var(--muted);
    }

    .card-badge {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(245, 158, 11, 0.16);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.4);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
    }

    .info-item {
        background: var(--panel-soft);
        border-radius: 14px;
        padding: 14px 16px;
        border: 1px solid rgba(15, 118, 110, 0.08);
        display: grid;
        gap: 6px;
    }

    .info-label {
        font-size: 12px;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        word-break: break-all;
    }

    .info-value.empty {
        color: #9ca3af;
        font-weight: 500;
    }

    .info-value.mono {
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    }

    .notice-card {
        background: var(--panel);
        border-radius: 18px;
        border: 1px dashed rgba(15, 118, 110, 0.3);
        padding: 18px 20px;
        color: var(--muted);
    }

    .notice-card h3 {
        margin: 0 0 12px;
        font-size: 16px;
        color: var(--text);
    }

    .notice-list {
        display: grid;
        gap: 8px;
        font-size: 13px;
        padding-left: 18px;
    }

    .notice-list li {
        list-style: disc;
    }

    @media (max-width: 768px) {
        .profile-hero {
            padding: 20px 20px;
        }

        .hero-name {
            font-size: 22px;
        }

        .hero-avatar {
            width: 58px;
            height: 58px;
            font-size: 22px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="main-content">
    <div class="profile-page">
        <section class="profile-hero">
            <div class="hero-top">
                <div class="hero-user">
                    <div class="hero-avatar">
                        <?= substr($userData['username'], 0, 1) ?>
                    </div>
                    <div>
                        <div class="hero-greeting">欢迎回来</div>
                        <div class="hero-name"><?= $userData['username'] ?></div>
                        <div class="hero-meta">商户ID：<?= UID ?></div>
                    </div>
                </div>
                <div class="status-pill <?= empty($userData['tel']) && empty($userData['email']) ? 'status-warn' : 'status-ok' ?>">
                    <?= empty($userData['tel']) && empty($userData['email']) ? '资料待完善' : '账户已激活' ?>
                </div>
            </div>

            <div class="hero-actions">
                <a href="/user/order.php" class="hero-btn"><i class="fa fa-list"></i>订单列表</a>
                <a href="/user/visitors.php" class="hero-btn ghost"><i class="fa fa-search"></i>游客查单</a>
                <a href="/user/profile.php" class="hero-btn ghost"><i class="fa fa-user"></i>个人资料</a>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-tile">
                <div class="stat-label">支付模式</div>
                <div class="stat-value">直付下单</div>
                <div class="stat-meta">下单后直接跳转支付平台完成付款</div>
            </div>
            <div class="stat-tile">
                <div class="stat-label">累计消费</div>
                <div class="stat-value">¥<?= number_format($userData['expend'], 2) ?></div>
                <div class="stat-meta">实时同步您的消费记录</div>
            </div>
            <div class="stat-tile">
                <div class="stat-label">安全状态</div>
                <div class="stat-value"><?= empty($userData['tel']) && empty($userData['email']) ? '待完善' : '已激活' ?></div>
                <div class="stat-meta">绑定手机/邮箱更安全</div>
            </div>
        </section>

        <section class="info-card">
            <div class="card-head">
                <div>
                    <h2>账户信息</h2>
                    <p>用于接口对接与安全验证，请妥善保管</p>
                </div>
                <div class="card-badge">重要</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">商户ID</div>
                    <div class="info-value mono"><?= UID ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">商户密钥</div>
                    <div class="info-value mono"><?= $token ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">登录手机</div>
                    <div class="info-value <?= empty($userData['tel']) ? 'empty' : '' ?>">
                        <?= empty($userData['tel']) ? '未绑定手机' : $userData['tel'] ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">登录邮箱</div>
                    <div class="info-value <?= empty($userData['email']) ? 'empty' : '' ?>">
                        <?= empty($userData['email']) ? '未绑定邮箱' : $userData['email'] ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="notice-card">
            <h3>安全提示</h3>
            <ul class="notice-list">
                <li>请勿向他人泄露商户密钥或登录信息。</li>
                <li>建议尽快绑定手机号或邮箱，以便找回账号。</li>
                <li>发现异常订单请及时联系客服处理。</li>
            </ul>
        </section>
    </div>
</main>

<script>
    $('#menu-index').addClass('open menu-current');
</script>
