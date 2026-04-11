<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php
$avatarUrl = User::getAvatar($userData['photo']);
?>

<style>
    .profile-edit {
        max-width: 960px;
        margin: 0 auto;
        display: grid;
        gap: 18px;
    }

    .profile-card {
        background: var(--panel);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-soft);
        padding: 24px;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
        display: grid;
        gap: 18px;
    }

    .card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .card-title h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--text);
    }

    .card-title p {
        margin: 0;
        font-size: 12px;
        color: var(--muted);
    }

    .edit-grid {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 20px;
        align-items: start;
    }

    .avatar-panel {
        background: var(--panel-soft);
        border-radius: 16px;
        padding: 18px;
        border: 1px dashed rgba(15, 118, 110, 0.25);
        display: grid;
        gap: 12px;
        justify-items: center;
        text-align: center;
    }

    .avatar-panel img {
        width: 120px;
        height: 120px;
        border-radius: 24px;
        object-fit: cover;
    }

    .avatar-panel .hint {
        font-size: 12px;
        color: var(--muted);
    }

    .form-grid {
        display: grid;
        gap: 14px;
    }

    .form-field {
        display: grid;
        gap: 8px;
    }

    .form-field label {
        font-size: 12px;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .form-field input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(15, 118, 110, 0.18);
        background: #ffffff;
        font-size: 14px;
        transition: border 0.2s ease, box-shadow 0.2s ease;
    }

    .form-field input:focus {
        outline: none;
        border-color: rgba(15, 118, 110, 0.4);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
    }

    .field-hint {
        font-size: 12px;
        color: var(--muted);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        background: var(--primary);
        color: #ffffff;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
    }

    .btn.ghost {
        background: rgba(15, 118, 110, 0.1);
        border-color: rgba(15, 118, 110, 0.2);
        color: var(--primary-strong);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    @media (max-width: 900px) {
        .edit-grid {
            grid-template-columns: 1fr;
        }

        .avatar-panel {
            justify-items: start;
            text-align: left;
        }
    }
</style>

<main class="main-content">
    <div class="profile-edit">
        <section class="profile-card">
            <div class="card-title">
                <h2>编辑个人资料</h2>
                <p></p>
            </div>

            <form class="layui-form" id="profile-form">
                <div class="edit-grid">
                    <div class="avatar-panel">
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" id="avatar-preview" data-default="<?= htmlspecialchars($avatarUrl) ?>">
                        <button type="button" class="btn" id="avatar-upload-btn"><i class="fa fa-upload"></i>上传头像</button>
                        <div class="hint">支持 JPG / PNG / GIF</div>
                    </div>

                    <div class="form-grid">
                        <input type="hidden" name="photo" id="photo_input" value="<?= htmlspecialchars($userData['photo']) ?>">

                        <div class="form-field">
                            <label>手机号码</label>
                            <input type="text" name="tel" value="<?= htmlspecialchars($userData['tel']) ?>" placeholder="请输入手机号码" inputmode="numeric">
                            <div class="field-hint">用于登录与安全验证</div>
                        </div>

                        <div class="form-field">
                            <label>邮箱地址</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" placeholder="请输入邮箱地址">
                            <div class="field-hint">用于找回密码与通知</div>
                        </div>

                        <input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>

                        <div class="form-actions">
                            <button class="btn" lay-submit lay-filter="profile-save" type="submit"><i class="fa fa-save"></i>保存资料</button>
                            <button class="btn ghost" type="reset"><i class="fa fa-undo"></i>重置修改</button>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
</main>

<script>
    $('#menu-index').addClass('open menu-current');

    layui.use(['form', 'upload'], function() {
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var layer = layui.layer;

        upload.render({
            elem: '#avatar-upload-btn',
            field: 'image',
            url: '/user/profile.php?action=upload_avatar',
            accept: 'images',
            exts: 'jpg|png|gif|bmp|jpeg|webp',
            data: {
                token: '<?= LoginAuth::genToken() ?>'
            },
            before: function() {
                layer.load(2, {shade: [0.3, '#000']});
            },
            done: function(res) {
                layer.closeAll('loading');
                if (res.code === 0) {
                    $('#avatar-preview').attr('src', res.data);
                    $('#photo_input').val(res.data);
                } else {
                    layer.msg(res.msg || '上传失败');
                }
            },
            error: function() {
                layer.closeAll('loading');
                layer.msg('上传出错，请稍后重试');
            }
        });

        form.on('submit(profile-save)', function(data) {
            $.ajax({
                type: 'POST',
                url: '/user/profile.php?action=update',
                data: data.field,
                dataType: 'json',
                beforeSend: function() {
                    layer.load(2, {shade: [0.3, '#000']});
                },
                success: function(res) {
                    layer.closeAll('loading');
                    if (res.code === 0) {
                        layer.msg('资料已保存', {icon: 1});
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        layer.msg(res.msg || '保存失败');
                    }
                },
                error: function(xhr) {
                    layer.closeAll('loading');
                    try {
                        var err = JSON.parse(xhr.responseText);
                        layer.msg(err.msg || '保存失败');
                    } catch (e) {
                        layer.msg('网络错误，请稍后重试');
                    }
                }
            });
            return false;
        });

        $('#profile-form').on('reset', function() {
            var defaultAvatar = $('#avatar-preview').data('default');
            var originalPhoto = <?= json_encode($userData['photo']) ?>;
            $('#avatar-preview').attr('src', defaultAvatar);
            $('#photo_input').val(originalPhoto || '');
        });
    });
</script>
