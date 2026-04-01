<template>
    <div>
        <Row style="margin-bottom:10px">
            <i-col span="16">
                <Button @click.native="addUser" type="primary">
                    {{$t('admin_new_user')}}
                </Button>
                <!-- <Button @click.native="batchImportModel = true" type="primary">
                    {{$t('user_batch_import_user')}}
                </Button> -->
                <!-- <Button type="primary" :to="{name: 'group-management'}">
                    {{$t('admin_team_management')}}
                </Button> -->
            </i-col>
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('admin_input_user_info_email')"
                    clearable
                    search 
                    :enter-button="true"/>
            </div>
        </Row>
        <Row>
            <Table
                size="large"
                highlight-row
                ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                stripe
                show-header
                @on-sort-change="sortChange"
                @on-selection-change="selChange"
                @on-filter-change = "filterChange">
                <div slot="footer">
                    <Button type="primary" style="margin-left:10px" @click="batchModel = true" :disabled="selItem.length == 0">
                        {{$t('admin_batch_delete_member')}}
                    </Button>
                    <div style="float:right;margin-right:10px;font-size:12px">
                        <Page
                            :total="count"
                            :current="page"
                            :page-size="limit"
                            :page-size-opts="[10,15,20,25,30,50]"
                            show-total
                            show-elevator
                            show-sizer
                            placement="top"
                            @on-change="changePage"
                            transfer
                            @on-page-size-change="changePageSize">
                        </Page>
                    </div>
                </div>
            </Table>
        </Row>
        <Modal v-model="delModel" :title="$t('admin_operation_tips')">
            <p>{{$t('admin_delete_current_member')}}</p>
            <div slot="footer">
                <Button type="text" @click="delModel = false">{{$t('admin_cancel')}}</Button>
                <Button type="error" @click="remove" :loading="removeLoading">{{$t('admin_confirm')}}</Button>
            </div>
        </Modal>
        <Modal v-model="batchModel" :title="$t('admin_operation_tips')">
            <p>{{$t('admin_sure_delete_checked_members')}}</p>
            <div slot="footer">
                <Button type="text" @click="batchModel = false">{{$t('admin_cancel')}}</Button>
                <Button type="error" @click="batchDel" :loading="batchDelLoading">{{$t('admin_confirm')}}</Button>
            </div>
        </Modal>
        <Modal v-model="groupModal" :title="$t('admin_select_group')">
            <RadioGroup v-model="groupId">
                <Radio v-for="(item,index) in tableGroups" :label="item.id" :key="index" v-show="index != 0">{{item.name}}</Radio>
            </RadioGroup>

            <div slot="footer">
                <Button type="text" @click="groupModal = false">{{$t('admin_cancel')}}</Button>
                <Button type="success" :disabled="!this.groupId" @click="sureMass" :loading="sureMassLoading">{{$t('admin_deter_transfer')}}</Button>
            </div>
        </Modal>
        <Modal v-model="addModal">
            <p slot="header">
                <span>{{$t('admin_new_user')}}</span>
            </p>
            <div style="text-align:left">
                <Form ref="addUserForm" :model="addUserData" :label-width="100" :rules="addUserObject.ruleValidate">
                    <FormItem :label="$t('user_avatar')">
                        <div class="upload-list" v-if="addUserData.avatar">
                            <img :src="formatUrl(addUserData.avatar)">
                        </div>
                        <Upload ref="upload" 
                            :show-upload-list="false" 
                            :on-success="CreatehandleSuccessIcon" 
                            :format="['jpg','jpeg','png']" 
                            :on-format-error="handleFormatError" 
                            :max-size="2048" 
                            :action="addUserObject.upload_config.url" 
                            :name="addUserObject.upload_config.name"
                            :data="addUserObject.upload_config.data"
                            style="display: inline-block;width:58px;">
                            <Button type="primary" style="float:left">{{$t('user_avatar_settings')}}</Button>
                        </Upload>
                    </FormItem>
                    <Form-item :label="$t('admin_name')" prop="nickname">
                        <Row>
                            <i-col span="20">
                                <Input v-model="addUserData.nickname" :placeholder="$t('admin_enter_user_name')" icon="md-person"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_password')" prop="password">
                        <Row>
                            <i-col span="20">
                                <Input v-model="addUserData.password" :placeholder="$t('admin_enter_password')" icon="md-lock"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_email')" prop="email">
                        <Row>
                            <i-col span="20">
                                <Input v-model="addUserData.email" :placeholder="$t('admin_enter_email')" icon="ios-mail"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <!-- <Form-item :label="$t('admin_contact_number')" prop="phone">
                        <Row>
                            <i-col span="20">
                                <Input v-model="addUserData.phone" :placeholder="$t('admin_enter_phone')"
                                       icon="ios-call"/>
                            </i-col>
                        </Row>
                    </Form-item> -->
                    <Form-item :label="$t('admin_status')" prop="status">
                        <Radio-group v-model="addUserData.status">
                            <Radio v-for="(v,index) in addUserObject.userStatuses" :label="index" :key="index">{{v}}</Radio>
                        </Radio-group>
                    </Form-item>
                    <Form-item :label="$t('admin_type')" prop="type">
                        <Radio-group v-model="addUserData.type" @on-change="changeType">
                            <Radio v-for="(v,index) in addUserObject.userTypes" :label="index" :key="index">{{v}}</Radio>
                        </Radio-group>
                    </Form-item>
                    <Form-item :label="$t('admin_role')" prop="roles">
                        <Checkbox-group v-model="addUserData.roles">
                            <Checkbox v-for="(v,index) in addUserObject.userRoles" :label="index" :key="index"> {{v}}</Checkbox>
                        </Checkbox-group>
                    </Form-item>
                    <Form-item :label="$t('admin_site')" prop="site_id" v-if="addUserObject.showSite">
                        <Row>
                            <i-col span="20">
                                <Select v-model="addUserData.site_id" :placeholder="$t('admin_unselected')" clearable>
                                    <Option
                                    v-for="(item, key) in addUserObject.sites"
                                    :value="item.id"
                                    :key="key"
                                    >{{item.name}}</Option>
                                </Select>
                            </i-col>
                        </Row>
                    </Form-item>
                    <!-- <Form-item :label="$t('admin_group')" prop="group_id">
                        <Row>
                            <i-col span="20">
                                <Select v-model="addUserData.group_id" :placeholder="$t('admin_unselected')" clearable>
                                    <Option
                                    v-for="(item, key) in tableGroups"
                                    :value="item.id"
                                    :key="key"
                                    >{{item.name}}</Option>
                                </Select>
                            </i-col>
                        </Row>
                    </Form-item> -->
                </Form>
            </div>
            <div slot="footer">
                <Button type="success" size="large" long @click.native="saveUser" :loading="addLoading">
                {{$t('admin_save')}}
                </Button>
            </div>
        </Modal>
        <Modal v-model="batchImportModel" :title="$t('user_upload_excel')">
            <Upload
                type="drag"
                :action="serverUrl"
                :data="uploadData"
                :format="['csv', 'xls', 'xlsx']"
                :before-upload="handleUpload"
                :default-file-list="uploadedTaskFiles"
                :on-success="handleUploadSuccess"
                :on-format-error="handleFileFormatError"
                :on-remove = "handleRemoveFile"
                :on-progress = "handleUploading">
                <div style="padding: 20px 0">
                    <Icon type="ios-cloud-upload" size="52" style="color: #3399ff"></Icon>
                    <p class="desc">{{$t('user_click_select_file_drag_file')}}</p>
                    <p class="supports">{{$t('user_support_file_extensions')}} </p>
                </div>
            </Upload>
            <a :href="staticBase + '/template/user-import-template.xlsx'" style="text-decoration: underline">{{$t('user_download_excel_template')}}</a>
            <div slot="footer">
                <Button type="success" size="large" long @click.native="importNext">
                    {{$t('user_next_step')}}
                </Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import Vue from 'vue';
