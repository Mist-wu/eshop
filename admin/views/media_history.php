<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    body {
        background: #f7f8fa;
    }

    .media-history-page {
        padding: 14px 16px 18px;
    }

    .media-history-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .media-history-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }

    .media-history-tip {
        font-size: 12px;
        color: #94a3b8;
    }

    .media-history-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        overflow: auto;
        padding: 6px;
    }

    .media-history-item {
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        padding: 6px;
        background: #fff;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .media-history-item img {
        width: 100%;
        height: 110px;
        object-fit: cover;
        border-radius: 8px;
        display: block;
    }

    .media-history-item:hover {
        border-color: #4c9ffe;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-1px);
    }

    .media-history-name {
        margin-top: 6px;
        font-size: 12px;
        color: #5a6675;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-history-actions {
        display: flex;
        justify-content: center;
        padding: 10px 0 2px;
    }

    .media-history-empty {
        grid-column: 1 / -1;
        text-align: center;
        color: #94a3b8;
        padding: 24px 0;
    }
</style>

<div class="media-history-page">
    <div class="media-history-toolbar">
        <div class="media-history-title">历史图片</div>
        <div class="media-history-tip">点击图片即可选择</div>
    </div>
    <div class="media-history-grid" id="media-history-grid"></div>
    <div class="media-history-actions">
        <button type="button" class="layui-btn layui-btn" id="media-history-more">加载更多</button>
    </div>
</div>

<script>
    var historyPage = 1;
    var historyLoading = false;
    var targetId = '<?= addslashes($target) ?>';

    function loadHistoryImages(reset) {
        if (historyLoading) {
            return;
        }
        historyLoading = true;
        $('#media-history-more').prop('disabled', true).text('加载中...');
        $.ajax({
            type: 'GET',
            url: './media.php?action=lib',
            data: {
                page: historyPage
            },
            success: function (resp) {
                var images = resp && resp.data && resp.data.images ? resp.data.images : [];
                var imageItems = images.filter(function (item) {
                    return item.media_type === 'image';
                });

                if (reset) {
                    $('#media-history-grid').empty();
                }

                if (reset && imageItems.length === 0) {
                    $('#media-history-grid').html('<div class="media-history-empty">暂无图片</div>');
                }

                imageItems.forEach(function (item) {
                    var cardHtml = '<div class="media-history-item" data-url="' + item.media_url + '">' +
                        '<img src="' + item.media_icon + '" alt="">' +
                        '<div class="media-history-name">' + item.media_name + '</div>' +
                        '</div>';
                    $('#media-history-grid').append(cardHtml);
                });

                if (resp && resp.data && resp.data.hasMore) {
                    historyPage++;
                    $('#media-history-more').show();
                } else {
                    $('#media-history-more').hide();
                }
            },
            error: function () {
                if (reset) {
                    $('#media-history-grid').html('<div class="media-history-empty">加载失败，请稍后重试</div>');
                }
            },
            complete: function () {
                historyLoading = false;
                $('#media-history-more').prop('disabled', false).text('加载更多');
            }
        });
    }

    $('#media-history-more').on('click', function () {
        loadHistoryImages(false);
    });

    $('#media-history-grid').on('click', '.media-history-item', function () {
        var url = $(this).data('url');
        if (targetId && parent && parent.$) {
            parent.$('#' + targetId).val(url).trigger('input');
        }
        if (parent && parent.layer) {
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        }
    });

    $(function () {
        loadHistoryImages(true);
    });
</script>
