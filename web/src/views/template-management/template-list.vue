<template>
    <div>
        <Row class="margin-bottom-10">
            <i-col span="4">
                <!--<Button type="primary" icon="md-add" :to="{name: 'template-edit',params: {id: 'new'}}"-->
                <!--v-if="canEditTemplate">-->
                <!--{{$t('project_new_template')}}-->
                <!--</Button>-->
                <Button type="primary" icon="md-add" @click="selectCategoryModal = true" v-if="canEditTemplate">
                    {{$t('project_new_template')}}
                </Button>
            </i-col>
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
      <Row>
        <div class="tabs">
          <div :class="curTab == item.id ? 'active' : ''" v-for="item in newTypeList" :key="item.id" @click="changeTab(item)">{{item.name}}</div>
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
                    @on-filter-change="filterChange"
                    @on-sort-change="sortChange"
            ></Table>
            <div style="margin: 10px;overflow: hidden">
                <div style="float: right;">
                    <Page
                            :total="count"
                            :current="page"
                            :page-size="limit"
                            :page-size-opts="[5,10,15,20,25]"
                            show-total
                            show-elevator
                            show-sizer
                            transfer
                            placement="top"
                            @on-change="changePage"
                            @on-page-size-change="changePageSize"
                    ></Page>
                </div>
            </div>
        </Row>
        <Modal
                v-model="deleteComfirmModal"
                :title="$t('project_operation_tip')">
            <p>{{$t('project_sure_delete_current_template')}}</p>
            <div slot="footer">
                <Button type="text" @click="deleteComfirmModal = false">{{$t('project_cancel')}}</Button>
                <Button type="error" @click="deleteTemplate" :loading="deleteLoading">{{$t('project_delete')}}</Button>
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
                @click="goTemplateEdit(category.id)">
                    <img class="category-image" :src="staticBase + category.thumbnail" alt="" height="64px">
                    <h4 class="category-name">{{category.desc.name}}</h4>
                </div>
            </div>
        </Modal>
        <Modal
                :width="500"
                v-model="doingProjectsModal"
                :title="$t('project_operation_tip')">
            <p style="font-size: 16px; color: #202637; padding: 0 14px; margin-bottom: 10px" >{{$t('project_edit_template_tip')}}</p>
            <p style="font-size: 14px; color: #5c667d; margin: 4px 0; padding: 0 14px;" v-for="(item, index) in doingProjects" :key="index">{{item}}</p>
            <span slot="footer">
                <Button type="default" @click="doingProjectsModal = false">{{$t('project_close')}}</Button>
            </span>
        </Modal>
    </div>
</template>

