<?php
/**
 * 默认模板商品列表页
 */
defined('EM_ROOT') || exit('access denied!');

doAction('goods_list');

$initialKeyword = '';
if (isset($keyword) && is_string($keyword)) {
    $initialKeyword = trim($keyword);
}

$searchEnabled = _g('search_show') == 'n' ? false : true;
if (!$searchEnabled) {
    $initialKeyword = '';
}

$categoryEnabled = _g('category_show') == 'n' ? false : true;

$currentSortId = '';
if (isset($sortid) && $sortid !== '') {
    $currentSortId = (string)$sortid;
} elseif (isset($sort_id) && $sort_id !== '') {
    $currentSortId = (string)$sort_id;
}

$sortMap = [];
$parentSorts = [];
$childSorts = [];
$parentLookup = [];
if (!empty($sort)) {
    foreach ($sort as $item) {
        $sortId = (string)($item['sort_id'] ?? '');
        if ($sortId === '') {
            continue;
        }

        $sortMap[$sortId] = $item;

        $pid = (int)($item['pid'] ?? 0);
        if ($pid === 0) {
            $parentSorts[$sortId] = $item;
        } else {
            if (!isset($childSorts[(string)$pid])) {
                $childSorts[(string)$pid] = [];
            }
            $childSorts[(string)$pid][] = $item;
            $parentLookup[$sortId] = (string)$pid;
        }
    }
}
if (empty($parentSorts) && !empty($sortMap)) {
    $parentSorts = $sortMap;
}

$currentSortName = '';
$currentParentSortId = '';
if ($currentSortId !== '' && isset($sortMap[$currentSortId])) {
    $currentSortName = $sortMap[$currentSortId]['sortname'] ?? '';
    $currentPid = (int)($sortMap[$currentSortId]['pid'] ?? 0);
    $currentParentSortId = $currentPid > 0 ? (string)$currentPid : $currentSortId;
}

$isFeaturedView = $currentSortId === '' && $initialKeyword === '';
$siteName = Option::get('blogname');
if (empty($siteName) && isset($blogname)) {
    $siteName = $blogname;
}

$homeBulletin = _g('home_bulletin');
if (empty($homeBulletin)) {
    $homeBulletin = Option::get('home_bulletin');
}

if (empty($homeBulletin)) {
    $homeBulletin = '<p>本站使用 EMSHOP 免费开源程序搭建，此处公告内容配置请前往后台面板 - 外观管理 - 模板管理处配置</p>';
}

$topNotice = $homeBulletin;

$sectionTitle = '精选商品';
$sectionIcon = 'fa fa-star';
if ($initialKeyword !== '') {
    $sectionTitle = '搜索结果';
    $sectionIcon = 'fa fa-search';
} elseif ($currentSortId !== '') {
    $sectionTitle = $currentSortName !== '' ? $currentSortName : '分类商品';
    $sectionIcon = 'fa fa-th-large';
}

$childSortData = [];
foreach ($childSorts as $parentId => $items) {
    $childSortData[$parentId] = [];
    foreach ($items as $item) {
        $childSortData[$parentId][] = [
            'sort_id' => (string)$item['sort_id'],
            'sortname' => (string)$item['sortname'],
        ];
    }
}
?>

