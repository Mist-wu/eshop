<?php

/**
 * 阅读文章页面
 */
defined('EM_ROOT') || exit('access denied!');
?>
<article class="container log-con blog-container">
    <span class="back-top mh" onclick="history.go(-1);">&laquo;</span>
    <h1 class="log-title"><?php topflg($top) ?><?= $log_title ?></h1>
    <p class="date">
        发布于
        <time class="m-r-5"><?= date('Y-n-j H:i', $date) ?></time>
        <span class="m-r-5">阅读：<?= $views ?></span>
    </p>
    <hr class="bottom-5" />
    <div class="markdown" id="eshopEchoLog"><?= $log_content ?></div>

    <?php doAction('log_related', $logData) ?>




    <div style="clear:both;"></div>
</article>
<?php include View::getBlogView('footer') ?>
