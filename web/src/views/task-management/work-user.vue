<template>
    <div class="subcontent">
        <div style="margin-top:10px" v-if="step_type" id="subcontent">
            <Row class="top-count">
                <i-col span="4">
                    <span>{{$t('admin_total_time',{num: sumData.work_time})}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_totle_valid_work')}}：{{sumData.work_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_totle_submitted_work')}}：{{sumData.submit_count || 0}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('admin_total_mark_number')}}：{{sumData.label_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_total_count')}}：{{sumData.point_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_totle_lines')}}：{{sumData.line_count}}</span>
                </i-col>
            </Row>
            <Row class="top-count">
                <i-col span="4">
                    <span>{{$t('operator_totle_boxs')}}：{{sumData.rect_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_totle_polygons')}}：{{sumData.polygon_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('operator_number_others')}}
                        <Tooltip :transfer="true" :content="$t('operator_number_others_desc')">
                            <Icon type="ios-help-circle-outline" style="font-size: 14px"/>
                        </Tooltip>：{{sumData.other_count}}
                    </span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('admin_total_approved')}}：{{sumData.allowed_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('admin_total_rejected')}}：{{sumData.refused_count}}</span>
                </i-col>
                <i-col span="4">
                    <span>{{$t('admin_total_reset')}}：{{sumData.reseted_count}}</span>
                </i-col>
            </Row>
            <Row class="top-count">
                <i-col span="4">
                    <span>{{$t('admin_total_time_vocie')}}：{{sumData.label_time}}</span>
                </i-col>
            </Row>
            <component 
                style="margin-top: 20px"
                :is="currentView"
                ref="stepcomponent">
            </component>
        </div>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
import execute from "./components/execute.vue";
import audit from "./components/audit.vue";
import quality from "./components/quality.vue";
export default {
    props: {
        step_type: {
            type: String
        },
    },
    data () {
        return {
            loading: false,
            dailyModal: false,
            sumData: {},
            keyword: '',
            count: 0,
            page: 1,
            limit: +(localStorage.getItem('workListLimit') || 10),
            orderby: '',
            sort: 'desc',
            roles: [],
            ViewMap: [
                execute,
                audit,
                quality
            ],
        };
    },
    computed: {
        currentView () {
            if (this.step_type == '0') {
                return this.ViewMap[0];
            } else if (this.step_type == '1') {
                return this.ViewMap[1];
            } else {
                return this.ViewMap[2];
            }
        }
    },
    components: {
        execute,
        audit,
        quality
    },
    methods: {
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.sumData = res.data.info.stat === '' ? {} : res.data.info.stat;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
    },
    mounted () {
        this.getTaskDetail();
    }
};
</script>
<style scoped>
    .subcontent{
        background: #fff;
        margin-top: 20px;
        padding: 20px;
    }
    .top-count{
        margin-top: 10px;
        padding: 5px;
        border-bottom: none;
        font-size: 14px;
    }
</style>




