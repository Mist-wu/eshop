<?php
defined('EM_ROOT') || exit('access denied!');

$blogname = (string) Option::get('blogname');
$admin_brand_name = strcasecmp($blogname, 'eshop') === 0 ? strtoupper($blogname) : $blogname;
?>
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
<!--    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name=renderer content=webkit>
    <title>管理中心 - <?= Option::get('blogname') ?></title>
    <link rel="shortcut icon" href="<?= empty(Option::get('admin_icon')) ? EM_URL . 'favicon.ico' : Option::get('admin_icon'); ?>"/>

    <link rel="stylesheet" href="<?= EM_URL ?>admin/views/layui-v2.11.6//layui/css/layui.css">
    <script src="<?= EM_URL ?>admin/views/layui-v2.11.6/layui/layui.js"></script>


    <!-- jquery v3.5.1 -->
    <script src="<?= EM_URL ?>admin/views/js/jquery.min.3.5.1.js"></script>



    <!-- 字体 -->
    <link rel="stylesheet" type="text/css" href="<?= EM_URL ?>admin/views/font-awesome-4.7.0/css/font-awesome.min.css">



    <link rel="stylesheet" href="<?= EM_URL ?>admin/views/css/style.css?v=<?= time() ?>">


    <script src="<?= EM_URL ?>admin/views/js/common.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

    <script>
        $(function(){
            var Accordion = function(el, multiple) {
                this.el = el || {};
                this.multiple = multiple || false;

                // Variables privadas
                var links = this.el.find('.link');
                // Evento
                links.on('click', {el: this.el, multiple: this.multiple}, this.dropdown)
            }

            Accordion.prototype.dropdown = function(e) {
                var $el = e.data.el;
                $this = $(this),
                    $next = $this.next();

                $this.find('.admin-arrow').toggleClass('active')

                $next.slideToggle();
                $this.parent().toggleClass('open');

                if (!e.data.multiple) {
                    $el.find('.submenu').not($next).slideUp().parent().removeClass('open');
                    $el.find('.submenu').not($next).slideUp().parent().children().children().removeClass('active');
                };
            }

            var accordion = new Accordion($('#accordion'), false);

            // 菜单搜索功能
            var $searchInput = $('#menu-search-input');
            var $searchClear = $('#menu-search-clear');
            var $accordion = $('#accordion');

            // 收集所有菜单项数据
            var menuData = [];
            $accordion.children('.admin-menu-item').each(function() {
                var $item = $(this);
                var data = {
                    element: $item,
                    isParent: $item.hasClass('has-submenu'),
                    text: '',
                    children: []
                };

                if (data.isParent) {
                    // 获取父菜单文本（去除图标和箭头）
                    var $link = $item.find('.link').first();
                    data.text = $link.clone().children().remove().end().text().trim();

                    $item.find('.submenu .admin-menu-item').each(function() {
                        var $child = $(this);
                        var $childLink = $child.find('.menu-link');
                        data.children.push({
                            element: $child,
                            text: $childLink.text().trim()
                        });
                    });
                } else {
                    var $link = $item.find('.menu-link');
                    data.text = $link.text().trim();
                }
                menuData.push(data);
            });

            // 移除所有高亮
            function removeAllHighlights() {
                $('.menu-search-highlight').each(function() {
                    var $this = $(this);
                    $this.replaceWith($this.text());
                });
            }

            // 高亮元素中的文本
            function highlightInElement($element, keyword) {
                // 先移除该元素内的旧高亮
                $element.find('.menu-search-highlight').each(function() {
                    $(this).replaceWith($(this).text());
                });

                // 遍历文本节点并高亮
                $element.contents().each(function() {
                    if (this.nodeType === 3) { // 文本节点
                        var text = this.nodeValue;
                        var lowerText = text.toLowerCase();
                        var lowerKeyword = keyword.toLowerCase();

                        if (lowerText.indexOf(lowerKeyword) !== -1) {
                            var regex = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                            var highlightedHtml = text.replace(regex, '<span class="menu-search-highlight">$1</span>');
                            $(this).replaceWith(highlightedHtml);
                        }
                    }
                });
            }

            // 搜索输入事件
            $searchInput.on('input', function() {
                var keyword = $(this).val().trim();

                if (keyword === '') {
                    // 清空搜索，恢复所有菜单
                    $searchClear.hide();
                    removeAllHighlights();

                    // 显示所有菜单项
                    menuData.forEach(function(item) {
                        item.element.show();
                        if (item.isParent) {
                            // 收起子菜单
                            item.element.removeClass('open');
                            item.element.find('.submenu').slideUp();
                            item.element.find('.admin-arrow').removeClass('active');
                            // 显示所有子菜单项
                            item.children.forEach(function(child) {
                                child.element.show();
                            });
                        }
                    });
                } else {
                    $searchClear.show();
                    removeAllHighlights();

                    var lowerKeyword = keyword.toLowerCase();

                    // 遍历所有菜单项
                    menuData.forEach(function(item) {
                        var itemText = item.text.toLowerCase();
                        var itemMatched = itemText.indexOf(lowerKeyword) !== -1;

                        if (item.isParent) {
                            // 处理有子菜单的项
                            var hasMatchedChild = false;

                            // 检查子菜单
                            item.children.forEach(function(child) {
                                var childText = child.text.toLowerCase();
                                if (childText.indexOf(lowerKeyword) !== -1) {
                                    hasMatchedChild = true;
                                    child.element.show();
                                    // 高亮子菜单文本
                                    highlightInElement(child.element.find('.menu-link'), keyword);
                                } else {
                                    // 如果父菜单匹配，显示所有子菜单；否则隐藏
                                    if (itemMatched) {
                                        child.element.show();
                                    } else {
                                        child.element.hide();
                                    }
                                }
                            });

                            if (itemMatched || hasMatchedChild) {
                                item.element.show();
                                // 展开父菜单
                                item.element.addClass('open');
                                item.element.find('.submenu').slideDown();
                                item.element.find('.admin-arrow').addClass('active');

                                // 如果父菜单文本匹配，高亮显示
                                if (itemMatched) {
                                    highlightInElement(item.element.find('.link').first(), keyword);
                                }
                            } else {
                                item.element.hide();
                            }
                        } else {
                            // 处理没有子菜单的项
                            if (itemMatched) {
                                item.element.show();
                                // 高亮文本
                                highlightInElement(item.element.find('.menu-link'), keyword);
                            } else {
                                item.element.hide();
                            }
                        }
                    });
                }
            });

            // 清除搜索按钮
            $searchClear.on('click', function() {
                $searchInput.val('').trigger('input').focus();
            });

            // 支持 ESC 键清除搜索
            $searchInput.on('keydown', function(e) {
                if (e.keyCode === 27) { // ESC
                    $(this).val('').trigger('input');
                }
            });
        })
    </script>
    <?php doAction('adm_head') ?>
