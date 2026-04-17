function getChecked(node) {
    let re = false;
    $('input.' + node).each(function (i) {
        if (this.checked) {
            re = true;
        }
    });
    return re;
}

function timestamp() {
    return new Date().getTime();
}

function delArticle(msg, text, url, token) {
    layer.confirm(text, {
        title: msg,
        icon: 3,
        btn: ['<span class="">删除</span>', '取消']
    }, function (index) {
        localStorage.setItem('alert_action_success', '删除');
        window.location = url + '&rm=1&token=' + token;
        layer.close(index);
    }, function (index) {
        layer.close(index);
    });
}

function initDisplayState(id) {
    const storedState = localStorage.getItem('em_' + id);
    const element = $("#" + id);
    const iconElement = element.prev().find(".icofont-simple-down, .icofont-simple-right");

    if (storedState) {
        const isVisible = storedState === "down";
        element.toggle(isVisible);
        iconElement.attr("class", isVisible ? "icofont-simple-down" : "icofont-simple-right");
    }
}
function initCheckboxState(id) {
    const isChecked = localStorage.getItem(id) === 'true';
    $('#' + id).prop('checked', isChecked);
}
function eb_confirm(id, property, token) {
    let url;
    let msg = '';
    let text = ''


    switch (property) {
        case 'article':
            url = 'article.php?action=del&gid=' + id;
            text = '删除这篇文章？';
            delArticle(msg, text, url, token)
            break;
        case 'stock':
            url = 'stock.php?action=del&id=' + id;
            text = '确定要删除这个库存吗?';
            delStock(msg, text, url, token)
            break;
        case 'goods':

            url = 'goods.php?action=del&id=' + id;
            text = '删除这件商品？';
            delProduct(msg, text, url, token)
            break;
        case 'attr_value':
            url = 'sku.php?action=delAttrValue&id=' + id;
            text = '确定要删除这个规格吗？';
            delAttrValue(msg, text, url, token)
            break;
        case 'draft':
            url = 'article.php?action=del&draft=1&gid=' + id;
            text = '删除这篇草稿？';
            delAlert(msg, text, url, token)
            break;
        case 'tw':
            url = 'twitter.php?action=del&id=' + id;
            text = '删除这条微语？';
            delAlert(msg, text, url, token)
            break;
        case 'comment':
            url = 'comment.php?action=del&id=' + id;
            text = '删除这条评论？';
            delAlert(msg, text, url, token)
            break;
        case 'commentbyip':
            url = 'comment.php?action=delbyip&ip=' + id;
            text = '删除来自该IP的所有评论？';
            delAlert(msg, text, url, token)
            break;
        case 'link':
            url = 'link.php?action=del&linkid=' + id;
            text = '删除该链接？';
            delAlert(msg, text, url, token)
            break;
        case 'navi':
            url = 'navbar.php?action=del&id=' + id;
            text = '删除该导航？';
            delAlert(msg, text, url, token)
            break;
        case 'media':
            url = 'media.php?action=delete&aid=' + id;
            text = '删除该文件？';
            delAlert(msg, text, url, token)
            break;
        case 'avatar':
            url = 'blogger.php?action=delicon';
            text = '删除头像？';
            delAlert(msg, text, url, token)
            break;
        case 'sort':
            url = 'sort.php?action=del&sid=' + id;
            text = '删除该分类？';
            delAlert(msg, text, url, token)
            break;
        case 'del_user':
            url = 'user.php?action=del&uid=' + id;
            text = '删除该用户？';
            delAlert(msg, text, url, token)
            break;
        case 'forbid_user':
            url = 'user.php?action=forbid&uid=' + id;
            text = '禁用该用户？';
            delAlert(msg, text, url, token, '禁用')
            break;
        case 'tpl':
            url = 'template.php?action=del&tpl=' + id;
            text = '删除该模板？';
            delAlert(msg, text, url, token)
            break;
        case 'reset_widget':
            url = 'widgets.php?action=reset';
            text = '重置组件？重置会丢失自定义的组件';
            delAlert(msg, text, url, token, '重置')
            break;
        case 'media_sort':
            url = 'media.php?action=del_media_sort&id=' + id;
            text = '删除该资源分类？不会删除分类下资源文件';
            delAlert(msg, text, url, token)
            break;
    }
}

function infoAlert(msg) {
    layer.alert(msg, {
        icon: 2,
        shadeClose: true,
        title: '',
    });
}

