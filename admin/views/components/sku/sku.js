/**
 * EmSku - 简化版 SKU 组件
 * 约定优于配置
 *
 * 用法:
 *   EmSku.init({ goods_id: 123, type_id: 0 });
 *
 * @package ESHOP
 */
const EmSku = (function() {
    'use strict';

    const API_URL = './sku_api.php';

    // 组件状态
    let state = {
        goods_id: 0,
        type_id: 0,
        goods_type: '',
        mode: 'single',  // single | multi | remote
        templates: [],
        members: [],
        price_fields: [],
        spec: [],
        sku_data: {},
        remote_skus: [], // 对接商品的 SKU 组合
        remote_spec_names: [],
        initialized: false,
        loading_count: 0,
        loading_message: '',
        last_error: ''
    };

    // jQuery 和 Layui 引用
    let $, form, layer;

    /**
     * 初始化 SKU 组件
     */
    function init(options) {
        $ = layui.$;
        form = layui.form;
        layer = layui.layer;

        state.goods_id = options.goods_id || 0;
        state.type_id = options.type_id || 0;
        state.goods_type = options.goods_type || '';
        state.last_error = '';

        beginLoading('规格信息加载中...');

        // 一次请求加载所有数据
        $.get(API_URL + '?action=init', {
            goods_id: state.goods_id,
            type_id: state.type_id,
            goods_type: state.goods_type
        }, function(res) {
            if (res.code !== 0) {
                state.last_error = res.msg || '加载 SKU 数据失败';
                layer.msg(res.msg || '加载 SKU 数据失败');
                return;
            }

            // 更新状态
            Object.assign(state, res.data);
            state.initialized = true;
            state.last_error = '';

            // 渲染 UI
            if (state.mode === 'remote') {
                // 对接商品模式：隐藏规格类型选择、模板选择、规格选择
                showRemoteMode();
                renderRemoteSkuTable();
            } else if (state.mode === 'multi') {
                renderTemplateSelect();
                setModeRadio();
                showMultiMode();
                if (state.type_id > 0) {
                    renderSpecTable();
                    renderMultiSkuTable();
                } else {
                    $('#em-sku-table').html('<div class="em-sku-empty">请先选择规格模板</div>');
                }
            } else {
                renderTemplateSelect();
                setModeRadio();
                showSingleMode();
                renderSingleSkuTable();
            }

            // 绑定事件
            bindEvents();

        }, 'json').fail(function(xhr) {
            state.last_error = '规格信息加载失败，请刷新页面后重试';
            notifyStatus();
            layer.msg('网络错误');
        }).always(function() {
            endLoading();
        });
    }

    /**
     * 渲染模板下拉框选项
     */
    function renderTemplateSelect() {
        let html = '<option value="">请选择规格模板</option>';
        state.templates.forEach(function(t) {
            const selected = t.id == state.type_id ? 'selected' : '';
            html += '<option value="' + t.id + '" ' + selected + '>' + escapeHtml(t.title) + '</option>';
        });
        $('#em-template-select').html(html);
        form.render('select');
    }

    /**
     * 设置规格类型单选按钮
     */
    function setModeRadio() {
        const val = state.mode === 'multi' ? 'y' : 'n';
        $('input[name="is_sku"][value="' + val + '"]').prop('checked', true);
        form.render('radio');
    }

    /**
     * 显示单规格模式 UI
     */
    function showSingleMode() {
        $('#em-sku-mode').show();
        $('#em-sku-template').hide();
        $('#em-sku-spec-wrap').hide();
        state.mode = 'single';
    }

    /**
     * 显示多规格模式 UI
     */
    function showMultiMode() {
        $('#em-sku-mode').show();
        $('#em-sku-template').show();
        state.mode = 'multi';
    }

    /**
     * 显示对接商品模式 UI（隐藏规格相关选项）
     */
    function showRemoteMode() {
        $('#em-sku-mode').hide();
        $('#em-sku-template').hide();
        $('#em-sku-spec-wrap').hide();
        // 禁用原来的 radio 按钮，防止被提交
        $('input[name="is_sku"]').prop('disabled', true);
        // 添加隐藏字段保持 group_id = -1（对接商品标识）
        if ($('input[name="group_id"][type="hidden"]').length === 0) {
            $('#em-sku-widget').append('<input type="hidden" name="group_id" value="-1" />');
        }
    }

    /**
     * 渲染单规格表格
     */
    function renderSingleSkuTable() {
        const singleData = state.sku_data['0'] || {};
        beginLoading('规格信息加载中...');
        $('#em-sku-table').html('<div class="em-sku-empty">加载中...</div>');

        $.get(API_URL + '?action=render_single', {
            price_fields: JSON.stringify(state.price_fields),
            sku_data: JSON.stringify(singleData)
        }, function(html) {
            state.last_error = '';
            $('#em-sku-table').html(html);
            form.render();
        }).fail(function() {
            state.last_error = '规格信息加载失败，请刷新页面后重试';
            notifyStatus();
            layer.msg('渲染单规格信息失败');
        }).always(function() {
            endLoading();
        });
    }

    /**
     * 渲染规格选择表格
     */
    function renderSpecTable() {
        if (state.spec.length === 0) {
            $('#em-sku-spec-wrap').hide();
            $('#em-sku-spec').html('');
            return;
        }

        beginLoading('规格信息加载中...');
        $.get(API_URL + '?action=render_spec', {
            specs: JSON.stringify(state.spec)
        }, function(html) {
            state.last_error = '';
            $('#em-sku-spec-wrap').show();
            $('#em-sku-spec').html(html);
            form.render('checkbox');
        }).fail(function() {
            state.last_error = '规格信息加载失败，请刷新页面后重试';
            notifyStatus();
            layer.msg('渲染规格选择失败');
        }).always(function() {
            endLoading();
        });
    }

    /**
     * 渲染多规格表格
     */
    function renderMultiSkuTable() {
        const combinations = generateCombinations();

        if (combinations.length === 0) {
            $('#em-sku-table').html('<div class="em-sku-empty">请先选择规格值，系统将自动生成SKU组合</div>');
            return;
        }

        const specNames = state.spec
            .filter(function(s) { return s.value.length > 0; })
            .map(function(s) { return s.title; });

        beginLoading('规格信息加载中...');
        $('#em-sku-table').html('<div class="em-sku-empty">加载中...</div>');
        $.post(API_URL, {
            action: 'render_multi',
            combinations: JSON.stringify(combinations),
            price_fields: JSON.stringify(state.price_fields),
            sku_data: JSON.stringify(state.sku_data),
            spec_names: JSON.stringify(specNames)
        }, function(html) {
            state.last_error = '';
            $('#em-sku-table').html(html);
            form.render();
        }).fail(function() {
            state.last_error = '规格信息加载失败，请刷新页面后重试';
            notifyStatus();
            layer.msg('渲染多规格信息失败');
        }).always(function() {
            endLoading();
        });
    }

    /**
     * 渲染对接商品的 SKU 表格
     */
    function renderRemoteSkuTable() {
        // 判断是单规格还是多规格
        if (state.remote_skus.length === 0) {
            // 单规格对接商品
            const singleData = state.sku_data['0'] || {};
            beginLoading('规格信息加载中...');
            $('#em-sku-table').html('<div class="em-sku-empty">加载中...</div>');
            $.get(API_URL + '?action=render_single', {
                price_fields: JSON.stringify(state.price_fields),
                sku_data: JSON.stringify(singleData)
            }, function(html) {
                state.last_error = '';
                $('#em-sku-table').html(html);
                // 添加隐藏字段 is_sku = n（先移除可能存在的旧字段）
                $('input[name="is_sku"][type="hidden"]').remove();
                $('#em-sku-table').prepend('<input type="hidden" name="is_sku" value="n" />');
                form.render();
            }).fail(function() {
                state.last_error = '规格信息加载失败，请刷新页面后重试';
                notifyStatus();
                layer.msg('渲染对接商品规格失败');
            }).always(function() {
                endLoading();
            });
        } else {
            // 多规格对接商品
            beginLoading('规格信息加载中...');
            $('#em-sku-table').html('<div class="em-sku-empty">加载中...</div>');
            $.post(API_URL, {
                action: 'render_multi',
                combinations: JSON.stringify(state.remote_skus),
                price_fields: JSON.stringify(state.price_fields),
                sku_data: JSON.stringify(state.sku_data),
                spec_names: JSON.stringify(state.remote_spec_names.length > 0 ? state.remote_spec_names : ['规格'])
            }, function(html) {
                state.last_error = '';
                $('#em-sku-table').html(html);
                // 添加隐藏字段 is_sku = y（先移除可能存在的旧字段）
                $('input[name="is_sku"][type="hidden"]').remove();
                $('#em-sku-table').prepend('<input type="hidden" name="is_sku" value="y" />');
                form.render();
            }).fail(function() {
                state.last_error = '规格信息加载失败，请刷新页面后重试';
                notifyStatus();
                layer.msg('渲染对接商品规格失败');
            }).always(function() {
                endLoading();
            });
        }
    }

    /**
     * 根据选中的规格值生成 SKU 组合（笛卡尔积）
     */
    function generateCombinations() {
        const selectedSpecs = [];

        state.spec.forEach(function(spec) {
            const selectedValues = [];
            spec.value.forEach(function(valId) {
                const opt = spec.options.find(function(o) { return o.id == valId; });
                if (opt) {
                    selectedValues.push({ id: valId, name: opt.title });
                }
            });
            if (selectedValues.length > 0) {
                selectedSpecs.push({
                    title: spec.title,
                    values: selectedValues
                });
            }
        });

        if (selectedSpecs.length === 0) {
            return [];
        }

        // 笛卡尔积
        return selectedSpecs.reduce(function(acc, spec) {
            if (acc.length === 0) {
                return spec.values.map(function(v) {
                    return { sku: String(v.id), values: [v.name] };
                });
            }
            const result = [];
            acc.forEach(function(item) {
                spec.values.forEach(function(v) {
                    result.push({
                        sku: item.sku + '-' + v.id,
                        values: item.values.concat([v.name])
                    });
                });
            });
            return result;
        }, []);
    }

    /**
     * 保存当前表单数据（重新渲染前调用）
     */
    function preserveFormData() {
        // 保存单规格数据
        $('#em-single-sku-table tbody tr').each(function() {
            const data = {};
            $(this).find('input').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    // 解析: skus[field] 或 skus[member][id]
                    const match = name.match(/skus\[([^\]]+)\](?:\[([^\]]+)\])?/);
                    if (match) {
                        if (match[2]) {
                            data['member_' + match[2]] = $(this).val();
                        } else {
                            data[match[1]] = $(this).val();
                        }
                    }
                }
            });
            state.sku_data['0'] = data;
        });

        // 保存多规格数据
        $('#em-multi-sku-table tbody tr').each(function() {
            const sku = $(this).data('sku');
            if (!sku) return;

            if (!state.sku_data[sku]) {
                state.sku_data[sku] = {};
            }

            $(this).find('input').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    // 解析: skus[sku][field] 或 skus[sku][member][id]
                    const match = name.match(/skus\[[^\]]+\]\[([^\]]+)\](?:\[([^\]]+)\])?/);
                    if (match) {
                        if (match[2]) {
                            state.sku_data[sku]['member_' + match[2]] = $(this).val();
                        } else {
                            state.sku_data[sku][match[1]] = $(this).val();
                        }
                    }
                }
            });
        });
    }

    /**
     * 绑定所有事件处理器
     */
    function bindEvents() {
        // 对接商品模式不需要绑定这些事件
        if (state.mode === 'remote') {
            bindBatchFillEvent();
            return;
        }

        // 规格类型切换（单规格/多规格）
        form.on('radio(em-sku-mode)', function(data) {
            if (data.value === 'y') {
                showMultiMode();
                if (state.type_id > 0) {
                    renderSpecTable();
                    renderMultiSkuTable();
                } else {
                    $('#em-sku-spec-wrap').hide();
                    $('#em-sku-table').html('<div class="em-sku-empty">请先选择规格模板</div>');
                }
            } else {
                preserveFormData();
                showSingleMode();
                renderSingleSkuTable();
            }
        });

        // 模板切换
        form.on('select(em-template-select)', function(data) {
            state.type_id = parseInt(data.value) || 0;

            if (state.type_id === 0) {
                state.spec = [];
                $('#em-sku-spec-wrap').hide();
                $('#em-sku-spec').html('');
                $('#em-sku-table').html('<div class="em-sku-empty">请先选择规格模板</div>');
                return;
            }

            // 加载新模板的规格数据
            beginLoading('规格信息加载中...');
            $('#em-sku-table').html('<div class="em-sku-empty">加载中...</div>');
            $.get(API_URL + '?action=load_spec', {
                type_id: state.type_id,
                goods_id: state.goods_id
            }, function(res) {
                if (res.code !== 0) {
                    state.last_error = res.msg || '加载规格失败';
                    notifyStatus();
                    layer.msg(res.msg || '加载规格失败');
                    return;
                }
                state.last_error = '';
                state.spec = res.data.spec || [];
                renderSpecTable();
                renderMultiSkuTable();
            }, 'json').fail(function() {
                state.last_error = '规格信息加载失败，请刷新页面后重试';
                notifyStatus();
                layer.msg('加载规格失败');
            }).always(function() {
                endLoading();
            });
        });

        // 规格值勾选（使用 Layui 表单事件）
        form.on('checkbox(em-spec-checkbox)', function(data) {
            const $this = $(data.elem);
            const specId = $this.data('spec-id');
            const valueId = String(data.value);
            const checked = data.elem.checked;

            // 更新状态
            state.spec.forEach(function(s) {
                if (s.id == specId) {
                    if (checked) {
                        if (s.value.indexOf(valueId) === -1) {
                            s.value.push(valueId);
                        }
                    } else {
                        s.value = s.value.filter(function(v) { return v != valueId; });
                    }
                }
            });

            // 保存表单数据并重新渲染
            preserveFormData();
            renderMultiSkuTable();
        });

        // 批量填充
        bindBatchFillEvent();
    }

    /**
     * 绑定批量填充事件
     */
    function bindBatchFillEvent() {
        $(document).off('click.embatch').on('click.embatch', '.em-batch-fill', function() {
            const field = $(this).data('field');
            const fieldLabel = $(this).parent().text().trim();

            layer.prompt({
                title: '批量填充: ' + fieldLabel,
                formType: 0
            }, function(value, index) {
                $('input[data-field="' + field + '"]').val(value);
                layer.close(index);
            });
        });
    }

    /**
     * HTML 实体转义
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function beginLoading(message) {
        state.loading_count += 1;
        if (message) {
            state.loading_message = message;
        }
        notifyStatus();
    }

    function endLoading() {
        state.loading_count = Math.max(0, state.loading_count - 1);
        if (state.loading_count === 0) {
            state.loading_message = '';
        }
        notifyStatus();
    }

    function getStatus() {
        const busy = !state.initialized || state.loading_count > 0 || !!state.last_error;
        const message = state.last_error || state.loading_message || '规格信息加载中，请稍候...';
        return {
            busy: busy,
            ready: !busy,
            message: message,
            last_error: state.last_error,
            loading_count: state.loading_count
        };
    }

    function notifyStatus() {
        const status = getStatus();
        const $widget = $('#em-sku-widget');
        if ($widget.length) {
            $widget.attr('data-loading', status.busy ? '1' : '0');
            $widget.attr('aria-busy', status.busy ? 'true' : 'false');
        }
        if ($) {
            $(document).trigger('emSku:status', [status]);
        }
    }

    // 公开 API
    return {
        init: init,
        getState: function() { return state; },
        getStatus: getStatus,
        isBusy: function() { return getStatus().busy; }
    };
})();
