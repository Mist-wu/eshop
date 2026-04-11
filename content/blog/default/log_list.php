<?php

/**
 * 首页模板
 */
defined('EM_ROOT') || exit('access denied!');
?>
<main class="container blog-container">
    <div class="row">
        <div class="column-big">
            <?php
            $slidesStr = _em('slideShow');
            if (!empty($slidesStr)) : ?>
                <div class="slideshow-container">
                    <?php
                    $slides = explode(PHP_EOL, $slidesStr);
                    foreach ($slides as $item):
                        $slide = explode('|', $item);
                        $slideImg = isset($slide[0]) ? $slide[0] : '';
                        $slideTitle = isset($slide[1]) ? $slide[1] : '';
                        $slideLink = isset($slide[2]) ? $slide[2] : '';
                    ?>
                        <div class="mySlides fade">
                            <a href="<?= $slideLink; ?>" target="_blank"><img src="<?= $slideImg ?>" height="260" width="100%" alt=""></a>
                            <div class="slideshow-text"><?= $slideTitle ?></div>
                        </div>
                    <?php endforeach; ?>
                    <a class="slideshow-prev" onclick="plusSlides(-1)">&#10094;</a>
                    <a class="slideshow-next" onclick="plusSlides(1)">&#10095;</a>
                </div>
                <br>
                <script>
                    let slides = document.querySelectorAll('.mySlides');
                    let slideIndex = 1;
                    let timeoutID;

                    const showSlides = (n) => {
                        let i;

                        if (n > slides.length) {
                            slideIndex = 1;
                        }
                        if (n < 1) {
                            slideIndex = slides.length;
                        }

                        for (i = 0; i < slides.length; i++) {
                            slides[i].style.display = "none";
                        }

                        slides[slideIndex - 1].style.display = 'block';
                        clearTimeout(timeoutID);
                        timeoutID = setTimeout(autoSlides, 2000);
                    };

                    const plusSlides = (n) => {
                        showSlides(slideIndex += n);
                    };

                    const currentSlide = (n) => {
                        showSlides(slideIndex = n);
                    };

                    function autoSlides() {
                        let i;

                        for (i = 0; i < slides.length; i++) {
                            slides[i].style.display = "none";
                        }

                        slideIndex++;
                        if (slideIndex > slides.length) {
                            slideIndex = 1;
                        }

                        slides[slideIndex - 1].style.display = "block";
                        timeoutID = setTimeout(autoSlides, 6000);
                    }

                    autoSlides();
                </script>
            <?php endif; ?>
            <?php doAction('index_loglist_top');
            if (!empty($logs)):
                foreach ($logs as $value):
            ?>
                    <div class="shadow-theme bottom-5">
                        <?php if (!empty($value['log_cover'])) : ?>
                            <a href="<?= $value['log_url'] ?>" style="display: block;" class="loglist-cover">
                                <img src="<?= $value['log_cover'] ?>" alt="article cover" class="rea-width">
                            </a>
                        <?php endif ?>
                        <a style="display: block;" href="<?= $value['log_url'] ?>" class="card-padding loglist-body">
                            <h3 class="card-title">
                                <span href="<?= $value['log_url'] ?>" class="loglist-title"><?= $value['log_title'] ?></span>
                                <?php topflg($value['top'], $value['sortop'], isset($sortid) ? $sortid : '') ?>
                            </h3>
                            <div class="loglist-content markdown"><?php echo subContent($value['log_description'], 180, 1); ?></div>
                            <div class="loglist-tag"><?php blog_tag($value['logid']) ?></div>
                        </a>
                        <div class="row info-row">
                            <div class="log-info">
                                发布于&nbsp;
                                <time><?= date('Y-n-j H:i', $value['date']) ?></time>
                            </div>
                            <div class="log-count">
                                <a href="<?= $value['log_url'] ?>" class="m-r-10"><span class="iconfont icon-view"></span> <?= $value['views'] ?></a>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            else:
                ?>
                <p>抱歉，暂时还没有内容。</p>
            <?php endif ?>
            <div class="pagination bottom-5">
                <?= $page_url ?>
            </div>
        </div>
        <?php include View::getBlogView('side') ?>
    </div>
</main>

<?php include View::getBlogView('footer') ?>