/**
 * em 消息提示组件
 * 用法：
 *   em.msg('提示内容')
 *   em.msg('提示内容', 'success')
 *   em.msg('提示内容', 'error')
 *   em.msg('提示内容', 'warning')
 *   em.msg('提示内容', 'loading')
 *   em.msg('提示内容', { type: 'success', duration: 3000 })
 */
var em = (function() {
    // SVG图标
    var icons = {
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        loading: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>'
    };

    // 获取或创建容器
    function getContainer() {
        var container = document.querySelector('.em-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'em-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    // 创建toast元素
    function createToast(content, type) {
        var toast = document.createElement('div');
        toast.className = 'em-toast em-toast-' + type;
        toast.innerHTML =
            '<div class="em-toast-icon">' + icons[type] + '</div>' +
            '<div class="em-toast-content">' + content + '</div>';
        return toast;
    }

    // 移除toast
    function removeToast(toast) {
        toast.classList.add('em-toast-out');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 250);
    }

    // 主方法
    function msg(content, options) {
        var type = 'info';
        var duration = 2500;

        // 处理参数
        if (typeof options === 'string') {
            type = options;
        } else if (typeof options === 'object' && options !== null) {
            type = options.type || 'info';
            duration = options.duration !== undefined ? options.duration : 2500;
        }

        // 验证类型
        if (!icons[type]) {
            type = 'info';
        }

        var container = getContainer();
        var toast = createToast(content, type);

        // loading 直接添加到 body，不占用消息列表位置
        if (type === 'loading') {
            document.body.appendChild(toast);
        } else {
            container.appendChild(toast);
        }

        // 自动关闭（loading类型不自动关闭）
        var timer = null;
        if (type !== 'loading' && duration > 0) {
            timer = setTimeout(function() {
                removeToast(toast);
            }, duration);
        }

        // 返回关闭方法（用于手动关闭loading）
        return {
            close: function() {
                if (timer) clearTimeout(timer);
                removeToast(toast);
            }
        };
    }

    // 快捷方法
    function success(content, duration) {
        return msg(content, { type: 'success', duration: duration });
    }

    function error(content, duration) {
        return msg(content, { type: 'error', duration: duration });
    }

    function warning(content, duration) {
        return msg(content, { type: 'warning', duration: duration });
    }

    function loading(content) {
        return msg(content || '加载中...', { type: 'loading', duration: 0 });
    }

    return {
        msg: msg,
        success: success,
        error: error,
        warning: warning,
        loading: loading
    };
})();

/**
 * 禁用无库存的规格
 */
function initSku(data){
    // 1. 收集所有规格选项
    var specOptions = {};
    $('.spec-option').each(function() {
        var $this = $(this);
        var id = $this.data('id').toString();
        specOptions[id] = {
            element: $this,
            hasStock: true // 默认有货
        };
    });

    // 2. 获取当前选中的规格
    var currentSelectedSpecs = [];
    $('.spec-option.active').each(function() {
        currentSelectedSpecs.push($(this).data('id').toString());
    });


    // 遍历每个规格组
    $('.spec-group').each(function() {
        var $group = $(this);
        var groupIndex = $('.spec-group').index($group); // 获取当前组的索引（0=类型组，1=时长组）

        // 遍历当前组的每个规格选项
        $group.find('.spec-option').each(function() {
            var $this = $(this);
            var thisSpecId = $this.data('id').toString();

            // 如果这个规格已经被选中，则保持可用状态
            if ($this.hasClass('active')) {
                $this.removeClass('disabled').addClass('available');
                return;
            }

            // 优先级1：如果已选中其他规格，检查是否可以与已选中规格组合成有库存的SKU
            var hasValidCombination = false;

            if (currentSelectedSpecs.length > 0) {
                // 检查该规格选项是否可以与已选中的规格组合成有库存的SKU
                $.each(data.skus.option_value, function(key, val) {
                    if (val.stock > 0) {
                        var skuSpecIds = key.split('-');

                        // 检查当前SKU是否包含这个规格ID
                        if (skuSpecIds[groupIndex] === thisSpecId) {
                            // 检查当前SKU是否与已选中的其他规格兼容
                            var isCompatible = true;

                            // 直接遍历已选中的DOM元素，避免data-id冲突
                            $('.spec-option.active').each(function() {
                                var $selectedOption = $(this);
                                var selectedSpecId = $selectedOption.data('id').toString();
                                var selectedGroupIndex = $('.spec-group').index($selectedOption.closest('.spec-group'));

                                // 跳过当前正在检查的规格组
                                if (selectedGroupIndex === groupIndex) {
                                    return true; // continue
                                }

                                // 检查已选中规格是否在当前SKU中
                                if (skuSpecIds[selectedGroupIndex] !== selectedSpecId) {
                                    isCompatible = false;
                                    return false; // break
                                }
                            });

                            if (isCompatible) {
                                hasValidCombination = true;
                                return false; // 找到一个有效组合即可
                            }
                        }
                    }
                });
            } else {
                // 优先级2：如果未选中任何规格，检查该规格选项是否参与任意一个有库存的SKU组合
                $.each(data.skus.option_value, function(key, val) {
                    if (val.stock > 0) {
                        var skuSpecIds = key.split('-');

                        if (skuSpecIds[groupIndex] === thisSpecId) {
                            hasValidCombination = true;
                            return false; // 找到一个有效组合即可
                        }
                    }
                });
            }

            // 根据检查结果启用或禁用
            if (hasValidCombination) {
                $this.removeClass('disabled').addClass('available');
            } else {
                $this.addClass('disabled').removeClass('available');
            }
        });
    });
}

var couponState = {
    applied: false,
    code: '',
    discount: '0.00'
};

function getCouponCode() {
    var $input = $('#coupon_code');
    if ($input.length) {
        return $.trim($input.val());
    }
    return '';
}

function updateCouponUI() {
    var $input = $('#coupon_code');
    var $btn = $('#coupon_apply_btn');
    var $tip = $('#coupon_tip');
    var $discount = $('#coupon_discount');
    var $change = $('.coupon-change-link');
    if (!$input.length) {
        return;
    }
    if (couponState.applied) {
        $input.prop('disabled', true);
        $btn.text('使用中').addClass('layui-btn-disabled').prop('disabled', true);
        if ($change.length) {
            $change.show();
        }
        if ($tip.length) {
            if ($discount.length) {
                $discount.text(couponState.discount || '0.00');
            }
            $tip.show();
        }
    } else {
        $input.prop('disabled', false);
        $btn.text('使用').removeClass('layui-btn-disabled').prop('disabled', false);
        if ($change.length) {
            $change.hide();
        }
        if ($tip.length) {
            $tip.hide();
        }
    }
}

function resetCouponState() {
    couponState.applied = false;
    couponState.code = '';
    couponState.discount = '0.00';
    updateCouponUI();
}

function refreshGoodsInfo(options){
    options = options || {};
    var quantity = $('#quantity').val();
    var couponCode = '';
    var includeCoupon = false;
    if (options.forceCoupon) {
        couponCode = options.couponCode || getCouponCode();
        includeCoupon = couponCode !== '';
    } else if (couponState.applied) {
        couponCode = couponState.code || getCouponCode();
        includeCoupon = couponCode !== '';
    }
    // 获取所有类同时包含 spec-option 和 active 的元素
    var $activeOptions = $('.spec-option.active');
    var activeDataIds = [];
    $activeOptions.each(function() {
        activeDataIds.push($(this).data('id'));
    });
    var loadIndex = layer.load(2);
    $.ajax({
        url: '/user/shop.php?action=getGoodsInfo',
        type: 'POST',
        data: (function(){
            var payload = {
                goods_id: $('#goods_id').val(),
                quantity: quantity,
                sku_ids: activeDataIds
            };
            if (includeCoupon && couponCode) {
                payload.coupon_code = couponCode;
            }
            return payload;
        })(),
        dataType: 'json',
        timeout: 15000, // 超时时间（毫秒）
        beforeSend: function(xhr) {
            // 请求发送前的处理（如设置请求头）
        },
        success: function(e) {
            if(e.code == 400){
                layer.msg(e.msg)
                return;
            }
            $('.dynamic-price').html(e.data.price);
            $('.dynamic-stock').html(e.data.stock);
            $('.dynamic-sales').html(e.data.sales);

            // 假设返回的数据存储在response变量中
            var data = e.data;
            if (data && typeof data === 'object') {
                if (includeCoupon) {
                    if (data.coupon_error) {
                        if (options.forceCoupon || couponState.applied) {
                            layer.msg(data.coupon_error);
                        }
                        if (couponState.applied) {
                            resetCouponState();
                        }
                    } else if (data.coupon) {
                        couponState.applied = true;
                        couponState.code = data.coupon.code || couponCode;
                        couponState.discount = data.coupon.discount_amount || '0.00';
                        updateCouponUI();
                    }
                }

                var qtyInput = document.getElementById('quantity');
                if (qtyInput) {
                    if (data.min_qty) {
                        qtyInput.setAttribute('min', parseInt(data.min_qty, 10));
                    }
                    if (data.max_qty && parseInt(data.max_qty, 10) > 0) {
                        qtyInput.setAttribute('max', parseInt(data.max_qty, 10));
                    } else {
                        qtyInput.removeAttribute('max');
                    }
                }
            }
            // 1. 收集所有规格选项
            var specOptions = {};
            $('.spec-option').each(function() {
                var $this = $(this);
                var id = $this.data('id').toString();
                specOptions[id] = {
                    element: $this,
                    hasStock: true // 默认有货
                };
            });

            // 2. 获取当前选中的规格
            var currentSelectedSpecs = [];
            $('.spec-option.active').each(function() {
                currentSelectedSpecs.push($(this).data('id').toString());
            });
            

            // 遍历每个规格组
            $('.spec-group').each(function() {
                var $group = $(this);
                var groupIndex = $('.spec-group').index($group); // 获取当前组的索引（0=类型组，1=时长组）
                
                // 遍历当前组的每个规格选项
                $group.find('.spec-option').each(function() {
                    var $this = $(this);
                    var thisSpecId = $this.data('id').toString();
                    
                    // 如果这个规格已经被选中，则保持可用状态
                    if ($this.hasClass('active')) {
                        $this.removeClass('disabled').addClass('available');
                        return;
                    }
                    
                    // 优先级1：如果已选中其他规格，检查是否可以与已选中规格组合成有库存的SKU
                    var hasValidCombination = false;
                    
                    if (currentSelectedSpecs.length > 0) {
                        // 检查该规格选项是否可以与已选中的规格组合成有库存的SKU
                        $.each(data.skus.option_value, function(key, val) {
                            if (val.stock > 0) {
                                var skuSpecIds = key.split('-');
                                
                                // 检查当前SKU是否包含这个规格ID
                                if (skuSpecIds[groupIndex] === thisSpecId) {
                                    // 检查当前SKU是否与已选中的其他规格兼容
                                    var isCompatible = true;
                                    
                                    // 直接遍历已选中的DOM元素，避免data-id冲突
                                    $('.spec-option.active').each(function() {
                                        var $selectedOption = $(this);
                                        var selectedSpecId = $selectedOption.data('id').toString();
                                        var selectedGroupIndex = $('.spec-group').index($selectedOption.closest('.spec-group'));
                                        
                                        // 跳过当前正在检查的规格组
                                        if (selectedGroupIndex === groupIndex) {
                                            return true; // continue
                                        }
                                        
                                        // 检查已选中规格是否在当前SKU中
                                        if (skuSpecIds[selectedGroupIndex] !== selectedSpecId) {
                                            isCompatible = false;
                                            return false; // break
                                        }
                                    });
                                    
                                    if (isCompatible) {
                                        hasValidCombination = true;
                                        return false; // 找到一个有效组合即可
                                    }
                                }
                            }
                        });
                    } else {
                        // 优先级2：如果未选中任何规格，检查该规格选项是否参与任意一个有库存的SKU组合
                        $.each(data.skus.option_value, function(key, val) {
                            if (val.stock > 0) {
                                var skuSpecIds = key.split('-');
                                
                                if (skuSpecIds[groupIndex] === thisSpecId) {
                                    hasValidCombination = true;
                                    return false; // 找到一个有效组合即可
                                }
                            }
                        });
                    }
                    
                    // 根据检查结果启用或禁用
                    if (hasValidCombination) {
                        $this.removeClass('disabled').addClass('available');
                    } else {
                        $this.addClass('disabled').removeClass('available');
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            // 请求失败的回调（超时、网络错误、服务器错误等）
            if(error == 'timeout'){
                layer.msg('请求超时，请重试');
            }else{
                layer.msg('请求失败：' + error);
            }

        },
        complete: function(xhr, status) {
            // 请求完成的回调（无论成功/失败都会执行）
            layer.close(loadIndex);
        }
    });
}

function getQuantityLimits() {
    var input = document.getElementById('quantity');
    var min = 1;
    var max = 0;
    if (input) {
        var minAttr = parseInt(input.getAttribute('min'), 10);
        var maxAttr = input.getAttribute('max');
        var maxParsed = maxAttr ? parseInt(maxAttr, 10) : 0;
        if (!isNaN(minAttr) && minAttr > 0) {
            min = minAttr;
        }
        if (!isNaN(maxParsed) && maxParsed > 0) {
            max = maxParsed;
        }
    }
    if (max > 0 && max < min) {
        max = min;
    }
    return { min: min, max: max };
}

function clampQuantity(value, limits) {
    var qty = parseInt(value, 10);
    if (isNaN(qty)) {
        qty = limits.min;
    }
    if (qty < limits.min) {
        qty = limits.min;
    }
    if (limits.max > 0 && qty > limits.max) {
        qty = limits.max;
    }
    return qty;
}

function validateQuantity(notify) {
    var input = document.getElementById('quantity');
    if (!input) {
        return { valid: true, value: 1, limits: { min: 1, max: 0 } };
    }
    var limits = getQuantityLimits();
    var raw = parseInt(input.value, 10);
    var clamped = clampQuantity(raw, limits);
    if (raw !== clamped) {
        input.value = clamped;
        if (notify && window.layer) {
            if (raw < limits.min) {
                layer.msg('最小购买数量为' + limits.min);
            } else if (limits.max > 0 && raw > limits.max) {
                layer.msg('最大购买数量为' + limits.max);
            }
        }
        return { valid: false, value: clamped, limits: limits };
    }
    return { valid: true, value: clamped, limits: limits };
}


$(function(){
    /* 弹窗事件 */
    $("body").on("click", ".em-modal", function(){
        var modal = $(this).data('modal');
        $('#'+modal).fadeIn(300);
        $('body').css('overflow', 'hidden');

        // 重新渲染Layui表单
        layui.use('form', function() {
            layui.form.render();
        });
    })

    // 绑定关闭事件
    $('.close-modal-btn').on('click', function() {
        var modal = $(this).data('modal');
        hideEmModal(modal);
    });

    // 抽屉内数量选择
    var minusBtn = document.getElementById('drawerMinusBtn');
    var plusBtn = document.getElementById('drawerPlusBtn');
    var quantityInput = document.getElementById('quantity');

    $('#drawerMinusBtn').on('click', function() {
        var limits = getQuantityLimits();
        var quantity = clampQuantity(quantityInput.value, limits);
        if (quantity > limits.min) {
            quantityInput.value = quantity - 1;
            refreshGoodsInfo();
            return;
        }
        if (window.layer) {
            layer.msg('最小购买数量为' + limits.min);
        }
    });

    $('#drawerPlusBtn').on('click', function() {
        var limits = getQuantityLimits();
        var quantity = clampQuantity(quantityInput.value, limits);
        if (limits.max > 0 && quantity >= limits.max) {
            if (window.layer) {
                layer.msg('最大购买数量为' + limits.max);
            }
            return;
        }
        quantityInput.value = quantity + 1;
        refreshGoodsInfo();
    });

    $('#buyForm #quantity').on('change', function(){
        validateQuantity(true);
        refreshGoodsInfo();
    })

    var couponBtn = $('#coupon_apply_btn');
    if (couponBtn.length) {
        couponBtn.on('click', function() {
            var code = getCouponCode();
            if (!code) {
                layer.msg('请输入优惠券码');
                return;
            }
            refreshGoodsInfo({ forceCoupon: true, couponCode: code });
        });
    }

    var couponChange = $('.coupon-change-link');
    if (couponChange.length) {
        couponChange.on('click', function() {
            resetCouponState();
            refreshGoodsInfo();
        });
    }

    updateCouponUI();



    // 规格选择
    var specOptions = document.querySelectorAll('.spec-option');
    specOptions.forEach(function(option) {
        option.addEventListener('click', function() {

            if (this.classList.contains('disabled')) {
                return;
            }

            var parent = this.parentElement;
            // 判断当前选项是否已激活
            if (this.classList.contains('active')) {
                // 已激活则直接取消
                this.classList.remove('active');
            } else {
                // 未激活则先移除同组其他选项的active，再给当前选项添加
                parent.querySelectorAll('.spec-option').forEach(function(item) {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            }
            // 无论选中还是取消，都重新计算价格库存
            refreshGoodsInfo();
        });
    });

    // 支付方式选择
    var $paymentItems = $('.payment-item');
    $paymentItems.click(function() {
        // 移除其他选中状态
        $paymentItems.removeClass('active');
        // 添加当前选中状态
        $(this).addClass('active');
    });

    // 默认选中第一个
    $paymentItems.eq(0).addClass('active');
    
})


    
function hideEmModal(modal){
    $('#'+modal).fadeOut(300);
    $('body').css('overflow', 'auto');
} 

// v2 提交订单
function toBuy(){
    var qtyCheck = validateQuantity(true);
    if (!qtyCheck.valid) {
        return;
    }
    // 使用jQuery的serializeArray()获取表单数据，然后转换为对象
    const formDataArray = $('#buyForm').serializeArray();
    const formData = {};
    
    // 处理表单数据，包括多选的情况
    formDataArray.forEach(function(item) {
        if (formData[item.name]) {
            // 如果字段已存在，转换为数组
            if (!Array.isArray(formData[item.name])) {
                formData[item.name] = [formData[item.name]];
            }
            formData[item.name].push(item.value);
        } else {
            formData[item.name] = item.value;
        }
    });
    
    // 获取支付方式
    var payment_plugin = $('.payment-item.active').data('method');
    var payment_name = $('.payment-item.active').data('name');
    var payment_title = $('.payment-item.active .payment-name').text();
    if (!payment_plugin) {
        layer.msg('当前商品暂未配置可用支付方式');
        return;
    }

    // 获取所有选中的规格 和 active 的元素
    var $activeOptions = $('.spec-option.active');
    var activeDataIds = [];
    $activeOptions.each(function() {
        activeDataIds.push($(this).data('id'));
    });
    
    // 添加到表单数据中
    formData.payment_plugin = payment_plugin;
    formData.payment_title = payment_title;
    formData.payment_name = payment_name;
    formData.sku_ids = activeDataIds;
    if (couponState.applied) {
        formData.coupon_code = couponState.code || getCouponCode();
    } else if (formData.coupon_code) {
        delete formData.coupon_code;
    }
    
    console.log('表单数据：', formData);
    // 显示加载层
    let loadIndex = layer.load(2);
    
    // 发送AJAX请求
    $.ajax({
        url: '/user/shop.php?action=xiadan',
        type: 'POST',
        data: formData,
        dataType: 'json',
        timeout: 20000, // 超时时间（毫秒）
        success: function(response) {
            if(response.code == 400){
                layer.msg(response.msg);
            }
            if(response.code == 200){
                layer.msg('正在跳转支付页面');
                location.href="/?action=pay&out_trade_no=" + response.data.out_trade_no;
            }
            if(response.code == 302){
                layer.msg(response.msg);
                location.href=response.url;
            }
        },
        error: function(xhr, status, error) {
            // 请求失败的回调
            if(status == 'timeout'){
                layer.msg('请求超时，请重试');
            }else{
                layer.msg('请求失败：' + error);
            }
        },
        complete: function(xhr, status) {
            // 请求完成的回调（无论成功/失败都会执行）
            layer.close(loadIndex);
        }
    });
}

// v1 提交订单
function toBuyNow(goods_id){
    var quantity = $('#goods-quantity').val();
    var couponCode = couponState.applied ? (couponState.code || getCouponCode()) : '';
    // closeDrawerFunc();
    // 获取所有类同时包含 spec-option 和 active 的元素
    var $activeOptions = $('.spec-option.active');
    var activeDataIds = [];
    $activeOptions.each(function() {
        activeDataIds.push($(this).data('id'));
    });
    var payment_plugin = $('.payment-item.active').data('method');
    var payment_title = $('.payment-item.active .payment-name').text();
    if (!payment_plugin) {
        layer.msg('当前商品暂未配置可用支付方式');
        return;
    }

    const attach = {};
    $('.attach-input').each(function() {
        const $input = $(this);
        const value = $input.val().trim(); // 获取当前输入框的值
        const name = $input.attr('name'); // 获取 name 属性，如 "attach[手机号]"

        // 3. 解析 name 中的 key（如从 "attach[手机号]" 中提取 "手机号"）
        const key = name.match(/attach\[(.+?)\]/)[1];

        // 4. 存入对象（键为解析出的 key，值为输入框的值）
        attach[key] = value;
    });

    const required = {};
    $('.required-input').each(function() {
        const $input = $(this);
        const value = $input.val().trim(); // 获取当前输入框的值
        const name = $input.attr('name'); // 获取 name 属性，如 "required[手机号]"

        // 3. 解析 name 中的 key（如从 "attach[手机号]" 中提取 "手机号"）
        const key = name.match(/required\[(.+?)\]/)[1];

        // 4. 存入对象（键为解析出的 key，值为输入框的值）
        required[key] = value;
    });



    console.log('商品规格：' + activeDataIds);
    console.log('购买数量：' + quantity);
    console.log('支付插件：' + payment_plugin);
    console.log('支付名称：' + payment_title);
    console.log('附件内容：' + attach);
    console.log('必填项：' + required);


    // 开始下单
    let loadIndex = layer.load(2);
    var submitData = {
        goods_id: goods_id,
        quantity: quantity,
        sku_ids: activeDataIds,
        payment_plugin: payment_plugin,
        payment_title: payment_title,
        attach: attach,
        required: required
    };
    if (couponState.applied && couponCode) {
        submitData.coupon_code = couponCode;
    }

    $.ajax({
        url: '/user/shop.php?action=xiadan',
        type: 'POST',
        data: submitData,
        dataType: 'json',
        timeout: 20000, // 超时时间（毫秒）
        beforeSend: function(xhr) {
            // 请求发送前的处理（如设置请求头）
        },
        success: function(e) {
            if(e.code == 400){
                layer.msg(e.msg);
            }
            if(e.code == 200){
                layer.msg('正在跳转支付页面');
                location.href="/?action=pay&out_trade_no=" + e.data.out_trade_no;
            }
        },
        error: function(xhr, status, error) {
            // 请求失败的回调（超时、网络错误、服务器错误等）
            if(error == 'timeout'){
                layer.msg('请求超时，请重试');
            }else{
                layer.msg('请求失败：' + error);
            }

        },
        complete: function(xhr, status) {
            // 请求完成的回调（无论成功/失败都会执行）
            layer.close(loadIndex);
        }
    });
}
