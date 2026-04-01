<template>
    <Row v-if="dataStat.length">
        <i-col span="13">
            <chart :options="chart_data" ref="chart_data" auto-resize :style="{height:'250px',width:'100%'}"></chart>
            <div class="category-count">
                <p style="color:#999;">{{type == 'data' ? $t('home_data_volume') : $t('home_space_occupation')}}</p>
                <p class="count">{{barText}}</p>
            </div>
        </i-col>
        <i-col span="11">
            <div style="font-size:12px;line-height: 30px;height: 270px;display: flex; flex-direction: column;justify-content: center;">
                <p v-for="(item, index) in dataStat" :key="index">
                    <span :style="{'background': chart_data.color[index], 'display': 'inline-block', 'width': '5px', 'height': '5px', 'borderRadius': '50%'}"></span>
                    <span class="category">{{item.name}}</span><Divider type="vertical" />
                    <span class="category">{{getPercent(index)}}%</span>
                    <span style="margin-left:10px;" v-if="type === 'data'">{{item.value}} {{$t('home_set')}}</span>
                    <span style="margin-left:10px;" v-else>{{item.value}} G</span>
                </p>
            </div>
        </i-col>
    </Row>
    <div v-else style="text-align: center;">
        <img src="../../../images/default-image/home-default-img.png" style="margin-top: 30px" alt="">
    </div>
</template>
<script>
import ECharts from 'vue-echarts';
export default {
    props: {
        dataStat: {
            type: Array,
            default: []
        },
        type: {
            type: String,
        },
    },
    components: {
        chart: ECharts,
    },
    data () {
        return {
            barText: '',
            chart_data: {
                color: ['#48a9ef', '#99d97c', '#ffd562', '#99c1ff', '#fa8c16', '#b37feb'],
                grid: {
                    top: 0,
                    left: 0,
                    right: '40',
                    bottom: '0',
                    containLabel: true
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)',
                    position: 'right',
                },
                // legend: {
                //     orient: 'vertical',
                //     // x: 'right',
                //     y: 'middle',
                //     right: 0,
                //     data: ['图片数据', '音频数据', '视频数据', '文本数据']
                // },
                graphic: {
                    elements: [
                        // {
                        //     type: 'text',
                        //     left: 'center', // 相对父元素居中
                        //     top: '110', // 相对父元素上下的位置
                        //     style: {
                        //         fill: '#333333',
                        //         text: this.type == 'data' ? '数据总量' : '总空间(G)',
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
                        //     top: '120'
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
            }

        };
    },
    watch: {
        dataStat () {
            if (this.dataStat.length) {
                this.$set(this.chart_data.series[0], 'data', this.dataStat);
                if (this.type == 'data') {
                    this.barText = (+this.totle < 10000) ? +this.totle.toFixed(2) : ((+this.totle / 10000).toFixed(2) + 'W');
                } else {
                    this.barText = (+this.totle).toFixed(2) < 1024 ? (+this.totle).toFixed(2) + 'G' : (+this.totle / 1024).toFixed(2) + 'T';
                }
            }
        },
        // type () {
        //     this.$set(this.chart_data.graphic.elements[0].style, 'text', (this.type == 'data') ? '数据总量' : '总空间(G)');
        // }
    },
    computed: {
        totle () {
            let num = 0;
            $.each(this.dataStat, (k, v) => {
                num += v.value * 1;
            });
            return num;
        }
    },
    mounted () {
        if (this.dataStat.length) {
            this.$set(this.chart_data.series[0], 'data', this.dataStat);
            this.chart_data.graphic.elements[1].style.text = +this.totle;
        }
    },
    methods: {
        getPercent (index) {
            if (this.totle == 0) {
                return 0;
            } else {
                let num = ((+this.dataStat[index].value / this.totle) * 100).toFixed(2);
                return num;
            }
        }
    }
};
</script>
<style scoped>
    .category {
        display: inline-block;
        /* width: 40px; */
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