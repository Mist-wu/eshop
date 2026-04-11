<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    body{
        overflow: hidden;
    }
</style>
<form class="layui-form" id="form" method="post" action="page.php?action=save">
    <div style="padding: 25px;" id="open-box">
        <!-- 基本信息设置 -->
        <div class="form-section">

            <div class="layui-form-item">
                <label class="layui-form-label" for="password">标题</label>
                <div class="layui-input-block">
                    <input type="text" name="title" id="title" value="<?= $title ?>" class="layui-input" placeholder="页面标题"/>
                </div>
                <a href="#mediaModal" data-toggle="modal" data-target="#mediaModal"><i class="icofont-plus"></i>资源媒体库</a>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label" for="one_text">内容</label>
                <div class="layui-input-block">
                    <textarea id="basic-example" name="pagecontent"><?= $content ?></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label" for="password">模板</label>
                <div class="layui-input-block">
                    <input class="layui-input" id="template" name="template" value="<?= $template ?>">
                    <small class="form-text text-muted">用于自定义页面模板，对应模板目录下xxx.php文件，xxx即为模板名，可不填</small>
                </div>
            </div>



        </div>

    </div>

    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <input type="hidden" name="ishide" id="ishide" value="<?= $hide ?>"/>
            <input type="hidden" name="pageid" value="<?= $pageId ?>"/>
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">保存配置</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>




<!--资源库-->
<div class="" id="mediaModal" style="display: none;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">资源媒体库</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between">
                    <div><a href="#" id="mediaAdd" class="btn btn-sm btn-success shadow-sm mb-3">上传图片/文件</a></div>
                    <div>
                        <?php if (User::haveEditPermission() && $mediaSorts): ?>
                            <select class="form-control" id="media-sort-select">
                                <option value="">选择资源分类…</option>
                                <?php foreach ($mediaSorts as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= $v['sortname'] ?></option>
                                <?php endforeach ?>
                            </select>
                        <?php endif ?>
                    </div>
                </div>
                <form action="media.php?action=operate_media" method="post" name="form_media" id="form_media">
                    <div class="row" id="image-list"></div>
                    <div class="text-center">
                        <button type="button" class="btn btn-success btn-sm mt-2" id="load-more">加载更多…</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- 封面图裁剪 -->
<div class="" id="modal" style="display: none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">上传封面</h5>
                <button type="button" class="close close-modal-btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="img-container">
                    <div class="row">
                        <div class="col-md-11">
                            <img src="" id="sample_image"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>按住 Shift 等比例调整裁剪区域</div>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" id="crop" class="btn btn-sm btn-success">保存</button>
                    <button type="button" id="use_original_image" class="btn btn-sm btn-google">使用原图</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="dropzone-previews" style="display: none;"></div>
<script src="./views/js/dropzone.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
<script src="./views/js/media-lib.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
<script src="./tinymce/tinymce.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>


<script>

    layui.use(['table'], function(){
        var $ = layui.$;
        var form = layui.form;
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
                    parent.layer.msg('操作成功');
                    window.parent.table.reload();
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });



    })


</script>



<script>

    function insert_media_img(fileurl,thumburl){
        // 检查编辑器是否已经初始化
        if (!editorInstance) {
            console.error('TinyMCE 编辑器尚未初始化');
            return;
        }
        // 正确的内容插入方式
        editorInstance.insertContent(`<img src="${fileurl}" alt="插入的图片" />`);
    };

    function insert_media_video(fileurl) {
        editorInstance.insertContent('<video class=\"video-js\" controls preload=\"auto\" width=\"100%\" data-setup=\'{"aspectRatio":"16:9"}\'> <source src="' + fileurl + '" type=\'video/mp4\' > </video>');
    }

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

    $(function() {
        tinymce.init({
            selector: 'textarea#basic-example',
            language: 'zh_CN',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'wordcount', 'autosave'
            ],
            images_upload_handler: example_image_upload_handler,
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


        var maxHeight = $(window.parent).innerHeight() * 0.75;



        // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
        $("#open-box").css({
            "max-height": maxHeight + "px", // 单位必须加 px
            "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
        });

    });
</script>


<script>
    $("#menu_category_view").addClass('active');
    $("#menu-appearance").attr('class', 'admin-menu-item has-list in');
    $("#menu-appearance .fa-angle-right").attr('class', 'fas arrow iconfont icon-you active');
    $("#menu-appearance > .submenu").css('display', 'block');

    $("#alias").keyup(function () {
        checkalias();
    });
    $("#title").focus(function () {
        $("#title_label").hide();
    });
    $("#title").blur(function () {
        if ($("#title").val() == '') {
            $("#title_label").show();
        }
    });
    if ($("#title").val() != '') $("#title_label").hide();



    // 封面图
    $(function () {
        var $modal = $('#modal');
        var image = document.getElementById('sample_image');
        var cropper;
        $('#upload_img').change(function (event) {
            var files = event.target.files;
            var done = function (url) {
                image.src = url;
                zui.Modal.open({id:'modal'})
            };
            if (files && files.length > 0) {
                if (!files[0].type.startsWith('image')) {
                    alert('只能上传图片');
                    return;
                }
                reader = new FileReader();
                reader.onload = function (event) {
                    done(reader.result);
                };
                reader.readAsDataURL(files[0]);
            }
        });
        $modal.on('shown.bs.modal', function () {
            cropper = new Cropper(image, {
                aspectRatio: NaN,
                viewMode: 1
            });
        }).on('hidden.bs.modal', function () {
            cropper.destroy();
            cropper = null;
        });

        // 上传图片
        function uploadImage(blob, filename) {
            var formData = new FormData();
            formData.append('image', blob, filename);
            $.ajax('./article.php?action=upload_cover', {
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    $('.close-modal-btn').click()
                    if (data.code == 0) {
                        $('#cover_image').attr('src', data.data);
                        $('#cover').val(data.data);
                        $('#cover_rm').show();
                    } else {
                        alert(data.msg);
                    }
                },
                error: function (xhr) {
                    var data = xhr.responseJSON;
                    if (data && typeof data === "object") {
                        alert(data.msg);
                    } else {
                        alert("上传封面出错了");
                    }
                }
            });
        }

        $('#crop').click(function () {
            canvas = cropper.getCroppedCanvas({
                width: 650,
                height: 366
            });
            canvas.toBlob(function (blob) {
                uploadImage(blob, 'cover.jpg')
            });
        });

        $('#use_original_image').click(function () {
            var blob = $('#upload_img')[0].files[0];
            uploadImage(blob, blob.name)
        });

        $('#cover_rm').click(function () {
            $('#cover_image').attr('src', "./views/images/cover.svg");
            $('#cover').val("");
            $('#cover_rm').hide();
        });
    });

    $('#cover').blur(function () {
            c = $('#cover').val();
            if (!c) {
                $('#cover_image').attr('src', "./views/images/cover.svg");
                $('#cover_rm').hide();
                return
            }
            $('#cover_image').attr('src', c);
            $('#cover_rm').show();
        }
    );

    // 显示插件扩展label
    const postBar = $("#post_bar");
    var a = postBar.children()
    console.log(a)
    if (postBar.children().length === 0) {
        $("#post_bar_label").hide();
    }


</script>
