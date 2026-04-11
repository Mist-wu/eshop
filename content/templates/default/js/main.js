$(function() {
    // 导航悬停显示二级菜单（添加 on 类作为备用）
    $(".header .nav-bar .nav > li.has-submenu").hover(
        function() {
            $(this).addClass("on");
        },
        function() {
            $(this).removeClass("on");
        }
    );

    // 移动端导航
    $("#m-btn").click(function() {
        $("#mask").slideToggle(0);
        $("body").toggleClass("open");
    });

    $("#mask").click(function() {
        $(this).hide();
        $("body").removeClass("open");
    });
});
