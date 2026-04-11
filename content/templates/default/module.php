<?php
/**
 * 侧边栏组件、页面模块
 */
defined('EM_ROOT') || exit('access denied!');



?>

<?php



/**
 * 页顶：导航
 */
function blog_navi() {
    global $CACHE;
    $navi_cache = $CACHE->readCache('navi');

    foreach ($navi_cache as $value):
        // 只显示顶级导航
        if ($value['pid'] != 0) {
            continue;
        }

        // 处理链接属性
        $newtab = $value['newtab'] == 'y' ? 'target="_blank"' : '';
        $value['url'] = $value['isdefault'] == 'y' ? EM_URL . $value['url'] : trim($value['url'], '/');
        $current_tab = EM_URL . trim(Dispatcher::setPath(), '/') == $value['url'] ? 'active' : '';

        // 检查是否有子菜单
        $has_children = !empty($value['children']) || !empty($value['childnavi']);

        // 生成导航项ID
        $nav_id = 'navbar-item-' . $value['id'];
        ?>
        <li id="<?= $nav_id ?>" class="nav-item <?= $current_tab ?> <?= $has_children ? 'has-submenu' : '' ?>">
            <a href="<?= $value['url'] ?>" <?= $newtab ?>><?= $value['naviname'] ?></a>

            <?php if ($has_children): ?>
                <ul class="sub-menu">
                    <?php
                    // 显示分类子菜单
                    if (!empty($value['children'])):
                        foreach ($value['children'] as $child): ?>
                            <li class="sub-item">
                                <a href="<?= Url::sort($child['id']) ?>"><?= isset($child['sortname']) ? $child['sortname'] : $child['naviname'] ?></a>
                            </li>
                        <?php endforeach;
                    endif;

                    // 显示自定义子菜单
                    if (!empty($value['childnavi'])):
                        foreach ($value['childnavi'] as $child):
                            $child_newtab = $child['newtab'] == 'y' ? 'target="_blank"' : '';
                            ?>
                            <li class="sub-item">
                                <a href="<?= $child['url'] ?>" <?= $child_newtab ?>><?= $child['naviname'] ?></a>
                            </li>
                        <?php endforeach;
                    endif;
                    ?>
                </ul>
            <?php endif; ?>
        </li>
    <?php endforeach;
}
 ?>
<?php
/**
 * 文章列出卡片：置顶标志
 */
function topflg($top, $sortop = 'n', $sortid = null) {
    $ishome_flg = '<span class="log-topflg" >置顶</span>';
    $issort_flg = '<span class="log-topflg" >分类置顶</span>';
    if (blog_tool_ishome()) {
        echo $top == 'y' ? $ishome_flg : '';
    } elseif ($sortid) {
        echo $sortop == 'y' ? $issort_flg : '';
    }
}

?>
<?php
/**
 * 文章详情页：编辑链接
 */
function editflg($logid, $author) {
    $editflg = User::haveEditPermission() || $author == UID ? '<a href="' . EM_URL . 'admin/article.php?action=edit&gid=' . $logid . '" target="_blank"><span class="iconfont icon-edit"></span></a>' : '';
    echo $editflg;
}

?>
<?php
/**
 * 文章详情页：分类
 */
function blog_sort($sortID) {
    $Sort_Model = new Sort_Model();
    $r = $Sort_Model->getOneSortById($sortID);
    $sortName = isset($r['sortname']) ? $r['sortname'] : '';
    ?>
    <?php if (!empty($sortName)) { ?>
        <a href="<?= Url::sort($sortID) ?>"><?= $sortName ?></a>
    <?php }
} ?>
<?php
/**
 * 首页文章列表：分类
 */
function bloglist_sort($sortID) {
    $Sort_Model = new Sort_Model();
    $r = $Sort_Model->getOneSortById($sortID);
    $sortName = isset($r['sortname']) ? $r['sortname'] : '';
    ?>
    <?php if (!empty($sortName)) { ?>
        <span class="loglist-sort">
            <a href="<?= Url::sort($sortID) ?>"><?= $sortName ?></a>
        </span>
    <?php }
} ?>
<?php
/**
 * 首页文章列表和文章详情页：标签
 */
function blog_tag($blogid) {
    $tag_model = new Tag_Model();
    $tag_ids = $tag_model->getTagIdsFromBlogId($blogid);
    $tag_names = $tag_model->getNamesFromIds($tag_ids);
    if (!empty($tag_names)) {
        $tag = '';
        foreach ($tag_names as $value) {
            $tag .= "    <a href=\"" . Url::tag(rawurlencode($value)) . "\" class='tags' title='标签' >" . htmlspecialchars($value) . '</a>';
        }
        echo $tag;
    }
}

?>
<?php
/**
 * 首页文章列表和文章详情页：作者
 */
function blog_author($uid) {
    $User_Model = new User_Model();
    $user_info = $User_Model->getOneUser($uid);
    $author = $user_info['nickname'];
    echo '<a href="' . Url::author($uid) . "\">$author</a>";
}

?>
<?php
/**
 * 文章详情页：相邻文章
 */
function neighbor_log($neighborLog) {
    extract($neighborLog) ?>
    <?php if ($prevLog): ?>
        <span class="prev-log"><a href="<?= Url::log($prevLog['gid']) ?>" title="上一篇：<?= $prevLog['title'] ?>"><span class="iconfont icon-prev"></span></a></span>
    <?php endif ?>
    <?php if ($nextLog): ?>
        <span class="next-log"><a href="<?= Url::log($nextLog['gid']) ?>" title="下一篇：<?= $nextLog['title'] ?>"><span class="iconfont icon-next"></span></a></span>
    <?php endif ?>
<?php } ?>
<?php
/**
 * 文章详情页：评论列表
 */
