// 添加新的键值对
$(document).on('click', '.add-btn', function() {
    const newKvItem = $('<div class="kv-item">')
        .append($('<div class="layui-input-inline"><input step="1" name="discount[number][]" type="number" class="key-input kv-input layui-input" placeholder="购买数量" /></div>'))
        .append($('<div class="layui-input-inline"><input step="0.01" name="discount[amount][]" type="number" class="value-input kv-input layui-input" placeholder="每一件优惠金额" /></div> '))
        .append($('<button type="button" class="layui-btn layui-bg-red kv-btn delete-btn btn danger btn-sm" title="删除"><i class="fa fa-trash-o"></i></button>'))
        .append($('<button type="button" class="layui-btn layui-bg-blue kv-btn add-btn btn primary btn-sm btn-add" title="添加"><i class="fa fa-plus"></i></button>'));

    // 添加动画效果
    newKvItem.hide().insertAfter($(this).closest('.kv-item')).fadeIn(300);
});

// 删除键值对
$(document).on('click', '.delete-btn', function() {
    const $kvItem = $(this).closest('.kv-item');
    // 确保至少保留一个键值对
    if ($('.kv-item').length > 1) {
        $kvItem.fadeOut(50, function() {
            $(this).remove();
        });
    } else {
        // 清空输入而不是删除最后一项
        $kvItem.find('.key-input').val('');
        $kvItem.find('.value-input').val('');
        // 提示效果
        $kvItem.addClass('border-danger');
        setTimeout(() => {
            $kvItem.removeClass('border-danger');
        }, 1000);
    }
});




// 监听类型单选按钮的change事件
$('input[name="type"]').change(function() {
    var type = $(this).val();
    if(type == 'post'){
        $('.post-type').show();
    }else{
        $('.post-type').hide();
    }
});

// 提交表单
$("#addgoods").submit(function (event) {
    event.preventDefault();
    if($('#pubPost').is(':disabled')){
        return false;
    }
    $('#pubPost').attr('disabled', 'disabled');
    $.ajax({
        type: "POST",
        url: $('#addgoods').attr('action'),
        data: $('#addgoods').serialize(),
        dataType: "json",
        success: function (e) {
            $('#pubPost').removeAttr('disabled');
            if(e.type == 'edit'){
                layer.confirm(e.msg + '商品已更新，请选择您接下来的操作！', {
                    title: '温馨提示',
                    btn: ['返回列表', '继续编辑'], // 自定义按钮文本
                    skin: 'layui-layer-primary' // 可选：指定墨绿主题
                }, function(index) {
                    window.location.href = 'goods.php';
                }, function(index) {
                    layer.close(index); // 关闭当前弹层
                });
            }
            if(e.type == 'add'){
                layer.confirm(e.msg + '商品已添加，请选择您接下来的操作！', {
                    title: '温馨提示',
                    btn: ['返回列表', '继续添加'], // 自定义按钮文本
                    skin: 'layui-layer-primary' // 可选：指定墨绿主题
                }, function(index) {
                    window.location.href = 'goods.php';
                }, function(index) {
                    layer.close(index); // 关闭当前弹层
                });
            }

        },
        error: function (xhr) {
            $('#pubPost').removeAttr('disabled');
            const errorMsg = JSON.parse(xhr.responseText).msg;
            zui.Messager.show({
                content: errorMsg,
                type: 'danger',
            });
        }
    });
});





$(function() {

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


    tinymce.init({
        selector: 'textarea.basic-example',
        language: 'zh_CN',
        height: 500,
        license_key: 'gpl',
        promotion: false,
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
        sandbox_iframes: false,
        images_upload_handler: example_image_upload_handler,

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
});