function delAlert(msg, text, url, token, btnText = '删除') {
    // icon: 0 default, 1 ok, 2 err, 3 ask
    layer.confirm(text, {icon: 3, title: msg, skin: 'class-layer-danger', btn: [btnText, '取消']}, function (index) {
        window.location = url + '&token=' + token;
        layer.close(index);
    });
}

function delAlert2(msg, text, actionClosure, btnText = '删除') {
    layer.confirm(text, {icon: 3, title: msg, skin: 'class-layer-danger', btn: [btnText, '取消']}, function (index) {
        actionClosure(); // 执行闭包
        layer.close(index);
    });
}



function delProduct(msg, text, url, token) {



    layer.confirm(text, {icon: 3, title: msg, skin: 'class-layer-danger', btn: ['删除', '取消']}, function (index) {

        if(demo == 1){
            layer.msg('演示站点无法进行该操作！');
            return false;
        }

        window.location = url + '&rm=1&token=' + token;
        layer.close(index);
    });

}

function delAttrValue(msg, text, url, token) {
    layer.confirm(text, {icon: 3, title: msg, skin: 'class-layer-danger', btn: ['删除', '取消']}, function (index) {
        window.location = url + '&rm=1&token=' + token;
        layer.close(index);
    });
}




function submitForm(formId, successMsg) {
    $.ajax({
        type: "POST",
        url: $(formId).attr('action'),
        data: $(formId).serialize(),
        success: function (e) {
            if(e.code == 400){
                return layer.msg(e.msg)
            }
            layer.msg(successMsg || '保存成功')
        },
        error: function (xhr) {
            const errorMsg = JSON.parse(xhr.responseText).msg;
            layer.msg(errorMsg)
        }
    });
}

function focusEle(id) {
    try {
        document.getElementById(id).focus();
    } catch (e) {
    }
}

function hideActived() {
    $(".alert-success").slideUp(300);
    $(".alert-danger").slideUp(300);
}

// Click action of [More Options] 
let icon_mod = "down";

function displayToggle(id) {
    $("#" + id).toggle();
    if (icon_mod === "down") {
        icon_mod = "right";
        $(".icofont-simple-down").attr("class", "icofont-simple-right");
    } else {
        icon_mod = "down";
        $(".icofont-simple-right").attr("class", "icofont-simple-down");
    }

    // 使用本地存储来保存状态
    localStorage.setItem('em_' + id, icon_mod);
}

function applyStoredState(id) {
    let storedState = localStorage.getItem('em_' + id);
    if (storedState) {
        icon_mod = storedState;
        if (icon_mod === "right") {
            $("#" + id).hide();
            $(".icofont-simple-down").attr("class", "icofont-simple-right");
        } else {
            $("#" + id).show();
            $(".icofont-simple-right").attr("class", "icofont-simple-down");
        }
    }
}

function doToggle(id) {
    $("#" + id).toggle();
}

function insertTag(tag, boxId) {
    var targetinput = $("#" + boxId).val();
    if (targetinput == '') {
        targetinput += tag;
    } else {
        var n = ',' + tag;
        targetinput += n;
    }
    $("#" + boxId).val(targetinput);
    if (boxId == "tag") $("#tag_label").hide();
}

function isalias(a) {
    var reg1 = /^[\w-]*$/;
    var reg2 = /^\d+$/;
    var reg3 = /^post(-\d+)?$/;
    if (!reg1.test(a)) {
        return 1;
    } else if (reg2.test(a)) {
        return 2;
    } else if (reg3.test(a)) {
        return 3;
    } else if (a === 't' || a === 'm' || a === 'admin') {
        return 4;
    } else {
        return 0;
    }
}

function checkform() {
    var a = $.trim($("#alias").val());
    var t = $.trim($("#title").val());
    if (typeof productTextRecord !== "undefined") {  // 提交时，重置原文本记录值，防止出现离开提示
        productTextRecord = $("textarea[name=product_content]").text();
    } else {
        pageText = $("textarea").text();
    }

    if (0 == isalias(a)) {
        return true;
    } else {
        infoAlert("链接别名错误");
        $("#alias").focus();
        return false;
    }
}

function checkalias() {
    var a = $.trim($("#alias").val());
    if (1 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，应由字母、数字、下划线、短横线组成</span>');
    } else if (2 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，不能为纯数字</span>');
    } else if (3 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，不能为\'post\'或\'post-数字\'</span>');
    } else if (4 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，与系统链接冲突</span>');
    } else {
        $("#alias_msg_hook").html('');
        $("#msg").html('');
    }
}



