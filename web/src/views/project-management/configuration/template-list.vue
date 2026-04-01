<template>
    <div>
        <Row class="margin-bottom-10">
            <Button type="primary" icon="md-add" @click='creatTemplate' style="float:left" v-if="canEditTemplate" >
                {{$t('project_new_template')}}
            </Button>
            <span style="line-height:32px;margin-left:80px;font-size:14px" v-if="categoryId == proCategoryId">
                <span style="margin-right:20px">{{$t('project_default_template')}}： {{template.name}}</span>
                <Button size="small" type="warning" @click="copyTemplate(template.id)" :disabled="btnLoading">{{$t('project_use_this_copy_template')}}</Button>
            </span>
            <div class="search_input">
                <Input v-model="keyword"
                       @on-enter="changeKeyword"
                       @on-search="changeKeyword"
                       :placeholder="$t('project_input_template_name_id')"
                       clearable
                       search
                       :enter-button="true"/>
            </div>
        </Row>
        <Row class="searchable-table-con1">
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
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
// import templateEditVue from './template-edit.vue';
export default {
    props: {
        loadData: {
            type: Boolean
        },
        // template: {
        //     type: Object,
        //     default: {}
        // },
        categoryId: {
            type: String
        },
        proCategoryId: {
            type: String
        }
    },
    data () {
        const typeMap = [
            {
                label: this.$t('project_common_template'),
                value: '0'
            },
            {
                label: this.$t('project_private_template'),
                value: '1'
            },
        ];
        return {
            loading: false,
            btnLoading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 8,
            status: [],
            orderby: '',
            sort: '',
            type: '',
            currentTemplate: {},
            tableOption: [
                {
                    title: this.$t('project_template_id'),
                    key: 'id',
                    align: 'center',
                    width: 100,
                    sortable: 'custom',
                },
                {
                    title: this.$t('project_template_name'),
                    key: 'name',
                    align: 'center',
                    render: (h, params) => {
                        return h('span', params.row.name);
                    }
                },
                {
                    title: this.$t('project_template_category_name'),
                    key: 'name',
                    align: 'center',
                    render: (h, params) => {
                        return h('div', ((params.row.category && params.row.category.name) || ''));
                    }
                },
                {
                    title: this.$t('project_type'),
                    key: 'type',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', this.smartArr(para.row.type));
                    },
                    filterMultiple: false,
                    filters: typeMap,
                    filterMethod: () => true

                },
                {
                    title: this.$t('project_founder'),
                    key: 'user',
                    align: 'center',
                    render: (h, para) => {
                        if (para.row.type === '0') { // 公共模板 不显示创建人
                            return h('span', '--');
                        } else {
                            return h('div', [
                                h('Tooltip', {
                                    props: {
                                        content: this.$t('project_email') + ': ' + para.row.user.email,
                                        placement: 'top',
                                        transfer: true,
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {}, [
                                                h('div', 'ID: ' + para.row.user.id),
                                                h('div', this.$t('project_email') + ': ' + para.row.user.email),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', para.row.user.nickname)
                                ]),
                            ]);
                        }
                    },
                },
                {
                    title: this.$t('project_operation'),
                    align: 'center',
                    key: 'status',
                    width: 300,
                    render: (h, para) => {
                        if (para.row.type == '1') {
                            return h('div', [
                                h(
                                    'Button',
                                    {
                                        props: {
                                            disabled: this.btnLoading
                                        },
                                        style: {
                                            marginRight: '3px'
                                        },
                                        on: {
                                            click: () => {
                                                this.copyTemplate(para.row.id);
                                            }
                                        }
                                    },
                                    this.$t('project_use_copy_template')
                                ),
                                h(
                                    'Button',
                                    {
                                        props: {
                                            disabled: this.btnLoading
                                        },
                                        on: {
                                            click: () => {
                                                this.templateUse(para.row.id);
                                                this.$emit('set-template', para.row);
                                            }
                                        }
                                    },
                                    this.$t('project_use_this_template')
                                )
                            ]);
                        } else {
                            return h('div', [
                                h(
                                    'Button',
                                    {
                                        props: {
                                            disabled: this.btnLoading
                                        },
                                        style: {
                                            marginRight: '10px'
                                        },
                                        on: {
                                            click: () => {
                                                this.copyTemplate(para.row.id);
                                            }
                                        }
                                    },
                                    this.$t('project_use_copy_template')
                                )
                            ]);
                        }
                    }
                }
            ],
            tableData: [],
            wantDeleteTemplateId: '',
            deleteComfirmModal: false,
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
    computed: {
        canEditTemplate () {
            return this.$store.state.user.userInfo.open_template_diy === '1';
        }
    },
    methods: {
        templateUse (id) {
            $.ajax({
                url: api.template.use,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    template_id: id
                },
                success: res => {

                }
            });
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.slice();
            this.page = 1;
            this.getData();
        },
        // 创建模板
        creatTemplate () {
            this.$emit('create-template');
        },
        copyTemplate (id) {
            this.btnLoading = true;
            $.ajax({
                url: api.template.copy,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    template_id: id
                },
                success: res => {
                    this.btnLoading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$emit('set-template', res.data.info);
                    }
                },
                error: (res, textStatus, responseText) => {
                    this.btnLoading = false;
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
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
                url: api.template.list,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    status: this.status.toString(),
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit,
                    type: this.type.toString(),
                    category_id: this.categoryId,
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
                        this.tableData = data.list;
                        this.count = +data.count; // 整数
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
        getTemplateDetail () {
            $.ajax({
                url: api.template.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    template_id: this.templateId
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.currentTemplate = res.data.template;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                },
            });
        },
        smartArr (str1) {
            let showArr = '';
            if (str1 == '0') {
                showArr = this.$t('project_common_template');
            } else if (str1 == '1') {
                showArr = this.$t('project_private_template');
            }
            return showArr;
        },
    },
};
</script>
<style lang="scss">
    .preview-modal .ivu-modal {
        width:  80%!important;
        max-width: 1440px;
    }
</style>


