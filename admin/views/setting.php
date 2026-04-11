<?php defined('EM_ROOT') || exit('access denied!'); ?>

<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li class="layui-this"><a href="./setting.php">基础设置</a></li>
        <li><a href="./setting.php?action=shop">商城配置</a></li>
        <li><a href="./setting.php?action=user">用户设置</a></li>
        <li><a href="./setting.php?action=seo">SEO设置</a></li>
        <li><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel">
    <div style="padding: 20px;">
        <style>
            .upload-field {
                display: flex;
                align-items: center;
                width: 100%;
                border: 1px solid #dfe3e8;
                border-radius: 10px;
                overflow: hidden;
                background: #fff;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
            }

            .upload-field:focus-within {
                border-color: #4c9ffe;
                box-shadow: 0 0 0 3px rgba(76, 159, 254, 0.15);
            }

            .upload-field .upload-input {
                border: 0;
                box-shadow: none;
                height: 38px;
                line-height: 38px;
                flex: 1;
            }

            .upload-field .upload-input:focus {
                box-shadow: none;
            }

            .upload-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 3px 6px 3px 8px;
            }

            .upload-actions .layui-btn {
                border: 0;
                border-radius: 6px;
                height: 32px;
                line-height: 32px;
                margin: 0;
                padding: 0 12px;
                font-weight: 600;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            }

            .upload-select {
                background: #eef4ff;
                color: #2b57d9;
                border: 1px solid #cfe0ff;
            }

            .upload-select:hover {
                background: #dfe9ff;
                color: #1f49b7;
            }

            .upload-btn {
                background: linear-gradient(135deg, #5fa8ff, #2f82ff);
                color: #fff;
            }

            .upload-btn:hover {
                background: linear-gradient(135deg, #3f8bff, #1f6fff);
            }

            .upload-clear {
                background: #f4f6f8;
                color: #55606e;
                border: 1px solid #e1e6eb;
            }

            .upload-clear:hover {
                background: #e9eef3;
                color: #3f4a59;
            }

        </style>
        <form action="setting.php?action=save" method="post" name="setting_form" id="setting_form" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">站点标题</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $blogname ?>" name="blogname">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">站点地址</label>
                <div class="layui-input-block">
                    <input class="layui-input readonly" value="<?= $blogurl ?>" name="blogurl" type="url" readonly required>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" name="detect_url" id="detect_url" value="y" <?= $conf_detect_url ?> title="自动检测站点地址 (如开启后首页样式丢失，请关闭并手动填写站点地址)">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选择时区</label>
                <div class="layui-input-block">
                    <select name="timezone">
                        <?php foreach ($tzlist as $key => $value):
                            $ex = $key == $timezone ? "selected=\"selected\"" : '' ?>
                            <option value="<?= $key ?>" <?= $ex ?>><?= $value ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">前台 每页显示数据数量</label>
                <div class="layui-input-block">
                    <input class="layui-input" style="width:100px;" value="<?= $index_lognum ?>" name="index_lognum" type="number" min="1" />
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">后台 每页展示条目数量</label>
                <div class="layui-input-block">
                    <input class="layui-input" style="width:100px;" value="<?= $admin_article_perpage_num ?>" name="admin_article_perpage_num" type="number" min="1" max="1000" />
                    <div class="layui-form-mid layui-text-em">影响后台商品、订单、用户列表</div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">ICP备案号</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $icp ?>" name="icp"/>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">首页底部信息(支持html，可用于添加流量统计代码)</label>
                <div class="layui-input-block">
                    <textarea name="footer_info" rows="6" class="layui-textarea"><?= $footer_info ?></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">后台icon图标</label>
                <div class="layui-input-block">
                    <div class="upload-field">
                        <input type="text" value="<?= $admin_icon ?? '' ?>" placeholder="" class="layui-input upload-input" name="admin_icon" id="admin-icon">
                        <div class="upload-actions">
                            <button type="button" class="layui-btn upload-select media-history-btn" data-target="#admin-icon">选择</button>
                            <button type="button" id="admin-icon-btn" class="layui-btn upload-btn">上传图片</button>
                            <button type="button" class="layui-btn upload-clear" data-target="#admin-icon">清空</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">前台icon图标</label>
                <div class="layui-input-block">
                    <div class="upload-field">
                        <input type="text" value="<?= $home_icon ?? '' ?>" placeholder="" class="layui-input upload-input" name="home_icon" id="home-icon">
                        <div class="upload-actions">
                            <button type="button" class="layui-btn upload-select media-history-btn" data-target="#home-icon">选择</button>
                            <button type="button" id="home-icon-btn" class="layui-btn upload-btn">上传图片</button>
                            <button type="button" class="layui-btn upload-clear" data-target="#home-icon">清空</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">网站Logo</label>
                <div class="layui-input-block">
                    <div class="upload-field">
                        <input type="text" value="<?= $logo ?>" placeholder="" class="layui-input upload-input" name="logo" id="logo">
                        <div class="upload-actions">
                            <button type="button" class="layui-btn upload-select media-history-btn" data-target="#logo">选择</button>
                            <button type="button" id="logo-btn" class="layui-btn upload-btn">上传图片</button>
                            <button type="button" class="layui-btn upload-clear" data-target="#logo">清空</button>
                        </div>
                    </div>
                </div>
            </div>


            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn layui-btn-green" lay-submit lay-filter="demo1">保存设置</button>
                    <button type="reset" class="layui-btn layui-btn">重置</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    layui.use(['table'], function(){
        var $ = layui.$;
        var upload = layui.upload;
        var element = layui.element;

        var adminIconUpload = upload.render({
            elem: '#admin-icon-btn',
            field: 'image',
            url: './article.php?action=upload_cover', // 实际使用时改成您自己的上传接口即可。
            before: function(obj){
                // 预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result){
                    $('#ID-upload-demo-img').attr('src', result); // 图片链接（base64）
                });

                element.progress('filter-demo', '0%'); // 进度条复位
                loadIndex = layer.load(2);
            },
            done: function(res){
                if(res.code == 400){
                    return layer.msg(res.msg)
                }
                // 若上传失败
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                // 上传成功的一些操作
                if(res.code == 0){
                    $('#admin-icon').val(res.data)
                }
                $('#ID-upload-demo-text').html(''); // 置空上传失败的状态
            },
            error: function(){
                // 演示失败状态，并实现重传
                var demoText = $('#ID-upload-demo-text');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    adminIconUpload.upload();
                });
            },
            // 进度条
            progress: function(n, elem, e){
                element.progress('filter-demo', n + '%'); // 可配合 layui 进度条元素使用
                if(n == 100){
                    layer.close(loadIndex)
                }
            }
        });

        var homeIconUpload = upload.render({
            elem: '#home-icon-btn',
            field: 'image',
            url: './article.php?action=upload_cover', // 实际使用时改成您自己的上传接口即可。
            before: function(obj){
                // 预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result){
                    $('#ID-upload-demo-img').attr('src', result); // 图片链接（base64）
                });

                element.progress('filter-demo', '0%'); // 进度条复位
                loadIndex = layer.load(2);
            },
            done: function(res){
                if(res.code == 400){
                    return layer.msg(res.msg)
                }
                // 若上传失败
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                // 上传成功的一些操作
                if(res.code == 0){
                    $('#home-icon').val(res.data)
                }
                $('#ID-upload-demo-text').html(''); // 置空上传失败的状态
            },
            error: function(){
                // 演示失败状态，并实现重传
                var demoText = $('#ID-upload-demo-text');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    homeIconUpload.upload();
                });
            },
            // 进度条
            progress: function(n, elem, e){
                element.progress('filter-demo', n + '%'); // 可配合 layui 进度条元素使用
                if(n == 100){
                    layer.close(loadIndex)
                }
            }
        });

        var logoUpload = upload.render({
            elem: '#logo-btn',
            field: 'image',
            url: './article.php?action=upload_cover', // 实际使用时改成您自己的上传接口即可。
            before: function(obj){
                // 预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result){
                    $('#ID-upload-demo-img').attr('src', result); // 图片链接（base64）
                });

                element.progress('filter-demo', '0%'); // 进度条复位
                loadIndex = layer.load(2);
            },
            done: function(res){
                if(res.code == 400){
                    return layer.msg(res.msg)
                }
                // 若上传失败
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                // 上传成功的一些操作
                if(res.code == 0){
                    $('#logo').val(res.data)
                }
                $('#ID-upload-demo-text').html(''); // 置空上传失败的状态
            },
            error: function(){
                // 演示失败状态，并实现重传
                var demoText = $('#ID-upload-demo-text');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    logoUpload.upload();
                });
            },
            // 进度条
            progress: function(n, elem, e){
                element.progress('filter-demo', n + '%'); // 可配合 layui 进度条元素使用
                if(n == 100){
                    layer.close(loadIndex)
                }
            }
        });




    })

    $("#menu-system").attr('class', 'admin-menu-item has-list in');
    $("#menu-system .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-system > .submenu").css('display', 'block');
    $('#menu-setting > a').attr('class', 'menu-link active')

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
        $(target).val('');
    });

    // 提交表单
    $("#setting_form").submit(function (event) {
        event.preventDefault();
        submitForm("#setting_form");
    });

</script>
