<?php
/**
 * 页面底部信息
 */
defined('EM_ROOT') || exit('access denied!');
?>

<footer class="main-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand"><?= $blogname ?></div>
            <div class="footer-meta">
                <span><?= $footer_info ?></span>
                <?php if (!empty($icp)): ?>
                    <a href="https://beian.miit.gov.cn/" target="_blank"><?= $icp ?></a>
                <?php endif; ?>
                <?php doAction('index_footer') ?>
            </div>
        </div>
    </div>
</footer>

<!-- 底部导航 -->
<nav class="footer-nav tel-footer" role="navigation" aria-label="移动端主导航">
    <a href="<?= EM_URL ?>" class="nav-item" data-nav="home" aria-label="首页">
        <i class="fa fa-home nav-icon" aria-hidden="true"></i>
        <span class="nav-text">首页</span>
    </a>
    <a href="<?= EM_URL ?>user/visitors.php" class="nav-item" data-nav="order" aria-label="订单">
        <i class="fa fa-list-alt nav-icon" aria-hidden="true"></i>
        <span class="nav-text">订单</span>
    </a>
    <?php if(Option::get('login_switch') == 'y' && Option::get('register_switch') == 'y'): ?>
    <a href="<?= EM_URL ?>user" class="nav-item" data-nav="user" aria-label="我的">
        <i class="fa fa-user nav-icon" aria-hidden="true"></i>
        <span class="nav-text">我的</span>
    </a>
    <?php endif; ?>
</nav>

<script>
// 移动端导航激活状态
(function() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.footer-nav .nav-item');

    navItems.forEach(item => {
        const href = item.getAttribute('href');
        const navType = item.getAttribute('data-nav');

        // 移除所有激活状态
        item.classList.remove('active');

        // 判断当前页面并添加激活状态
        if (navType === 'home' && (currentPath === '/' || currentPath.endsWith('index.php'))) {
            item.classList.add('active');
        } else if (navType === 'order' && currentPath.includes('visitors.php')) {
            item.classList.add('active');
        } else if (navType === 'user' && currentPath.includes('/user') && !currentPath.includes('visitors.php')) {
            item.classList.add('active');
        }
    });
})();
</script>




<script>
    let tipsMsg = {
        least_one    : '',
        exceeds      : '',
        exceeds_limit: '',
        mobile_order : ''
    };
</script>
<?php doAction('page_footer') ?>
</body>
</html>
