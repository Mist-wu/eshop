<?php
defined('EM_ROOT') || exit('access denied!');
?>

<!-- 记得引入Clipboard.js库 -->

<style>
    .container {
        max-width: 1200px;
        padding: 20px 0;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border: none;
        margin-bottom: 20px;
    }

    .card:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 15px 20px;
        border-radius: 10px 10px 0 0 !important;
        font-weight: 600;
        font-size: 16px;
    }

    /* 卡密列表样式 */
    .kami-list {
        padding: 15px;
    }

    .kami-item {
        padding: 15px;
        margin-bottom: 12px;
        background-color: #fff;
        border-radius: 8px;
        border: 1px solid #eee;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
    }

    .kami-item:hover {
        border-color: #4d6bfe;
        background-color: #f9fbff;
    }

    .kami-index {
        min-width: 36px;
        height: 36px;
        line-height: 36px;
        text-align: center;
        background-color: #eef2ff;
        color: #4d6bfe;
        border-radius: 6px;
        font-weight: 600;
        margin-right: 15px;
        flex-shrink: 0;
        user-select: none;
    }

    .kami-content {
        flex-grow: 1;
        word-break: break-all;
        padding: 5px 0;
        font-family: 'Courier New', monospace;
        color: #333;
    }

    .kami-actions {
        margin-left: 15px;
        flex-shrink: 0;
    }

    /* 复制按钮样式 */
    .copy-btn {
        padding: 5px 12px;
        font-size: 13px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .copy-btn:hover {
        background-color: #4d6bfe;
        color: white;
    }

    .copy-btn.copied {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    /* 使用说明样式 */
    .instructions {
        padding: 20px;
        line-height: 1.8;
        color: #555;
    }

    /* 下载按钮样式 */
    .download-btn {
        transition: all 0.2s ease;
    }

    .download-btn:hover {
        transform: translateY(-2px);
    }

    /* 复制全部按钮样式 */
    .copy-all-btn {
        margin-right: 10px;
        transition: all 0.2s ease;
    }

    .copy-all-btn:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 576px) {
        .kami-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .kami-index {
            /*margin-bottom: 10px;*/
        }

        .kami-actions {
            margin-left: 0;
            /*margin-top: 10px;*/
            align-self: flex-end;
        }

        .card-header-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .copy-all-btn {
            margin-right: 0;
        }
    }
    p{
        margin-bottom: 0;
    }
    .container{
        padding: 0;
    }
</style>

<div class="container" style="margin-top: -30px;">
    <!-- 保持原有HTML结构不变 -->
    <?php if(!empty($goods['pay_content'])): ?>
        <div class="card">
            <div class="card-header">产品使用说明</div>
            <div class="instructions"><?= $goods['pay_content'] ?></div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><?= $goods['type'] == 'xuni' ? '发货信息' : '卡密信息' ?></span>
            <?php if($goods['type'] != 'xuni'): ?>
            <div class="card-header-actions">
                <button id="copyAllBtn" class="copy-all-btn btn btn-sm btn-primary">
                    <i class="fas fa-copy mr-1"></i>复制全部卡密
                </button>
                <a href="order.php?order_list_id=<?= $id ?>&action=download" class="btn btn-sm btn-info download-btn">
                    <i class="fas fa-download mr-1"></i> 下载全部<?= $total ?>个卡密
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php if($goods['type'] == 'xuni'): ?>
        <div class="kami-list">
            <div class="kami-item">
<!--                <div class="kami-index"></div>-->
                <div class="kami-content" id="kami-0"><?= $order['device'] ?></div>
                <div class="kami-actions">
                    <button class="copy-btn btn btn-sm btn-outline-secondary" data-clipboard-target="#kami-0">
                        <i class="fas fa-copy mr-1"></i>复制
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="kami-list">
                <?php foreach($data as $key => $val): ?>
                    <div class="kami-item">
                        <div class="kami-index"><?= $key + 1 ?></div>
                        <div class="kami-content" id="kami-<?= $key ?>"><?= $val['content'] ?></div>
                        <div class="kami-actions">
                            <button class="copy-btn btn btn-sm btn-outline-secondary" data-clipboard-target="#kami-<?= $key ?>">
                                <i class="fas fa-copy mr-1"></i>复制
                            </button>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
    $(function () {
        // 初始化单个卡密复制功能
        var clipboard = new ClipboardJS('.copy-btn');

        clipboard.on('success', function(e) {
            layer.msg('复制成功');
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            layer.msg('复制失败，请手动复制');
            console.error('复制失败:', e);
        });

        // 带确认提示的复制全部卡密功能（修复反馈问题）
        $('#copyAllBtn').click(function() {
            // 先显示确认提示框
            layer.confirm(
                '该操作最多复制500条内容。如果您购买的数量超过500条，请点击下载全部卡密按钮',
                {
                    title: '复制确认',
                    icon: 3,
                    btn: ['复制', '取消']
                },
                function(index) {  // 点击确定后的回调
                    layer.close(index); // 关闭确认框

                    // 1. 收集所有卡密内容
                    var allContent = [];
                    $('.kami-content').each(function(idx, el) {
                        // allContent.push((idx + 1) + '. ' + $(el).text().trim());
                        allContent.push($(el).text().trim());
                    });
                    var fullText = allContent.join('\n');

                    // 2. 创建隐藏文本框用于复制（解决ClipboardJS事件触发问题）
                    var tempInput = $('<textarea>').val(fullText).css({
                        position: 'absolute',
                        left: '-9999px'
                    }).appendTo('body');

                    // 3. 选中并复制内容
                    tempInput.select();
                    var isSuccess = document.execCommand('copy');

                    // 4. 反馈结果
                    if (isSuccess) {
                        layer.msg('复制成功');
                    } else {
                        layer.msg('复制失败，请手动操作');
                    }

                    // 5. 清理临时元素
                    tempInput.remove();
                    // 清除选中状态
                    window.getSelection().removeAllRanges();
                }
            );
        });
    });
</script>
