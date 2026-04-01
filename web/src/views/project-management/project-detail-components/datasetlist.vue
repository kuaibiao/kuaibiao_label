<template>
    <div>
        <Tabs value="dataset">
            <TabPane :label="$t('project_dataset')" name="dataset">
                <Row class="margin-bottom-10">
                    <div class="search_input">
                        <Input v-model="keyword"
                            @on-enter="changeKeyword"
                            @on-search="changeKeyword"
                            :placeholder="$t('project_dataset_input_text')"
                            clearable
                            search
                            :enter-button="true"/>
                    </div>
                </Row>
                <Row>
                    <Table
                        size="large"
                        highlight-row
                        ref="prejectTable"
                        :columns="tableOption"
                        :data="tableData"
                        :loading="loading"
                        stripe
                        show-header
                        @on-filter-change = "filterChange"
                        @on-sort-change = "sortChange"
                    ></Table>
                    <div style="margin: 10px;overflow: hidden">
                        <div style="float: right;">
                            <Page
                                :total="count"
                                :current="page"
                                :page-size ="limit"
                                :page-size-opts="[5,8,10,20]"
                                show-total
                                show-elevator
                                show-sizer
                                transfer
                                placement = "top"
                                @on-change="changePage"
                                @on-page-size-change = "changePageSize"
                                ></Page>
                        </div>
                    </div>
                </Row>
            </TabPane>
            <TabPane label="FTP" name="ftp">
                <Form 
                ref="ftpForm" 
                label-position="left" 
                :rules="ruleValidate" 
                :model="ftp" 
                :label-width="120" 
                @submit.native.prevent 
                style="padding: 20px 30px 0">
                    <FormItem :label="$t('project_ftp_address') + '：'" prop="ftp_host">
                        <Input v-model="ftp.ftp_host" style="width: 350px"/>
                    </FormItem>
                    <FormItem :label="$t('project_username') + '：'" prop="ftp_username">
                        <Input v-model="ftp.ftp_username" style="width: 350px"/>
                    </FormItem>
                    <FormItem :label="$t('user_password') + '：'" prop="ftp_password">
                        <Input v-model="ftp.ftp_password" style="width: 350px"/>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="sendToFtp">{{$t('project_ftp_push')}}</Button>
                    </FormItem>
                </Form>
            </TabPane>
        </Tabs>
        
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
export default {
    props: {
        loadData: {
            type: Boolean
        },
        postData: {
            type: Object
        }
    },
    data () {
        return {
            loading: false,
            btnLoading: false,
            ftp: {
                ftp_host: '',
                ftp_username: '',
                ftp_password: '',
            },
            keyword: '',
            count: 0,
            page: 1,
            limit: 5,
            status: [],
            types: [],
            orderby: '',
            sort: '',
            type: '',
            currentTemplate: {},
            ruleValidate: {
                ftp_host: [
                    { required: true, message: this.$t('project_ftp_cannot_empty'), trigger: 'blur' }
                ],
                ftp_username: [
                    { required: true, message: this.$t('project_input_user_name'), trigger: 'blur' }
                ],
                ftp_password: [
                    { required: true, message: this.$t('project_input_password'), trigger: 'blur' }
                ],
            },
            tableOption: [
                {
                    title: 'ID',
                    key: 'id',
                    align: 'center',
                },
                {
                    title: this.$t('project_dataset'),
                    key: 'name',
                    align: 'center',
                },
                {
                    title: this.$t('project_type'),
                    key: 'type',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', this.types[para.row.type]);
                    },
                },
                {
                    title: this.$t('project_operation'),
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h(
                                'Button',
                                {
                                    props: {
                                        type: 'primary',
                                        disabled: this.btnLoading
                                    },
                                    style: {
                                        marginRight: '10px'
                                    },
                                    on: {
                                        click: () => {
                                            this.loading = true;
                                            this.getFtp(para.row.id);
                                        }
                                    }
                                },
                                this.$t('project_push')
                            )
                        ]);
                    }
                }
            ],
            tableData: [],
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        },
        loadData () {
            if (this.loadData) {
                this.page = 1;
                this.getData();
            }
        },
    },
    methods: {
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.slice();
            this.page = 1;
            this.getData();
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.download.datasetList,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit,
                },
                success: (res) => {
                    let data = res.data;
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.count = +data.count; // 整数
                        this.types = data.types;
                        this.tableData = data.list;
                        this.$emit('sel-updated');
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                },
            });
        },
        getFtp (id) {
            $.ajax({
                url: api.download.getFtp,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    dataset_id: id,
                    project_id: this.postData.project_id,
                    batch_ids: this.postData.batch_ids,
                    step_id: this.postData.step_id,
                    type: this.postData.type,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_ftp_pushing') + res.data.ftp,
                            duration: 3
                        });
                        this.$emit('sel-selected');
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                },
            });
        },
        sendToFtp () {
            this.$refs.ftpForm.validate((valid) => {
                if (valid) {
                    this.loading = true;
                    let configs = {
                        ...this.postData.configs,
                        ftp_host: this.ftp.ftp_host,
                        ftp_username: this.ftp.ftp_username,
                        ftp_password: this.ftp.ftp_password,
                    };
                    let data = {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.postData.project_id,
                        batch_ids: this.postData.batch_ids,
                        step_id: this.postData.step_id,
                        pack_script_id: this.postData.pack_script_id,
                        configs: JSON.stringify(configs)
                    };
                    $.ajax({
                        url: api.download.fileBuild,
                        type: 'post',
                        data: data,
                        success: (res) => {
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                                this.loading = false;
                            } else {
                                this.$Message.success({
                                    content: this.$t('project_ftp_pushing') + this.ftp.ftp_host,
                                    duration: 3
                                });
                                this.$emit('sel-selected');
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.loading = false;
                            });
                        },
                    });
                }
            });
        }
    },
};
</script>
<style lang="scss">
    .preview-modal .ivu-modal {
        width: 80%!important;
        max-width: 1440px;
    }
</style>


