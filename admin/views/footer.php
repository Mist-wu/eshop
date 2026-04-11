<?php defined('EM_ROOT') || exit('access denied!'); ?>

</div>
</div>
</div>
</div>
<?php doAction('adm_footer') ?>

<script>
    $(function () {
        $(document).on('click', 'a.scroll-to-top', function (e) {
            var $anchor = $(this);
            $('html, body').stop().animate({
                scrollTop: ($($anchor.attr('href')).offset().top)
            }, 1000, 'easeInOutExpo');
            e.preventDefault();
        });



        // 初始化
        const menu = $("#left-menu");
        const overlay = $(".overlay");
        const toggleBtn = $(".show-nav");
        overlay.css({ opacity: 0.06, display: "none" });

        // 切换菜单
        toggleBtn.click(function() {
            $('#content').addClass('toright')
            $('#left-menu').addClass('toright')
            overlay.show();
        });

        // 点击遮罩层关闭菜单
        overlay.click(function() {
            $('#content').removeClass('toright')
            $('#left-menu').removeClass('toright')
            overlay.hide();
        });

    });
</script>
</body>
</html>
