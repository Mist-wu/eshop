<?php defined('EM_ROOT') || exit('access denied!'); ?>


<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li><a href="./setting.php">基础设置</a></li>
        <li><a href="./setting.php?action=shop">商城配置</a></li>
        <li><a href="./setting.php?action=user">用户设置</a></li>
        <li><a href="./setting.php?action=seo">SEO设置</a></li>
        <li><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li class="layui-this"><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel">
    <div style="padding: 20px;">
        <style>
            .custom-upload-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 16px;
                background: #EDF2F1;
                color: #4C7D71;
                border: 1px solid #DAE6E3;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s ease;
                outline: none;
                text-decoration: none;
            }
            .custom-upload-btn:hover {
                background: #d6e4e1;
                color: #3a6359;
                border-color: #c2d3cd;
            }
            .custom-upload-btn:active {
                background: #c2d3cd;
                color: #2d4d47;
                border-color: #a9beb8;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(76, 125, 113, 0.2);
            }
        </style>
        <form action="blogger.php?action=update" method="post" name="profile_setting_form" id="profile_setting_form" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">头像</label>
                <div class="layui-input-block">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div>
                            <img src="<?= $icon ?>" width="120" height="120" id="avatar_image" class="rounded-circle" />
                            <input type="hidden" name="avatar" id="avatar_input" value="<?= $icon ?>">
                        </div>
                        <div>
                            <button type="button" class="custom-upload-btn" id="avatar_upload_btn">
                                <i class="layui-icon layui-icon-upload"></i> 上传头像
                            </button>
                            <div class="layui-word-aux" style="margin-top: 10px; font-size: 12px; color: #999;">建议尺寸：120x120像素，支持JPG、PNG格式</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">昵称</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $nickname ?>" name="name" maxlength="20" required>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">登录账号</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $username ?>" name="username" id="username">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">邮箱</label>
                <div class="layui-input-block">
                    <input class="layui-input" value="<?= $email ?? '' ?>" name="email" id="email" type="email" placeholder="请输入邮箱地址">
                </div>
            </div>
            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn" lay-submit lay-filter="demo1">保存设置</button>
                    <a href="javascript:;" type="button" class="layui-btn layui-btn-blue" data-toggle="modal" id="editPasswordModal">修改密码</a>
                    <button type="reset" class="layui-btn layui-btn">重置</button>
                </div>
            </div>
        </form>
    </div>
</div>




<div id="modal" style="display: none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="img-container">
                    <div class="row">
                        <div class="col-md-11">
                            <img src="" id="sample_image"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" id="crop" class="btn btn-sm btn-success">保存</button>
                <button type="button" id="use_original_image" class="btn btn-sm btn-primary">使用原图</button>
            </div>
        </div>
    </div>
</div>





<script>

    layui.use(['form', 'upload', 'element'], function() {
        var $ = layui.$;
        var layer = layui.layer;
        var util = layui.util;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;
        
        // 头像上传
        var avatarUploadInst = upload.render({
            elem: '#avatar_upload_btn',
            field: 'image',
            url: './blogger.php?action=update_avatar', // 头像上传接口
            accept: 'images', // 只接受图片
            exts: 'jpg|png|gif|bmp|jpeg', // 图片格式
            before: function(obj){
                layer.load(2);
            },
            done: function(res){
                // 上传成功
                if(res.code == 0){
                    $('#avatar_image').attr('src', res.data); // 更新头像预览
                    $('#avatar_input').val(res.data); // 更新隐藏输入框值
                    layer.msg('头像上传成功', {icon: 1});
                } else {
                    layer.msg(res.msg || '上传失败');
                }
                layer.closeAll('loading');
            },
            error: function(){
                layer.closeAll('loading');
                layer.msg('上传出错');
            }
        });
        
        $('#editPasswordModal').click(function(){
            layer.open({
                type: 1,
                area: '350px',
                resize: false,
                shadeClose: true,
                title: '修改密码',
                id: 'edit',
                content: `
          <div class="layui-form" lay-filter="filter-test-layer" style="margin: 16px;">
            <div class="demo-login-container">
              <div class="layui-form-item">
                <div class="layui-input-wrap">
                  <div class="layui-input-prefix">
                    <i class="layui-icon layui-icon-password"></i>
                  </div>
                  <input type="password" name="new_passwd" value="" placeholder="新密码" lay-reqtext="请填写密码" autocomplete="off" class="layui-input" lay-affix="eye">
                </div>
              </div>
                <div class="layui-form-item">
                <div class="layui-input-wrap">
                  <div class="layui-input-prefix">
                    <i class="layui-icon layui-icon-password"></i>
                  </div>
                  <input type="password" name="new_passwd2" value="" placeholder="确认密码" lay-reqtext="请填写密码" autocomplete="off" class="layui-input" lay-affix="eye">
                </div>
              </div>

<input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
              <div class="layui-form-item">
                <button class="layui-btn" lay-submit lay-filter="pwd-save">保存</button>
                <button class="layui-btn layui-btn" lay-filter="pwd-quit">取消</button>
              </div>
            </div>
          </div>
        `,
                success: function(){
                    // 对弹层中的表单进行初始化渲染
                    form.render();
                    // 表单提交事件
                    form.on('submit(pwd-save)', function(data){
                        var field = data.field; // 获取表单字段值
                        // 此处可执行 Ajax 等操作
                        $.ajax({
                            type: "POST",
                            url: "blogger.php?action=change_password",
                            data: field,
                            dataType: "json",
                            success: function (e) {
                                if(e.code == 400){
                                    return layer.msg(e.msg)
                                }
                                layer.close('edit')
                                layer.msg('修改成功');
                            },
                            error: function (xhr) {
                                layer.msg(JSON.parse(xhr.responseText).msg);
                            }
                        });
                        return false; // 阻止默认 form 跳转
                    });
                }
            });
        })

    })

    $("#menu-system").attr('class', 'admin-menu-item has-list in');
    $("#menu-system .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
    $("#menu-system > .submenu").css('display', 'block');
    $('#menu-setting > a').attr('class', 'menu-link active')

    $(function () {


        // 提交表单
        $("#profile_setting_form").submit(function (event) {
            event.preventDefault();
            submitForm("#profile_setting_form");
        });

        // 修改用户密码表单提交
        $("#passwd_setting_form").submit(function (event) {
            event.preventDefault();
            submitForm("#passwd_setting_form", '密码修改成功, 请退出重新登录');
            $("#editPasswordModal").modal('hide');
        });
    });
</script>
