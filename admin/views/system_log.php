<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php
$levelLabels = [
    'all' => '全部',
    'error' => '错误',
    'warning' => '警告',
    'info' => '信息',
    'debug' => '调试',
    'other' => '其他',
];
$levelBadges = [
    'error' => 'ERROR',
    'warning' => 'WARN',
    'info' => 'INFO',
    'debug' => 'DEBUG',
    'other' => 'OTHER',
];
$levelIcons = [
    'error' => 'fa-times-circle',
    'warning' => 'fa-exclamation-triangle',
    'info' => 'fa-info-circle',
    'debug' => 'fa-bug',
    'other' => 'fa-circle',
];
$levelClasses = ['error', 'warning', 'info', 'debug', 'other'];
$hasLogs = !empty($logDates);
$hasEntries = !empty($entriesPage);
$keywordSafe = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
$selectedDateSafe = htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8');
$logBaseSafe = htmlspecialchars($logBaseDisplay, ENT_QUOTES, 'UTF-8');
$logFileSafe = htmlspecialchars($logFileDisplay, ENT_QUOTES, 'UTF-8');
$isEmptyLog = ((int)$stats['all'] === 0);
?>

<style>

    .log-card{
        background: var(--admin-panel);
        border-radius: 20px;
        border: 1px solid var(--admin-border-soft);
        box-shadow: var(--admin-shadow);
        padding: 26px 28px 30px;
        position: relative;
        overflow: hidden;
    }
    .log-header{
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }
    .log-icon{
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: rgba(15,118,110,0.14);
        color: var(--admin-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 12px 22px rgba(15,118,110,0.2);
    }
    .log-title{
        font-size: 22px;
        font-weight: 700;
        color: var(--admin-text);
        margin-bottom: 4px;
    }
    .log-subtitle{
        font-size: 13px;
        color: var(--admin-muted);
    }

    .log-meta{
        margin-top: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        position: relative;
        z-index: 1;
    }
    .log-chip{
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        background: var(--admin-panel-soft);
        color: var(--admin-muted);
        border: 1px solid var(--admin-border-soft);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-chip strong{
        color: var(--admin-text);
        font-weight: 600;
    }
    .log-chip.is-error{
        background: rgba(239,68,68,0.12);
        color: #b91c1c;
        border-color: rgba(239,68,68,0.25);
    }
    .log-chip.is-warning{
        background: rgba(245,158,11,0.16);
        color: #92400e;
        border-color: rgba(245,158,11,0.3);
    }
    .log-chip.is-info{
        background: rgba(59,130,246,0.14);
        color: #1d4ed8;
        border-color: rgba(59,130,246,0.24);
    }
    .log-chip.is-debug{
        background: rgba(15,118,110,0.16);
        color: var(--admin-primary-strong);
        border-color: rgba(15,118,110,0.28);
    }
    .log-filter{
        margin-top: 20px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        background: var(--admin-panel-soft);
        border: 1px solid var(--admin-border-soft);
        border-radius: 16px;
        padding: 18px;
        position: relative;
        z-index: 1;
    }
    .log-filter .layui-form-item{
        margin-bottom: 0;
    }
    .log-filter label{
        display: block;
        font-size: 12px;
        color: var(--admin-muted);
        margin-bottom: 6px;
    }
    .log-filter .layui-input,
    .log-filter .layui-select{
        height: 38px;
        border-radius: 10px;
        border-color: rgba(148,163,184,0.3);
        background: #fff;
    }
    .log-filter-actions{
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .log-filter-actions .layui-btn{
        height: 38px;
        line-height: 38px;
        border-radius: 999px;
        padding: 0 20px;
    }
    .log-list{
        margin-top: 22px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .log-line{
        position: relative;
        background: #fff;
        border: 1px solid var(--admin-border-soft);
        border-radius: 14px;
        padding: 12px 14px 12px 18px;
        display: grid;
        grid-template-columns: 80px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: flex-start;
    }
    .log-line:before{
        content: '';
        position: absolute;
        left: 0;
        top: 10px;
        bottom: 10px;
        width: 4px;
        border-radius: 6px;
        background: var(--admin-primary);
    }
    .log-line.is-error:before{ background: #ef4444; }
    .log-line.is-warning:before{ background: #f59e0b; }
    .log-line.is-info:before{ background: #3b82f6; }
    .log-line.is-debug:before{ background: #0f766e; }
    .log-time{
        color: var(--admin-muted);
        font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }
    .log-content{
        min-width: 0;
    }
    .log-badge{
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 8px;
        background: rgba(15,118,110,0.1);
        color: var(--admin-primary-strong);
    }
    .log-badge.is-error{ background: rgba(239,68,68,0.16); color: #b91c1c; }
    .log-badge.is-warning{ background: rgba(245,158,11,0.18); color: #92400e; }
    .log-badge.is-info{ background: rgba(59,130,246,0.16); color: #1d4ed8; }
    .log-badge.is-debug{ background: rgba(15,118,110,0.16); color: var(--admin-primary-strong); }
    .log-message{
        font-size: 13px;
        color: var(--admin-text);
        line-height: 1.6;
        word-break: break-all;
        white-space: pre-wrap;
    }
    .log-copy{
        border: none;
        background: rgba(15,118,110,0.08);
        color: var(--admin-primary-strong);
        border-radius: 10px;
        padding: 0 12px;
        height: 32px;
        line-height: 32px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .log-copy:hover{
        background: rgba(15,118,110,0.18);
    }
    .log-footer{
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }
    .log-count{
        font-size: 13px;
        color: var(--admin-muted);
    }
    .log-empty{
        margin-top: 22px;
        padding: 36px 24px;
        border-radius: 18px;
        border: 1px dashed var(--admin-border-soft);
        text-align: center;
        background: var(--admin-panel-soft);
        color: var(--admin-muted);
    }
    .log-empty i{
        font-size: 26px;
        color: var(--admin-primary);
        margin-bottom: 8px;
    }
    .log-empty strong{
        display: block;
        font-size: 15px;
        color: var(--admin-text);
        margin-bottom: 6px;
    }
    .log-empty span{
        font-size: 12px;
    }
    @media (max-width: 1100px){
        .log-filter{
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px){
        .log-card{
            padding: 22px;
        }
        .log-header{
            align-items: flex-start;
        }
        
        .log-filter{
            grid-template-columns: 1fr;
        }
        .log-line{
            grid-template-columns: 1fr;
        }
        .log-copy{
            width: 100%;
        }
    }
</style>

<div class="log-page">
    <div class="log-card">
        <div class="log-header">
            <div class="log-icon"><i class="fa fa-file-text-o"></i></div>
            <div class="log-text">
                <div class="log-title">系统日志</div>
                <div class="log-subtitle">查看程序运行日志，支持按日期、级别、关键词筛选。</div>
            </div>
        </div>

        <div class="log-meta">
            <div class="log-chip">日志目录：<strong><?= $logBaseSafe ?: 'content/log' ?></strong></div>
            <?php if ($selectedDate): ?>
                <div class="log-chip">当前日期：<strong><?= $selectedDateSafe ?></strong></div>
            <?php endif; ?>
            <div class="log-chip is-info">总计：<strong><?= (int)$stats['all'] ?></strong></div>
            <div class="log-chip is-error">错误：<strong><?= (int)$stats['error'] ?></strong></div>
            <div class="log-chip is-warning">警告：<strong><?= (int)$stats['warning'] ?></strong></div>
            <div class="log-chip is-info">信息：<strong><?= (int)$stats['info'] ?></strong></div>
            <div class="log-chip is-debug">调试：<strong><?= (int)$stats['debug'] ?></strong></div>
        </div>

        <?php if ($hasLogs): ?>
            <form class="layui-form log-filter" method="get" action="system_log.php">
                <div class="layui-form-item">
                    <label>日志日期</label>
                    <div class="layui-input-block">
                        <select name="date" class="layui-select">
                            <?php foreach ($logDates as $date): ?>
                                <?php $dateSafe = htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>
                                <option value="<?= $dateSafe ?>" <?= $date === $selectedDate ? 'selected' : '' ?>><?= $dateSafe ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label>日志级别</label>
                    <div class="layui-input-block">
                        <select name="level" class="layui-select">
                            <?php foreach (['all', 'error', 'warning', 'info', 'debug'] as $lv): ?>
                                <option value="<?= $lv ?>" <?= $lv === $level ? 'selected' : '' ?>><?= $levelLabels[$lv] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label>关键词</label>
                    <div class="layui-input-block">
                        <input type="text" name="keyword" class="layui-input" value="<?= $keywordSafe ?>" placeholder="输入关键词筛选日志">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label>每页数量</label>
                    <div class="layui-input-block">
                        <select name="perpage" class="layui-select">
                            <?php foreach ($perpageOptions as $size): ?>
                                <option value="<?= $size ?>" <?= $size === $perpage ? 'selected' : '' ?>><?= $size ?> 条</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="log-filter-actions">
                    <button type="submit" class="layui-btn">筛选</button>
                    <a class="layui-btn layui-btn" href="system_log.php">重置</a>
                </div>
            </form>

            <?php if (!$logFileExists): ?>
                <div class="log-empty">
                    <i class="fa fa-inbox"></i>
                    <strong>所选日期暂无日志</strong>
                    <span>请选择其他日期，或稍后再试。</span>
                </div>
            <?php elseif (!$hasEntries): ?>
                <div class="log-empty">
                    <?php if ($isEmptyLog): ?>
                        <i class="fa fa-inbox"></i>
                        <strong>当前日志为空</strong>
                        <span>系统已创建该日志文件，但尚未写入内容。</span>
                    <?php else: ?>
                        <i class="fa fa-filter"></i>
                        <strong>未找到匹配的日志</strong>
                        <span>请调整筛选条件或重置后再试。</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="log-list">
                    <?php foreach ($entriesPage as $entry): ?>
                        <?php
                        $entryLevel = in_array($entry['level'], $levelClasses, true) ? $entry['level'] : 'other';
                        $entryLabel = $levelBadges[$entryLevel] ?? strtoupper($entryLevel);
                        $entryIcon = $levelIcons[$entryLevel] ?? 'fa-circle';
                        $entryTime = htmlspecialchars($entry['time'], ENT_QUOTES, 'UTF-8');
                        $entryMessage = htmlspecialchars($entry['message'], ENT_QUOTES, 'UTF-8');
                        $entryRaw = htmlspecialchars($entry['raw'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="log-line is-<?= $entryLevel ?>">
                            <div class="log-time"><?= $entryTime ?></div>
                            <div class="log-content">
                                <div class="log-badge is-<?= $entryLevel ?>"><i class="fa <?= $entryIcon ?>"></i><?= $entryLabel ?></div>
                                <div class="log-message"><?= $entryMessage ?></div>
                            </div>
                            <button type="button" class="log-copy" data-log="<?= $entryRaw ?>">复制</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="log-footer">
                    <div class="log-count">当前筛选 <strong><?= (int)$filteredCount ?></strong> 条</div>
                    <div class="pager"><?= $pageurl ?></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="log-empty">
                <i class="fa fa-inbox"></i>
                <strong>暂无系统日志</strong>
                <span>系统写入日志后会在这里展示。</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    layui.use(['form'], function(){
        var form = layui.form;
        form.render();
    });


    $(document).on('click', '.log-copy', function(){
        var text = $(this).data('log') || '';
        if (!text) {
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function(){
                layer.msg('已复制');
            }).catch(function(){
                layer.msg('复制失败');
            });
        } else {
            var $temp = $('<textarea>').val(text).appendTo('body').select();
            try {
                document.execCommand('copy');
                layer.msg('已复制');
            } catch (e) {
                layer.msg('复制失败');
            }
            $temp.remove();
        }
    });

    $(function () {
        $("#menu-system").attr('class', 'admin-menu-item has-list in');
        $("#menu-system .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-system > .submenu").css('display', 'block');
        $('#menu-system-log > a').attr('class', 'menu-link active');
    });
</script>
