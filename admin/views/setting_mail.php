<?php defined('EM_ROOT') || exit('access denied!'); ?>

<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li><a href="./setting.php">基础设置</a></li>
        <li><a href="./setting.php?action=shop">商城配置</a></li>
        <li><a href="./setting.php?action=user">用户设置</a></li>
        <li><a href="./setting.php?action=seo">SEO设置</a></li>
        <li class="layui-this"><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel">
    <div style="padding: 20px;">
        <form action="setting.php?action=mail_save" method="post" name="setting_form" id="setting_form" class="layui-form">

            <div class="layui-form-item">
                <label class="layui-form-label">发送人邮箱</label>
                <div class="layui-input-block">
                    <input type="email" class="layui-input" value="<?= $smtp_mail ?>" name="smtp_mail">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">SMTP密码</label>
                <div class="layui-input-block">
                    <input type="text" name="smtp_pw" class="layui-input" value="<?= $smtp_pw ?>" autocomplete="new-password">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">发送人名称（选填，建议填写站点名称）</label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" value="<?= $smtp_from_name ?>" name="smtp_from_name">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">SMTP服务器</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $smtp_server ?>" name="smtp_server">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">端口 (465：ssl协议，如QQ邮箱，网易邮箱等，587：STARTTLS协议 如：Outlook邮箱)</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $smtp_port ?>" name="smtp_port">
                </div>
            </div>

            <div class="layui-form-item">
                <blockquote class="layui-elem-quote">
                    <b>以QQ邮箱配置为例</b><br>
                    发送人邮箱：你的QQ邮箱<br>
                    SMTP密码：见QQ邮箱顶部设置-> 账户 -> 开启IMAP/SMTP服务 -> 生成授权码（即为SMTP密码）<br>
                    发送人名称：你的姓名或者站点名称<br>
                    SMTP服务器：smtp.qq.com<br>
                    端口：465<br>
                </blockquote>
            </div>

            <div id="testMailTemplate" style="display:none;">
                <div class="layui-form em-testmail-form">
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">接收邮箱</label>
                        <div class="layui-input-block">
                            <input class="layui-input" type="email" name="testTo" placeholder="输入接收邮箱">
                        </div>
                    </div>
                    <div class="em-testmail-msg" role="status" aria-live="polite"></div>
                </div>
            </div>

            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn" lay-submit lay-filter="demo1">保存设置</button>
                    <input type="button" value="发送测试" class="layui-btn layui-btn-blue" id="testSendOpenBtn"/>
                    <button type="reset" class="layui-btn layui-btn">重置</button>
                </div>
            </div>
        </form>
    </div>
</div>





