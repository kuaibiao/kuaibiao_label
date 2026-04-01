<template>
    <Row v-if="categoryGroup.length">
        <i-col :span="categoryNum > 0 ? 8 : 16">
            <chart :options="chart_data" ref="chart_data" auto-resize :style="{height:'260px',width:'100%'}"></chart>
            <div class="category-count">
                <p style="color:#999;">{{$t('home_project_num')}}</p>
                <p class="count">{{barText}}</p>
            </div>
        </i-col>
        <i-col :span="categoryNum > 0 ? 16 : 8">
            <div v-if="categoryNum == 0" style="font-size:12px;line-height: 30px;height: 300px;display: flex; flex-direction: column;justify-content: center;">
                <p v-for="(item, index) in categoryGroup" :key="index">
                    <span :style="{'background': chart_data.color[index], 'display': 'inline-block', 'width': '6px', 'height': '6px', 'borderRadius': '50%'}"></span>
                    <span style="margin-left: 6px">{{item.name}}</span>
                    <span class="category">{{getPercent(index)}}%</span>
                    <span>{{item.value}} {{$t('home_pcs')}}</span>
                </p>
            </div>
            <div v-if="categoryNum > 0" style="font-size:12px;line-height: 26px;height: 300px;display: flex; flex-direction: column;justify-content: center;">
                <Row>
                    <i-col span="13">
                        <p v-for="(item, index) in categoryPrev" :key="index">
                            <span :style="{'background': chart_data.color[index], 'display': 'inline-block', 'width': '6px', 'height': '6px', 'borderRadius': '50%'}"></span>
                            <span>{{item.name}}</span>
                            <span class="category">{{getPercent(index)}}%</span>
                            <span>{{item.value}} {{$t('home_pcs')}}</span>
                        </p>
                    </i-col>
                    <i-col span="11">
                        <p v-for="(item, index) in categoryNext" :key="index">
                            <span :style="{'background': chart_data.color[categoryNum + index], 'display': 'inline-block', 'width': '6px', 'height': '6px', 'borderRadius': '50%'}"></span>
                            <span>{{item.name}}</span>
                            <span class="category">{{getPercent(categoryNum + index)}}%</span>
                            <span>{{item.value}} {{$t('home_pcs')}}</span>
                        </p>
                    </i-col>
                </Row>
            </div>
        </i-col>
    </Row>
    <div v-else style="text-align: center;">
        <img src="../../../images/default-image/home-default-img.png" style="margin-top: 30px" alt="">
    </div>
</template>
<script>
import ECharts from 'vue-echarts';
import cloneDeep from 'lodash.clonedeep';
export default {
    props: {
        categoryGroup: {
            type: Array,
            default: []
        }
    },
    components: {
        chart: ECharts
    },
    data () {
        return {
            barText: '',
            chart_data: {
                color: ['#1890ff', '#91d5ff', '#722ed1', '#b37feb', '#fa541c', '#95de64', '#fa8c16', '#fff566', '#ffd8bf', '#fb2323', '#fa00e0', '#4537f7', '#1ee3f5', '#1ef52d', '#076398', '#092858', '#0a0d75', '#007c3d'],
                grid: {
                    top: 0,
                    left: 0,
                    right: '40',
                    bottom: '0',
                    containLabel: true
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)'
                },
                graphic: {
                    elements: [
                        // {
                        //     type: 'text',
                        //     left: 'center', // 相对父元素居中
                        //     top: '120', // 相对父元素上下的位置
                        //     style: {
                        //         fill: '#333333',
                        //         text: '项目数',
                        //         fontSize: 14,
                        //         font: '12px Arial Normal',
                        //     }
                        // },
                        // {
                        //     type: 'text',
                        //     style: {
                        //         text: '',
                        //         width: 25,
                        //         height: 30,
                        //         fontSize: 23,
                        //     },
                        //     left: 'center',
                        //     top: '140'
                        // },
                    ]
                },
                series: [
                    {
                        name: this.$t('home_data_ratio'),
                        type: 'pie',
                        center: ['50%', '50%'],
                        radius: ['55%', '70%'],
                        avoidLabelOverlap: false,
                        label: {
                            normal: {
                                show: false,
                                position: 'left'
                            },
                        },
                        labelLine: {
                            normal: {
                                show: false
                            }
                        },
                        data: []
                    }
                ]
            },
            categoryNum: 0,
            categoryPrev: [],
            categoryNext: [],
            categoryGroupSort: []
        };
    },
    computed: {
        totle () {
            let num = 0;
            $.each(this.categoryGroup, (k, v) => {
                num += +v.value;
            });
            return num;
        },
    },
    watch: {
        categoryGroup () {
            if (this.categoryGroup.length) {
                this.$set(this.chart_data.series[0], 'data', this.categoryGroup);
                this.barText = (+this.totle < 10000) ? +this.totle.toFixed(2) : ((+this.totle / 10000).toFixed(2) + 'w');
            }
            if(this.categoryGroup.length > 7) {
                if(this.categoryGroup.length % 2 == 0) {
                    this.categoryNum = this.categoryGroup.length / 2
                } else {
                    this.categoryNum = parseInt(this.categoryGroup.length / 2) + 1
                }
            }
        },
        categoryNum () {
            if(this.categoryNum > 0) {
                this.categoryPrev = this.categoryGroup.slice(0, this.categoryNum);
                this.categoryNext = this.categoryGroup.slice(this.categoryNum);
            }
        }
    },
    mounted () {
        if (this.categoryGroup.length) {
            this.$set(this.chart_data.series[0], 'data', this.categoryGroup);
            this.barText = (+this.totle < 10000) ? +this.totle.toFixed(2) : ((+this.totle / 10000).toFixed(2) + 'w');
        }
    },
    methods: {
        getPercent (index) {
            if (this.totle == 0) {
                return 0;
            }
            let num = ((+this.categoryGroup[index].value / this.totle) * 100).toFixed(2);
            return num;
        }
    }
};
</script>
<style scoped>
     .category {
        display: inline-block;
        width: 45px;
    }
    .category-count {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        text-align: center;
    }
    .count {
        font-size: 24px;
        text-align: center;
    }

</style>