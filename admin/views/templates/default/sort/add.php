<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    html, body {
        height: 100%;
    }

    body {
        margin: 0;
        background: #f6f8fb;
        overflow: hidden;
    }

    .sort-form {
        height: 100%;
    }

    .sort-modal {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .sort-modal__body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 18px 22px 24px;
    }

    .form-card {
        background: var(--admin-panel);
        border: 1px solid var(--admin-border-soft);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .form-section + .form-section {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px dashed var(--admin-border-soft);
    }

    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--admin-text);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--admin-primary-strong);
    }

    .sort-form .layui-form-item {
        margin-bottom: 14px;
    }

    .sort-form .layui-form-label {
        float: none;
        width: auto;
        padding: 0 0 6px;
        text-align: left;
        color: var(--admin-text);
        font-weight: 600;
        line-height: 1.4;
    }

    .sort-form .layui-input-block {
        margin-left: 0;
    }

    .sort-form .layui-input,
    .sort-form .layui-textarea,
    .sort-form select {
        border-radius: 10px;
        background: #ffffff;
    }

    .form-hint {
        margin-top: 6px;
        font-size: 12px;
        color: var(--admin-muted);
    }

    .form-media {
        display: flex;
        gap: 16px;
        align-items: flex-start;
    }

    .media-input {
        flex: 1;
        min-width: 0;
        display: grid;
        gap: 8px;
    }

    .upload-field {
        display: flex;
        align-items: center;
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
        min-height: 38px;
        padding: 4px 0;
        transition: all 0.2s ease;
    }

    .upload-field:focus-within {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .upload-field .upload-input {
        border: 0;
        box-shadow: none;
        height: 38px;
        line-height: 38px;
        flex: 1;
        padding-left: 0px;
    }

    .upload-field .upload-input:focus {
        box-shadow: none;
    }

    .upload-preview-thumb {
        width: 38px;
        height: 38px;
        margin: 0 8px 0 8px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        flex-shrink: 0;
        background: #f9fafb;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .upload-preview-thumb:hover:not(.placeholder) {
        background: #f3f4f6;
        border-color: #0f766e;
    }

    .upload-preview-thumb.placeholder {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        cursor: default;
        border-color: #e5e7eb;
    }

    .upload-preview-thumb.placeholder::before {
        content: '📷';
        font-size: 18px;
        opacity: 0.6;
    }

    .upload-preview-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .upload-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px 3px 8px;
    }

    .upload-actions .layui-btn {
        height: 32px;
        line-height: 32px;
        margin: 0;
        padding: 0 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .upload-actions .layui-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }

    .media-preview {
        display: none;
    }

    .sort-modal__footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e6e6e6;
        padding: 12px 16px;
        text-align: center;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }

    @media (max-width: 768px) {
        .sort-modal__body,
        .sort-modal__footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        .form-media {
            flex-direction: column;
        }

        .media-preview {
            width: 100%;
        }
    }
</style>

