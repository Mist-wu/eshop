<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
</style>


<form class="layui-form " action="?action=edit_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">等级名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" class="layui-input" value="<?= $info['tier_name'] ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">折扣率(%)</label>
            <div class="layui-input-block">
                <input type="number" name="discount" class="layui-input" value="<?= $info['discount'] ?? '' ?>" placeholder="例如：90 表示9折">
            </div>
            <div class="layui-form-mid layui-word-aux" style="margin-left: 110px;">输入折扣百分比，如90表示按普通用户价格的90%计算</div>
        </div>

        <input type="hidden" name="id" value="<?= $id ?>" />
        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">立即提交</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>

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
            <div class="modal-footer" style="    align-items: center;
    display: flex
;
    flex: none;
    gap: .75rem;
    padding: 1.25rem;">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" id="crop" class="btn btn-sm btn-success">保存</button>
                <button type="button" id="use_original_image" class="btn btn-sm btn-primary">使用原图</button>
            </div>
        </div>
    </div>
</div>

<script>
    layui.use(['table'], function(){
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;
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
                    parent.layer.close('edit')
                    parent.layer.msg('编辑成功');
                    window.parent.table.reload();
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });


        var uploadInst = upload.render({
            elem: '#ID-upload-demo-btn',
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
                // 若上传失败
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                // 上传成功的一些操作
                if(res.code == 0){
                    $('#sortimg').val(res.data)
                }
                $('#ID-upload-demo-text').html(''); // 置空上传失败的状态
            },
            error: function(){
                // 演示失败状态，并实现重传
                var demoText = $('#ID-upload-demo-text');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function(){
                    uploadInst.upload();
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






    var maxHeight = $(window.parent).innerHeight() * 0.75;
    // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>
