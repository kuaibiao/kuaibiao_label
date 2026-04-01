<template>
    <div class="layout">
        <Card>
            <p slot="title" style="text-align:center;font-size:16px">{{$t('operator_my_performance')}}</p>
            <div id="service_request_con2">
                <chart :options="chart_data" ref="chart_data" auto-resize :style="{height:'300px',width:'100%'}"></chart>
            </div>
        </Card>
        <Card>
            <p slot="title" style="text-align:center;font-size:16px;margin-top:20px;">{{$t('operator_performance_ranking')}}</p>
            <div>
                <chart :options="chart_data_totle" ref="chart_data" auto-resize :style="{height:'300px',width:'100%'}"></chart>
            </div>
        </Card>
    </div>
</template>
<script>
// import echarts from 'echarts';
import api from "@/api";
import util from "@/libs/util";
import Vue from "vue";
import ECharts from 'vue-echarts';

import 'echarts/lib/chart/line';
import 'echarts/lib/chart/bar';
import 'echarts/lib/chart/pie';
import 'echarts/lib/component/legend';
import 'echarts/lib/component/tooltip';
import 'echarts/lib/component/title';
import 'echarts/lib/component/toolbox.js';
import 'echarts/lib/component/dataZoom.js';

Vue.component('chart', ECharts);

var posList = [
    'left', 'right', 'top', 'bottom',
    'inside',
    'insideTop', 'insideLeft', 'insideRight', 'insideBottom',
    'insideTopLeft', 'insideTopRight', 'insideBottomLeft', 'insideBottomRight'
];
const configParameters = {
    rotate: {
        min: -90,
        max: 90
    },
    align: {
        options: {
            left: 'left',
            center: 'center',
            right: 'right'
        }
    },
    verticalAlign: {
        options: {
            top: 'top',
            middle: 'middle',
            bottom: 'bottom'
        }
    },
    position: {
        options: posList.reduce((map, pos) => {
            map[pos] = pos;
            return map;
        }, {}),
        // options: echarts.util.reduce(posList, function (map, pos) {
        //     map[pos] = pos;
        //     return map;
        // }, {})
    },
    distance: {
        min: 0,
        max: 100
    }
};

const config = {
    rotate: 90,
    align: 'left',
    verticalAlign: 'middle',
    position: 'insideBottom',
    distance: 15,
    onChange: function () {
        var labelOption = {
            normal: {
                rotate: config.rotate,
                align: config.align,
                verticalAlign: config.verticalAlign,
                position: config.position,
                distance: config.distance
            }
        };
        ECharts.setOption({
            series: [{
                label: labelOption
            }, {
                label: labelOption
            }, {
                label: labelOption
            }, {
                label: labelOption
            }]
        });
    }
};

