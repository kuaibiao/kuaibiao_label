<template>
  <div class="subcontent">
        <component 
            style="margin-top: 20px"
            :is="currentView"
            :sumData="sumData"
            :labelTypeList="labelTypeList"
            :stepid="stepId"
            ref="stepcomponent"
            :projectId="projectId">
        </component>
  </div>
</template>

<script>
import api from "@/api";
import util from "@/libs/util";
import execute from "./components/execute.vue";
import audit from "./components/audit.vue";
export default {
    props: {
        step_type: {
            type: String
        },
        projectId: {
            type: String
        },
        stepId: {
            type: String
        }
    },
    data () {
        return {
            ViewMap: [
                execute,
                audit
            ],
            user_id: this.$store.state.user.userInfo.id,
            sumData: {},
            template_label_types: {},
            labelTypeList: {},
        };
    },
    watch: {
        projectId () {
            if (this.projectId && this.user_id) {
                this.getUserData(this.user_id);
            }
        },
        user_id () {
            if (this.projectId && this.user_id) {
                this.getUserData(this.user_id);
            }
        }
    },
    components: {
        execute,
        audit
    },
    computed: {
        currentView () {
            if (this.step_type == '0') {
                return this.ViewMap[0];
            } else if (this.step_type == '1') {
                return this.ViewMap[1];
            }
        }
    },
    mounted () {
        this.$store.state.app.userInfoRequest.then(res => {
            this.user_id = res.data.user.id;
            if (this.projectId && this.user_id) {
                this.getUserData(this.user_id);
            }
        });
    },
    methods: {
        // 获取数据总数
        getUserData (id) {
            $.ajax({
                url: api.stat.workstat,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    user_id: id,
                    project_id: this.projectId
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.sumData = {...res.data.total};
                        this.template_label_types = res.data.template_label_types;
                        let obj = {};
                        $.each(this.template_label_types, (k, v) => {
                            if (!obj[v] && res.data.label_types[v]) {
                                obj[v] = {
                                    title: res.data.label_types[v],
                                    value: res.data.label_stat[v],
                                }
                            }
                        })
                        this.labelTypeList = obj;
                        // console.log(this.labelTypeList)
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        }
    }
};
</script>

<style scoped>
    .subcontent{
        background: #fff;
        margin-top: 10px;
        padding: 20px;
    }
    .top-count{
        margin-top: 10px;
        padding: 5px;
        border-bottom: none;
        font-size: 14px;
    }
</style>
