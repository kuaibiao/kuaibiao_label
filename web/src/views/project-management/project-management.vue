<template>
    <div>
        <Row class="margin-bottom-10">
              <Button type="primary" icon="md-add" @click="getCategoryList">
                  {{$t('project_create_project')}}
              </Button>
              <!-- <Button type="primary" :to="{name: 'deployment'}">{{$t('system_index_deploy')}}</Button> -->
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('project_search_project_nameid')"
                    clearable
                    search
                    :enter-button="true"
                    />
            </div>
        </Row>
        <Row>
            <Table
                size="large"
                highlight-row
                ref="projectTable"
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
                        :page-size-opts="[5,10,15,20,25]"
                        show-total
                        show-elevator
                        show-sizer
                        placement = "top"
                        transfer
                        @on-change="changePage"
                        @on-page-size-change = "changePageSize"
                        ></Page>
                </div>
            </div>
        </Row>
        <Modal
                v-model="deleteComfirmModal"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_delete_current_project')}}</p>
            <div slot="footer">
                <Button type="text" @click="deleteComfirmModal = false">{{$t('project_cancel')}}</Button>
                <Button type="error" @click="deleteProject">{{$t('project_delete')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="reopenModel"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_restart_current_project')}}</p>
            <div slot="footer">
                <Button type="text" @click="reopenModel = false">{{$t('project_cancel')}}</Button>
                <Button type="success" @click="reopenProject">{{$t('project_restart')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="pauseModel"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_pause_current_project')}}</p>
            <div slot="footer">
                <Button type="text" @click="pauseModel = false">{{$t('project_cancel')}}</Button>
                <Button type="error" @click="pauseProject">{{$t('project_pause')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="recoverModel"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_recover_current_project')}}</p>
            <div slot="footer">
                <Button type="text" @click="recoverModel = false">{{$t('project_cancel')}}</Button>
                <Button type="success" @click="recoverProject">{{$t('project_recover')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="finishModel"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_completed_current_project')}}</p>
            <div slot="footer">
                <Button type="text" @click="finishModel = false">{{$t('project_cancel')}}</Button>
                <Button type="success" @click="finishProject">{{$t('project_sure')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="selectCategoryModal"
                :title="$t('project_select_type')"
                :footer-hide="true"
                :scrollable="true"
                class="select-category-modal"
        >
            <div class="category-list">
                <div class="category-item"
                v-for="category in categoryList"
                :key="category.id"
                @click="goCreateProject(category.id)">
                    <img class="category-image" :src="staticBase + category.thumbnail" alt="" height="64px">
                    <h4 class="category-name">{{category.name}}</h4>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
import ProjectOp from './components/project-op';
import unpackOp from './components/unpackOp';
import taskOp from './components/task-op';
import Vue from 'vue';

export default {
    name: 'project-management',
    data () {
        return {
            loading: false,
            staticBase: api.staticBase,
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            status: '',
            orderby: '',
            sort: '',
            category_id: '',
            statuses: [],
            tableOption: [
                {
                    title: 'ID',
                    key: 'id',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
                },
                {
                    title: this.$t('project_project_name'),
                    key: 'name',
                    // ellipsis: true,
                    render: (h, params) => {
                        return h('router-link', {
                            props: {
                                to: {
                                    name: 'project-detail',
                                    params: {
                                        id: params.row.id,
                                        tab: 'overview'
                                    }
                                }
                            }
                        }, params.row.name);
                    }
                },
                {
                    title: this.$t('admin_type'),
                    key: 'category_id',
                    maxWidth: 130,
                    align: 'center',
                    render: (h, para) => {
                        return h('span', this.categories[para.row.category.id]);
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                {
                  title: this.$t('admin_assign_type'),
                  key: 'assign_type',
                  maxWidth: 130,
                  align: 'center',
                  render: (h, para) => {
                    return h('span', this.assign_types[para.row.assign_type]);
                  },
                  // filterMultiple: false,
                  // filters: [],
                  // filterMethod: () => true
                },
                {
                    title: this.$t('project_publisher'),
                    key: 'user_name',
                    align: 'center',
                    maxWidth: 140,
                    render: (h, para) => {
                        return h('div', [
                            h('Tooltip', {
                                props: {
                                    content: this.$t('project_email') + ': ' + para.row.user.email,
                                    placement: 'top',
                                    transfer: true,
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + para.row.user.id),
                                            h('div', this.$t('project_email') + ': ' + para.row.user.email),
                                        ]);
                                    }
                                }
                            }, [
                                h('div', para.row.user.nickname),
                                h('div', para.row.user.company_name)
                            ]),
                        ]);
                    }
                },
                {
                    title: this.$t('admin_status'),
                    key: 'status',
                    align: 'left',
                    maxWidth: 180,
                    render: (h, para) => {
                        return h('div', [
                            h(unpackOp, {
                                props: {
                                    status: para.row.status,
                                    statuses: this.statuses,
                                    row: para.row
                                },
                            })
                        ]);
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                {
                    title: this.$t('project_number_jobs'),
                    key: 'amount',
                    maxWidth: 110,
                    sortable: 'custom',
                    align: 'center',
                    render: (h, para) => {
                        let amount;
                        //教学模式,要显示上传作业量
                        if (para.row.assign_type > 0){
                            amount = para.row.upload_data_count;
                        } else {
                            //抢单模式
                            amount = para.row.amount
                        }
                        return h('span', amount);
                    },
                },
                {
                    title: this.$t('project_starting_time'),
                    key: 'start_time',
                    align: 'center',
                    maxWidth: 120,
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.start_time * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('project_finish_time'),
                    key: 'end_time',
                    align: 'center',
                    maxWidth: 120,
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.end_time * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('project_operation'),
                    align: 'left',
                    key: 'status',
                    maxWidth: 150,
                    render: (h, para) => {
                        return h('div', [
                            h(ProjectOp, {
                                props: {
                                    status: para.row.status,
                                    projectId: para.row.id,
                                    template_id: para.row.template_id,
                                },
                                on: {
                                    'copy-project': (projectId) => {
                                        this.copyProject(projectId);
                                    },
                                    'delete-project': (projectId) => {
                                        this.deleteComfirmModal = true;
                                        this.wantDeleteProjectId = projectId;
                                    },
                                    'pause-project': (projectId) => {
                                        this.pauseModel = true;
                                        this.pauseProjectId = projectId;
                                    },
                                    'recover-project': (projectId) => {
                                        this.recoverModel = true;
                                        this.recoverProjectId = projectId;
                                    },
                                    'reopen-project': (projectId) => {
                                        this.reopenModel = true;
                                        this.reopenProjectId = projectId;
                                    },
                                    'finish-project': (projectId) => {
                                        this.finishModel = true;
                                        this.finishProjectId = projectId;
                                    }
                                }
                            })
                        ]);
                    }
                }
            ],
            tableData: [],
            deleteComfirmModal: false,
            recoverModel: false,
            pauseModel: false,
            reopenModel: false,
            finishModel: false,
            wantDeleteProjectId: '',
            pauseProjectId: '',
            recoverProjectId: '',
            reopenProjectId: '',
            finishProjectId: '',
            selectCategoryModal: false,
            categories: [],
            categoryList: [],
            assign_types:[],
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        }
    },
    mounted () {
        this.getData();
    },
    methods: {
        copyProject (projectId) {
            $.ajax({
                url: api.project.copy,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: projectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_copy_success'),
                            duration: 3
                        });
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        deleteProject () {
            $.ajax({
                url: api.project.delete,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.wantDeleteProjectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_delete_success'),
                            duration: 3
                        });
                        this.getData();
                    }
                    this.deleteComfirmModal = false;
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.deleteComfirmModal = false;
                    });
                }
            });
        },
        reopenProject () {
            $.ajax({
                url: api.project.reopen,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.reopenProjectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_restart_success'),
                            duration: 3
                        });
                        this.getData();
                    }
                    this.reopenModel = false;
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.reopenModel = false;
                    });
                }
            });
        },
        recoverProject () {
            $.ajax({
                url: api.project.recover,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.recoverProjectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_recover_success'),
                            duration: 3
                        });
                        this.getData();
                    }
                    this.recoverModel = false;
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.recoverModel = false;
                    });
                }
            });
        },
        pauseProject () {
            $.ajax({
                url: api.project.pause,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.pauseProjectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_pauset_success'),
                            duration: 3
                        });
                        this.getData();
                    }
                    this.pauseModel = false;
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.pauseModel = false;
                    });
                }
            });
        },
        finishProject () {
            $.ajax({
                url: api.project.finish,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.finishProjectId
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_project_setup'),
                            duration: 3
                        });
                        this.getData();
                    }
                    this.finishModel = false;
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.finishModel = false;
                    });
                }
            });
        },
        changeCatrgoryFilter (categoryMap, statusesMap) {
            // 动态调整项目类型过滤器
            let idIndex = util.getKeyIndexFromTableOption(this.tableOption, 'id');
            let categoryIndex = util.getKeyIndexFromTableOption(this.tableOption, 'category_id');
            let statusIndex = util.getKeyIndexFromTableOption(this.tableOption, 'status');
            let amountIndex = util.getKeyIndexFromTableOption(this.tableOption, 'amount');
            let startIndex = util.getKeyIndexFromTableOption(this.tableOption, 'start_time');
            let endIndex = util.getKeyIndexFromTableOption(this.tableOption, 'end_time');
            if (categoryIndex < 0 || statusIndex < 0 || amountIndex < 0 || startIndex < 0 || endIndex < 0 || idIndex < 0) {
                return;
            }
            let cateType = this.tableOption[categoryIndex];
            cateType.filters = categoryMap;
            let status = this.tableOption[statusIndex];
            status.filters = statusesMap;
            // hack 动态filter
            Vue.nextTick(() => {
                if (this.category_id !== '') {
                    this.$set(this.$refs.projectTable.cloneColumns[categoryIndex], '_filterChecked', [this.category_id]);
                    this.$set(this.$refs.projectTable.cloneColumns[categoryIndex], '_isFiltered', true);
                }
                if (this.status !== '') {
                    this.$set(this.$refs.projectTable.cloneColumns[statusIndex], '_filterChecked', [this.status]);
                    this.$set(this.$refs.projectTable.cloneColumns[statusIndex], '_isFiltered', true);
                }
                if (this.orderby === 'id') {
                    this.$set(this.$refs.projectTable.cloneColumns[idIndex], '_sortType', this.sort);
                }
                if (this.orderby === 'amount') {
                    this.$set(this.$refs.projectTable.cloneColumns[amountIndex], '_sortType', this.sort);
                }
                if (this.orderby === 'start_time') {
                    this.$set(this.$refs.projectTable.cloneColumns[startIndex], '_sortType', this.sort);
                }
                if (this.orderby === 'end_time') {
                    this.$set(this.$refs.projectTable.cloneColumns[endIndex], '_sortType', this.sort);
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
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
            let categoryMap = [];
            let statusesMap = [];
            this.statuses = data.statuses;
            Object.keys(data.categories).forEach(v => {
                let category = {
                    label: data.categories[v],
                    value: v
                };
                categoryMap.push(category);
            });
            Object.keys(data.statuses).forEach(v => {
                let status = {
                    label: data.statuses[v],
                    value: v
                };
                statusesMap.push(status);
            });
            this.changeCatrgoryFilter(categoryMap, statusesMap);
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.project.projects,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    status: this.status,
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit,
                    category_id: this.category_id
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.categories = res.data.categories || [];
                        this.assign_types = res.data.assign_types || [];
                        this.getTableData(res.data);

                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        getCategoryList () {
            $.ajax({
                url: api.project.form,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.categoryList = res.data.categories || [];
                        this.selectCategoryModal = true;
                    }
                }
            });
        },
        goCreateProject (categoryId) {
            $.ajax({
                url: api.project.create,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    category_id: categoryId
                },
                success: res => {
                    let data = res.data;
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_project_created_successfully'),
                            duration: 3
                        });
                        this.$router.push({
                            name: 'project-create',
                            params: {
                                id: data.project_id,
                            }
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                },
            });
        },
    },
    components: {
        ProjectOp,
        taskOp,
        unpackOp
    }
};
</script>
<style lang="scss">
    .preview-modal .ivu-modal {
        width: 80% !important;
        max-width: 1440px;
    }

    .select-category-modal {
        .ivu-modal {
            width: 75% !important;
            min-width: 480px;
            max-width: 960px;
        }

        .category-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            padding: 0 15px;
        }

        .category-item {
            text-align: center;
            min-width: 200px;
            min-height: 160px;
            border: 1px solid #d7d7d7;
            border-radius: 10px;
            margin: 10px;
            padding: 20px 0;
            cursor: pointer;

            &:hover {
                background-color: rgba(24, 144, 255, 0.2);
                border-color: rgba(24, 144, 255, 0.2);
            }

            .category-name {
                font-size: 14px;
                color: rgba(0, 0, 0, 0.6);
                margin-top: 20px;
                font-weight: 600;
            }
        }
    }
</style>
<style>
/* 样式只针对火狐浏览器 */
@-moz-document url-prefix(){
    .ivu-table-filter-list .ivu-table-filter-select-item {
        padding: 7px 28px 7px 16px;
        white-space: nowrap;
    }
}
</style>

