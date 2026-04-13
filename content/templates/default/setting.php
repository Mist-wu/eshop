<?php
defined('EM_ROOT') || exit('access denied!');



function plugin_setting_view() {
    $tpl = TplOptions::getInstance();
    $data = $tpl->getTemplateOptions('default');
//    d($data);die;
    $data['favicon'] = empty($data['favicon']) ? '' : $data['favicon'];
    $data['home_bulletin'] = isset($data['home_bulletin']) ? $data['home_bulletin'] : Option::get('home_bulletin');
    $data['pay_type'] = empty($data['pay_type']) ? 2 : $data['pay_type'];
    $data['sales_show'] = empty($data['sales_show']) ? 'y' : $data['sales_show'];
    $data['stock_show'] = empty($data['stock_show']) ? 'y' : $data['stock_show'];
    $data['search_show'] = empty($data['search_show']) ? 'y' : $data['search_show'];
    $data['category_show'] = empty($data['category_show']) ? 'y' : $data['category_show'];
    ?>
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
        }



    </style>
    <form class="layui-form" id="form" method="post" action="?tpl=default&action=setting_ajax">
        <div style="padding: 25px;" id="open-box">
            <!-- 基本信息设置 -->
            <div class="form-section">

                <div class="layui-form-item">
                    <label class="layui-form-label">浏览器图标</label>
                    <div class="layui-input-block">
                        <div class="layui-input-group" style="width: 100%; display: flex;">
                            <input type="text" value="<?= $data['favicon'] ?>" placeholder="" class="layui-input" name="favicon" id="sortimg">
                            <div id="ID-upload-demo-btn" class="layui-input-split layui-input-suffix layui-btn" style="display: table-cell; line-height: 192%;">
                                上传图片
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">内容公告</label>
                    <div class="layui-input-block">
                        <textarea id="home_bulletin" name="home_bulletin"><?= $data['home_bulletin'] ?></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label" for="sales_show">显示销量</label>
                    <div class="layui-input-block">
                        <input type="radio" name="sales_show" value="y" <?= isset($data['sales_show']) && $data['sales_show'] == 'y' ? 'checked' : (empty($data['sales_show']) ? 'checked' : '') ?> title="显示">
                        <input type="radio" name="sales_show" value="n" <?= isset($data['sales_show']) && $data['sales_show'] == 'n' ? 'checked' : '' ?> title="隐藏">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label" for="stock_show">显示库存</label>
                    <div class="layui-input-block">
                        <input type="radio" name="stock_show" value="y" <?= isset($data['stock_show']) && $data['stock_show'] == 'y' ? 'checked' : (empty($data['stock_show']) ? 'checked' : '') ?> title="显示">
                        <input type="radio" name="stock_show" value="n" <?= isset($data['stock_show']) && $data['stock_show'] == 'n' ? 'checked' : '' ?> title="隐藏">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label" for="search_show">显示搜索框</label>
                    <div class="layui-input-block">
                        <input type="radio" name="search_show" value="y" <?= isset($data['search_show']) && $data['search_show'] == 'y' ? 'checked' : (empty($data['search_show']) ? 'checked' : '') ?> title="显示">
                        <input type="radio" name="search_show" value="n" <?= isset($data['search_show']) && $data['search_show'] == 'n' ? 'checked' : '' ?> title="隐藏">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label" for="category_show">显示商品分类</label>
                    <div class="layui-input-block">
                        <input type="radio" name="category_show" value="y" <?= isset($data['category_show']) && $data['category_show'] == 'y' ? 'checked' : (empty($data['category_show']) ? 'checked' : '') ?> title="显示">
                        <input type="radio" name="category_show" value="n" <?= isset($data['category_show']) && $data['category_show'] == 'n' ? 'checked' : '' ?> title="隐藏">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">手机端支付方式布局</label>

                    <div class="layui-input-block">
                        <input type="radio" name="pay_type" value="1" <?= $data['pay_type'] == 1 ? 'checked' : '' ?> title="一行一个">
                        <input type="radio" name="pay_type" value="2" <?= $data['pay_type'] == 2 ? 'checked' : '' ?> title="一行两个">
                    </div>

                </div>



            </div>

        </div>

        <div style="width: 100%; height: 50px;"></div>
        <div class="" id="form-btn">
            <div class="layui-input-block" style="margin: 0 auto;">
                <button type="submit" class="layui-btn" lay-submit lay-filter="submit">保存配置</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </form>
    <script src="./tinymce/tinymce.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
    <script>

        layui.use(['table'], function(){
            var $ = layui.$;
            var form = layui.form;
            var upload = layui.upload;
            var element = layui.element;
            form.on('submit(submit)', function(data){
                if (window.tinymce) {
                    tinymce.triggerSave();
                    data.field.home_bulletin = $('#home_bulletin').val();
                }
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
                        parent.layer.close('setting')
                        parent.layer.msg('已保存配置');
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
                    if(res.code == 400){
                        return layer.msg(res.msg)
                    }
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
            selector: 'textarea#home_bulletin',
            language: 'zh_CN',
            height: 320,
            promotion: false,
            images_upload_handler: example_image_upload_handler,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'wordcount', 'autosave'
            ],
            paste_webkit_styles: true,
            valid_children : '+div[style]',
            autosave_ask_before_unload: false,
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            setup: function(editor) {
                editor.on('input change undo redo cut paste', function() {
                    editor.save();
                });
            }
        });











        var maxHeight = $(window.parent).innerHeight() * 0.75;



        // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
        $("#open-box").css({
            "max-height": maxHeight + "px", // 单位必须加 px
            "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
        });

    </script>
<?php }

function plugin_setting($tpl) {
    $favicon = Input::postStrVar('favicon');
    // 富文本不要先 addslashes，否则 style 等属性会被重复转义
    $home_bulletin = isset($_POST['home_bulletin']) ? $_POST['home_bulletin'] : '';
    $pay_type = Input::postIntVar('pay_type', 2);
    $sales_show = Input::postStrVar('sales_show', 'y'); // 默认显示
    $stock_show = Input::postStrVar('stock_show', 'y'); // 默认显示
    $search_show = Input::postStrVar('search_show', 'y'); // 默认显示
    $category_show = Input::postStrVar('category_show', 'y'); // 默认显示
    $tplOptions = TplOptions::getInstance();


    $data = [
        [
            'template' => $tpl,
            'name'     => 'favicon',
            'depend'   => '',
            'data'     => serialize($favicon),
        ], [
            'template' => $tpl,
            'name'     => 'home_bulletin',
            'depend'   => '',
            'data'     => serialize($home_bulletin),
        ], [
            'template' => $tpl,
            'name'     => 'pay_type',
            'depend'   => '',
            'data'     => serialize($pay_type),
        ], [
            'template' => $tpl,
            'name'     => 'sales_show',
            'depend'   => '',
            'data'     => serialize($sales_show),
        ], [
            'template' => $tpl,
            'name'     => 'stock_show',
            'depend'   => '',
            'data'     => serialize($stock_show),
        ], [
            'template' => $tpl,
            'name'     => 'search_show',
            'depend'   => '',
            'data'     => serialize($search_show),
        ], [
            'template' => $tpl,
            'name'     => 'category_show',
            'depend'   => '',
            'data'     => serialize($category_show),
        ]
    ];

    $tplOptions->insert('data', $data, true);

    Output::ok();
}
