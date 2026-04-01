<template>
    <div>
        <Row class="margin-bottom-10">
            <Button @click="saveSetting" type="primary" style="float: right" :loading="importLoading">
                {{$t('user_sure_import')}}
            </Button>
        </Row>
        <Row class=" searchable-table-con1">
            <Table
                    size="large"
                    ref="userTable"
                    :columns="importOption"
                    :data="importData"
                    :loading="loading"
                    stripe
                    show-header
                    @on-selection-change="selChange"
            ></Table>
            <!-- <div style="margin: 10px;overflow: hidden">
                <div style="float: right;">
                    <Page
                            :total="count"
                            :current="page"
                            :page-size ="limit"
                            :page-size-opts="[10,15,20,25,30,50]"
                            show-total
                            show-elevator
                            show-sizer
                            transfer
                            placement = "top"
                            @on-change="changePage"
                            @on-page-size-change = "changePageSize"
                    ></Page>
                </div>
            </div> -->
        </Row>
        <Modal v-model="editModal" width="550">
            <p slot="header" style="text-align:center">
                <Icon type="ios-information-circle"></Icon>
                <span>{{$t('user_edit_account_information')}}</span>
            </p>
            <div style="text-align:left">
                <Form ref="formValidate" :model="currData" :label-width="80" :rules="ruleValidate" @submit.native.prevent>
                    <Row>
                        <i-col span="24">
                            <Form-item :label="$t('user_name')" prop="nickname">
                                <Row>
                                    <i-col span="20">
                                        <Input v-model="currData.nickname" :placeholder="$t('user_input_user_name')" icon="md-person"/>
                                    </i-col>
                                </Row>
                            </Form-item>
                            <Form-item :label="$t('user_password')" prop="password">
                                <Row>
                                    <i-col span="20">
                                        <Input v-model="currData.password" :placeholder="$t('user_input_password')" icon="md-lock"/>
                                     </i-col>
                                </Row>
                            </Form-item>
                            <Form-item :label="$t('user_email')" prop="email">
                                <Row>
                                    <i-col span="20">
                                        <Input v-model="currData.email" :placeholder="$t('user_input_email')" icon="ios-mail"/>
                                    </i-col>
                                </Row>
                            </Form-item>
                            <Form-item :label="$t('admin_contact_number')" prop="phone">
                                <Row>
                                    <i-col span="20">
                                        <Input v-model="currData.phone" :placeholder="$t('admin_enter_phone')" icon="ios-call"/>
                                    </i-col>
                                </Row>
                            </Form-item>
                            <Form-item :label="$t('user_role')" prop="role">
                                <Checkbox-group v-model="currData.role">
                                    <Checkbox v-for="(role,index) in roles" :label="index" :key="index"> {{role}}</Checkbox>
                                </Checkbox-group>
                            </Form-item>
                        </i-col>
                    </Row>
                </Form>
            </div>
            <div slot="footer">
                <Button type="success" size="large" long @click.native="saveBatch">
                    {{$t('user_save')}}
                </Button>
            </div>
        </Modal>
    </div>
