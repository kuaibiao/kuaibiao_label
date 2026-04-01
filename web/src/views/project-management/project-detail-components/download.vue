<template>
    <div class="subcontent" id="project_index">
        <div class="main-con">
            <Card>
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_download_result')}}</div>
                <Form ref="formValidate" :model="currData" label-position="top" :rules="ruleValidate" id="file_pack" @submit.native.prevent>
                    <Form-item :label="this.$t('project_data_packages_type')" class="form-item">   
                        <Row id="anchorBatch">
                            <i-col span="24">
                                <!-- <p><Button size="small" @click="selAll" >{{$t('project_select_all')}}</Button></p> -->
                                <span style="font-size: 14px;line-height: 34px">{{$t('project_data_packages_name')}}：</span>
                                <Checkbox-group v-model="currData.batches" style="display: inline" >
                                    <Checkbox v-for="(batch,index) in batches" :label="batch.id" :key="index" disabled class="sel-group-sty"> {{batch.name}}({{batch.id}})</Checkbox>
                                </Checkbox-group>
                            </i-col>
                            <!-- <i-col span="24">
                                <span style="font-size: 14px;line-height: 34px">打包类型：</span>
                                <Radio-group v-model="currData.packtype">
                                    <Radio label="0" class="sel-group-sty2"> {{$t('project_a_pack')}}</Radio>
                                    <Radio label="1" class="sel-group-sty2"> {{$t('project_num_pack')}}</Radio>
                                </Radio-group>
                            </i-col> -->
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('project_select_download_step')" prop="step" class="form-item">
                        <Row id="anchorStep">
                            <i-col span="24">
                                <Radio-group v-model="currData.step">
                                    <Radio v-for="(step,index) in steps" :label="step.id" :key="index" class="sel-group-sty">{{stepTypes[step.type]}} ({{step.id}})</Radio>
                                </Radio-group>
                            </i-col>
                        </Row>                                                                     
                    </Form-item>
                    <div style="position: relative">
                        <Input 
                        search 
                        v-model="typeKeyword" 
                        clearable 
                        :error="false"
                        style="position: absolute;top: -10px;right: 20px;width: 200px;z-index: 999"
                        :placeholder="$t('operator_input_text')" />
                    </div>
                    
                    <Form-item :label="$t('project_select_download_file_format')" prop="type" class="form-item">
                        <Row id="anchorType">
                            <i-col span="24" style="max-height: 150px; overflow: auto">
                                <Radio-group v-model="currData.type">
                                    <Radio 
                                        v-for="(type) in types"
                                        :label="type.id" 
                                        :key="type.id"
                                        v-show="isShow(type.name)"
                                        class="sel-group-sty">{{type.name}}</Radio>
                                </Radio-group>
                            </i-col>
                        </Row>
                    </Form-item>
                </Form>
                <p style="font-size: 15px;font-weight: 700;">{{$t('project_config_options')}}：</p>
                <Form label-position="right" :model="currData" :label-width="80" id="file_pack2" @submit.native.prevent>
                    <FormItem :label="$t('project_chinese_escape') + ':'" style="margin-left: 20px">
                        <Radio-group v-model="currData.configs">
                            <Radio label="1" class="sel-group-sty2"> Unicode</Radio>
                            <Radio label="0" class="sel-group-sty2"> {{$t('project_no_escape')}}</Radio>
                        </Radio-group>
                    </FormItem>
                    <FormItem :label="$t('project_filter_results') + ':'" style="margin-left: 20px">
                        <Radio-group v-model="currData.removeEmpty">
                            <Radio label="0" class="sel-group-sty2"> {{$t('project_no_filter')}}</Radio>
                            <Radio label="1" class="sel-group-sty2"> {{$t('project_delete_null_result')}}</Radio>
                        </Radio-group>
                    </FormItem>
                    <!-- <FormItem :label="$t('project_data_packages_type') + ':'" style="margin-left: 20px">
                        <Radio-group v-model="currData.packtype">
                            <Radio label="0" class="sel-group-sty2"> {{$t('project_a_pack')}}</Radio>
                            <Radio label="1" class="sel-group-sty2"> {{$t('project_num_pack')}}</Radio>
                        </Radio-group>
                    </FormItem> -->
                    <span style="font-size: 14px;margin-left: 28px;line-height: 34px">{{$t('project_select_time')}}:</span>
                    <FormItem :label="$t('project_start_time')" style="display: inline-block; margin: 0 5px 0 20px;">
                        <DatePicker transfer type="datetime" placement="bottom" :placeholder="$t('project_start_time')" v-model="startTime" :options="startTimeOptions" style="width: 200px"></DatePicker>
                    </FormItem>
                    <FormItem :label="$t('project_end_time')" style="display: inline-block;">
                        <DatePicker transfer type="datetime" placement="bottom" :placeholder="$t('project_end_time')" v-model="endTime" :options="endTimeOptions" style="width: 200px"></DatePicker>
                    </FormItem>
                </Form>
                <p style="padding-bottom: 20px;margin-bottom:10px;border-bottom: 1px dashed #ccc">
                    <Button type="primary" v-if="currData.type !== '76'" @click="fileBuild" size="large" :loading="downLoading">{{$t('project_data_packages')}}</Button>
                    <Button type="primary" v-if="currData.type === '76'" @click="showModal" size="large" >{{$t('project_send_result_to_dataset')}}</Button>
                </p>
                <p style="font-size:14px;font-weight:bold;line-height:32px;padding:10px;">
                    <span>{{$t('project_download_history')}}: </span>
                    <Button @click="reloadTab" :disabled="isDisabled" style="float:right" :type="isDisabled? 'default' : 'primary'">{{ btnText }}</Button>
                </p>
                <div>
                    <Table 
                        size="large" 
                        highlight-row 
                        ref="taskListTable" 
                        :columns="tableOption" 
                        :data="tableData" 
                        stripe 
                        show-header 
                        :loading="loading" 
                        @on-selection-change="selChange">
                        <div slot="footer">
                            <Button 
                            type="primary" 
                            style="margin-left:10px"  
                            @click="batchDownloadPack" 
                            :disabled="selItem.length == 0">{{$t('project_batch_download_package_results')}}</Button>
                            <!-- <Button 
                            type="primary" 
                            style="margin-left:10px"
                            @click="batchDownloadCheck" 
                            :disabled="selItem.length == 0">{{$t('project_Batch_download_detection_information')}}</Button> -->
                            <div style="float:right;margin-right:10px;font-size:12px">
                                <Page 
                                    :total="count" 
                                    :current="page" 
                                    :page-size="limit" 
                                    :page-size-opts="[5,10,15,20]" 
                                    show-total 
                                    show-elevator 
                                    show-sizer 
                                    placement="top" 
                                    transfer 
                                    @on-change="changePage"
                                    @on-page-size-change="changePageSize">
                                </Page>
                            </div>
                        </div>
                    </Table>
                </div>
                <Modal
                    :width="800"
                    v-model="selModal"
                    :mask-closable="false"
                    :title="$t('project_send_result_to_dataset')">
                    <datasetlist :loadData="loadData" :postData="postData" v-on:sel-updated="selUpdated" v-on:sel-selected="selSelected"></datasetlist>
                    <div slot="footer">
                    </div>
                </Modal>
            </Card>
        </div>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import Cookies from 'js-cookie';