// “页面”的 editor.md 编辑器 Ctrl + S 快捷键的自动保存动作
const pagetitle = $('title').text();

function pagesave() {
    document.addEventListener('keydown', function (e) {  // 阻止自动保存产生的浏览器默认动作
        if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
        }
    });
    let url = "page.php?action=save";
    if ($("[name='pageid']").attr("value") < 0) return infoAlert("请先发布页面！");
    if (!$("[name='pagecontent']").html()) return infoAlert("页面内容不能为空！");
    $('title').text('[保存中...] ' + pagetitle);
    $.post(url, $("#addproduct").serialize(), function (data) {
        $('title').text('[保存成功] ' + pagetitle);
        setTimeout(function () {
            $('title').text(pagetitle);
        }, 2000);
        pageText = $("textarea").text();
    }).fail(function () {
        $('title').text('[保存失败] ' + pagetitle);
        infoAlert("保存失败！")
    });
}

// toggle plugin
$.fn.toggleClick = function () {
    var functions = arguments;
    return this.click(function () {
        var iteration = $(this).data('iteration') || 0;
        functions[iteration].apply(this, arguments);
        iteration = (iteration + 1) % functions.length;
        $(this).data('iteration', iteration);
    });
};

function removeHTMLTag(str) {
    str = str.replace(/<\/?[^>]*>/g, ''); //去除HTML tag
    str = str.replace(/[ | ]*\n/g, '\n'); //去除行尾空白
    str = str.replace(/ /ig, '');
    return str;
}

// editor.md 的 js 钩子
var queue = new Array();
var hooks = {
    addAction: function (hook, func) {
        if (typeof (queue[hook]) == "undefined" || queue[hook] == null) {
            queue[hook] = new Array();
        }
        if (typeof func == 'function') {
            queue[hook].push(func);
        }
    },
    doAction: function (hook, obj) {
        try {
            for (var i = 0; i < queue[hook].length; i++) {
                queue[hook][i](obj);
            }
        } catch (e) {
        }
    }
}

// 粘贴上传图片函数
function imgPasteExpand(thisEditor) {
    var listenObj = document.querySelector("textarea").parentNode  // 要监听的对象
    var postUrl = './media.php?action=upload';  // emlog 的图片上传地址
    var emMediaPhpUrl = "./media.php?action=lib";  // emlog 的资源库地址,用于异步获取上传后的图片数据

    // 通过动态配置只读模式,阻止编辑器原有的粘贴动作发生,并恢复光标位置
    function preventEditorPaste() {
        let l = thisEditor.getCursor().line;
        let c = thisEditor.getCursor().ch - 3;

        thisEditor.config({readOnly: true,});
        thisEditor.config({readOnly: false,});
        thisEditor.setCursor({line: l, ch: c});
    }

    // 编辑器通过光标处位置前几位来替换文字
    function replaceByNum(text, num) {
        let l = thisEditor.getCursor().line;
        let c = thisEditor.getCursor().ch;

        thisEditor.setSelection({line: l, ch: (c - num)}, {line: l, ch: c});
        thisEditor.replaceSelection(text);
    }

    // 粘贴事件触发
    listenObj.addEventListener("paste", function (e) {
        if ($('.editormd-dialog').css('display') == 'block') return;  // 如果编辑器有对话框则退出
        if (!(e.clipboardData && e.clipboardData.items)) return;

        var pasteData = e.clipboardData || window.clipboardData; // 获取剪切板里的全部内容
        pasteAnalyseResult = new Array;  // 用于储存遍历分析后的结果

        for (var i = 0; i < pasteData.items.length; i++) {  // 遍历分析剪切板里的数据
            var item = pasteData.items[i];

            if ((item.kind == "file") && (item.type.match('^image/'))) {
                var imgData = item.getAsFile();
                if (imgData.size === 0) return;
                pasteAnalyseResult['type'] = 'img';
                pasteAnalyseResult['data'] = imgData;
                break;  // 当粘贴板中有图片存在时,跳出循环
            }
            ;
        }

        if (pasteAnalyseResult['type'] == 'img') {  // 如果剪切板中有图片,上传图片
            preventEditorPaste();
            uploadImg(pasteAnalyseResult['data']);
            return;
        }
    }, false);

    // 上传图片
    function uploadImg(img) {
        var formData = new FormData();
        var imgName = "粘贴上传" + new Date().getTime() + "." + img.name.split(".").pop();

        formData.append('file', img, imgName);
        thisEditor.insertValue("上传中...");
        $.ajax({
            url: postUrl, type: 'post', data: formData, processData: false, contentType: false, xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    thisEditor.insertValue("....");
                    xhr.upload.addEventListener('progress', function (e) {  // 用以显示上传进度
                        console.log('进度(byte)：' + e.loaded + ' / ' + e.total);
                        let percent = Math.floor(e.loaded / e.total * 100);
                        if (percent < 10) {
                            replaceByNum('..' + percent + '%', 4);
                        } else if (percent < 100) {
                            replaceByNum('.' + percent + '%', 4);
                        } else {
                            replaceByNum(percent + '%', 4);
                        }
                    }, false);
                }
                return xhr;
            }, success: function (result) {
                console.log('上传成功！正在获取结果...');
                $.get(emMediaPhpUrl, function (resp) {
                    var image = resp.data.images[0];
                    if (image) {
                        console.log('获取结果成功！')
                        replaceByNum(`[![](${image.media_icon})](${image.media_url})`, 10);  // 这里的数字 10 对应着’上传中...100%‘是10个字符
                    } else {
                        console.log('获取结果失败！')
                        infoAlert('获取结果失败！');
                    }
                })
            }, error: function (result) {
                infoAlert('上传失败,图片类型错误或网络错误');
                replaceByNum('上传失败,图片类型错误或网络错误', 6);
            }
        })
    }
}