import api from '@/api';
import util from '@/libs/util';

export default {
    data () {
        const validatePhone = (rule, value, callback) => {
            var re = /^[0-9\-+\s]{5,20}$/;
            if (value == '') {
                callback();
            } else if (!re.test(value)) {
                callback(new Error(this.$t('admin_valide_phone')));
            } else {
                callback();
            }
        };
        const valideNewPassword = (rule, value, callback) => {
            var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/;
            if (value && !re.test(value)) {
                callback(new Error(this.$t('admin_valide_password')));
            } else {
                callback();
            }
        };
        const valideName = (rule, value, callback) => {
            let re = /(^[\u4e00-\u9fa5\w\.\-*][\u4e00-\u9fa5\w\.\-\s*]{0,19}$)/u;
            let reg = /[^\-\.\w\s\u4e00-\u9fa5]/g;
            if (reg.test(value)) {
                callback(new Error(this.$t('user_name_cannot_special_characters'))); // 不能输入特殊字符
            }
            if(value[0] == ' ') {
                callback(new Error(this.$t('user_name_begin_cannot_empty')));
            } else if (!re.test(value)) {
                callback(new Error(this.$t('user_nickname_valide')));
            } else {
                callback();
            }
        };
        return {
            staticBase: api.staticBase,
            loading: false,
            batchImportModel: false,
            errorListModal: false,
            serverUrl: api.upload.projectFiles,
            uploadedTaskFiles: [],
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            group_id: '',
            role_id: '',
            groupId: '',
            addModal: false,
            groupModal: false,
            editModal: false,
            delModel: false,
            batchModel: false,
            del_id: 0,
            selItem: [],
            addLoading: false,
            removeLoading: false,
            batchDelLoading: false,
            sureMassLoading: false,
            tableGroups: [],
            tableUserStatuses: [],
            tableUserRoles: [],
            tableUserTypes:[],
            tableRoleGroup:{},
            tableOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center'
                },
                {
                    title: 'ID',
                    key: 'id',
                    align: 'center',
                    sortable: 'custom',
                },
                {
                    title: this.$t('admin_site'),
                    key: 'site_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('router-link', {
                            attrs: {
                                to: '/site/detail/' + para.row.site.id
                            },
                        }, para.row.site.name);
                    },
                    //filters: [],
                    filterMultiple: false,
                    filterMethod: () => true
                },
                {
                    title: this.$t('admin_nickname'),
                    key: 'nickname',
                    align: 'center',
                    render: (h, para) => {
                        return h('router-link', {
                            attrs: {
                                to: '/user/detail/' + para.row.id + '/record'
                            },
                        }, para.row.nickname);
                    }
                },
                {
                    title: this.$t('user_email'),
                    key: 'email',
                    align: 'center',
                    render: (h, para) => {
                        return h('router-link', {
                            attrs: {
                                to: '/user/detail/' + para.row.id + '/record'
                            },
                        }, para.row.email);
                    }
                },
                {
                    title: this.$t('admin_status'),
                    key: 'status',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {}, this.tableUserStatuses[para.row.status]);
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                {
                    title: this.$t('admin_type'),
                    key: 'type',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {}, this.tableUserTypes[para.row.type]);
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                {
                    title: this.$t('admin_role'),
                    key: 'role_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {}, this.formatRole(para.row.roles));
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                // {
                //     title: this.$t('admin_group'),
                //     key: 'group_id',
                //     align: 'center',
                //     render: (h, para) => {
                //         return h('span', {}, para.row.group.name);
                //     },
                //     filters: [],
                //     filterMultiple: false,
                //     filterMethod: () => true
                // },
                {
                    title: this.$t('admin_created_time'),
                    key: 'created_at',
                    align: 'center',
                    render: (h, para) => {
                        return h('span',
                            util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    },
                    sortable: 'custom',
                },
                {
                    title: this.$t('admin_handle'),
                    align: 'center',
                    width: 200,
                    render: (h, params) => {
                        if (params.row.id === this.$store.state.user.userInfo.id) {
                            return h('div', [
                                h('Button', {
                                        props: {
                                            type: 'primary',
                                            size: 'small'
                                        },
                                        style: {
                                            margin: '5px'
                                        },
                                        on: {
                                            click: () => {
                                                this.$router.push({
                                                    name: 'user-detail',
                                                    params: {
                                                        id: params.row.id,
                                                        tab: 'record'
                                                    }
                                                });
                                            }
                                        }
                                    },
                                    // '查看'
                                    this.$t('admin_check')
                                )
                            ]);
                        } else {
                            return h('div', [
                                h('Button', {
                                        props: {
                                            type: 'primary',
                                            size: 'small'
                                        },
                                        style: {
                                            margin: '5px'
                                        },
                                        on: {
                                            click: () => {
                                                this.$router.push({
                                                    name: 'user-detail',
                                                    params: {
                                                        id: params.row.id,
                                                        tab: 'record'
                                                    }
                                                });
                                            }
                                        }
                                    },
                                    // '查看'
                                    this.$t('admin_check')
                                ),
                                h('Button', {
                                        props: {
                                            type: 'primary',
                                            size: 'small'
                                        },
                                        style: {
                                            margin: '5px'
                                        },
                                        on: {
                                            click: () => {
                                                this.addUserData.id = params.row.id;
                                                
                                                this.editUser();
                                            }
                                        }
                                    },
                                    // '查看'
                                    this.$t('admin_edit')
                                ),
                                h(
                                    'Button',
                                    {
                                        props: {
                                            size: 'small'
                                        },
                                        style: {
                                            margin: '5px'
                                        },
                                        on: {
                                            click: () => {
                                                this.del_id = params.row.id;
                                                this.delModel = true;
                                            }
                                        }
                                    }, this.$t('admin_delete'))
                            ]);
                        }
                    }
                }
            ],
            tableData: [],
            addUserData: {
                id: '',
                avatar: '',
                nickname: '',
                password: '',
                email: '',
                phone: '',
                type: '',
                status: 0,
                roles: [],
                group_id: '',
                site_id: '',
            },
            addUserObject: {
                userStatuses: [],
                userRoles: [],
                userTypes:[],
                roleGroup:{},
                sites:[],
                showSite:false,
                ruleValidate: {
                    nickname: [
                        {required: true, message: this.$t('admin_valide_name'), trigger: 'blur'},
                        { validator: valideName, trigger: 'blur' }
                    ],
                    email: [
                        {required: true, message: this.$t('admin_valide_email'), trigger: 'blur'},
                        {type: 'email', message: this.$t('admin_email_format_error'), trigger: 'blur'}
                    ],
                    password: [
                        {required: false, message: this.$t('admin_password_empty'), trigger: 'blur'}, 
                        {type: 'string', validator: valideNewPassword, trigger: 'blur'}
                    ],
                    phone: [
                        { validator: validatePhone, trigger: 'blur' }
                    ],
                    site_id: [
                        {required: true, type: 'string', message: this.$t('admin_valid_status'), trigger: 'change'}
                    ],
                    status: [
                        {required: true, type: 'integer', message: this.$t('admin_valid_status'), trigger: 'change'}
                    ],
                    type: [
                        {required: true, type: 'string', message: this.$t('admin_valid_type'), trigger: 'change'}
                    ],
                    roles: [
                        {required: true, type: 'array', min: 1, message: this.$t('admin_choose_one_role'), trigger: 'change'}
                    ]
                },
                // 上传图片
                upload_config: {
                    url: api.upload.image,
                    name: 'image',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    }
                },
            }
        };
    },
    computed: {
        uploadData () {
            return {
                access_token: this.$store.state.user.userInfo.accessToken,
                user_import: 'user_import',
                type: 'uploadfile'
            };
        },
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
        this.$store.state.app.userInfoRequest.then(res => {
            let userInfo = res.data.user;
        });
        this.getData();
    },
    methods: {
        importNext () {
            if (this.uploadedTaskFiles.length) {
                this.$router.push({
                    name: 'import-user',
                    query: {
                        path: decodeURIComponent(this.uploadedTaskFiles[0].response.data.urlpath)
                    }
                });
            } else {
                this.$Message.warning(this.$t('user_import_excel_file'));
            }
        },
        handleRemoveFile (file) {
            this.uploadedTaskFiles.splice(file, 1);
        },
        handleUpload () {
            if (this.uploadedTaskFiles.length > 0) {
                this.$Message.warning(this.$t('user_upload_maximum_file'));
                return false;
            }
        },
        handleUploading () {
            this.uploading = true;
        },
        handleFileFormatError (file) {
            this.$Message.warning({
                content: file.name + this.$t('user_malformat'),
                duration: 3
            });
        },
        handleUploadSuccess (response, file, fileList) {
            this.uploadedTaskFiles.push(file);
            this.uploading = false;
        },
        changeType () {
            let type = this.addUserData.type;
            this.addUserObject.userRoles = this.addUserObject.roleGroup[type];

            //console.log('type: '+type)
            //console.log('this.addUserObject.showSite: '+this.addUserObject.showSite)

            //非root, 表单显示租户选择
            let rootTypes = ['3', 3];
            if (rootTypes.indexOf(type) >= 0)
            {
                this.addUserObject.showSite = true;
            }
            else
            {
                this.addUserObject.showSite = false;
            }

            //console.log('this.addUserObject.showSite: '+this.addUserObject.showSite)
            
        },
        formatUrl (url) {
            if (url.indexOf('http') > -1) {
                return url;
            } else {
                return api.staticBase + url;
            }
        },
        CreatehandleSuccessIcon (res, file) {
            file.url = res.data.url;
            this.addUserData.avatar = res.data.url;
        },
        handleMaxSize (file) {
            this.$Notice.warning({
                title: this.$t('admin_upload_filesize_limit'),
                desc: this.$t('admin_upload_filesize_limit_con')
            });
        },
        batchImport () {
            this.batchImportModel = true;
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        getTableData (data) {
            this.tableData = data.list;
            $.each(this.tableData, (k, v) => {
                if (v.id === this.$store.state.user.userInfo.id) {
                    this.$set(this.tableData[k], '_disabled', true);
                }
            });
            this.count = +data.count; // 整数
            let groupMap = [];
            let tableGroups = data.groups;
            for (const key in tableGroups) {
                if (tableGroups.hasOwnProperty(key)) {
                    let group = {
                        label: tableGroups[key].name,
                        value: tableGroups[key].id
                    };
                    groupMap.push(group);
                }
            }
            let roleMap = [];
            this.tableRoles = data.roles;
            Object.keys(this.tableRoles).forEach((v, k) => {
                let role = {
                    label: data.roles[v],
                    value: v
                };
                roleMap.push(role);
            });
            this.changeTagFilter(roleMap, groupMap);
        },
        changeTagFilter (roleMap, groupMap) {
            // 动态调整项目类型过滤器
            let roleIndex = util.getKeyIndexFromTableOption(this.tableOption, 'role_id');
            let groupIndex = util.getKeyIndexFromTableOption(this.tableOption, 'group_id');
            if (groupIndex < 0 || roleIndex < 0) {
                return;
            }
            let roleType = this.tableOption[roleIndex];
            roleType.filters = roleMap;
            let groupType = this.tableOption[groupIndex];
            groupType.filters = groupMap;
            // hack 动态filter
            Vue.nextTick(() => {
                if (this.group_id) {
                    this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_filterChecked', [this.group_id]);
                    this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_isFiltered', true);
                }
                if (this.role_id) {
                    this.$set(this.$refs.userTable.cloneColumns[roleIndex], '_filterChecked', [this.role_id]);
                    this.$set(this.$refs.userTable.cloneColumns[roleIndex], '_isFiltered', true);
                }
            });
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        formatRole (role) {
            let arr = [];
            $.each(role, (k, v) => {
                arr.push(this.tableUserRoles[v.item_name]);
            });
            return arr.toString();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.user.list,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    group_id: this.group_id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                    role_id: this.role_id
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.tableUserTypes = res.data.types;
                        this.tableUserRoles = res.data.roles;
                        this.tableGroups = res.data.groups;
                        this.tableUserStatuses = res.data.statuses;
                        this.getTableData(res.data);
                        this.selItem = [];
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        addUser () {

            this.addUserData = {
                avatar: '',
                nickname: '',
                password: '',
                email: '',
                //phone: res.data.user.phone,
                //type: 0,
                //status: 0,
                //roles: [],
                //group_id: 0
            };

            $.ajax({
                url: api.user.form,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                },
                success: res => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.addUserObject.userStatuses = res.data.statuses;
                        this.addUserObject.userRoles = res.data.roles;
                        this.addUserObject.userTypes = res.data.types;
                        this.addUserObject.roleGroup = res.data.roleGroup;
                        this.addUserObject.sites = res.data.sites;
                        this.addModal = true;

                        //---
                        //获取第一个状态,默认选择
                        let userStatusFirst = 0;
                        for(var k in this.addUserObject.userStatuses){
                            userStatusFirst = k;
                            break;
                        }
                        //默认选择
                        this.addUserData.status = +userStatusFirst;
                        //---

                        //---
                        //获取第一个类型,默认选择,并切换角色
                        let userTypeFirst = 0;
                        for(var k in this.addUserObject.userTypes){
                            userTypeFirst = k;
                            break;
                        }
                        //默认选择
                        this.addUserData.type = userTypeFirst;
                        console.log('this.addUserData.type: ' + this.addUserData.type)

                        //切换角色
                        this.changeType();
                        //---
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        editUser (){

            if (!this.addUserData.id)
            {
                return;
            }

            $.ajax({
                url: api.user.form,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                },
                success: res => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.addUserObject.userStatuses = res.data.statuses;
                        this.addUserObject.userRoles = res.data.roles;
                        this.addUserObject.userTypes = res.data.types;
                        this.addUserObject.roleGroup = res.data.roleGroup;
                        this.addUserObject.sites = res.data.sites;
                        this.addModal = true;

                        //---
                        //获取第一个状态,默认选择
                        let userStatusFirst = 0;
                        for(var k in this.addUserObject.userStatuses){
                            userStatusFirst = k;
                            break;
                        }
                        //默认选择
                        this.addUserData.status = +userStatusFirst;
                        //---

                        //---
                        //获取第一个类型,默认选择,并切换角色
                        let userTypeFirst = '';
                        for(var k in this.addUserObject.userTypes){
                            userTypeFirst = k;
                            break;
                        }
                        //默认选择
                        this.addUserData.type = userTypeFirst;

                        //切换角色
                        this.changeType();
                        //---

                        $.ajax({
                            url: api.user.detail,
                            type: 'post',
                            data: {
                                access_token: this.$store.state.user.userInfo.accessToken,
                                user_id: this.addUserData.id,
                            },
                            success: (res) => {
                                if (res.error) {
                                    this.$Message.warning({
                                        content: res.message,
                                        duration: 3
                                    });
                                } else {

                                    let userRoleArr = [];
                                    $.each(res.data.user.roles, (k, v) => {
                                        userRoleArr.push(v.item_name);
                                    });

                                    this.addUserData = {
                                        id: res.data.user.id,
                                        avatar: res.data.user.avatar,
                                        nickname: res.data.user.nickname,
                                        password: '',
                                        email: res.data.user.email,
                                        //phone: res.data.user.phone,
                                        type: res.data.user.type,
                                        status: +res.data.user.status,
                                        roles: userRoleArr,
                                        group_id: res.data.user.group.id,
                                        site_id: res.data.user.site.id
                                    };

                                    //切换角色
                                    this.changeType();
                                }
                            },
                        });

                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        saveUser () {
            console.log('saveUser:')
            this.$refs.addUserForm.validate((valid) => {
                if (valid) {
                    this.addLoading = true;
                    let url = this.addUserData.id ? api.user.update : api.user.create;
                    let data = {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        user_id: this.addUserData.id,
                        avatar: this.addUserData.avatar,
                        nickname: this.addUserData.nickname,
                        email: this.addUserData.email,
                        password: this.addUserData.password,
                        phone: this.addUserData.phone,
                        status: this.addUserData.status,
                        type: this.addUserData.type,
                        roles: this.addUserData.roles.toString(),
                        group_id: this.addUserData.group_id,
                        site_id: this.addUserData.site_id,
                    };
                    console.log(url)
                    console.log(data)
                    $.ajax({
                        url: url,
                        type: 'post',
                        data: data,
                        success: res => {
                            this.addLoading = false;
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.destroy();
                                this.$Message.success({
                                    content: this.$t('admin_added_successfully'),
                                    duration: 3
                                });
                                
                                this.addUserData = {
                                    avatar: '',
                                    nickname: '',
                                    password: '',
                                    email: '',
                                    phone: '',
                                    status: 0,
                                    type: '',
                                    roles: [],
                                    group_id: ''
                                };

                                //关闭弹框
                                this.addModal = false;

                                //刷新用户列表
                                this.getData();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.addLoading = false;
                            });
                        }
                    });
                }
            });
        },
        remove () {
            this.removeLoading = true;
            $.ajax({
                url: api.user.delete,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.del_id
                },
                success: res => {
                    this.removeLoading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 2
                        });
                        this.delModel = false;
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.removeLoading = false;
                    });
                }
            });
        },
        handleFormatError (file) {
            this.$Notice.warning({
                title: this.$t('admin_upload_file_format_incorrect'),
                desc: this.$t('admin_file_format')
            });
        },
        selChange (selection, row) {
            let arr = [];
            $.each(selection, function (k, v) {
                arr.push(v.id);
            });
            this.selItem = arr;
        },
        batchDel () {
            this.batchDelLoading = true;
            $.ajax({
                url: api.user.delete,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.selItem.toString()
                },
                success: res => {
                    this.batchDelLoading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getData();
                        this.batchModel = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.batchDelLoading = false;
                    });
                }
            });
        },
        sureMass () {
            this.sureMassLoading = true;
            $.ajax({
                url: api.team.moveGroupUser,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    group_id: this.groupId,
                    user_ids: this.selItem.toString(),
                },
                success: res => {
                    this.loading = false;
                    this.sureMassLoading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.groupModal = false;
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = true;
                        this.sureMassLoading = false;
                    });
                }
            });
        }
    },

};
</script>

<style scoped>
.upload-list {
  display: inline-block;
  width: 60px;
  height: 60px;
  text-align: center;
  line-height: 60px;
  border: 1px solid transparent;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  position: relative;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  margin-right: 4px;
}
.upload-list img {
  width: 100%;
  height: 100%;
}
</style>
