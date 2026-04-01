<template>
    <div v-if="dataGroup.dates && dataGroup.dates.length">
        <chart :options="chart_data" ref="chart_data" auto-resize :style="{height:'330px',width:'100%'}"></chart>
    </div>
    <div v-else style="text-align: center;">
        <img src="../../../images/default-image/home-default-img.png" style="margin-top: 50px" alt="">
    </div>
</template>
<script>
import ECharts from 'vue-echarts';

export default {
    props: {
        dataGroup: {
            type: Object,
            default: {}
        }
    },
    components: {
        chart: ECharts,
    },
    data () {
        return {
            chart_data: {
                color: [],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    },
                    position: 'left'
                },
                legend: {
                    // orient: 'horizontal',
                    // right: 40,
                    // y: 0,
                    // // data: [this.$t('home_data_published'), this.$t('home_data_execution'),"作业数量个人"]
                    // data: []
                },
                grid: {
                    top: '50',
                    left: '40',
                    right: '40',
                    bottom: '0',
                    containLabel: true
                },
                xAxis: [
                    // {
                    //     type: 'category',
                    //     boundaryGap: false,
                    //     data: []
                    // }
                ],
                yAxis: [
                    {
                        type: 'value',
                    }
                ],
                series: [
                    // {
                    //     name: this.$t('home_data_published'),
                    //     type: 'line',
                    //     smooth: true,
                    //     // stack: '总量',
                    //     data: []
                    // },
                    // {
                    //     name: this.$t('home_data_execution'),
                    //     type: 'line',
                    //     smooth: true,
                    //     data: []
                    // },
                    // {
                    //     name: "作业数量个人",
                    //     type: 'line',
                    //     smooth: true,
                    //     data: []
                    // }
                ]
            }

        };
    },
    mounted () {
        this.$nextTick(() => {
            if (this.dataGroup.num == 2) { // 管理员 作业员
                var color = ['#00cc99', '#cccc00', '#48a9ef'];
                var xAxis = [{
                        type: 'category',
                        boundaryGap: false,
                        data: this.dataGroup.dates
                }];
                var legend = {
                    orient: 'horizontal',
                    right: 30,
                    data: [this.$t('home_work_count_person'),this.$t('home_work_count_total'),this.$t('home_data_published')]
                };
                var series = [
                    {
                        name: this.$t('home_work_count_person'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.userFinData
                    },
                    {
                        name: this.$t('home_work_count_total'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.finData
                    },
                    {
                        name: this.$t('home_data_published'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: this.dataGroup.pubData
                    }
                ]
                this.$set(this.chart_data, 'color', color);
                this.$set(this.chart_data, 'legend', legend);
                this.$set(this.chart_data, 'xAxis', xAxis);
                this.$set(this.chart_data, 'series', series);
            } else if (this.dataGroup.num == 0) { // 管理员
                var color = ['#cccc00', '#48a9ef'];
                var xAxis = [{
                        type: 'category',
                        boundaryGap: false,
                        data: this.dataGroup.dates
                }];
                var legend = {
                    orient: 'horizontal',
                    right: 30,
                    data: [this.$t('home_work_count_total'),this.$t('home_data_published')]
                };
                var series = [
                    {
                        name: this.$t('home_work_count_total'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.finData
                    },
                    {
                        name: this.$t('home_data_published'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: this.dataGroup.pubData
                    }
                ]
                this.$set(this.chart_data, 'color', color);
                this.$set(this.chart_data, 'legend', legend);
                this.$set(this.chart_data, 'xAxis', xAxis);
                this.$set(this.chart_data, 'series', series);
            }
        });
    },
    watch: {
        dataGroup () {
            if (this.dataGroup.num == 2) { // 管理员 作业员
                var color = ['#00cc99', '#cccc00', '#48a9ef'];
                var xAxis = [{
                        type: 'category',
                        boundaryGap: false,
                        data: this.dataGroup.dates
                }];
                var legend = {
                    orient: 'horizontal',
                    right: 30,
                    data: [this.$t('home_work_count_person'),this.$t('home_work_count_total'),this.$t('home_data_published')]
                };
                var series = [
                    {
                        name: this.$t('home_work_count_person'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.userFinData
                    },
                    {
                        name: this.$t('home_work_count_total'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.finData
                    },
                    {
                        name: this.$t('home_data_published'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: this.dataGroup.pubData
                    }
                ]
                this.$set(this.chart_data, 'color', color);
                this.$set(this.chart_data, 'legend', legend);
                this.$set(this.chart_data, 'xAxis', xAxis);
                this.$set(this.chart_data, 'series', series);
            } else if (this.dataGroup.num == 0) { // 管理员
                var color = ['#cccc00', '#48a9ef'];
                var xAxis = [{
                        type: 'category',
                        boundaryGap: false,
                        data: this.dataGroup.dates
                }];
                var legend = {
                    orient: 'horizontal',
                    right: 30,
                    data: [this.$t('home_work_count_total'),this.$t('home_data_published')]
                };
                var series = [
                    {
                        name: this.$t('home_work_count_total'),
                        type: 'line',
                        smooth: true,
                        data: this.dataGroup.finData
                    },
                    {
                        name: this.$t('home_data_published'),
                        type: 'line',
                        smooth: true,
                        // stack: '总量',
                        data: this.dataGroup.pubData
                    }
                ]
                this.$set(this.chart_data, 'color', color);
                this.$set(this.chart_data, 'legend', legend);
                this.$set(this.chart_data, 'xAxis', xAxis);
                this.$set(this.chart_data, 'series', series);
            }
        }
    }
};
</script>

