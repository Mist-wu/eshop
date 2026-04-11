<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    #form-btn{
        background: #eee;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px;
        line-height: 50px;
        margin: 0 auto;
        text-align: center;
        z-index: 10;
    }
    html, body{
        height: 100%;
    }
    body{
        overflow: hidden;
    }
    #open-box{
        box-sizing: border-box;
        height: calc(100vh - 50px);
        overflow-y: auto;
    }
</style>


<form class="layui-form " action="user.php?action=edit_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">昵称</label>
            <div class="layui-input-block">
                <input type="text" name="nickname" class="layui-input" value="<?= $nickname ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">手机号码</label>
            <div class="layui-input-block">
                <input type="text" name="tel" class="layui-input" value="<?= $tel ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">邮箱</label>
            <div class="layui-input-block">
                <input type="email" name="email" class="layui-input" value="<?= $email ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">用户组</label>
            <div class="layui-input-block">
                <select class="layui-input" name="role">
                    <option value="writer" <?= $ex1 ?>>普通用户</option>
                    <option value="admin" <?= $ex3 ?>>管理员</option>
                </select>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">会员等级</label>
            <div class="layui-input-block">
                <select class="layui-input" name="level">
                    <option value="0" <?= $level == 0 ? 'selected' : '' ?>>普通用户</option>
                    <?php foreach($members as $val): ?>
                        <option value="<?= $val['id'] ?>" <?= $level == $val['id'] ? 'selected' : '' ?>><?= $val['tier_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">密码(不修改请留空)</label>
            <div class="layui-input-block">
                <input type="text" name="password" class="layui-input" autocomplete="new-password">
            </div>
        </div>

        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input type="hidden" value="<?= $uid ?>" name="uid"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">立即提交</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>



<script>
    layui.use(['table'], function(){
        var $ = layui.$;
        var form = layui.form;
        function closeParentModal() {
            if (window.parent && window.parent.AdminModal) {
                window.parent.AdminModal.close();
                return;
            }
            if (window.parent && window.parent.layer) {
                window.parent.layer.close('edit');
            }
        }

        function notifyParent(msg) {
            if (window.parent && window.parent.layer) {
                window.parent.layer.msg(msg);
                return;
            }
            layer.msg(msg);
        }

        form.on('submit(submit)', function(data){
            var field = data.field; // 获取表单全部字段值
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    if (window.parent && window.parent.table) {
                        window.parent.table.reload();
                    }
                    closeParentModal();
                    notifyParent('操作成功');
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });



    })

</script>