</template>
<script>
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
            if(!value) {
                callback()
            }
            if (!re.test(value)) {
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
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            editModal: false,
            importLoading: false,
            selItem: [],
            users: [],
            roles: [],
            statuses: [],
            currIndex: -1,
            currData: {
                nickname: '',
                password: '',
                email: '',
                phone: '',
                role: [],
            },
            successCount: 0,
            errorCount: 0,
            importOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center',
                },
                {
                    title: this.$t('user_name'),
                    key: 'nickname',
                    align: 'center',
                    render: (h, para) => {
                        if (para.row.success) {
                            return h('div', {
                                style: {
                                    position: 'relative',
                                }
                            }, [
                                h('Icon', {
                                    props: {
                                        type: 'md-checkmark'
                                    },
                                    style: {
                                        color: 'green',
                                    }
                                }),
                                h('span', {
                                    style: {
                                        marginLeft: '10px'
                                    }
                                }, para.row.nickname),
                            ]);
                        } else if (para.row.error) {
                            return h('div', [
                                h('div', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: 'hover',
                                            title: this.$t('user_import_failed_reason'),
                                            content: '',
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', para.row.error);
                                            }
                                        }
                                    }, [
                                        h('Icon', {
                                            props: {
                                                type: 'md-close'
                                            },
                                            style: {
                                                color: 'red',
                                            }
                                        }),
                                        h('span', {
                                            style: {
                                                marginLeft: '10px'
                                            }
                                        }, para.row.nickname),
                                        h('Icon', {
                                            style: {
                                                marginLeft: '6px',
                                                verticalAlign: 'top',
                                                color: 'red'
                                            },
                                            props: {
                                                type: 'ios-help-circle-outline',
                                                size: 18
                                            },
                                        }),

                                    ])
                                ]),
                            ]);
                        } else {
                            return h('div', [
                                h('span', para.row.nickname)
                            ]);
                        }
                    }
                },
                {
                    title: this.$t('user_password'),
                    key: 'password',
                    align: 'center',
                },
                {
                    title: this.$t('user_email'),
                    key: 'email',
                    align: 'center',
                },
                {
                    title: this.$t('user_phone'),
                    key: 'phone',
                    align: 'center',
                },
                {
                    title: this.$t('user_role'),
                    key: 'role',
                    align: 'center',
                    render: (h, params) => {
                        return h('span', {
                            domProps: {
                                innerHTML: this.smarty(params.row.role)
                            }
                        });
                    }
                },
                {
                    title: this.$t('admin_handle'),
                    align: 'center',
                    render: (h, params) => {
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
                                        this.currIndex = params.index;
                                        this.edit(this.importData[this.currIndex]);
                                    }
                                }
                            }, this.$t('user_edit')),
                            h('Button', {
                                props: {
                                    size: 'small'
                                },
                                style: {
                                    margin: '5px'
                                },
                                on: {
                                    click: () => {
                                        let index = params.index;
                                        if (this.selItem.indexOf(index) > -1) {
                                            this.selItem.splice(this.selItem.indexOf(index), 1);
                                        }
                                        this.importData.splice(index, 1);
                                    }
                                }
                            }, this.$t('user_delete')),
                        ]);
                    }
                }
            ],
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
                    {type: 'string', validator: valideNewPassword, trigger: 'blur'}
                ],
                phone: [
                    { validator: validatePhone, trigger: 'blur' }
                ],
                roles: [
                    {required: true, type: 'array', min: 1, message: this.$t('admin_choose_one_role'), trigger: 'change'}
                ],
            },
            importData: [],
        };
    },
    mounted () {
        this.getUserForm();
        this.getData();
    },
    methods: {
        getData () {
            $.ajax({
                url: api.user.userImport,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    url: this.$route.query.path
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.importData = res.data.list;
                        $.each(res.data.list, (k, v) => {
                            this.users.push(v.email);
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        getUserForm () {
            $.ajax({
                url: api.user.form,
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
                        this.statuses = {
                            ...res.data.statuses
                        };
                        this.roles = res.data.roles;
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
        selChange (selection, row) {
            let arr = [];
            $.each(selection, (k, v) => {
                arr.push(this.users.indexOf(v.email));
            });
            this.selItem = arr;
        },
        edit (row) {
            this.currData.nickname = row.nickname;
            this.currData.password = row.password;
            this.currData.email = row.email;
            this.currData.phone = row.phone;
            this.currData.role = row.role ? row.role : [];
            this.editModal = true;
        },
        saveBatch () {
            this.$refs.formValidate.validate((valid) => {
                if (valid) {
                    this.$set(this.importData[this.currIndex], 'nickname', this.currData.nickname);
                    this.$set(this.importData[this.currIndex], 'password', this.currData.password);
                    this.$set(this.importData[this.currIndex], 'email', this.currData.email);
                    this.$set(this.importData[this.currIndex], 'phone', this.currData.phone);
                    this.$set(this.importData[this.currIndex], 'role', this.currData.role);
                    this.editModal = false;
                }
            });
        },
        smarty (roles) {
            if (!roles || !roles.length) {
                return '';
            } else {
                let arrStr = '';
                roles.forEach((k, v) => {
                    arrStr += this.roles[k] + '<br/>';
                });
                return arrStr;
            }
        },
        saveSetting () {
            let lock = true;
            $.each(this.importData, (k, v) => {
                if (!v.nickname) {
                    lock = false;
                } else if (!v.password) {
                    lock = false;
                } else if (!v.email) {
                    lock = false;
                } else if (!v.role.length) {
                    lock = false;
                }
            });
            if (!lock) {
                this.$Message.warning({
                    content: this.$t('user_complete_each_user_information'),
                    duration: 2
                });
                return false;
            } else {
                this.successCount = 0;
                this.errorCount = 0;
                this.importLoading = true;
                this.importData.forEach((v, index) => {
                    setTimeout(() => {
                        this.saveUser(v, index);
                    }, 30 * index);
                });
            }
        },
        saveUser (v, index) {
            $.ajax({
                url: api.user.userImportSubmit,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    nickname: v.nickname,
                    email: v.email,
                    phone: v.phone,
                    password: v.password,
                    roles: v.role.toString(),
                },
                success: res => {
                    if (res.error) {
                        this.errorCount++;
                        this.$set(this.importData[index], 'error', res.message);
                        this.$set(this.importData[index], 'success', '');
                    } else {
                        this.successCount++;
                        this.$set(this.importData[index], 'error', '');
                        this.$set(this.importData[index], 'success', 'success');
                    };
                    setTimeout(() => {
                        if (index == this.importData.length - 1) {
                            this.$Notice.success({
                                title: this.$t('user_import_result'),
                                // desc: '您已导入成功' + this.successCount + '条数据，导入失败' + this.errorCount + '条数据',
                                desc: this.$t('user_import_data_description', {successCount: this.successCount, errorCount: this.errorCount}),
                                duration: 3,
                            });
                            this.importLoading = false;
                        }
                    }, 1000);
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        }
    }
};
</script>

