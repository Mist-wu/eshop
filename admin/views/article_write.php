<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    html, body {
        height: 100%;
        margin: 0;
    }

    .article-form-shell {
        height: 100%;
        background: #f8fafb;
    }

    #form {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .article-form-container {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 20px;
    }

    .article-form-footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e6e6e6;
        padding: 12px 16px;
        text-align: center;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }

    .layui-form-item {
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: box-shadow 0.3s ease;
        margin-bottom: 16px;
    }

    .layui-form-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .layui-form-label {
        font-weight: 500;
        color: #374151;
    }

    .layui-input, .layui-textarea, .layui-select {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .layui-input:focus, .layui-textarea:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
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

    .form-tips {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }
</style>

<div class="article-form-shell">
    <form class="layui-form" id="form" method="post" action="article_save.php">
        <input type="hidden" name="ishide" id="ishide" value="<?= $hide ?>" />
        <input type="hidden" name="as_logid" id="as_logid" value="<?= $logid ?>" />
        <input type="hidden" name="gid" id="gid" value="<?= $logid ?>" />
        <input type="hidden" name="author" id="author" value="<?= $author ?>" />

        <div class="article-form-container">
            <div class="layui-form-item">
                <label class="layui-form-label">文章标题</label>
                <div class="layui-input-block">
                    <input type="text" name="title" class="layui-input" value="<?= $title ?>">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">文章内容</label>
                <div class="layui-input-block">
                    <textarea class="basic-example" name="content"><?= $content ?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">摘要（选填）</label>
                <div class="layui-input-block">
                    <textarea class="layui-textarea" name="logexcerpt"><?= $excerpt ?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">封面图</label>
                <div class="layui-input-block">
                    <div class="upload-field">
                        <div id="cover-preview-wrapper" class="upload-preview-thumb <?= empty($cover) ? 'placeholder' : '' ?>" title="点击预览封面图">
                            <img id="ID-upload-demo-img" src="<?= $cover ?>" alt="" style="display: <?= !empty($cover) ? 'block' : 'none' ?>;" />
                        </div>
                        <input type="text" value="<?= $cover ?>" placeholder="封面图" class="layui-input upload-input" name="cover" id="sortimg">
                        <div class="upload-actions">
                            <button type="button" class="layui-btn layui-btn-blue media-history-btn" data-target="#sortimg">选择</button>
                            <button type="button" id="ID-upload-demo-btn" class="layui-btn layui-btn-purple">上传图片</button>
                            <button type="button" class="layui-btn layui-btn-red upload-clear" data-target="#sortimg">清空</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">文章分类</label>
                <div class="layui-input-block">
                    <select name="sort">
                        <option value="">选择分类</option>
                        <?php
                        foreach ($sorts as $key => $value):
                            if ($value['pid'] != 0) {
                                continue;
                            }
                            $flg = $value['sid'] == $sortid ? 'selected' : '';
                            ?>
                            <option value="<?= $value['sid'] ?>" <?= $flg ?>><?= $value['sortname'] ?></option>
                            <?php
                            $children = $value['children'];
                            foreach ($children as $key):
                                $value = $sorts[$key];
                                $flg = $value['sid'] == $sortid ? 'selected' : '';
                                ?>
                                <option value="<?= $value['sid'] ?>" <?= $flg ?>>&nbsp; &nbsp; &nbsp; <?= $value['sortname'] ?></option>
                            <?php
                            endforeach;
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">标签</label>
                <div class="layui-input-block">
                    <input type="text" name="tag" class="layui-input" value="<?= $tagStr ?>">
                    <div class="form-tips">也用于页面关键词，英文逗号分隔</div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">跳转链接</label>
                <div class="layui-input-block">
                    <input type="text" name="link" class="layui-input" value="<?= $link ?>">
                    <div class="form-tips">填写后不展示文章内容直接跳转该地址</div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="allow_remark" title="允许评论" <?= $is_allow_remark ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="top" title="首页置顶" <?= $is_top ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="sortop" title="分类置顶" <?= $is_sortop ?>>
                </div>
            </div>
        </div>

        <!-- 底部按钮 -->
        <div class="article-form-footer">
            <button type="submit" class="layui-btn layui-btn-green" lay-submit lay-filter="submit">保存</button>
            <button type="button" class="layui-btn" id="btn-cancel">取消</button>
        </div>
    </form>
</div>


<script src="./tinymce/tinymce.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>


<script>
    layui.use(['form', 'laydate', 'util'], function(){
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;

        // 取消按钮关闭弹窗
        $('#btn-cancel').on('click', function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
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
                        return layer.msg(e.msg)
                    }
                    // 在父页面显示提示
                    parent.layer.msg(e.msg || '保存成功');
                    // 立即关闭弹窗
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                    // 刷新父页面表格
                    if (parent.window.table && parent.window.table.reload) {
                        parent.window.table.reload('index');
                    }
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false;
        });

        // 更新封面图预览
        function updateCoverPreview(url) {
            var $img = $('#ID-upload-demo-img');
            var $wrapper = $('#cover-preview-wrapper');

            if (url && url.trim()) {
                $img.attr('src', url).show();
                $wrapper.removeClass('placeholder').css('cursor', 'pointer');
            } else {
                $img.attr('src', '').hide();
                $wrapper.addClass('placeholder').css('cursor', 'default');
            }
        }

        $('#sortimg').on('input', function () {
            updateCoverPreview($(this).val());
        });

        // 点击封面图预览
        $('#cover-preview-wrapper').on('click', function () {
            var url = $('#sortimg').val();
            if (url && url.trim()) {
                layer.photos({
                    photos: {
                        "title": "文章封面预览",
                        "start": 0,
                        "data": [{
                            "alt": "文章封面",
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

        // 页面加载时初始化
        $(document).ready(function(){
            updateCoverPreview($('#sortimg').val());
        });

        var uploadInst = upload.render({
            elem: '#ID-upload-demo-btn',
            field: 'image',
            url: './article.php?action=upload_cover',
            before: function(obj){
                obj.preview(function(index, file, result){
                    $('#ID-upload-demo-img').attr('src', result);
                });
                loadIndex = layer.load(2);
            },
            done: function(res){
                layer.close(loadIndex);
                if(res.code == 400){
                    return layer.msg(res.msg)
                }
                if(res.code > 0){
                    return layer.msg('上传失败');
                }
                if(res.code == 0){
                    $('#sortimg').val(res.data).trigger('input');
                }
            },
            error: function(){
                layer.close(loadIndex);
                layer.msg('上传失败，请重试');
            }
        });
    })
</script>


<script>
    $("#alias").keyup(function() {
        checkalias();
    });
    $("#menu-blog").attr('class', 'admin-menu-item has-list in');
    $("#menu-blog .fa-angle-right").attr('class', 'fas arrow iconfont icon-you active');
    $("#menu-blog > .submenu").css('display', 'block');
    $('#menu-blog-list > a').attr('class', 'menu-link active')

    $(function(){

        const example_image_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '/admin/article.php?action=upload_cover2');
            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };
            xhr.onload = () => {
                if (xhr.status === 403) {
                    reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                    return;
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('HTTP Error: ' + xhr.status);
                    return;
                }
                const json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location != 'string') {
                    reject('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                resolve(json.location);
            };
            xhr.onerror = () => {
                reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
            };
            const formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        });

        // 编辑器
        tinymce.init({
            selector: 'textarea.basic-example',
            language: 'zh_CN',
            height: 500,
            promotion: false,
            images_upload_handler: example_image_upload_handler,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'wordcount', 'autosave'
            ],
            autosave_ask_before_unload: false,
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',

            // 添加初始化完成后的回调
            setup: function(editor) {
                editor.on('init', function() {
                    // 保存编辑器实例到全局变量
                    editorInstance = editor;
                    console.log('TinyMCE 初始化完成');
                });
                editor.on('input change undo redo cut paste', function() {
                    // 手动更新关联的文本域
                    editor.save();
                });
            }
        }).then(function(editors) {
            // 可选：Promise 方式获取编辑器实例
            if (editors && editors.length > 0) {
                editorInstance = editors[0];
            }
        }).catch(function(error) {
            console.error('TinyMCE 初始化失败:', error);
        });
    })



    // 文章编辑界面全局快捷键 Ctrl（Cmd）+ S 保存内容
    document.addEventListener('keydown', function(e) {
        if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
            autosave(2);
        }
    });

    // 显示插件扩展label
    const postBar = $("#post_bar");
    if (postBar.children().length === 0) {
        $("#post_bar_label").hide();
    }

    // 自定义字段
    $(document).on('click', '.field_del', function() {
        $(this).closest('.field_list').remove();
    });
    $(document).on('click', '.field_add', function() {
        var newField = `
                    <div class="form-row field_list">
                        <div class="col-sm-4">
                            <input type="text" name="field_keys[]" list="customFieldList" value="" id="field_keys" class="form-control" placeholder="字段名称" maxlength="120" required>
                            <datalist id="customFieldList">
                                <?php foreach ($customFields as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $k . '【' . $v['name'] . '】' . $v['description'] ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-sm-6 mx-sm-3">
                            <input type="text" name="field_values[]" value="" id="field_values" class="form-control" placeholder="字段值" required>
                        </div>
                        <div class="col-auto mt-1">
                            <button type="button" class="btn btn-outline-danger field_del">删除</button>
                        </div>
                    </div>
                `;
        $('#field_box').append(newField);
    });

    // 高级选项展开状态
    initDisplayState('adv_set');
    // 自动截取摘要状态
    initCheckboxState('auto_excerpt');
    // 自动提取封面状态
    initCheckboxState('auto_cover');
</script>