</head>
<body id="page-top">
<div id="editor-md-dialog"></div>
<div id="admin-container">
    <!-- 遮罩层 -->
    <div class="overlay"></div>


    <nav class="menu-container" id="left-menu">
        <a class="logo" href="<?= EM_URL ?>admin">
            <?= $admin_brand_name ?>
        </a>
        <!-- 菜单搜索框 -->
        <div class="menu-search-box">
            <input type="text" id="menu-search-input" class="menu-search-input" placeholder="搜索菜单...">
            <i class="fa fa-search menu-search-icon"></i>
            <i class="fa fa-times menu-search-clear" id="menu-search-clear" style="display:none;"></i>
        </div>
        <ul id="accordion" class="menu accordion">
            <li class="admin-menu-item" id="menu-dashboard">
                <a href="<?= EM_URL ?>admin" class="menu-link"><i class="fa fa-one fa-dashboard"></i>控制台</a>
            </li>

            <li class="admin-menu-item has-submenu" id="menu-goods">
                <div class="menu-link link">
                    <i class="fa fa-one fa-cube"></i><span>商品管理</span><i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-goods-list" class="admin-menu-item"><a href="<?= EM_URL ?>admin/goods.php" class="menu-link">商品列表</a></li>
                    <li id="menu-sort-list" class="admin-menu-item"><a href="<?= EM_URL ?>admin/sort.php" class="menu-link">商品分类</a></li>
                    <li id="menu-sku-list" class="admin-menu-item"><a href="<?= EM_URL ?>admin/sku.php" class="menu-link">商品规格</a></li>
                    <li id="menu-coupon-index" class="admin-menu-item"><a href="<?= EM_URL ?>admin/coupon.php" class="menu-link">优惠券</a></li>
                </ul>
            </li>
            <li class="admin-menu-item has-submenu" id="menu-order">
                <div class="menu-link link">
                    <i class="fa fa-one fa-list-ul"></i>订单管理<i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-order-goods" class="admin-menu-item"><a href="<?= EM_URL ?>admin/order.php" class="menu-link">商品订单</a></li>
                </ul>
            </li>

            <li class="admin-menu-item has-submenu" id="menu-user">
                <div class="menu-link link">
                    <i class="fa fa-one fa-user"></i>用户管理<i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-user-default" class="admin-menu-item"><a href="<?= EM_URL ?>admin/user.php" class="menu-link">用户管理</a></li>
                    <li id="menu-user-member" class="admin-menu-item"><a href="<?= EM_URL ?>admin/member.php" class="menu-link">会员等级</a></li>
                </ul>
            </li>
            <li class="admin-menu-item has-submenu" id="menu-blog">
                <div class="menu-link link">
                    <i class="fa fa-one fa-columns"></i>博客管理<i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-blog-list" class="admin-menu-item"><a href="<?= EM_URL ?>admin/article.php" class="menu-link">文章列表</a></li>
                    <li id="menu-blog-sort" class="admin-menu-item"><a href="<?= EM_URL ?>admin/sort.php?type=blog" class="menu-link">文章分类</a></li>
                    <li id="menu-blog-widgets" class="admin-menu-item"><a href="<?= EM_URL ?>admin/widgets.php" class="menu-link">边栏管理</a></li>
                    <li id="menu-blog-link" class="admin-menu-item"><a href="<?= EM_URL ?>admin/link.php" class="menu-link">友情链接</a></li>
                </ul>
            </li>
            <li class="admin-menu-item has-submenu" id="menu-appearance">
                <div class="menu-link link">
                    <i class="fa fa-one fa-inbox"></i>外观设置<i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-template" class="admin-menu-item"><a href="<?= EM_URL ?>admin/template.php" class="menu-link">模板管理</a></li>
                    <li id="menu-navi" class="admin-menu-item"><a href="<?= EM_URL ?>admin/navbar.php" class="menu-link">导航管理</a></li>
                    <li id="menu-page" class="admin-menu-item"><a href="<?= EM_URL ?>admin/page.php" class="menu-link">页面管理</a></li>
                </ul>
            </li>

            <li class="admin-menu-item has-submenu" id="menu-system">
                <div class="menu-link link">
                    <i class="fa fa-one fa-cog"></i>系统管理<i class="admin-arrow fa fa-angle-right"></i>
                </div>
                <ul class="submenu">
                    <li id="menu-setting" class="admin-menu-item"><a href="<?= EM_URL ?>admin/setting.php" class="menu-link">基础设置</a></li>
                    <li id="menu-media" class="admin-menu-item"><a href="<?= EM_URL ?>admin/media.php" class="menu-link">资源管理</a></li>
                    <li id="menu-system-log" class="admin-menu-item"><a href="<?= EM_URL ?>admin/system_log.php" class="menu-link">系统日志</a></li>
                </ul>
            </li>
            <?php doAction('adm_menu') ?>
        </ul>



    </nav>

    <div class="main">
        <header class="top-nav pc-top-nav">
            <div class="top-nav-left">
                <button type="button" class="menu-toggle-btn" id="menu-toggle-btn" aria-label="切换侧边栏">
                    <i class="fa fa-bars"></i>
                </button>
                <span class="layui-breadcrumb" lay-separator="/">
                    <?= $br ?>
                </span>
            </div>
            <nav class="top-nav-right">
                <ul class="layui-nav" lay-bar="disabled">
                    <li class="layui-nav-item" lay-unselect>
                        <a href="<?= EM_URL ?>" target="_blank" class="nav-link-home">
                            <i class="layui-icon layui-icon-home"></i>
                            <span>网站首页</span>
                        </a>
                    </li>
                    <li class="layui-nav-item nav-user-item" lay-unselect>
                        <a href="javascript:;" class="nav-link-user">
                            <img src="<?= User::getAvatar($user['photo']) ?>" class="nav-avatar">
                            <span class="nav-username"><?= $user['nickname'] ?></span>
                        </a>
                        <dl class="layui-nav-child layui-nav-child-c">
                            <dd><a href="<?= EM_URL ?>admin/blogger.php"><i class="layui-icon layui-icon-set"></i>个人信息</a></dd>
                            <dd><a href="javascript:;" class="delete-cache"><i class="layui-icon layui-icon-refresh"></i>清除缓存</a></dd>
                            <dd class="nav-divider"></dd>
                            <dd><a href="<?= EM_URL ?>admin/account.php?action=logout" class="nav-logout"><i class="layui-icon layui-icon-release"></i>退出登录</a></dd>
                        </dl>
                    </li>
                </ul>
            </nav>
        </header>

        <header class="top-nav mobile-top-nav">
            <div class="mobile-nav-left">
                <button type="button" class="mobile-menu-btn" id="mobile-menu-btn">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
            <nav class="mobile-nav-right">
                <!-- 用户菜单 -->
                <div class="mobile-dropdown">
                    <a href="javascript:;" class="mobile-dropdown-toggle nav-link-user">
                        <img src="<?= User::getAvatar($user_cache[UID]['avatar']) ?>" class="nav-avatar">
                        <i class="layui-icon layui-icon-down"></i>
                    </a>
                    <div class="mobile-dropdown-menu mobile-dropdown-right">
                        <a href="<?= EM_URL ?>" class="mobile-dropdown-item">
                            <i class="layui-icon layui-icon-home"></i>网站首页
                        </a>
                        <a href="<?= EM_URL ?>admin/blogger.php" class="mobile-dropdown-item">
                            <i class="layui-icon layui-icon-set"></i>个人信息
                        </a>
                        <a href="javascript:;" class="mobile-dropdown-item delete-cache">
                            <i class="layui-icon layui-icon-refresh"></i>清除缓存
                        </a>
                        <div class="mobile-dropdown-divider"></div>
                        <a href="<?= EM_URL ?>admin/account.php?action=logout" class="mobile-dropdown-item nav-logout">
                            <i class="layui-icon layui-icon-release"></i>退出登录
                        </a>
                    </div>
                </div>
            </nav>
        </header>

        <script>
            // 手机端下拉菜单
            $('.mobile-dropdown-toggle').click(function(e){
                e.preventDefault();
                e.stopPropagation();
                var $dropdown = $(this).parent('.mobile-dropdown');
                var isOpen = $dropdown.hasClass('open');
                // 关闭其他下拉菜单
                $('.mobile-dropdown').removeClass('open');
                // 切换当前下拉菜单
                if(!isOpen){
                    $dropdown.addClass('open');
                }
            });
            // 点击其他地方关闭下拉菜单
            $(document).click(function(){
                $('.mobile-dropdown').removeClass('open');
            });
            // 阻止下拉菜单内部点击冒泡
            $('.mobile-dropdown-menu').click(function(e){
                e.stopPropagation();
            });

            var menuStorageKey = 'admin-menu-collapsed';
            try {
                if (localStorage.getItem(menuStorageKey) === '1') {
                    $('#admin-container').addClass('menu-collapsed');
                }
            } catch (e) {}

            $('#menu-toggle-btn').click(function(){
                $('#admin-container').toggleClass('menu-collapsed');
                try {
                    localStorage.setItem(menuStorageKey, $('#admin-container').hasClass('menu-collapsed') ? '1' : '0');
                } catch (e) {}
            });

            $('#mobile-menu-btn').click(function(){
                $('#left-menu').addClass('show');
                $('.overlay').addClass('show');
                document.body.style.overflow = 'hidden';
            })
            $('.overlay').click(function(){
                $('#left-menu').removeClass('show');
                $('.overlay').removeClass('show');
                document.body.style.overflow = '';
            })
            // 手机端菜单点击链接后自动关闭
            $('#left-menu .menu-link[href]').click(function(){
                if($(window).width() <= 800 && $(this).attr('href') !== 'javascript:;'){
                    $('#left-menu').removeClass('show');
                    $('.overlay').removeClass('show');
                    document.body.style.overflow = '';
                }
            })

            $('.delete-cache').click(function(){
                layer.load(2);
                $.ajax({
                    type: "POST",
                    url: "<?= EM_URL ?>admin/index.php?action=delete_cache",
                    data: { type: 'cache' },
                    dataType: "json",
                    success: function (e) {
                        if(e.code == 400){
                            layer.msg(e.msg)
                        }else{
                            layer.msg('缓存删除成功')
                        }

                    },
                    error: function (xhr) {
                        layer.msg(JSON.parse(xhr.responseText).msg);
                    },
                    complete: function(xhr, textStatus) {
                        layer.closeAll('loading');
                    }
                });
            })

        </script>

        <div id="admin-content">