<form class="layui-form sort-form" action="sort.php?action=save" id="form">
    <div class="sort-modal">
        <div class="sort-modal__body">
            <div class="form-card">
                <div class="form-section">
                    <div class="section-title">基础信息</div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">分类名</label>
                        <div class="layui-input-block">
                            <input type="text" name="sortname" class="layui-input" placeholder="请输入分类名称">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">别名</label>
                        <div class="layui-input-block">
                            <input type="text" name="alias" class="layui-input" placeholder="可选，便于识别">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">父分类</label>
                        <div class="layui-input-block">
                            <select class="layui-input" name="pid">
                                <option value="0">无</option>
                                <?php
                                foreach($sorts as $key => $val):
                                    if($val['pid'] != 0){
                                        continue;
                                    }
                                ?>
                                    <option value="<?= $key ?>"><?= $val['sortname'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">分类图片</label>
                        <div class="layui-input-block">
                            <div class="upload-field">
                                <div id="sortimg-preview-wrapper" class="upload-preview-thumb placeholder" title="点击预览分类图片">
                                    <img id="sortimg-preview-img" src="" alt="" style="display: none;" />
                                </div>
                                <input type="text" placeholder="分类图片地址" class="layui-input upload-input" name="sortimg" id="sortimg">
                                <div class="upload-actions">
                                    <button type="button" class="layui-btn layui-btn-blue media-history-btn" data-target="#sortimg">选择</button>
                                    <button type="button" id="ID-upload-demo-btn" class="layui-btn layui-btn-purple">上传图片</button>
                                    <button type="button" class="layui-btn layui-btn-red upload-clear" data-target="#sortimg">清空</button>
                                </div>
                            </div>
                            <div class="form-hint">建议上传 1:1 或 4:3 比例图片。</div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">排序</label>
                        <div class="layui-input-block">
                            <input type="number" name="taxis" class="layui-input" placeholder="数值越大越靠前">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">SEO 设置</div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">标题（用于分类页的 title）支持变量: {{site_title}}, {{site_name}}, {{sort_name}}</label>
                        <div class="layui-input-block">
                            <input type="text" name="title" class="layui-input" placeholder="例如：{{sort_name}} - {{site_name}}">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">关键词（用于分类页的 keywords，英文逗号分割）</label>
                        <div class="layui-input-block">
                            <textarea class="layui-textarea" name="kw" placeholder="多个关键词用英文逗号分隔"></textarea>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">描述（用于分类页的 description）</label>
                        <div class="layui-input-block">
                            <textarea class="layui-textarea" name="description" placeholder="建议 80-120 字"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="type" value="<?= $type ?>" />
            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        </div>
        <div class="sort-modal__footer">
            <button type="submit" class="layui-btn layui-btn-green" lay-submit lay-filter="submit">保存</button>
            <button type="button" class="layui-btn" id="btn-cancel">取消</button>
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
            <div class="modal-footer" style="align-items: center; display: flex; flex: none; gap: .75rem; padding: 1.25rem;">
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

        function notifyParent(msg) {
            if (parent.layer && parent.layer.msg) {
                parent.layer.msg(msg);
            } else {
                layer.msg(msg);
            }
        }

        function closeParentModal() {
            if (parent.AdminModal && typeof parent.AdminModal.close === 'function') {
                parent.AdminModal.close();
            }
        }

        function updatePreview(src) {
            var $img = $('#sortimg-preview-img');
            var $wrapper = $('#sortimg-preview-wrapper');

            if (src && src.trim()) {
                $img.attr('src', src).show();
                $wrapper.removeClass('placeholder').css('cursor', 'pointer');
            } else {
                $img.attr('src', '').hide();
                $wrapper.addClass('placeholder').css('cursor', 'default');
            }
        }

        $('#sortimg').on('input', function(){
            updatePreview($(this).val().trim());
        });

        // 点击预览图查看大图
        $('#sortimg-preview-wrapper').on('click', function () {
            var url = $('#sortimg').val();
            if (url && url.trim()) {
                layer.photos({
                    photos: {
                        "title": "分类图片预览",
                        "start": 0,
                        "data": [{
                            "alt": "分类图片",
                            "pid": 1,
                            "src": url
                        }]
                    },
                    anim: 5
                });
            }
        });

        // 选择历史图片
        $('.media-history-btn').on('click', function () {
            var targetId = $(this).data('target').replace('#', '');
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
        });

        // 清空按钮
        $('.upload-clear').on('click', function () {
            var target = $(this).data('target');
            $(target).val('').trigger('input');
        });

        form.on('submit(submit)', function(data){
            var field = data.field;
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 400){
                        return layer.msg(e.msg);
                    }
                    notifyParent('添加成功');
                    closeParentModal();
                    if (parent.table && parent.table.reload) {
                        parent.table.reload();
                    }
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false;
        });

        $('#btn-cancel').on('click', function(){
            closeParentModal();
        });

        var uploadInst = upload.render({
            elem: '#ID-upload-demo-btn',
            field: 'image',
            url: './article.php?action=upload_cover',
            before: function(obj){
                obj.preview(function(index, file, result){
                    updatePreview(result);
                });
                loadIndex = layer.load(2);
            },
            done: function(res){
                layer.close(loadIndex);
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                if(res.code == 0){
                    $('#sortimg').val(res.data);
                    updatePreview(res.data);
                }
            },
            error: function(){
                layer.close(loadIndex);
                layer.msg('上传失败，请重试');
            }
        });
    });
</script>
