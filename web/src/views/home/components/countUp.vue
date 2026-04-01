<template>
    <div>
        <slot name="intro"></slot>
        <div v-if="endVal" class="card-count" :style="{textAlign: 'center', color: 'rgba(0,0,0,0.7)', fontSize: countSize, fontWeight: countWeight}">
            <div class="card-count-info">
                <div>
                    <p v-cloak :id="idName">{{ endVal }}</p>
                    <p class="card-count-title">个人</p>
                </div>
                <p class="card-count-icon">/</p>
                <div>
                    <p>152</p>
                    <p class="card-count-title">全部</p>
                </div>
            </div>
        </div>
        <div v-else class="card-count" :style="{textAlign: 'center', color: 'rgba(0,0,0,0.7)', fontSize: countSize, fontWeight: countWeight, height: '51px'}">
            <div class="card-count-info">
                <div>
                    <p>{{ '0' }}</p>
                    <p class="card-count-title">个人</p>
                </div>
                <p class="card-count-icon">/</p>
                <div>
                    <p>{{ '0' }}</p>
                    <p class="card-count-title">全部</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import CountUp from 'countup';

function transformValue (val) {
    let endVal = 0;
    let unit = '';
    if (val < 1000) {
        endVal = val;
    } else if (val >= 1000 && val < 1000000) {
        endVal = parseInt(val / 1000);
        unit = 'K+';
    } else if (val >= 1000000 && val < 10000000000) {
        endVal = parseInt(val / 1000000);
        unit = 'M+';
    } else {
        endVal = parseInt(val / 1000000000);
        unit = 'B+';
    }
    return {
        val: endVal,
        unit: unit
    };
}

export default {
    data () {
        return {
            unit: '',
            demo: {}
        };
    },
    name: 'countUp',
    props: {
        idName: String,
        className: String,
        startVal: {
            type: Number,
            default: 0
        },
        endVal: {
            type: Number,
            default: 0,
            required: true
        },
        decimals: {
            type: Number,
            default: 0
        },
        duration: {
            type: Number,
            default: 2
        },
        delay: {
            type: Number,
            default: 500
        },
        options: {
            type: Object,
            default: () => {
                return {
                    useEasing: true,
                    useGrouping: true,
                    separator: ',',
                    decimal: '.'
                };
            }
        },
        color: String,
        countSize: {
            type: String,
            default: '35px'
        },
        countWeight: {
            type: Number,
            default: 500
        },
        introText: [String, Number]
    },
    mounted () {
        this.$nextTick(() => {
            if (this.endVal && this.idName) {
                setTimeout(() => {
                    let res = transformValue(this.endVal);
                    let endVal = res.val;
                    this.unit = res.unit;
                    let demo = {};
                    this.demo = demo = new CountUp(this.idName, this.startVal, this.endVal, this.decimals, this.duration, this.options);
                    if (!demo.error) {
                        demo.start();
                    }
                }, this.delay);
            }
        });
    },
    watch: {
        endVal (val) {
            if (this.endVal && this.idName) {
                setTimeout(() => {
                    let res = transformValue(this.endVal);
                    let endVal = res.val;
                    this.unit = res.unit;
                    let demo = {};
                    this.demo = demo = new CountUp(this.idName, this.startVal, this.endVal, this.decimals, this.duration, this.options);
                    if (!demo.error) {
                        demo.start();
                    }
                }, this.delay);
            }
        }
    }
};
</script>