<main class="blog-container goods-list-page df-list-page">
    <?php doAction('index_sorts_top'); ?>

    <div class="df-shell">
        <section class="df-topbar">
            <div class="df-site-notice">
                <div class="df-site-notice-head">
                    <div class="df-site-notice-title">
                        <i class="fa fa-volume-up"></i>
                        <span>站点公告</span>
                    </div>
                </div>
                <div class="df-site-notice-body"><?= $topNotice ?></div>
            </div>
        </section>

        <section class="df-category-section">
            <div class="df-category-wrapper<?= !$categoryEnabled && $searchEnabled ? ' search-only' : '' ?>">
                <?php if ($categoryEnabled): ?>
                <div class="df-category-grid">
                <a href="javascript:;" class="df-category-card is-featured js-featured-card<?= $isFeaturedView ? ' is-active' : '' ?>" data-sort-name="精选商品">
                    <span class="df-category-badge"><i class="fa fa-star"></i></span>
                    <span class="df-category-label">精选商品</span>
                </a>

                <?php foreach ($parentSorts as $item): ?>
                    <?php
                    $sortId = (string)$item['sort_id'];
                    $isActive = $currentParentSortId !== '' && $currentParentSortId === $sortId && $initialKeyword === '';
                    $sortImg = !empty($item['sortimg']) ? $item['sortimg'] : '';
                    ?>
                    <a href="javascript:;" class="df-category-card js-parent-card<?= $isActive ? ' is-active' : '' ?>" data-sort-id="<?= $sortId ?>" data-sort-name="<?= htmlspecialchars($item['sortname'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($sortImg !== ''): ?>
                            <span class="df-category-thumb">
                                <img src="<?= $sortImg ?>" alt="<?= htmlspecialchars($item['sortname'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='<?= EM_URL ?>admin/views/images/cover.svg'; this.onerror=null;">
                            </span>
                        <?php else: ?>
                            <span class="df-category-badge is-muted"><i class="fa fa-th-large"></i></span>
                        <?php endif; ?>
                        <span class="df-category-label"><?= $item['sortname'] ?></span>
                    </a>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($searchEnabled): ?>
                <form class="df-search-form js-goods-search-form" action="javascript:;" autocomplete="off">
                    <div class="df-search-shell">
                        <i class="fa fa-search df-search-icon"></i>
                        <input class="df-search-input js-goods-search-input" type="text" maxlength="60" placeholder="输入关键词" value="<?= htmlspecialchars($initialKeyword, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="df-search-clear js-goods-search-clear<?= $initialKeyword !== '' ? ' is-visible' : '' ?>" aria-label="清空搜索">
                            <i class="fa fa-times"></i>
                        </button>
                        <button type="submit" class="df-search-submit">搜索</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($categoryEnabled): ?>
            <div class="df-subtabs-wrap js-subtabs-card<?= empty($currentParentSortId) || empty($childSorts[$currentParentSortId]) ? ' is-hidden' : '' ?>">
                <div class="df-subtabs js-subtabs">
                    <?php if (!empty($currentParentSortId) && !empty($childSorts[$currentParentSortId])): ?>
                        <?php foreach ($childSorts[$currentParentSortId] as $item): ?>
                            <a href="javascript:;" class="df-subtab-chip js-child-chip<?= $currentSortId === (string)$item['sort_id'] ? ' is-active' : '' ?>" data-sort-id="<?= $item['sort_id'] ?>" data-sort-name="<?= htmlspecialchars($item['sortname'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= $item['sortname'] ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="df-products-section" data-api="/?rest-api=getCategoryProducts">
            <div class="df-products-head">
                <div class="df-section-title js-section-title">
                    <i class="<?= $sectionIcon ?>"></i>
                    <span><?= $sectionTitle ?></span>
                </div>
            </div>

            <?php doAction('index_goodslist_top'); ?>

            <div id="goods-list-wrapper">
                <?php if (!empty($goods_list)): ?>
                    <div class="df-products-grid grid-cols-xs-2 grid-cols-sm-2 grid-cols-md-3 grid-cols-lg-4 grid-cols-xl-6 grid-gap-15">
                        <?php foreach ($goods_list as $item): ?>
                            <a class="df-product-card" href="<?= $item['url'] ?>" title="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"<?= !empty($item['link']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                                <div class="df-product-thumb">
                                    <img src="<?= $item['cover'] ?>" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" onerror="this.src='<?= EM_URL ?>admin/views/images/cover.svg'; this.onerror=null;">
                                </div>
                                <div class="df-product-body">
                                    <div class="df-product-name"><?= $item['title'] ?></div>
                                    <div class="df-product-badges">
                                        <span class="df-pill <?= !empty($item['is_auto']) ? 'is-auto' : 'is-manual' ?>">
                                            <i class="fa <?= !empty($item['is_auto']) ? 'fa-bolt' : 'fa-pencil' ?>"></i>
                                            <?= !empty($item['is_auto']) ? '自动发货' : '人工发货' ?>
                                        </span>
                                    </div>
                                    <?php if (_g('stock_show') != 'n' || _g('sales_show') != 'n'): ?>
                                        <div class="df-product-stats">
                                            <?php if (_g('stock_show') != 'n'): ?>
                                                <span class="is-left">库存：<?= (int)($item['stock'] ?? 0) ?></span>
                                            <?php endif; ?>
                                            <?php if (_g('sales_show') != 'n'): ?>
                                                <span class="is-right">销量：<?= (int)($item['sales'] ?? 0) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="df-product-footer">
                                        <div class="df-product-price-box">
                                            <span class="df-price-label">PRICE</span>
                                            <div class="df-product-price-row">
                                                <div class="df-product-price"><?= $item['price'] ?> <em>CNY</em></div>
                                                <?php if (!empty($item['market_price']) && $item['market_price'] > $item['price']): ?>
                                                    <div class="df-product-market">&yen;<?= $item['market_price'] ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="df-product-action"><i class="fa fa-arrow-right"></i></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="df-empty-state">
                        <i class="fa fa-inbox"></i>
                        <h3>暂无商品</h3>
                        <p><?= $initialKeyword !== '' ? '没有找到与"' . htmlspecialchars($initialKeyword, ENT_QUOTES, 'UTF-8') . '"相关的商品，试试更换搜索词。' : ($currentSortId !== '' ? '当前分类下暂无商品，可以切换到其他分类继续浏览。' : '精选商品还在整理中，稍后再来看看。') ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script>
(function ($) {
    if (!$ || !$.ajax) {
        return;
    }

    var $listSection = $('.df-products-section');
    var $wrapper = $('#goods-list-wrapper');
    if (!$listSection.length || !$wrapper.length) {
        return;
    }

    var apiUrl = $listSection.data('api') || '/?rest-api=getCategoryProducts';
    var fallbackCover = '<?= EM_URL ?>admin/views/images/cover.svg';
    var homeUrl = '<?= EM_URL ?>';
    var searchEnabled = <?= $searchEnabled ? 'true' : 'false' ?>;
    var stockShow = <?= _g('stock_show') != 'n' ? 'true' : 'false' ?>;
    var salesShow = <?= _g('sales_show') != 'n' ? 'true' : 'false' ?>;
    var initialSortIdRaw = <?= json_encode($currentSortId) ?>;
    var initialSortNameRaw = <?= json_encode($currentSortName, JSON_UNESCAPED_UNICODE) ?>;
    var initialKeywordRaw = <?= json_encode($initialKeyword, JSON_UNESCAPED_UNICODE) ?>;
    var parentLookup = <?= json_encode($parentLookup, JSON_UNESCAPED_UNICODE) ?>;
    var childSortsMap = <?= json_encode($childSortData, JSON_UNESCAPED_UNICODE) ?>;

    var $searchForm = $('.js-goods-search-form');
    var $searchInput = $('.js-goods-search-input');
    var $searchClear = $('.js-goods-search-clear');
    var $sectionTitle = $('.js-section-title span');
    var $sectionIcon = $('.js-section-title i');
    var $subtabsCard = $('.js-subtabs-card');
    var $subtabs = $('.js-subtabs');
    var reqId = 0;
    var xhr = null;

    var state = {
        mode: 'default',
        sortId: '',
        sortName: '',
        parentSortId: '',
        keyword: ''
    };

    function trimKeyword(value) {
        return $.trim(String(value == null ? '' : value)).slice(0, 60);
    }

    function escHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeList(resp) {
        if (!resp) {
            return [];
        }
        var data = resp.data || resp.result || resp;
        var list = data.list || data.goods || data.items || data.goods_list || data;
        if ($.isArray(list)) {
            return list;
        }
        if (list && typeof list === 'object') {
            var normalized = [];
            $.each(list, function (_, item) {
                normalized.push(item);
            });
            return normalized;
        }
        return [];
    }

    function getParentSortId(sortId) {
        var key = String(sortId || '');
        if (!key) {
            return '';
        }
        if (parentLookup[key]) {
            return String(parentLookup[key]);
        }
        if (childSortsMap[key]) {
            return key;
        }
        return key;
    }

    function buildSkeleton(count) {
        var html = '<div class="df-products-grid grid-cols-xs-2 grid-cols-sm-2 grid-cols-md-3 grid-cols-lg-4 grid-cols-xl-6 grid-gap-15">';
        for (var i = 0; i < count; i++) {
            html += '' +
                '<div class="df-skeleton-card">' +
                    '<div class="df-skeleton-thumb"></div>' +
                    '<div class="df-skeleton-body">' +
                        '<div class="df-skeleton-line short"></div>' +
                        '<div class="df-skeleton-line"></div>' +
                        '<div class="df-skeleton-line"></div>' +
                        '<div class="df-skeleton-line price"></div>' +
                    '</div>' +
                '</div>';
        }
        html += '</div>';
        return html;
    }

    function buildEmpty(message) {
        return '' +
            '<div class="df-empty-state">' +
                '<i class="fa fa-inbox"></i>' +
                '<h3>暂无商品</h3>' +
                '<p>' + escHtml(message || '精选商品还在整理中，稍后再来看看。') + '</p>' +
            '</div>';
    }

    function buildCard(item) {
        var title = escHtml(item.title || item.name || '');
        var url = escHtml(item.url || item.link || '#');
        var cover = escHtml(item.cover || item.img || fallbackCover);
        var priceText = escHtml(item.price != null ? item.price : '');
        var marketText = item.market_price != null ? String(item.market_price) : '';
        var marketValue = parseFloat(marketText);
        var priceValue = parseFloat(item.price != null ? item.price : 0);
        var stockValue = parseInt(item.stock != null ? item.stock : 0, 10);
        var salesValue = parseInt(item.sales != null ? item.sales : 0, 10);
        var autoRaw = item.is_auto != null ? item.is_auto : item.isAuto;
        var isAuto = autoRaw === true || autoRaw === 1 || autoRaw === '1' || autoRaw === 'y';
        var hasLink = item.link != null && String(item.link).trim() !== '';
        var targetAttr = hasLink ? ' target="_blank" rel="noopener noreferrer"' : '';

        var html = '';
        html += '<a class="df-product-card" href="' + url + '" title="' + title + '"' + targetAttr + '>';
        html += '  <div class="df-product-thumb">';
        html += '    <img src="' + cover + '" alt="' + title + '" loading="lazy" onerror="this.src=\'' + fallbackCover + '\'; this.onerror=null;">';
        html += '  </div>';
        html += '  <div class="df-product-body">';
        html += '    <div class="df-product-name">' + title + '</div>';
        html += '    <div class="df-product-badges">';
        html += '      <span class="df-pill ' + (isAuto ? 'is-auto' : 'is-manual') + '"><i class="fa ' + (isAuto ? 'fa-bolt' : 'fa-pencil') + '"></i>' + (isAuto ? '自动发货' : '人工发货') + '</span>';
        html += '    </div>';
        if (stockShow || salesShow) {
            html += '    <div class="df-product-stats">';
            if (stockShow) {
                html += '      <span class="is-left">库存：' + escHtml(stockValue) + '</span>';
            }
            if (salesShow) {
                html += '      <span class="is-right">销量：' + escHtml(salesValue) + '</span>';
            }
            html += '    </div>';
        }
        html += '    <div class="df-product-footer">';
        html += '      <div class="df-product-price-box">';
        html += '        <span class="df-price-label">PRICE</span>';
        html += '        <div class="df-product-price-row">';
        html += '          <div class="df-product-price">' + priceText + ' <em>CNY</em></div>';
        if (marketText && !isNaN(marketValue) && marketValue > priceValue) {
            html += '          <div class="df-product-market">&yen;' + escHtml(marketText) + '</div>';
        }
        html += '        </div>';
        html += '      </div>';
        html += '      <span class="df-product-action"><i class="fa fa-arrow-right"></i></span>';
        html += '    </div>';
        html += '  </div>';
        html += '</a>';
        return html;
    }

    function buildGrid(list, keywordText) {
        if (!list || !list.length) {
            var emptyText = '精选商品还在整理中，稍后再来看看。';
            if (keywordText) {
                emptyText = '没有找到与“' + keywordText + '”相关的商品。';
            } else if (state.mode === 'category') {
                emptyText = '当前分类下暂无商品，可以切换其他分类继续浏览。';
            }
            return buildEmpty(emptyText);
        }

        var html = '<div class="df-products-grid grid-cols-xs-2 grid-cols-sm-2 grid-cols-md-3 grid-cols-lg-4 grid-cols-xl-6 grid-gap-15">';
        for (var i = 0; i < list.length; i++) {
            html += buildCard(list[i]);
        }
        html += '</div>';
        return html;
    }

    function setLoading(isLoading) {
        $wrapper.toggleClass('is-loading', isLoading);
    }

    function flashLoaded() {
        $wrapper.addClass('is-loaded');
        setTimeout(function () {
            $wrapper.removeClass('is-loaded');
        }, 380);
    }

    function setSectionHeading(mode, text) {
        if (mode === 'search') {
            $sectionIcon.attr('class', 'fa fa-search');
            $sectionTitle.text('搜索结果');
            return;
        }
        if (mode === 'category') {
            $sectionIcon.attr('class', 'fa fa-th-large');
            $sectionTitle.text(text || '分类商品');
            return;
        }
        $sectionIcon.attr('class', 'fa fa-star');
        $sectionTitle.text('精选商品');
    }

    function updateSearchControls() {
        if (!searchEnabled || !$searchInput.length) {
            return;
        }
        var value = trimKeyword($searchInput.val());
        if (String($searchInput.val()) !== value) {
            $searchInput.val(value);
        }
        $searchClear.toggleClass('is-visible', value !== '');
    }

    function updateParentTabs(parentSortId) {
        $('.js-parent-card').removeClass('is-active');
        $('.js-featured-card').removeClass('is-active');

        if (!parentSortId) {
            if (state.mode === 'default') {
                $('.js-featured-card').addClass('is-active');
            }
            return;
        }

        $('.js-parent-card').each(function () {
            var $item = $(this);
            if (String($item.data('sort-id')) === String(parentSortId)) {
                $item.addClass('is-active');
                return false;
            }
        });
    }

    function renderSubtabs(parentSortId, activeSortId) {
        var items = childSortsMap[String(parentSortId || '')] || [];
        if (!items.length) {
            $subtabs.empty();
            $subtabsCard.addClass('is-hidden');
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var activeClass = String(activeSortId || '') === String(item.sort_id) ? ' is-active' : '';
            html += '<a href="javascript:;" class="df-subtab-chip js-child-chip' + activeClass + '" data-sort-id="' + escHtml(item.sort_id) + '" data-sort-name="' + escHtml(item.sortname) + '">' + escHtml(item.sortname) + '</a>';
        }

        $subtabs.html(html);
        $subtabsCard.removeClass('is-hidden');
    }

    function loadProducts() {
        reqId += 1;
        var currentId = reqId;

        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
        }

        setLoading(true);
        $wrapper.html(buildSkeleton(6));

        xhr = $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            timeout: 15000,
            data: {
                sort_id: state.mode === 'category' ? state.sortId : '',
                q: state.mode === 'search' ? state.keyword : ''
            }
        }).done(function (resp) {
            if (currentId !== reqId) {
                return;
            }
            var list = normalizeList(resp);
            $wrapper.html(buildGrid(list, state.keyword));
            flashLoaded();
        }).fail(function (_, textStatus) {
            if (currentId !== reqId || textStatus === 'abort') {
                return;
            }
            $wrapper.html(buildEmpty('加载失败，请稍后重试。'));
        }).always(function () {
            if (currentId !== reqId) {
                return;
            }
            setLoading(false);
        });
    }

    function requestDefaultList() {
        if (state.mode === 'default' && state.keyword === '' && state.sortId === '') {
            return;
        }
        state.mode = 'default';
        state.sortId = '';
        state.sortName = '';
        state.parentSortId = '';
        state.keyword = '';
        if (searchEnabled && $searchInput.length) {
            $searchInput.val('');
        }
        updateSearchControls();
        updateParentTabs('');
        renderSubtabs('', '');
        setSectionHeading('default', '');
        loadProducts();
    }

    function requestCategory(sortId, sortName) {
        var normalizedSortId = String(sortId || '');
        if (!normalizedSortId) {
            requestDefaultList();
            return;
        }
        if (state.mode === 'category' && state.sortId === normalizedSortId) {
            return;
        }
        state.mode = 'category';
        state.sortId = normalizedSortId;
        state.sortName = String(sortName || '');
        state.parentSortId = getParentSortId(normalizedSortId);
        state.keyword = '';
        if (searchEnabled && $searchInput.length) {
            $searchInput.val('');
        }
        updateSearchControls();
        updateParentTabs(state.parentSortId);
        renderSubtabs(state.parentSortId, state.sortId);
        setSectionHeading('category', state.sortName);
        loadProducts();
    }

    function requestSearch() {
        if (!searchEnabled || !$searchInput.length) {
            return;
        }
        var keyword = trimKeyword($searchInput.val());
        if (state.mode === 'search' && state.keyword === keyword) {
            return;
        }
        if (!keyword) {
            requestDefaultList();
            return;
        }
        state.mode = 'search';
        state.sortId = '';
        state.sortName = '';
        state.parentSortId = '';
        state.keyword = keyword;
        updateSearchControls();
        updateParentTabs('');
        renderSubtabs('', '');
        setSectionHeading('search', keyword);
        loadProducts();
    }

    $(document).on('click', '.js-parent-card', function (e) {
        e.preventDefault();
        var $item = $(this);
        requestCategory($item.data('sort-id'), $.trim(String($item.data('sort-name') || '')));
    });

    $(document).on('click', '.js-featured-card', function (e) {
        e.preventDefault();
        requestDefaultList();
    });

    $(document).on('click', '.js-child-chip', function (e) {
        e.preventDefault();
        var $item = $(this);
        requestCategory($item.data('sort-id'), $.trim(String($item.data('sort-name') || $item.text())));
    });

    if (searchEnabled && $searchForm.length) {
        $searchForm.on('submit', function (e) {
            e.preventDefault();
            requestSearch();
        });

        $searchInput.on('input', updateSearchControls);
        $searchClear.on('click', function () {
            $searchInput.val('');
            updateSearchControls();
            $searchInput.trigger('focus');
        });
    }

    var initialKeyword = trimKeyword(initialKeywordRaw);
    var initialSortId = initialSortIdRaw ? String(initialSortIdRaw) : '';
    var initialSortName = initialSortNameRaw ? String(initialSortNameRaw) : '';

    if (initialKeyword) {
        state.mode = 'search';
        state.keyword = initialKeyword;
        setSectionHeading('search', initialKeyword);
        updateParentTabs('');
        renderSubtabs('', '');
    } else if (initialSortId) {
        state.mode = 'category';
        state.sortId = initialSortId;
        state.sortName = initialSortName;
        state.parentSortId = getParentSortId(initialSortId);
        setSectionHeading('category', initialSortName);
        updateParentTabs(state.parentSortId);
        renderSubtabs(state.parentSortId, state.sortId);
    } else {
        state.mode = 'default';
        setSectionHeading('default', '');
        updateParentTabs('');
        renderSubtabs('', '');
    }

    if (searchEnabled && $searchInput.length) {
        $searchInput.val(state.keyword);
        updateSearchControls();
    }
})(window.jQuery);
</script>
