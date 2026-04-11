<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .repair-page{
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 24px 60px;
        background: linear-gradient(180deg, rgba(15,118,110,0.08), rgba(244,245,243,0));
    }
    .repair-card{
        width: min(980px, 100%);
        background: var(--admin-panel);
        border-radius: 18px;
        border: 1px solid var(--admin-border-soft);
        box-shadow: var(--admin-shadow);
        padding: 34px 40px 36px;
        position: relative;
        overflow: hidden;
    }
    .repair-card:before{
        content: '';
        position: absolute;
        right: -140px;
        top: -140px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(15,118,110,0.22), rgba(15,118,110,0));
    }
    .repair-load{
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 6px;
        padding: 18px 16px;
        margin-bottom: 22px;
        border-radius: 16px;
        background: var(--admin-panel-soft);
        border: 1px dashed var(--admin-border-soft);
        position: relative;
        z-index: 1;
    }
    .repair-load.is-success{
        background: rgba(15,118,110,0.08);
        border-color: rgba(15,118,110,0.2);
    }
    .repair-load.is-error{
        background: rgba(239,68,68,0.08);
        border-color: rgba(239,68,68,0.3);
    }
    .repair-load-icon{
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(15,118,110,0.12);
        color: var(--admin-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .repair-load.is-success .repair-load-icon{
        background: rgba(16,185,129,0.16);
        color: #047857;
    }
    .repair-load.is-error .repair-load-icon{
        background: rgba(239,68,68,0.12);
        color: #b91c1c;
    }
    .repair-load-title{
        font-size: 15px;
        font-weight: 600;
        color: var(--admin-text);
    }
    .repair-load-desc{
        font-size: 13px;
        color: var(--admin-muted);
    }
    .repair-hero{
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 22px;
        position: relative;
        z-index: 1;
    }
    .repair-icon{
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--admin-primary), #14b8a6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 22px;
        box-shadow: 0 10px 20px rgba(15,118,110,0.25);
    }
    .repair-title{
        font-size: 22px;
        font-weight: 700;
        color: var(--admin-text);
        margin-bottom: 4px;
    }
    .repair-subtitle{
        font-size: 14px;
        color: var(--admin-muted);
    }
    .repair-tip{
        margin-top: 10px;
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(15,118,110,0.08);
        border: 1px solid rgba(15,118,110,0.2);
        color: var(--admin-primary-strong);
        font-size: 13px;
        line-height: 1.6;
        display: inline-flex;
        align-items: flex-start;
        gap: 8px;
    }
    .repair-tip i{
        margin-top: 2px;
    }
    .repair-actions{
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 16px 0 22px;
        flex-wrap: wrap;
    }
    .repair-actions .layui-btn{
        height: 44px;
        line-height: 44px;
        font-size: 15px;
        padding: 0 26px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--admin-primary), #14b8a6);
        border: none;
        box-shadow: 0 10px 20px rgba(15,118,110,0.2);
    }
    .repair-state{
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 13px;
        background: var(--admin-panel-soft);
        color: var(--admin-muted);
    }
    .repair-state.is-running{
        background: rgba(15,118,110,0.12);
        color: var(--admin-primary-strong);
    }
    .repair-state.is-success{
        background: rgba(16,185,129,0.14);
        color: #047857;
    }
    .repair-state.is-error{
        background: rgba(239,68,68,0.14);
        color: #b91c1c;
    }
    .repair-progress{
        background: var(--admin-panel-soft);
        border-radius: 16px;
        padding: 18px 20px;
        border: 1px solid var(--admin-border-soft);
        margin-bottom: 22px;
    }
    .repair-progress-track{
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: rgba(15,118,110,0.12);
        overflow: hidden;
    }
    .repair-progress-bar{
        width: 0;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--admin-primary), #10b981);
        transition: width 0.4s ease;
    }
    .repair-progress-meta{
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 13px;
        color: var(--admin-muted);
    }
    .repair-current{
        margin-top: 8px;
        font-size: 13px;
        color: var(--admin-text);
    }
    .repair-current span{
        font-weight: 600;
    }
    .repair-log{
        background: #0b2622;
        border-radius: 16px;
        padding: 16px 18px;
        color: #e5f5f2;
        position: relative;
        overflow: hidden;
    }
    .repair-log:before{
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.35;
        background-image: radial-gradient(rgba(13,148,136,0.35) 1px, transparent 0);
        background-size: 16px 16px;
        pointer-events: none;
    }
    .repair-log-title{
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }
    .repair-log-body{
        max-height: 240px;
        overflow-y: auto;
        font-size: 13px;
        line-height: 1.6;
        padding-right: 6px;
        position: relative;
        z-index: 1;
    }
    .repair-log-line{
        padding: 4px 0;
        border-bottom: 1px dashed rgba(94,234,212,0.18);
    }
    .repair-log-line:last-child{
        border-bottom: none;
    }
    .repair-log-line.is-success{
        color: #a7f3d0;
    }
    .repair-log-line.is-error{
        color: #fecaca;
    }
    .repair-log-empty{
        color: rgba(226,232,240,0.75);
    }
    @media (max-width: 900px){
        .repair-card{
            padding: 26px 22px 28px;
        }
        .repair-hero{
            flex-direction: column;
            align-items: flex-start;
        }
        .repair-actions{
            flex-direction: column;
            align-items: stretch;
        }
        .repair-actions .layui-btn{
            width: 100%;
        }
    }
