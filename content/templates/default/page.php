<?php
/**
 * 自建页面模板
 */
defined('EM_ROOT') || exit('access denied!');
?>

    <article class="container log-con blog-container mt-3" style="max-width: 850px;">

        <div class="card mb-3 p-3" style="">
            <h1 class="page-title mb-3 text-center"><?= $log_title ?></h1>
            <div class="markdown">
                <?= $log_content ?>
            </div>

        </div>
    </article>

<?php
include View::getView('footer');
?>