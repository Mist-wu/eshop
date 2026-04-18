<?php
/*
Plugin Name: 数据统计
Version: 1.0.2
Plugin URL:
Description: 在后台首页展示数据统计图表
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

function adm_home(){
    echo <<<html
<style>
/* 统计图表卡片样式 */
.chart-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #EDF2F1;
    transition: all 0.5s ease;
    padding: 24px;
    height: 100%;
    position: relative;
}
.chart-card:hover {
    // transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(76, 125, 113, 0.15);
    border-color: #4C7D71;
}

/* 按钮组容器 */
.plugin_adm_home_filter {
    // display: flex;
    // justify-content: flex-end;
    margin-bottom: 15px;
    margin-top: 20px;
}

.plugin_adm_home_div {
    background: #fff;
    padding: 6px;
    border-radius: 8px;
    display: inline-flex;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    border: 1px solid #EDF2F1;
    width: calc(100% - 14px);
}

.plugin_adm_home_div .layui-btn {
    background: transparent;
    color: #6B7280;
    border: none;
    border-radius: 6px;
    height: 32px;
    line-height: 32px;
    padding: 0 16px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
    margin: 0 !important;
}

.plugin_adm_home_div .layui-btn:hover {
    color: #4C7D71;
    background: #F3F4F6;
}

.plugin_adm_home_div .layui-btn.active {
    background: #4C7D71;
    color: #fff;
    box-shadow: 0 2px 4px rgba(76, 125, 113, 0.2);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.chart-title {
    font-size: 18px;
    font-weight: 700;
    color: #1F2937;
    display: flex;
    align-items: center;
}
.chart-title::before {
    content: '';
    display: block;
    width: 4px;
    height: 18px;
    background: #4C7D71;
    border-radius: 2px;
    margin-right: 10px;
}
</style>
<script src="/content/plugins/adm_home/js/echarts.min.js"></script>

<div class="plugin_adm_home_filter">
    <div class="plugin_adm_home_div">
        <button type="button" class="layui-btn active" data-type="">最近7天</button>
        <button type="button" class="layui-btn " data-type="week">本周</button>
        <button type="button" class="layui-btn" data-type="month">本月</button>
        <button type="button" class="layui-btn" data-type="year">今年</button>
    </div>
</div>

<div style="padding: 0px;margin: 0 auto;" class="grid-cols-lg-1 grid-cols-xl-2 grid-gap-12">
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">销售与利润趋势</div>
        </div>
        <div id="adm-home-one" style="width: 100%;height:400px;"></div>
    </div>
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">订单数量与下单用户</div>
        </div>
        <div id="adm-home-two" style="width: 100%;height:400px;"></div>
    </div>
</div>

<script type="text/javascript">
    // 基于准备好的dom，初始化echarts实例
    var admHomeOne = echarts.init(document.getElementById('adm-home-one'));
    // 指定图表的配置项和数据
    var admHomeOneOption = {
        backgroundColor: 'transparent',
        grid: {
            top: 40,
            right: 20,
            bottom: 20,
            left: 20,
            containLabel: true
        },
        legend: {
            top: 0,
            right: 0,
            itemWidth: 10,
            itemHeight: 10,
            textStyle: { color: '#6B7280' }
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'line', lineStyle: { color: '#EDF2F1' } },
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            borderColor: '#EDF2F1',
            textStyle: { color: '#1F2937' },
            extraCssText: 'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);'
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: [],
            axisLine: { lineStyle: { color: '#EDF2F1' } },
            axisLabel: { color: '#6B7280', margin: 15 }
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: '#F3F4F6', type: 'dashed' } },
            axisLabel: { color: '#6B7280' }
        },
        series: [{
            data: [],
            type: 'line',
            name: '销售额',
            smooth: true,
            symbol: 'circle',
            symbolSize: 6,
            areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                    offset: 0, color: 'rgba(76, 125, 113, 0.2)'
                }, {
                    offset: 1, color: 'rgba(76, 125, 113, 0)'
                }])
            },
            lineStyle: { width: 3, color: '#4C7D71' },
            itemStyle: { color: '#4C7D71', borderWidth: 2, borderColor: '#fff' }
        },{
            data: [],
            type: 'line',
            name: '利润',
            smooth: true,
            symbol: 'circle',
            symbolSize: 6,
            areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                    offset: 0, color: 'rgba(252, 211, 77, 0.2)'
                }, {
                    offset: 1, color: 'rgba(252, 211, 77, 0)'
                }])
            },
            lineStyle: { width: 3, color: '#FCD34D' },
            itemStyle: { color: '#FCD34D', borderWidth: 2, borderColor: '#fff' }
        }]
    };
    
    admHomeOne.setOption(admHomeOneOption);
    
    var admHomeTwo = echarts.init(document.getElementById('adm-home-two'));
    var admHomeTwoOption = {
        backgroundColor: 'transparent',
        grid: {
            top: 40,
            right: 20,
            bottom: 20,
            left: 20,
            containLabel: true
        },
        legend: {
            top: 0,
            right: 0,
            itemWidth: 10,
            itemHeight: 10,
            textStyle: { color: '#6B7280' }
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            borderColor: '#EDF2F1',
            textStyle: { color: '#1F2937' },
            extraCssText: 'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);'
        },
        xAxis: {
            type: 'category',
            data: [],
            axisLine: { lineStyle: { color: '#EDF2F1' } },
            axisLabel: { color: '#6B7280', margin: 15 }
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: '#F3F4F6', type: 'dashed' } },
            axisLabel: { color: '#6B7280' }
        },
        series: [{
            data: [],
            type: 'bar',
            name: '订单数量',
            barWidth: 20,
            itemStyle: { 
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#4C7D71' },
                    { offset: 1, color: '#6BA596' }
                ]),
                borderRadius: [4, 4, 0, 0]
            }
        },{
            data: [],
            type: 'bar',
            name: '下单用户',
            barWidth: 20,
            itemStyle: { 
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#1F2937' },
                    { offset: 1, color: '#374151' }
                ]),
                borderRadius: [4, 4, 0, 0]
            }
        }]
    };
    
    admHomeTwo.setOption(admHomeTwoOption);
    
    function plugin_adm_home_data(type = ""){
        $.get("/?plugin=adm_home&type=" + type, function(e){
            console.log(e)
            admHomeOneOption.xAxis.data = e.data.oneTitle;
            admHomeTwoOption.xAxis.data = e.data.oneTitle;
            admHomeOneOption.series[0].data = e.data.oneValue[0];
            admHomeOneOption.series[1].data = e.data.oneValue[1];
            admHomeOne.setOption(admHomeOneOption);
            
            admHomeTwoOption.series[0].data = e.data.twoValue[0];
            admHomeTwoOption.series[1].data = e.data.twoValue[1];
            admHomeTwo.setOption(admHomeTwoOption);
        }, "json");
    }
    
    setTimeout(function(){
        plugin_adm_home_data()
    }, 100);
    
    $('.plugin_adm_home_div .layui-btn').click(function(){
        $('.plugin_adm_home_div .layui-btn').removeClass('active');
        $(this).addClass('active');
        plugin_adm_home_data($(this).data('type'));
    })
    
    // 监听窗口大小变化，自适应图表
    window.addEventListener('resize', function() {
        admHomeOne.resize();
        admHomeTwo.resize();
    });
    
</script>

html;
}

addAction('adm_main_content', 'adm_home');
