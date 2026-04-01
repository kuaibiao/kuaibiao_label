<template>
    <div class="subcontent">        
            <Tabs type="line" v-if="tasks.length > 0" @on-click="getStepData" v-model="tabValue" style="margin-top:10px">
                <TabPane v-for="(item, index) in tasks" :key="index" :label="stepTypes[item.step.type]" :name="item.id" :value="item.id">
                    <component 
                        v-if="currentStepType == item.step.type"
                        :is="ViewMap[item.step.type]"
                        :currentTask="currentTask"
                        :stepType="item.step.type"
                        >
                    </component>
                </TabPane>
            </Tabs>
            <div v-else style="text-align: center;">
                <img src="../../../images/default-image/home-default-img.png" style="margin: 30px 0 50px" alt="">
            </div>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import Cookies from 'js-cookie';
import execute from '../components/performance_execute.vue';
import audit from '../components/performance_audit.vue';
import accept from '../components/performance_accept.vue';
export default {
    props: {
        tasks: {
            type: Array
        },
        stepData: {
            type: Array
        },
        stepTypes: {
            type: Object
        }
    },
    data () {
        return {
            loading: false,
            currentTask: '',
            currentStepType: '',
            tabValue: '',
            ViewMap: {
                0: execute,
                1: audit,
                3: accept
            }
        };
    },
    watch: {
        tasks  () {
            if (this.tasks.length > 0) {
                this.currentStepType = this.tasks[0].step.type;
                this.currentTask = this.tasks[0].id;
            }
        },
    },
    mounted () {
        if (this.tasks.length > 0) {
            this.currentStepType = this.tasks[0].step.type;
            this.currentTask = this.tasks[0].id;
        }
    },
    methods: {
        getStepData (id) {
            $.each(this.tasks, (k,v) => {
                if (v.id == id) {
                    this.currentStepType = v.step.type;
                }
            })
            this.currentTask = id;
        }
    },
    components: {
        execute,
        audit,
        accept
    }
};
</script>
<style scoped>
    .subcontent{
        background:#fff;
        margin: 20px 25px 10px;
        padding: 20px;
        position: relative;
    }
    .top-count{
        margin-top: 10px;
        padding: 20px;
        border: 1px solid #e9eaec;
        border-bottom: none; 
        font-size: 14px;
    }
    .exportDataExcel{
        position: absolute;
        right: 130px;
        top: 30px;
        z-index: 2;
    }
    .exportDataCsv{
        position: absolute;
        right: 20px;
        top: 30px;
        z-index: 2;
    }
</style>

