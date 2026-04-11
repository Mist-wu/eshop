<?php defined('EM_ROOT') || exit('access denied!'); ?>

<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li><a href="./setting.php">基础设置</a></li>
        <li><a href="./setting.php?action=shop">商城配置</a></li>
        <li><a href="./setting.php?action=user">用户设置</a></li>
        <li class="layui-this"><a href="./setting.php?action=seo">SEO设置</a></li>
        <li><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel">
    <div style="padding: 20px;">
        <form action="setting.php?action=seo_save" method="post" name="setting_form" id="setting_form" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">链接格式</label>
                <div class="layui-input-block">
                    <input type="radio" name="permalink" value="0" title="默认格式&nbsp;&nbsp;<?= EM_URL ?>?post=1" <?= $ex0 ?>>
                </div>
                <div class="layui-input-block">
                    <input type="radio" name="permalink" value="1" title="文件格式&nbsp;&nbsp;<?= EM_URL ?>post-1.html" <?= $ex1 ?>>
                </div>
                <div class="layui-input-block">
                    <input type="radio" name="permalink" value="2" title="目录格式①&nbsp;&nbsp;<?= EM_URL ?>post/1" <?= $ex2 ?>>
                </div>
                <div class="layui-input-block">
                    <input type="radio" name="permalink" value="3" title="目录格式②&nbsp;&nbsp;<?= EM_URL ?>buy/1" <?= $ex3 ?>>
                </div>
                <!--<div class="layui-input-block">
                    <input type="radio" name="permalink" value="3" title="3 分类格式&nbsp;&nbsp;<?php /*= EM_URL */?>category/1.html" <?php /*= $ex3 */?>>
                </div>-->
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">站点浏览器标题(title)</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $site_title ?>" name="site_title">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">站点关键字(keywords)，多个用英文逗号分隔</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $site_key ?>" name="site_key">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">站点浏览器描述(description)</label>
                <div class="layui-input-block">
                    <textarea name="site_description" class="layui-textarea"><?= $site_description ?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">详情页面浏览器标题方案</label>
                <div class="layui-input-block">
                    <select name="log_title_style">
                        <option value="0" <?= $opt0 ?>>商品名称</option>
                        <option value="1" <?= $opt1 ?>>商品名称 - 站点标题</option>
                        <option value="2" <?= $opt2 ?>>商品名称 - 站点浏览器标题</option>
                    </select>
                </div>
            </div>



            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn" lay-submit lay-filter="demo1">保存设置</button>
                    <button type="reset" class="layui-btn layui-btn">重置</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    $(function () {
        $("#menu-system").attr('class', 'admin-menu-item has-list in');
        $("#menu-system .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-system > .submenu").css('display', 'block');
        $('#menu-setting > a').attr('class', 'menu-link active')

        // 提交表单
        $("#setting_form").submit(function (event) {
            event.preventDefault();
            submitForm("#setting_form");
        });
    });
</script>