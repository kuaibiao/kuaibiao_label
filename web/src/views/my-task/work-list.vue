<template>
    <div id="task_detail">
        <div style="position:relative">
            <Spin size="small" fix v-if="spinShow"></Spin>
            <ButtonGroup style="position:absolute;top: 20px;left:20px;z-index:4;">
                <Button :type="currentIndex == 'all' ? 'primary' : 'default'" @click="tabClick('all')">{{$t('operator_all_job')}}</Button>
                <Button :type="currentIndex == '2' ? 'primary' : 'default'" @click="tabClick('2')">{{$t('operator_submitted')}}</Button>
                <Button v-if="step_type == '0'" :type="currentIndex == '1' ? 'primary' : 'default'" @click="tabClick('1')">{{$t('operator_active')}}</Button>
                <Button v-if="step_type == '1'" :type="currentIndex == '1' ? 'primary' : 'default'" @click="tabClick('1')">{{$t('operator_auditing')}}</Button>
                <Button :type="currentIndex == '7' ? 'primary' : 'default'" @click="tabClick('7')">{{$t('operator_difficult')}}</Button>
                <Button :type="currentIndex == '5' ? 'primary' : 'default'" @click="tabClick('5')">{{$t('operator_rejected')}}</Button>
                <Button :type="currentIndex == '3' ? 'primary' : 'default'" @click="tabClick('3')">{{$t('operator_passed')}}</Button>
                <Button v-if="step_type != '0'" :type="currentIndex == '6' ? 'primary' : 'default'" @click="tabClick('6')">{{$t('operator_rework')}}</Button>
                <Button :type="currentIndex == '4' ? 'primary' : 'default'" @click="tabClick('4')">{{$t('operator_expired')}}</Button>
            </ButtonGroup>
            <component 
                :is="currentView" 
                ref="currView" 
                :image_label="is_image_label" 
                :step_type="step_type" 
                :task_view="view" 
                :templateInfo="template">
            </component>
        </div>

        <Modal v-model="downModal">
            <p slot="header" style="text-align:center">
                <Icon type="ios-help-circle" />
                <span>{{$t('operator_download_lists')}}</span>
            </p>
            <div>
                <Table
                    highlight-row
                    @on-select="handleSel"
                    ref="userTable"
                    :columns="tableOption"
                    :data="tableData"
                    stripe
                    show-header>
                </Table>
            </div>
            <div slot="footer">
                <Button type="success" size="large" long @click="download">
                    <!-- 下载 -->
                    {{$t('operator_download')}}
                </Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import api from "@/api";
import Vue from "vue";
import util from "@/libs/util";
import workdoing from "./components/work_doing";
import submited from "./components/work_submited";
import passed from "./components/work_passed";
import notpass from "./components/work_notpass";
import refused from "./components/work_refused";
import revised from "./components/work_revised";
import defficulty from "./components/work_defficulty";
import all from "./components/work_all";
export default {
    name: "task-detail",
    data () {
        return {
            downModal: false,
            spinShow: true,
            paths: [],
            currentIndex: this.$route.params.index || 'all',
            ViewMap: {
                all: all,
                1: workdoing,
                2: submited,
                3: passed,
                4: notpass,
                5: refused,
                6: revised,
                7: defficulty
            },
            is_image_label: "",
            step_type: "",
            view: "",
            template: [],
            tableOption: [
                {
                    type: "selection",
                    width: 60,
                    align: "center"
                },
                {
                    title: this.$t('operator_filename'),
                    key: "name",
                    align: "center"
                },
                {
                    title: this.$t('operator_creation_time'),
                    key: "cname",
                    align: "center",
                    render: (h, para) => {
                        return h(
                            "span",
                            util.timeFormatter(
                                new Date(para.row.ctime * 1000),
                                "yyyy-MM-dd hh:mm:ss"
                            )
                        );
                    }
                }
            ],
            tableData: [],
        };
    },
    computed: {
        currentView () {
            return this.ViewMap[this.$route.params.index];
        }
    },
    methods: {
        tabClick (index) {
            this.$router.push({
                name: 'my-task-detail',
                params: {
                    id: this.$route.params.id,
                    tab: 'work-list',
                    index: index
                },
            });
            this.currentIndex = index;
        },
        handleSel (selection) {
            let arr = [];
            $.each(selection, function (k, v) {
                arr.push(v.path);
            });
            this.paths = arr;
        },
        download () {
            $.each(this.paths, function (k, v) {
                window.open(api.download.file + '?file=' + v);
            });
        },
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id
                },
                success: res => {
                    let data = res.data;
                    this.spinShow = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.is_image_label = res.data.info.project.category.file_type;
                        this.view = res.data.info.project.category.view;
                        this.step_type = res.data.info.step.type;
                        this.template = res.data.template.config || [];
                        this.tableData = res.data.attachments;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.spinShow = false;
                    });
                }
            });
        }
    },
    mounted () {
        this.getTaskDetail();
    },
    components: {
        all,
        workdoing,
        submited,
        passed,
        notpass,
        refused,
        revised,
        defficulty
    }
};
</script>

<style scoped>
    .layout-content {
      position: relative;
      background: #ffffff;
      min-height: 200px;
      padding: 20px 20px 40px 20px;
    }
    .tabpane {
      /* width: 480px; */
      height: 37px;
      list-style: none;
      position: absolute;
      bottom: 0;
      left: 40px;
    }
    .tabpane li {
      font-size: 14px;
      float: left;
      /* width: 90px; */
      height: 37px;
      padding: 8px 16px;
      margin-right: 16px;
      line-height: 21px;
      color: #999999;
      text-align: center;
      cursor: pointer;
    }
    .tabpane li:hover {
      color: #333333;
    }
    .tabpane li.active {
      color: #2d8cf0;
      border-bottom: 2px solid #2d8cf0;
    }
</style>
<style>
    #task_detail .ivu-tabs-bar {
      border-bottom: none;
      margin-bottom: 0;
      margin-left: 40px;
    }
</style>