</style>

<div class="repair-page">
    <div class="repair-card">
        <div id="repair-load" class="repair-load">
            <div class="repair-load-icon">
                <i id="repair-load-icon" class="fa fa-spinner fa-spin"></i>
            </div>
            <div id="repair-load-title" class="repair-load-title">正在加载修复程序...</div>
            <div id="repair-load-desc" class="repair-load-desc">正在校验修复模块，请稍候。</div>
        </div>

        <div id="repair-body" style="display: none;">
            <div class="repair-hero">
                <div class="repair-icon"><i class="fa fa-wrench"></i></div>
                <div>
                    <div class="repair-title">EMSHOP 修复中心</div>
                    <div class="repair-subtitle">系统修复会自动按步骤执行，过程中请勿关闭页面或刷新。</div>
                    <div class="repair-tip">
                        <i class="fa fa-info-circle"></i>
                        <span>
                            修复系统会将数据结构修复到最新版本，但不会更新程序文件，使用该功能请优先保证您的程序当前处于最新版本。
                        </span>
                    </div>
                </div>
            </div>

            <div class="repair-actions">
                <button type="button" id="repair-start-btn" class="layui-btn layui-btn-lg">开始修复EMSHOP程序</button>
                <span id="repair-state" class="repair-state">待开始</span>
            </div>

            <div class="repair-progress">
                <div class="repair-progress-track">
                    <div id="repair-progress-bar" class="repair-progress-bar"></div>
                </div>
                <div class="repair-progress-meta">
                    <span id="repair-progress-text">0/0</span>
                    <span id="repair-progress-percent">0%</span>
                </div>
                <div class="repair-current">当前任务：<span id="repair-current-op">--</span></div>
            </div>

            <div class="repair-log">
                <div class="repair-log-title">修复状况</div>
                <div id="repair-log-body" class="repair-log-body">
                    <div class="repair-log-empty">等待开始...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        var $startBtn = $('#repair-start-btn');
        var $logBody = $('#repair-log-body');
        var $progressBar = $('#repair-progress-bar');
        var $progressText = $('#repair-progress-text');
        var $progressPercent = $('#repair-progress-percent');
        var $state = $('#repair-state');
        var $currentOp = $('#repair-current-op');
        var $repairBody = $('#repair-body');
        var $loadBox = $('#repair-load');
        var $loadTitle = $('#repair-load-title');
        var $loadDesc = $('#repair-load-desc');
        var $loadIcon = $('#repair-load-icon');

        var repairQueue = [];
        var totalCount = 0;
        var currentIndex = 0;
        var isRunning = false;
        var isProgramReady = false;
        var requestTimer = null;

        function setLoadState(state, title, desc){
            $loadBox.removeClass('is-success is-error');
            $loadIcon.removeClass().addClass('fa');
            if(state === 'success'){
                $loadBox.addClass('is-success');
                $loadIcon.addClass('fa-check');
            }else if(state === 'error'){
                $loadBox.addClass('is-error');
                $loadIcon.addClass('fa-times');
            }else{
                $loadIcon.addClass('fa-spinner fa-spin');
            }
            $loadTitle.text(title || '');
            if(typeof desc === 'string' && desc.length){
                $loadDesc.text(desc).show();
            }else{
                $loadDesc.hide();
            }
        }

        function setState(stateClass, text){
            $state.removeClass('is-running is-success is-error');
            if(stateClass){
                $state.addClass(stateClass);
            }
            $state.text(text || '');
        }

        function setCurrentOp(text){
            $currentOp.text(text || '--');
        }

        function appendLog(message, level){
            if(!message){
                return;
            }
            var timeText = new Date().toLocaleTimeString('zh-CN', { hour12: false });
            var $line = $('<div class="repair-log-line"></div>');
            if(level){
                $line.addClass('is-' + level);
            }
            $line.text('[' + timeText + '] ' + message);
            $logBody.append($line);
            $logBody.scrollTop($logBody[0].scrollHeight);
        }

        function updateProgress(current, total){
            var safeTotal = total > 0 ? total : 0;
            var safeCurrent = current > 0 ? current : 0;
            var percent = safeTotal ? Math.round((safeCurrent / safeTotal) * 100) : 0;
            if(percent > 100){
                percent = 100;
            }
            $progressBar.css('width', percent + '%');
            $progressText.text(safeCurrent + '/' + safeTotal);
            $progressPercent.text(percent + '%');
        }

        function scheduleNext(fn){
            if(!isRunning){
                return;
            }
            clearTimeout(requestTimer);
            requestTimer = setTimeout(function(){
                fn();
            }, 1000);
        }

        function stopRunning(message){
            isRunning = false;
            clearTimeout(requestTimer);
            setState('is-error', message || '修复中断');
            $startBtn.prop('disabled', false).removeClass('layui-btn-disabled').text('重新修复');
        }

        function completeAll(){
            updateProgress(totalCount, totalCount);
            setState('is-success', '修复已完成');
            setCurrentOp('全部完成');
            appendLog('全部修复任务已完成。', 'success');
            isRunning = false;
            $startBtn.prop('disabled', false).removeClass('layui-btn-disabled').text('再次修复');
        }

        function runNextAction(){
            if(!isRunning){
                return;
            }
            if(currentIndex >= totalCount){
                completeAll();
                return;
            }
            var item = repairQueue[currentIndex];
            scheduleNext(function(){
                requestAction(item.action, item.label, currentIndex + 1);
            });
        }

        function handleActionResponse(action, label, stepIndex, res){
            if(!res || typeof res.code === 'undefined'){
                appendLog(label + '：接口返回格式错误。', 'error');
                stopRunning('修复中断');
                return;
            }
            if(res.code != 200){
                appendLog(label + '：' + (res.msg || '修复失败'), 'error');
                stopRunning('修复失败');
                return;
            }

            if(res.msg){
                appendLog(label + '：' + res.msg, 'info');
            }else{
                appendLog(label + '：执行中...', 'info');
            }

            if(res.data === 'continue'){
                scheduleNext(function(){
                    requestAction(action, label, stepIndex);
                });
                return;
            }

            if(res.data === 'complete'){
                appendLog(label + '：完成', 'success');
                currentIndex += 1;
                updateProgress(currentIndex, totalCount);
                runNextAction();
                return;
            }

            appendLog(label + '：返回未知状态，已停止。', 'error');
            stopRunning('修复中断');
        }

        function requestAction(action, label, stepIndex){
            if(!isRunning){
                return;
            }
            setState('is-running', '修复进行中');
            setCurrentOp(label + ' (' + stepIndex + '/' + totalCount + ')');
            updateProgress(stepIndex, totalCount);

            $.ajax({
                url: 'repair_em.php',
                type: 'GET',
                dataType: 'json',
                data: { action: action },
                success: function(res){
                    handleActionResponse(action, label, stepIndex, res);
                },
                error: function(xhr){
                    var msg = '网络错误，请稍后重试。【' + action + '】';
                    if(xhr && xhr.responseJSON && xhr.responseJSON.msg){
                        msg = xhr.responseJSON.msg;
                    }
                    appendLog(label + '：' + msg, 'error');
                    stopRunning('修复失败');
                }
            });
        }

        function startRepair(){
            if(isRunning || !isProgramReady){
                return;
            }
            isRunning = true;
            $startBtn.prop('disabled', true).addClass('layui-btn-disabled').text('修复中...');
            $logBody.empty();
            setState('is-running', '正在准备');
            setCurrentOp('获取修复清单...');
            updateProgress(0, 0);

            $.ajax({
                url: 'repair_em.php',
                type: 'GET',
                dataType: 'json',
                success: function(res){
                    if(res && res.code == 200 && res.data){
                        repairQueue = [];
                        $.each(res.data, function(action, label){
                            repairQueue.push({ action: action, label: label });
                        });
                        totalCount = repairQueue.length;
                        currentIndex = 0;

                        if(totalCount === 0){
                            appendLog('未发现需要修复的项目。', 'info');
                            completeAll();
                            return;
                        }

                        setState('is-running', '修复进行中');
                        updateProgress(0, totalCount);
                        appendLog('修复任务已获取，共 ' + totalCount + ' 项。', 'info');
                        runNextAction();
                        return;
                    }

                    appendLog((res && res.msg) ? res.msg : '获取修复任务失败。', 'error');
                    stopRunning('修复失败');
                },
                error: function(xhr){
                    var msg = '网络错误，请稍后重试。';
                    if(xhr && xhr.responseJSON && xhr.responseJSON.msg){
                        msg = xhr.responseJSON.msg;
                    }
                    appendLog(msg, 'error');
                    stopRunning('修复失败');
                }
            });
        }

        $startBtn.on('click', startRepair);

        function initRepairProgram(){
            setLoadState('loading', '正在加载修复程序...', '正在校验修复模块，请稍候。');
            $repairBody.hide();
            $startBtn.prop('disabled', true).addClass('layui-btn-disabled').text('加载中...');
            setState('', '等待加载');
            $.ajax({
                url: '?action=get_repair_code',
                type: 'GET',
                dataType: 'json',
                success: function(res){
                    if(res && res.code == 200){
                        isProgramReady = true;
                        setLoadState('success', '修复程序加载成功', '修复模块已就绪，可以开始修复。');
                        $repairBody.stop(true, true).slideDown(200);
                        $startBtn.prop('disabled', false).removeClass('layui-btn-disabled').text('开始修复EMSHOP程序');
                        setState('', '待开始');
                        return;
                    }
                    if(res && res.code == 400){
                        isProgramReady = false;
                        setLoadState('error', '修复程序加载失败', res.msg || '加载失败，请稍后重试。');
                        $repairBody.hide();
                        $startBtn.prop('disabled', true).addClass('layui-btn-disabled').text('加载失败');
                        setState('is-error', '加载失败');
                        return;
                    }
                    isProgramReady = false;
                    setLoadState('error', '修复程序加载失败', (res && res.msg) ? res.msg : '接口返回异常，请稍后重试。');
                    $repairBody.hide();
                    $startBtn.prop('disabled', true).addClass('layui-btn-disabled').text('加载失败');
                    setState('is-error', '加载失败');
                },
                error: function(xhr){
                    var msg = '网络错误，请稍后重试。';
                    if(xhr && xhr.responseJSON && xhr.responseJSON.msg){
                        msg = xhr.responseJSON.msg;
                    }
                    isProgramReady = false;
                    setLoadState('error', '修复程序加载失败', msg);
                    $repairBody.hide();
                    $startBtn.prop('disabled', true).addClass('layui-btn-disabled').text('加载失败');
                    setState('is-error', '加载失败');
                }
            });
        }

        initRepairProgram();
    })();
</script>
