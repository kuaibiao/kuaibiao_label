<template>
    <div>
        <div style="position:relative">
            <Spin size="small" fix v-if="spinShow"></Spin>
            <div style="position:absolute;top: 20px;left:20px;z-index:4;">
                <Button type="default" style="margin-right: 20px" :to="{name: 'project-detail', params: {id: $route.params.projectId,tab: 'qc',}}">{{$t('project_back')}}</Button>
                <ButtonGroup>
                    <Button :type="currentIndex == 'all' ? 'primary' : 'default'" @click="tabClick('all')">{{$t('operator_all_job')}}</Button>
                    <Button :type="currentIndex == 'unresive' ? 'primary' : 'default'" @click="tabClick('unresive')">{{$t('admin_unclaimed')}}</Button>
                    <Button :type="currentIndex == 'doing' ? 'primary' : 'default'" @click="tabClick('doing')">{{$t('admin_active')}}</Button>
                    <Button :type="currentIndex == 'submited' ? 'primary' : 'default'" @click="tabClick('submited')">{{$t('admin_submitted')}}</Button>
                    <Button :type="currentIndex == 'revised' ? 'primary' : 'default'" @click="tabClick('revised')">{{$t('operator_rework_work')}}</Button>
                </ButtonGroup>
            </div>
            
            <component 
                :is="currentView" 
                ref="currView" 
                :image_label="is_image_label" 
                :step_type="step_type" 
                :task_view="view" 
                :templateInfo="template">
            </component>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import Vue from 'vue';
import util from '@/libs/util';
import unresive from './work_unresive';
import doing from './work_doing';
import submited from './work_submit';
import revised from './work_revised';
import notpass from './work_notpass';
import all from './work_all';
export default {
    name: 'work-list',
    data () {
        return {
            downModal: false,
            spinShow: true,
            paths: [],
            currentIndex: this.$route.params.index || 'all',
            ViewMap: {
                all: all,
                unresive,
                doing,
                submited,
                revised,
            },
            is_image_label: '',
            step_type: '',
            view: '',
            template: [],
            tableOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center'
                },
                {
                    title: this.$t('operator_filename'),
                    key: 'name',
                    align: 'center'
                },
                {
                    title: this.$t('operator_creation_time'),
                    key: 'cname',
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(para.row.ctime * 1000),
                                'yyyy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                }
            ],
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
                name: 'qc-work-list',
                params: {
                    projectId: this.$route.params.projectId,
                    id: this.$route.params.id,
                    index: index
                }
            });
            this.currentIndex = index;
        },
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: 'post',
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
        unresive,
        doing,
        submited,
        revised,
    }
};
</script>