<script>
    import api from '@/api';
    import util from '@/libs/util';

    export default {
        name: 'template-list',
        data () {
            return {
                staticBase: api.staticBase,
                loading: false,
                deleteLoading: false,
                doingProjectsModal: false,
                doingProjects: [],
                keyword: '',
                count: 0,
                page: 1,
                limit: 10,
                status: [],
                orderby: '',
                sort: '',
                type: '',
                category_id: '',
                tableOption: [
                    {
                        title: 'ID',
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
                            return h('router-link', {
                                attrs: {
                                    to: '/template/edit/' + params.row.id + '/' + params.row.category_id,
                                }
                            }, params.row.name);
                        }
                    },
                    {
                        title: this.$t('project_template_category_name'),
                        key: 'category_id',
                        align: 'center',
                        render: (h, params) => {
                            return h('div', ((params.row.category && params.row.category.name) || ''));
                        },
                        filterMultiple: false,
                        filters: [],
                        filterMethod: () => true
                    },
                    {
                        title: this.$t('project_type'),
                        key: 'type',
                        align: 'center',
                        render: (h, para) => {
                            return h('div', this.typeList[para.row.type]);
                        },
                        filterMultiple: false,
                        filters: [],
                        // filterMethod: () => true

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
                        title: this.$t('project_create_time'),
                        key: 'created_at',
                        align: 'center',
                        sortable: 'custom',
                        render: (h, para) => {
                            return h(
                                'span',
                                util.timeFormatter(
                                    new Date(+para.row.created_at * 1000),
                                    'MM-dd hh:mm'
                                )
                            );
                        }
                    },
                    {
                        title: this.$t('project_update_time'),
                        key: 'updated_at',
                        align: 'center',
                        sortable: 'custom',
                        render: (h, para) => {
                            return h(
                                'span',
                                util.timeFormatter(
                                    new Date(+para.row.updated_at * 1000),
                                    'MM-dd hh:mm'
                                )
                            );
                        }
                    },
                    {
                        title: this.$t('project_operation'),
                        align: 'left',
                        key: 'status',
                        render: (h, para) => {
                            if (!this.canEditTemplate) {
                                return h('div', [
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                type: 'info',
                                                size: 'small'
                                            },
                                            style: {
                                                marginRight: '4px'
                                            },
                                            on: {
                                                click: () => {
                                                    this.$router.push({
                                                        name: 'template-edit',
                                                        params: {
                                                            id: para.row.id,
                                                            categoryId: para.row.category_id,
                                                        }
                                                    });
                                                }
                                            }
                                        },
                                        this.$t('project_check')
                                    ),
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                size: 'small',
                                                type: 'warning',
                                            },
                                            style: {
                                                marginRight: '4px'
                                            },
                                            on: {
                                                click: () => {
                                                    this.copyTemplate(para.row.id);
                                                }
                                            }
                                        },
                                        this.$t('project_copy')
                                    ),
                                ]);
                            } else {
                                return h('div', [
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                type: 'primary',
                                                size: 'small'
                                            },
                                            style: {
                                                marginRight: '4px'
                                            },
                                            on: {
                                                click: () => {
                                                    this.$router.push({
                                                        name: 'template-edit',
                                                        params: {
                                                            id: para.row.id,
                                                            categoryId: para.row.category_id,
                                                        }
                                                    });
                                                }
                                            }
                                        },
                                        this.$t('project_edit')
                                    ),
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                size: 'small',
                                                type: 'warning',
                                            },
                                            style: {
                                                marginRight: '4px'
                                            },
                                            on: {
                                                click: () => {
                                                    this.copyTemplate(para.row.id);
                                                }
                                            }
                                        },
                                        this.$t('project_copy')
                                    ),
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                size: 'small'
                                            },
                                            style: {
                                                marginRight: '4px'
                                            },
                                            on: {
                                                click: () => {
                                                    $.ajax({
                                                        url: api.template.detail,
                                                        type: 'post',
                                                        data: {
                                                            access_token: this.$store.state.user.userInfo.accessToken,
                                                            template_id: para.row.id
                                                        },
                                                        success: res => {
                                                            if (res.error) {
                                                                this.$Message.warning({
                                                                    content: res.message,
                                                                    duration: 3
                                                                });
                                                            } else {
                                                                if (res.data.projects.length) {
                                                                    this.doingProjects = res.data.projects;
                                                                    this.doingProjectsModal = true;
                                                                } else {
                                                                    this.wantDeleteTemplateId = para.row.id;
                                                                    this.deleteComfirmModal = true;
                                                                }
                                                            }
                                                        },
                                                        error: (res, textStatus, responseText) => {
                                                            util.handleAjaxError(this, res, textStatus, responseText);
                                                        }
                                                    });
                                                }
                                            }
                                        },
                                        this.$t('project_delete')
                                    )
                                ]);
                            }
                        }
                    }
                ],
                tableData: [],
                wantDeleteTemplateId: '',
                deleteComfirmModal: false,
                selectCategoryModal: false,
                categoryList: [],
                typeList: [],
              curTab:0,
              newTypeList:[],
            };
        },
        watch: {
            keyword () {
                if (!this.keyword) { // 点击输入框的清空按钮是 ivu 未派发事件
                    this.page = 1;
                    this.getData();
                }
            }
        },
        computed: {
            canEditTemplate () {
                return this.$store.state.app.settings.open_template_diy === '1';
            }
        },
        mounted () {
            this.getData();
            this.getCategoryList();
        },
        methods: {
            toEditPage () {
                this.$router.push({
                    name: 'template-edit',
                    params: {
                        id: this.currentTemplate,
                        categoryId: this.currentCategory,
                    }
                });
            },
            filterChange (filter) {
                let key = filter.key;
                this[key] = filter._filterChecked.toString();
                this.page = 1;
                this.getData();
            },
            changeFilter () {
                let categoryMap = [];
                let typeMap = [];
                this.categoryList.forEach(v => {
                    let category = {
                        label: v.desc.name,
                        value: v.id
                    };
                    categoryMap.push(category);
                });
                Object.keys(this.typeList).forEach((v) => {
                    let status = {
                        label: this.typeList[v],
                        value: v
                    };
                    typeMap.push(status);
                });
                let idIndex = util.getKeyIndexFromTableOption(this.tableOption, 'id');
                let createdIndex = util.getKeyIndexFromTableOption(this.tableOption, 'created_at');
                let updatedIndex = util.getKeyIndexFromTableOption(this.tableOption, 'updated_at');
                let categoryIndex = util.getKeyIndexFromTableOption(this.tableOption, 'category_id');
                let typeIndex = util.getKeyIndexFromTableOption(this.tableOption, 'type');
                this.tableOption[categoryIndex].filters = categoryMap;
                this.tableOption[typeIndex].filters = typeMap;
                this.$nextTick(() => {
                    if (this.category_id !== '') {
                        this.$set(this.$refs.projectTable.cloneColumns[categoryIndex], '_filterChecked', [this.category_id]);
                        this.$set(this.$refs.projectTable.cloneColumns[categoryIndex], '_isFiltered', true);
                    }
                    if (this.type !== '') {
                        this.$set(this.$refs.projectTable.cloneColumns[typeIndex], '_filterChecked', [this.type]);
                        this.$set(this.$refs.projectTable.cloneColumns[typeIndex], '_isFiltered', true);
                    }
                    if (this.orderby === 'id') {
                        this.$set(this.$refs.projectTable.cloneColumns[idIndex], '_sortType', this.sort);
                    }
                    if (this.orderby === 'created_at') {
                        this.$set(this.$refs.projectTable.cloneColumns[createdIndex], '_sortType', this.sort);
                    }
                    if (this.orderby === 'updated_at') {
                        this.$set(this.$refs.projectTable.cloneColumns[updatedIndex], '_sortType', this.sort);
                    }
                });
            },
            changeTab(data){
                this.curTab = data.id;
                this.page = 1;
                this.getData();
            },
            getCategoryList () {
                $.ajax({
                    url: api.template.form,
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
                            this.changeFilter();
                        }
                    }
                });
            },
            deleteTemplate () {
                this.deleteLoading = true;
                $.ajax({
                    url: api.template.delete,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        template_id: this.wantDeleteTemplateId
                    },
                    success: res => {
                        this.deleteLoading = false;
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
                            this.deleteLoading = false;
                            this.deleteComfirmModal = false;
                        });
                    }
                });
            },
            goTemplateEdit (categoryId) {
                this.$router.push({
                    name: 'template-edit',
                    params: {
                        id: 'new',
                        categoryId: categoryId,
                    }
                });
            },
            copyTemplate (id) {
                $.ajax({
                    url: api.template.copy,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        template_id: id
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.success({
                                content: this.$t('project_copy_successful_template_name') + res.data.info.name,
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
            sortChange ({column, key, order}) {
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
                        type: this.curTab,
                        category_id: this.category_id.toString(),
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
                            this.typeList = res.data.types || {};
                            this.count = +data.count; // 整数
                            this.changeFilter(data);
                              this.newTypeList =[];
                              Object.keys(this.typeList).forEach((v) => {
                              let status = {
                                name: this.typeList[v],
                                id: v
                              };
                                this.newTypeList.push(status);
                            });
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            }
        },
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
<style scoped lang="scss">
.tabs{
  display: flex;
  justify-content: flex-start;
  align-items: center;
  >div{
    width: 80px;
    height: 34px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-sizing: border-box;
  }
  .active{
    background:#2b85e4;
    color:#FFFFFF;
    border-radius: 4px 4px 0 0;
  }
}
</style>