// 把粘贴上传图片函数，挂载到位于文章编辑器、页面编辑器处的 js 钩子处
hooks.addAction("loaded", imgPasteExpand);
hooks.addAction("page_loaded", imgPasteExpand);





$(function () {
    // 设置界面: 自动检测站点地址 如果设置“自动检测地址”，则设置 input 为只读，以表示该项是无效的
    if ($("#detect_url").prop("checked")) {
        $("[name=blogurl]").attr("readonly", "readonly")
        $("[name=blogurl]").addClass('readonly')
    }
    $("#detect_url").on("click", function () {
        if ($(this).prop("checked")) {
            $("[name=blogurl]").attr("readonly", "readonly")
            $("[name=blogurl]").addClass('readonly')
        } else {
            $("[name=blogurl]").removeAttr("readonly")
            $("[name=blogurl]").removeClass('readonly')
        }
    })

    // 复选框全选
    $('#checkAllItem').on("click", function () {
        let cardCheckboxes = $('.checkboxContainer').find('input[type=checkbox]');
        cardCheckboxes.prop('checked', $(this).prop('checked'));
    });

    $('.checkboxContainer').find('input[type=checkbox]').on("click", function () {
        let allChecked = true;
        $('.checkboxContainer').find('input[type=checkbox]').each(function () {
            if (!$(this).prop('checked')) {
                allChecked = false;
                return false;
            }
        });
        $('#checkAllItem').prop('checked', allChecked);
    });



    // 应用商店：查看应用信息
    $('#appModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var name = button.data('name');
        var url = button.data('url');
        var buy_url = button.data('buy-url');
        var modal = $(this);

        modal.find('.modal-body').empty();
        modal.find('.modal-title').html(name);
        modal.find('.modal-buy-url').attr('href', buy_url);

        var loadingSpinner = '<div class="spinner-border text-primary ml-3"><span class="sr-only">Loading...</span></div>';
        modal.find('.modal-title').append(loadingSpinner);

        var iframe = $('<iframe>', {
            'class': 'iframe-content',
            'src': url,
            'frameborder': 0
        });

        iframe.on('load', function () {
            $('.spinner-border').remove();
        });

        modal.find('.modal-body').append(iframe);
    });


    // 全选/取消全选
    $('#selectAll').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.select-item').prop('checked', isChecked);
        updateBatchDeleteButton();
    });

    // 单个选择框变化时
    $('.select-item').on('change', function() {
        // 如果所有单选框都被选中，则全选框也被选中
        var allChecked = $('.select-item:checked').length === $('.select-item').length;
        $('#selectAll').prop('checked', allChecked);

        // 如果有任何单选框被选中，则部分选中
        var anyChecked = $('.select-item:checked').length > 0;
        if (anyChecked && !allChecked) {
            $('#selectAll').prop('indeterminate', true);
        } else {
            $('#selectAll').prop('indeterminate', false);
        }

        updateBatchDeleteButton();
    });

    // 更新批量删除按钮状态
    function updateBatchDeleteButton() {
        var selectedCount = $('.select-item:checked').length;
        $('#batchDeleteBtn').prop('disabled', selectedCount === 0);
    }



})