<script>

        layui.use(['layer'], function () {
            var baseLayer = layui.layer;
            var Layer = window.top && window.top.layui && window.top.layui.layer ? window.top.layui.layer : baseLayer;
            var testLayerIndex = -1;

            function stripHtml(value) {
                if (!value) {
                    return '';
                }
                return String(value)
                    .replace(/<[^>]*>/g, '')
                    .replace(/&nbsp;/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function parseTestMailResponse(data) {
                var text = stripHtml(data);
                if (!text) {
                    return {type: 'error', text: '发送失败，请检查 SMTP 配置'};
                }
                var lower = text.toLowerCase();
                if (text.indexOf('发送成功') !== -1 || lower === 'success' || lower === 'ok' || text === '1') {
                    return {type: 'success', text: '发送成功'};
                }
                return {type: 'error', text: text};
            }

            function setTestMailMessage($msg, type, text) {
                if (!text) {
                    $msg.html('');
                    return;
                }
                var icon = 'layui-icon-close-fill';
                if (type === 'success') {
                    icon = 'layui-icon-ok-circle';
                } else if (type === 'loading') {
                    icon = 'layui-icon-loading-1 layui-anim layui-anim-rotate layui-anim-loop';
                } else if (type === 'info') {
                    icon = 'layui-icon-tips';
                }
                var html = '<div class="em-testmail-alert em-testmail-' + type + '">' +
                    '<i class="layui-icon ' + icon + '"></i>' +
                    '<span></span>' +
                    '</div>';
                $msg.html(html);
                $msg.find('span').text(text);
            }

            function setSendButtonState(layero, isLoading) {
                var $btn = layero.find('.layui-layer-btn0');
                if (!$btn.length) {
                    return;
                }
                if (isLoading) {
                    if (!$btn.data('origin-text')) {
                        $btn.data('origin-text', $btn.text());
                    }
                    $btn.text('发送中...').addClass('layui-btn-disabled is-loading');
                } else {
                    var originText = $btn.data('origin-text') || '发送';
                    $btn.text(originText).removeClass('layui-btn-disabled is-loading');
                }
            }

            $("#testSendOpenBtn").on('click', function () {
                var isMobile = $(window).width() < 640;
                var contentHtml = $('#testMailTemplate').html();
                testLayerIndex = Layer.open({
                    type: 1,
                    title: '发送测试',
                    shadeClose: true,
                    shade: 0.3,
                    area: isMobile ? '92%' : '460px',
                    content: contentHtml,
                    skin: 'em-layer-testmail',
                    btn: ['发送', '关闭'],
                    btnAlign: 'c',
                    zIndex: 19891015,
                    success: function(layero){
                        Layer.setTop(layero);
                        var $input = layero.find('input[name="testTo"]');
                        var defaultTo = $.trim($('input[name="smtp_mail"]').val() || '');
                        if (defaultTo) {
                            $input.val(defaultTo);
                        }
                        $input.focus();
                        $input.on('keydown', function(e){
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                layero.find('.layui-layer-btn0').trigger('click');
                            }
                        });
                        layero.find('.layui-layer-btn0').addClass('layui-btn layui-btn-blue');
                        layero.find('.layui-layer-btn1').addClass('layui-btn');
                        setTestMailMessage(layero.find('.em-testmail-msg'), 'info', '请输入接收邮箱并点击发送');
                    },
                    yes: function(index, layero){
                        var $msg = layero.find('.em-testmail-msg');
                        var $input = layero.find('input[name="testTo"]');
                        var testToVal = $.trim($input.val());
                        if (!testToVal) {
                            setTestMailMessage($msg, 'error', '请输入接收邮箱');
                            $input.focus();
                            return false;
                        }
                        var $sendBtn = layero.find('.layui-layer-btn0');
                        if ($sendBtn.hasClass('is-loading')) {
                            return false;
                        }
                        var payload = $("#setting_form").serializeArray();
                        payload.push({name: 'testTo', value: testToVal});
                        setSendButtonState(layero, true);
                        setTestMailMessage($msg, 'loading', '正在发送，请稍候...');
                        $.post("setting.php?action=mail_test", $.param(payload))
                            .done(function (data) {
                                var result = parseTestMailResponse(data);
                                setTestMailMessage($msg, result.type, result.text);
                            })
                            .fail(function (xhr) {
                                var errText = stripHtml(xhr && xhr.responseText ? xhr.responseText : '');
                                setTestMailMessage($msg, 'error', errText || '发送失败，请稍后重试');
                            })
                            .always(function () {
                                setSendButtonState(layero, false);
                            });
                        return false;
                    }
                });
            });
        });


    $(function () {
        // menu
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
<style>
    .em-layer-testmail {
        border-radius: 14px !important;
        overflow: hidden;
    }
    .em-layer-testmail .layui-layer-title {
        background: var(--admin-primary);
        color: #fff;
        border-bottom: none;
    }
    .em-layer-testmail .layui-layer-content {
        padding: 0;
    }
    .em-layer-testmail .layui-layer-btn {
        padding: 12px 20px 18px;
        text-align: center;
    }
    .em-layer-testmail .layui-layer-btn a {
        min-width: 92px;
        border-radius: 10px;
    }

    .em-testmail-form {
        padding: 18px 20px 8px;
    }
    .em-testmail-hint {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        background: rgba(15, 118, 110, 0.08);
        color: var(--admin-primary-strong);
        border: 1px dashed rgba(15, 118, 110, 0.22);
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 12px;
        margin-bottom: 16px;
    }
    .em-testmail-hint .layui-icon {
        font-size: 16px;
        margin-top: 2px;
    }
    .em-testmail-msg {
        min-height: 38px;
        margin-top: 6px;
    }
    .em-testmail-alert {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 13px;
        line-height: 1.4;
    }
    .em-testmail-alert .layui-icon {
        font-size: 16px;
    }
    .em-testmail-alert.em-testmail-success {
        background: rgba(16, 185, 129, 0.12);
        color: #0f766e;
        border: 1px solid rgba(16, 185, 129, 0.28);
    }
    .em-testmail-alert.em-testmail-error {
        background: rgba(239, 68, 68, 0.1);
        color: #b91c1c;
        border: 1px solid rgba(239, 68, 68, 0.28);
    }
    .em-testmail-alert.em-testmail-loading,
    .em-testmail-alert.em-testmail-info {
        background: rgba(15, 118, 110, 0.08);
        color: var(--admin-primary-strong);
        border: 1px dashed rgba(15, 118, 110, 0.25);
    }
</style>