const labelOption = {
    normal: {
        show: true,
        position: config.position,
        distance: config.distance,
        align: config.align,
        verticalAlign: config.verticalAlign,
        rotate: config.rotate,
        formatter: '{c}  {name|{a}}',
        fontSize: 16,
        rich: {
            name: {
                textBorderColor: '#fff'
            }
        }
    }
};
export default {
    data () {
        return {
            chart_data: {
                color: ['#48a9ef', '#99d97c', '#ffd562'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    orient: 'horizontal',
                    x: 'center',
                    y: 'top',
                    data: [this.$t('operator_amount'), this.$t('operator_box'), this.$t('operator_points')]
                },
                grid: {
                    top: '50',
                    left: '100',
                    right: '100',
                    bottom: '-20',
                    height: '200px',
                    containLabel: true
                },
                xAxis: [
                    {
                        type: 'category',
                        boundaryGap: false,
                        data: []
                    }
                ],
                yAxis: [
                    {
                        type: 'value',
                    }
                ],
                series: [
                    {
                        name: this.$t('operator_amount'),
                        type: 'line',
                        smooth: true,
                        // stack: null,
                        data: []
                    },
                    {
                        name: this.$t('operator_box'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: []
                    },
                    {
                        name: this.$t('operator_points'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: []
                    }
                ]
            },
            chart_data_totle: {
                color: ['#48a9ef', '#99d97c', '#ffd562'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                legend: {
                    data: [this.$t('operator_amount'), this.$t('operator_box'), this.$t('operator_points')]
                },
                grid: {
                    left: '100',
                    right: '100',
                    containLabel: true
                },
                calculable: true,
                xAxis: [
                    {
                        type: 'category',
                        axisTick: {show: false},
                        data: []
                        // data: [11,22,33,44,55,66,77,88,444,555,666,777,888,999,144]
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                dataZoom: [
                    {
                        type: 'slider',
                        show: false,
                        start: 0,
                        end: 70,
                        handleSize: 2,
                        height: 15
                    },
                // {
                //     type: 'slider',
                //     show: true,
                //     yAxisIndex: 0,
                //     filterMode: 'empty',
                //     width: 4,
                //     height: '20%',
                //     handleSize: 8,
                //     showDataShadow: false,
                //     left: '93%'
                // }
                ],
                series: [
                    {
                        name: this.$t('operator_amount'),
                        type: 'bar',
                        barWidth: 30,
                        barGap: 0,
                        label: labelOption,
                        data: []
                        // data:[11,22,33,44,55,66,77,88,41,53,66,77,88,99,16,]
                    },
                    {
                        name: this.$t('operator_box'),
                        type: 'bar',
                        barWidth: 30,
                        label: labelOption,
                        data: []
                        // data:[11,22,33,44,55,66,77,88,41,53,66,77,88,99,16,]
                    },
                    {
                        name: this.$t('operator_points'),
                        type: 'bar',
                        barWidth: 30,
                        label: labelOption,
                        data: []
                        // data:[11,22,33,44,55,66,77,88,41,53,66,77,88,99,16,]
                    }
                ]
            },
            // users: [],
            // work_count: [],
            // label_count: [],
            // point_count: [],
            // dates:[],
            // work_count_by_day:[],
            // label_count_by_day:[],
            // point_count_by_day:[],
        };
    },
    computed: {
    },
    methods: {
        getData () {
            let app = this;
            app.loading = true;
            $.ajax({
                url: api.stat.workstat,
                type: "post",
                data: {
                    access_token: app.$store.state.user.userInfo.accessToken,
                    task_id: app.$route.params.id,
                    limit: '1000',
                },
                success: function (res) {
                    app.loading = false;
                    if (res.error) {
                        app.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        app.totleData = res.data.list;
                        $.each(res.data.list, function (k, v) {
                            app.chart_data_totle.xAxis[0].data.push(v.user.nickname);
                            app.chart_data_totle.series[0].data.push(v.work_count);
                            app.chart_data_totle.series[1].data.push(v.label_count);
                            app.chart_data_totle.series[2].data.push(v.point_count);
                        });
                        if (res.data.list.length > 5) {
                            app.chart_data_totle.dataZoom.show = true;
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    this.loading = false;
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                    });
                }
            });
        },
        init () {
            let app = this;
            // var serviceRequestCharts = echarts.init(document.getElementById('service_request_con'));
            // serviceRequestCharts.setOption(option);
            // // serviceRequestCharts.on('click', function (params) {
            // //     // 控制台打印数据的名称
            // //     console.log(params.name);
            // //     $.each(app.totleData,function(k,v){
            // //         if(params.name == v.user.email) {
            // //             app.getDataByData(v.user.id)
            // //         }
            // //     })
            // // });
            // window.addEventListener('resize', function () {
            //     serviceRequestCharts.resize();
            // });
        },
        getDataByData (user_id) {
            let app = this;
            app.loading = true;
            $.ajax({
                url: api.stat.statByDay,
                type: "post",
                data: {
                    access_token: app.$store.state.user.userInfo.accessToken,
                    task_id: app.$route.params.id,
                    user_id: user_id,
                    limit: '1000',
                },
                success: function (res) {
                    app.loading = false;
                    if (res.error) {
                        app.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        $.each(res.data.list, function (k, v) {
                            app.chart_data.xAxis[0].data.push(v.date);
                            app.chart_data.series[0].data.push(v.work_count);
                            app.chart_data.series[1].data.push(v.label_count);
                            app.chart_data.series[2].data.push(v.point_count);
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    this.loading = false;
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                    });
                }
            });
        },
    },
    watch: {
        '$route' (to) {
            if (to.name === 'task-record') {
                this.getData();
                this.$store.state.app.userInfoRequest.then(res => {
                    this.getDataByData(res.data.user.id);
                });
            }
        }
    },
    mounted () {
        this.getData();
        this.$store.state.app.userInfoRequest.then(res => {
            this.getDataByData(res.data.user.id);
        });
    }
};
</script>
<style scoped>
.layout {
    width: 100%;
    min-height: 200px;
    padding: 20px;
    margin: 10px 0 20px;
    overflow: hidden;
    background: #fff;
    position: relative;
    border-radius: 7px;
    box-shadow: 0 2px 3px 0 rgba(0,0,0,.047);
}
</style>