import Vue from 'vue';
import progressOp from '../components/progress.vue';
import datasetlist from './datasetlist.vue';
export default {
    props: {
        project: {
            type: Object
        },
        stepTypes: {
            // type: Object
        }
    },
    data () {
        return {
            batchesIds: [],
            selModal: false,
            loadData: false,
            typeKeyword: '',
            currData: {
                type: '',
                step: '',
                batches: [],
                configs: '0',
                removeEmpty: '1',
                packtype: '0',
                ftp_host: '',
                ftp_username: '',
                ftp_password: '',
            },
            startTimeOptions: {
                disabledDate: date => {
                    let startTime2 = this.startTime2 ? new Date(this.startTime2).valueOf() - 86400000 : '';
                    let endTime2 = this.endTime2 ? new Date(this.endTime2).valueOf() : new Date(this.startTime2).valueOf();
                    return (date && (date.valueOf() < startTime2)) || (date.valueOf() > endTime2);
                }
            },
            endTimeOptions: {
                disabledDate: date => {
                    let startTime2 = this.startTime2 ? new Date(this.startTime2).valueOf() - 86400000 : '';
                    let endTime2 = this.endTime2 ? new Date(this.endTime2).valueOf() : new Date(this.startTime2).valueOf();
                    return (date && (date.valueOf() < startTime2)) || (date.valueOf() > endTime2);
                }
            },
            startTime: '',
            endTime: '',
            startTime2: '',
            endTime2: '',
            pack_statuses: [],
            types: [],
            loading: false,
            isDisabled: false,
            downLoading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 5,
            orderby: '',
            sort: 'asc',
            btnText: this.$t('project_refresh'),
            statuses: [],
            pack_script_id: '',
            step_id: '',
            selItem: [],
            postData: {},
            tableOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center'
                },
                {
                    title: this.$t('project_batch'),
                    key: 'batches',
                    render: (h, params) => {
                        return h('span', this.getBatchName(params.row.batches))
                    },
                },
                {
                    title: this.$t('project_file_format'),
                    key: 'packScript',
                    align: 'center',
                    render: (h, params) => {
                        return h('div', params.row.packScript.name);
                    },
                },
                {
                    title: this.$t('project_step'),
                    key: 'step_id',
                    width: 100,
                    align: 'center',
                    render: (h, para) => {
                        return h('span', para.row.step.name);
                    },
                },
                {
                    title: this.$t('project_config_options'),
                    key: 'pack_script_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', {}, [
                            h('div', [
                                h('Tooltip', {
                                    props: {
                                        placement: 'top',
                                        transfer: true,
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {
                                            }, [
                                                h('div', this.$t('project_chinese_escape') + ': ' + ((JSON.parse(para.row.configs).cnEscape == '1') ? 'Unicode' : this.$t('project_no_escape'))),
                                                h('div', this.$t('project_filter_results') + ': ' + ((JSON.parse(para.row.configs).RemoveEmpty == '0') ? this.$t('project_no_filter') : this.$t('project_delete_null_result'))),
                                                h('div', this.$t('project_start_time') + ': ' + (JSON.parse(para.row.configs).startTime ? JSON.parse(para.row.configs).startTime : '')),
                                                h('div', this.$t('project_end_time') + ': ' + (JSON.parse(para.row.configs).endTime ? JSON.parse(para.row.configs).endTime : '')),
                                            ]);
                                        }
                                    },
                                }, [
                                    h('span', this.$t('project_config_options'))
                                ]),
                            ])
                        ]);
                    },
                },
                {
                    title: this.$t('project_status'),
                    key: 'pack_status',
                    align: 'center',
                    width: 120,
                    render: (h, para) => {
                        return h('div', {}, [
                            h('div', this.pack_statuses[para.row.pack_status]),
                            h('div', para.row.pack_message)
                        ]);
                    },
                },
                {
                    title: this.$t('project_packaging_progress'),
                    key: 'name',
                    render: (h, params) => {
                        return h('div', [
                            h('div', [
                                h('Tooltip', {
                                    props: {
                                        content: '',
                                        placement: 'top-start',
                                        transfer: true,
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {
                                            }, [
                                                h('div', this.$t('project_successful_jobs_packaged') + '： ' + params.row.pack_item_succ),
                                                h('div', this.$t('project_failed_jobs_packaged') + '： ' + params.row.pack_item_fail),
                                            ]);
                                        }
                                    },
                                    'class': 'tool_tip',
                                    style: {
                                        display: 'inline'
                                    }
                                }, [
                                    h('div', [
                                        h(progressOp, {
                                            props: {
                                                row: params.row,
                                            }
                                        })
                                    ])

                                ]),
                            ]),
                        ]);
                    },
                },
                {
                    title: this.$t('project_result'),
                    key: 'pack_file',
                    ellipsis: true,
                    align: 'left',
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                content: params.row.pack_file,
                                placement: 'top-start',
                                transfer: true,
                            },
                            'class': 'tool_tip',
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', params.row.pack_file)
                        ]);
                    },
                },
                {
                    title: this.$t('project_create_time'),
                    key: 'created_at',
                    align: 'center',
                    width: 120,
                    render: (h, para) => {
                        return h('Tooltip', {
                            props: {
                                // content: params.row.name,
                                placement: 'top-start',
                                transfer: true,
                            },
                            'class': 'tool_tip',
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('project_start_packing_time') + ': ' + util.timeFormatter(
                                            new Date(+para.row.pack_start_time * 1000),
                                            'MM-dd hh:mm'
                                        )),
                                        h('div', this.$t('project_wrap_up_time') + ': ' + util.timeFormatter(
                                            new Date(+para.row.pack_end_time * 1000),
                                            'MM-dd hh:mm'
                                        )),
                                    ]);
                                }
                            },
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'MM-dd hh:mm'
                            ))
                        ]);
                    }
                },
                {
                    title: this.$t('project_download'),
                    key: 'updated_at',
                    align: 'center',
                    render: (h, para) => {
                        if ((para.row.pack_status === '3') && (para.row.pack_script_id !== '76')) {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        type: 'primary',
                                        size: 'small'
                                    },
                                    style: {
                                        margin: '5px'
                                    },
                                    nativeOn: {
                                        click: () => {
                                            util.downloadFile(this, api.download.file + '?file=' + para.row.pack_file_key)
                                            // window.open(api.download.file + '?file=' + para.row.pack_file_key);
                                        }
                                    }
                                }, this.$t('project_pack_result')),
                                // h(
                                //     'Button', {
                                //         props: {
                                //             size: 'small'
                                //         },
                                //         style: {
                                //             margin: '5px'
                                //         },
                                //         nativeOn: {
                                //             click: () => {
                                //                 window.open(api.download.file + '?file=' + para.row.check_file_key);
                                //             }
                                //         }
                                //     },
                                //     this.$t('project_testing_information')
                                // ),
                            ]);
                        }
                    }
                }
            ],
            tableData: [],
            ruleValidate: {
                // batches: [
                //     { required: true, type: 'array', min: 1, message: this.$t('project_select_atleast_one_batch'), trigger: 'change' }
                // ],
                step: [
                    { required: true, message: this.$t('project_select_steps'), trigger: 'change' }
                ],
                type: [
                    { required: true, message: this.$t('project_select_file_format'), trigger: 'change' }
                ],
                configs: [
                    { required: true, message: this.$t('project_select_configuration'), trigger: 'change' }
                ],
                packtype: [
                    {required: true, message: this.$t('project_change_package_type'), trigger: 'change'}
                ]
            }
        };
    },
    computed: {
        steps () {
            if (this.project) {
                return this.project.steps;
            } else {
                return [];
            }
        },
        batches () {
            if (this.project) {
                return this.project.batches;
            } else {
                return [];
            }
        }
    },
    watch: {
        project () {
            if (this.project.steps) {
                this.setDefaultCheckedStep();
                this.getData();
                this.getPackForm();
            }
        },
        batches () {
            if (this.batches.length > 0) {
                this.batchesIds = [];
                $.each(this.project.batches, (k, v) => {
                    this.batchesIds.push(v.id);
                });
            }
        },
    },
    mounted () {
        if (this.project.steps) {
            this.setDefaultCheckedStep();
            this.getData();
            this.getPackForm();
        }
    },
    methods: {
        selAll () {
            if (this.currData.batches.length == this.batchesIds.length) {
                this.currData.batches = [];
            } else {
                this.currData.batches = this.batchesIds;
            }
        },
        selUpdated () {
            this.loadData = false;
        },
        selSelected () {
            this.selModal = false;
            this.downLoading = false;
            this.getData();
            this.$refs.formValidate.resetFields();
            this.startTime = '';
            this.endTime = '';
        },
        isShow (name) {
            let keyword = this.typeKeyword.trim();
            if (!keyword) {
                return true;
            }
            if (name.toLowerCase().indexOf(keyword.toLowerCase()) !== -1) {
                return true;
            } else {
                return false;
            }
        },
        setDefaultCheckedStep () {
            if (!Array.isArray(this.project.steps)) {
                return;
            }
            // 有质检分布 默认选择最后一个质检
            // let qualitySteps = this.project.steps.filter((step) => {
            //     return step.type === '2';
            // });
            // if (qualitySteps.length) {
            //     this.currData.step = qualitySteps[qualitySteps.length - 1].id;
            //     return;
            // }
            // 无质检分布 有审核 默认选择最后一个审核
            let auditStep = this.project.steps.filter((step) => {
                return step.type === '1';
            });
            if (auditStep.length) {
                this.currData.step = auditStep[auditStep.length - 1].id;
                return;
            }
            // 无质检分布 无审核 默认选择最后一个分布
            this.currData.step = this.project.steps.length ? this.project.steps[this.project.steps.length - 1].id : '';
        },
        reloadTab () {
            this.getData();
            this.selItem = [];
            this.isDisabled = true;
            let timeLast = 10;
            // this.btnText = timeLast + '秒后重试';
            this.btnText = this.$t('project_try_again_after_seconds', {num: timeLast});
            let timer = setInterval(() => {
                if (timeLast >= 0) {
                    this.btnText = this.$t('project_try_again_after_seconds', {num: timeLast});
                    timeLast -= 1;
                } else {
                    clearInterval(timer);
                    this.btnText = this.$t('project_refresh');
                    this.isDisabled = false;
                }
            }, 1000);
        },
        getBatchName (batches) {
            let batchArr = [];
            $.each(batches, (k, v) => {
                batchArr.push(v.name)
            });
            return batchArr.toString();
        },
        changePage (page) {
            this.page = page;
            this.selItem = [];
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.selItem = [];
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            $.each(data.list, (k, v) => {
                if ((v.pack_status !== '3') || (v.pack_script_id === '76')) {
                    this.$set(data.list[k], '_disabled', true);
                }
            });
            this.tableData = data.list;
            this.count = +data.count;
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.download.filePack,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id,
                    limit: this.limit,
                    page: this.page
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.batchesIds = [];
                        $.each(this.batches, (k, v) => {
                            this.batchesIds.push(v.id);
                        });
                        this.currData.batches = this.batchesIds;
                        this.getTableData(res.data);
                        this.pack_statuses = res.data.pack_statuses;
                        this.startTime2 = util.timeFormatter(new Date(+this.project.start_time * 1000), 'yyyy-MM-dd');
                        this.endTime2 = util.timeFormatter(new Date(+this.project.end_time * 1000), 'yyyy-MM-dd');
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        getPackForm () {
            $.ajax({
                url: api.download.packForm,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.types = res.data.pack_scripts;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        // this.loading = false;
                    });
                }
            });
        },
        fileBuild () {
            if (this.downLoading) {
                return;
            }
            this.downLoading = true;
            let config;
            if (this.currData.packtype == '1') {
                config = {
                    cnEscape: this.currData.configs,
                    startTime: util.timeFormatter(this.startTime, 'yyyy-MM-dd hh-mm-ss'),
                    endTime: util.timeFormatter(this.endTime, 'yyyy-MM-dd hh-mm-ss'),
                    RemoveEmpty: this.currData.removeEmpty,
                };
            } else {
                config = {
                    'download': 'project',
                    cnEscape: this.currData.configs,
                    startTime: util.timeFormatter(this.startTime, 'yyyy-MM-dd hh-mm-ss'),
                    endTime: util.timeFormatter(this.endTime, 'yyyy-MM-dd hh-mm-ss'),
                    RemoveEmpty: this.currData.removeEmpty,
                    'batch_id': this.currData.batches.toString(),
                };
            }

            let startEnd = this.checkTime(this.startTime, this.endTime);
            this.$refs.formValidate.validate((valid) => {
                if (valid && startEnd) {
                    $.ajax({
                        url: api.download.fileBuild,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            project_id: this.$route.params.id,
                            batch_ids: this.currData.batches.toString(),
                            step_id: this.currData.step,
                            pack_script_id: this.currData.type,
                            configs: JSON.stringify(config),
                        },
                        success: (res) => {
                            this.downLoading = false;
                            this.loading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.getData();
                                this.$refs.formValidate.resetFields();
                                this.$Message.success({
                                    content: this.$t('project_submit_successful_wait_results'),
                                    duration: 3
                                });
                                this.startTime = '';
                                this.endTime = '';
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.downLoading = false;
                            });
                        },
                    });
                } else {
                    if (startEnd != true) {
                        this.$Message.warning({
                            content: this.$t('project_check_starting_ending_time'),
                            duration: 3
                        });
                    }
                    this.downLoading = false;
                    this.verdictAnchor();
                }
            });
        },
        showModal () {
            let config;
            if (this.currData.packtype == '1') {
                config = {
                    cnEscape: this.currData.configs,
                    startTime: util.timeFormatter(this.startTime, 'yyyy-MM-dd hh-mm-ss'),
                    endTime: util.timeFormatter(this.endTime, 'yyyy-MM-dd hh-mm-ss'),
                    RemoveEmpty: this.currData.removeEmpty,
                };
            } else {
                config = {
                    'download': 'project',
                    cnEscape: this.currData.configs,
                    startTime: util.timeFormatter(this.startTime, 'yyyy-MM-dd hh-mm-ss'),
                    endTime: util.timeFormatter(this.endTime, 'yyyy-MM-dd hh-mm-ss'),
                    RemoveEmpty: this.currData.removeEmpty,
                    'batch_id': this.currData.batches.toString(),
                };
            }

            let startEnd = this.checkTime(this.startTime, this.endTime);
            this.$refs.formValidate.validate((valid) => {
                if (valid && startEnd) {
                    this.loadData = true;
                    this.postData = {
                        project_id: this.$route.params.id,
                        batch_ids: this.currData.batches.toString(),
                        step_id: this.currData.step,
                        pack_script_id: this.currData.type,
                        configs: config,
                        type: this.project.category.type
                    };
                    this.selModal = true;
                } else {
                    if (!startEnd) {
                        this.$Message.warning({
                            content: this.$t('project_check_starting_ending_time'),
                            duration: 3
                        });
                    }
                    this.verdictAnchor();
                }
            });
        },
        selChange (selection, row) {
            let arr = [];
            $.each(selection, (k, v) => {
                arr.push({
                    pack_file_key: v.pack_file_key,
                    check_file_key: v.check_file_key
                });
            });
            this.selItem = arr;
        },
        batchDownloadPack () {
            this.selItem.forEach((item, index) => {
                setTimeout(() => {
                    window.open(api.download.file + '?file=' + item.pack_file_key);
                }, 300 * index);
            });
        },
        batchDownloadCheck () {
            this.selItem.forEach((item, index) => {
                setTimeout(() => {
                    window.open(api.download.file + '?file=' + item.check_file_key);
                }, 300 * index);
            });
        },
        selAll () {
            if (this.currData.batches.length == this.batchesIds.length) {
                this.currData.batches = [];
            } else {
                this.currData.batches = this.batchesIds;
            }
        },
        checkTime (starttime, endtime) {
            if (!starttime || !endtime) {
                return true;
            } else {
                return starttime < endtime;
            }
        },
        checkStartTime () {
            let downStartTime = this.startTime;
            let downEndTime = this.endTime;
            if (downStartTime > downEndTime && downEndTime !== '') {
                if (downStartTime !== '') {
                    this.$Message.warning({
                        content: this.$t('project_check_starting_time'),
                        duration: 3
                    });
                }
                this.startTime = '';
            }
        },
        checkEndTime (date) {
            let downStartTime = this.startTime;
            let downEndTime = this.endTime;
            if (downStartTime > downEndTime && downStartTime !== '') {
                if (downEndTime !== '') {
                    this.$Message.warning({
                        content: this.$t('project_check_ending_time'),
                        duration: 3
                    });
                }
                this.endTime = '';
            }
        },
        verdictAnchor () {
            // 锚点
            let anchorSource = this.$refs.formValidate.fields;
            let anchorTarget = '';
            anchorSource.map(function (val, index) {
                if (anchorSource[index].validateMessage !== '') {
                    if (index == 0) {
                        return (anchorTarget = 'anchorBatch');
                    } else if (index == 1) {
                        return (anchorTarget = 'anchorStep');
                    } else if (index == 2) {
                        return (anchorTarget = 'anchorType');
                    }
                }
            });
            this.goAnchor(anchorTarget);
        },
        goAnchor (selector) {
            let anchor = document.querySelector('#' + selector);
            anchor.scrollIntoView({ behavior: 'smooth', block: 'end', });
        }
    },
    components: {
        progressOp,
        datasetlist
    }
};
</script>
<style scoped>
    .subcontent {
      background: #efefef;
    }
    .main-con{
        background:#efefef;
        padding: 20px 25px 10px;
    }
    .blue-icon{
        display: inline-block;
        width: 3px;
        height: 18px;
        background: #2d8cf0;
        position: relative;
        top: 3px;
        margin-right:15px;
    }
    .form-item {
      border-bottom: 1px dashed #ccc;
      padding-bottom: 20px;
      position: relative;
    }
    .sel-group-sty {
      font-size: 14px;
      min-width: 270px;
      word-break: break-all;
      font-weight: 400;
    }
    .sel-group-sty2 {
      font-size: 14px;
      min-width: 300px;
      word-break: break-all;
      font-weight: 400;
    }
    #file_pack2 {
      padding-top: 5px;
      border-bottom: 1px dashed #cccccc;
      margin-bottom: 20px;
    }
    #file_pack3 {
      padding-top: 5px;
      border-bottom: 1px dashed #cccccc;
      margin-bottom: 20px;
    }
    #file_pack4 {
      padding-top: 5px;
      border-bottom: 1px dashed #cccccc;
      margin-bottom: 20px;
    }
</style>
<style>
    #project_index .item_title {
        margin-left: 8px;
        font-size: 15px;
        font-weight: 700;
    }
    #file_pack.ivu-form .ivu-form-item-label {
      font-size: 15px;
      font-weight: 700;
    }
    #file_pack2.ivu-form .ivu-form-item-label {
      font-size: 14px;
      font-weight: 500;
    }
</style>