function blog_comments($comments, $comnum) {
    extract($comments);
    if ($commentStacks): ?>
        <div class="comment-header"><b>收到<?= $comnum ?>条评论</b></div>
    <?php endif ?>
    <?php
    foreach ($commentStacks as $cid):
        $comment = $comments[$cid];
        ?>
        <div class="comment" id="<?= $comment['cid'] ?>">
            <?php
            $avatar = getEmUserAvatar($comment['uid'], $comment['mail']);
            ?>
            <div class="avatar"><img src="<?= $avatar ?>" alt="avatar"/></div>
            <div class="comment-infos">
                <div class="arrow"></div>
                <b><?= $comment['poster'] ?> </b><span class="comment-time"><?= $comment['date'] ?></span>
                <div class="comment-content"><?= $comment['content'] ?></div>
                <div class="comment-reply">
                    <span class="com-reply">回复</span>
                </div>
            </div>
            <?php blog_comments_children($comments, $comment['children']) ?>
        </div>
    <?php endforeach ?>
    <div id="pagenavi">
        <?= $commentPageUrl ?>
    </div>
<?php } ?>
<?php
/**
 * 文章详情页：子评论
 */
function blog_comments_children($comments, $children) {
    foreach ($children as $child):
        $comment = $comments[$child];
        ?>
        <div class="comment comment-children" id="<?= $comment['cid'] ?>">
            <?php
            $avatar = getEmUserAvatar($comment['uid'], $comment['mail']);
            ?>
            <div class="avatar"><img src="<?= $avatar ?>" alt="commentator"/></div>
            <div class="comment-infos">
                <div class="arrow"></div>
                <b><?= $comment['poster'] ?> </b><span class="comment-time"><?= $comment['date'] ?></span>
                <div class="comment-content"><?= $comment['content'] ?></div>
                <?php if ($comment['level'] < 4): ?>
                    <div class="comment-reply">
                        <span class="com-reply comment-replay-btn">回复</span>
                    </div>
                <?php endif ?>
            </div>
            <?php blog_comments_children($comments, $comment['children']) ?>
        </div>
    <?php endforeach ?>
<?php } ?>
<?php
/**
 * 文章详情页：评论表单
 */
function blog_comments_post($logid, $ckname, $ckmail, $ckurl, $verifyCode, $allow_remark) {
    $isLoginComment = Option::get('login_comment');
    if ($allow_remark == 'y'): ?>
        <div id="comments">
            <div class="comment-post" id="comment-post">
                <form class="commentform" method="post" name="commentform" action="<?= EM_URL ?>index.php?action=addcom" id="commentform">
                    <input type="hidden" name="gid" value="<?= $logid ?>"/>
                    <textarea class="form-control log_comment" name="comment" id="comment" rows="10" tabindex="4" placeholder="撰写评论" required></textarea>
                    <?php if (User::isVisitor() && $isLoginComment === 'n'): ?>
                        <div class="comment-info" id="comment-info">
                            <input class="form-control com_control comment-name" id="info_n" autocomplete="off" type="text" name="comname" maxlength="49"
                                   value="<?= $ckname ?>" size="22"
                                   tabindex="1" placeholder="昵称*" required/>
                            <input class="form-control com_control comment-mail" id="info_m" autocomplete="off" type="email" name="commail" maxlength="128"
                                   value="<?= $ckmail ?>" size="22"
                                   tabindex="2" placeholder="邮箱"/>
                        </div>
                    <?php endif ?>
                    <span class="com_submit_p">
                        <?php if (User::isVisitor() && $isLoginComment === 'y'): ?>
                            请先 <a href="./admin/index.php">登录</a> 再评论
                        <?php else: ?>
                            <input class="btn"<?php if ($verifyCode != "") { ?> type="button" data-toggle="modal" data-target="#myModal"<?php } else { ?> type="submit" <?php } ?>
                               id="comment_submit" value="发布评论" tabindex="6"/>
                        <?php endif; ?>
                    </span>
                    <?php if ($verifyCode != "") { ?>
                        <div class="modal" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content" style="display: table-cell;">
                                    <input type="hidden" id="EM_URL" value="<?= EM_URL ?>"/>
                                    <div class="modal-header" style="border-bottom: 0;">输入验证码</div>
                                    <?= $verifyCode ?>
                                    <div class="modal-footer" style="border-top: 0;">
                                        <button type="button" class="btn" id="close-modal" data-dismiss="modal">关闭</button>
                                        <button type="submit" class="btn" id="comment_submit2">提交</button>
                                    </div>
                                </div>
                            </div>
                            <div class="lock-screen"></div>
                        </div>
                    <?php } ?>
                    <input type="hidden" name="pid" id="comment-pid" value="0" tabindex="1"/>
                </form>
            </div>
        </div>
    <?php endif ?>
<?php } ?>
<?php
/**
 * 判断函数：是否是首页
 */
function blog_tool_ishome() {
    if (EM_URL . trim(Dispatcher::setPath(), '/') == EM_URL) {
        return true;
    } else {
        return FALSE;
    }
}

?>
<?php
function getEmUserAvatar($uid, $mail) {
    $avatar = '';
    if ($uid) {
        $userModel = new User_Model();
        $user = $userModel->getOneUser($uid);
        $avatar = $user['photo'];
    } elseif ($mail) {
        $avatar = getGravatar($mail);
    }
    return $avatar ?: EM_URL . "admin/views/images/avatar.svg";
}

?>