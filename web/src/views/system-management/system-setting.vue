<template>
  <div>
    <Row class="margin-bottom-10">
      <i-col
        span="10"
        push="0"
      >
        <Button
          @click="addIdentifyFun"
          type="primary"
        >
          {{$t('system_new_identify')}}
        </Button>
        <!-- <Button @click.native="add" type="primary">
                    新增标识
                </Button> -->
        <!-- <Button @click.native="initRoute" type="primary">
                    刷新路由(只增)
                </Button> -->
      </i-col>
      <i-col span="14">
        <div class="search_input">
          <Input
            v-model="keyword"
            @on-enter="changeKeyword"
            @on-search="changeKeyword"
            :placeholder="$t('system_input_input_indentify')"
            clearable
            search
            :enter-button="true"
          />
        </div>
      </i-col>
    </Row>
    <Row>
      <Table
        size="large"
        
        ref="userTable"
        :columns="tableOption"
        :data="tableData"
        :loading="loading"
        stripe
        show-header
      >
        <!-- 操作 -->
        <template
          slot="action"
          slot-scope="props"
        >
          <div v-if="props.rulesData.can_delete === '0'">
            <span v-if="isEdit && editId == props.id">
              <Button
                type="success"
                size="small"
                @click="operaSave"
              >{{$t('system_save')}}</Button>
              <Button
                size="small"
                @click="operaCancel"
              >{{$t('system_cancel')}}</Button>
            </span>
            <span v-else>
              <Button
                type="primary"
                size="small"
                @click="operaEdit(props.id,props.rulesData)"
              >{{$t('system_edit')}}</Button>
            </span>
          </div>
          <div v-else-if="addIdentify && props.id == ''">
            <Button
              type="success"
              size="small"
              @click="saveAddIndentity"
            >{{$t('system_new')}}</Button>
            <Button
              size="small"
              @click="cancelIndentifyFun"
            >{{$t('system_cancel')}}</Button>
          </div>
          <div v-else>
            <div v-if="isEdit && editId == props.id">
              <Button
                type="success"
                size="small"
                @click="operaSave"
              >{{$t('system_save')}}</Button>
              <Button
                size="small"
                @click="operaCancel"
              >{{$t('system_cancel')}}</Button>
            </div>
            <div v-else>
              <Button
                type="primary"
                size="small"
                @click="operaEdit(props.id,props.rulesData)"
              >{{$t('system_edit')}}</Button>
              <Button
                size="small"
                @click="operaDeleteModel(props.id)"
              >{{$t('system_delete')}}</Button>
            </div>
          </div>
        </template>
      </Table>
      <div style="margin: 10px;overflow: hidden">
        <div style="float: right;">
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
            @on-page-size-change="changePageSize"
          ></Page>
        </div>
      </div>
    </Row>
    <Modal v-model="addModal">
      <p
        slot="header"
        style="text-align:center"
      >
        <Icon type="information-circled"></Icon>
        <span>{{$t('system_new_info')}}</span>
      </p>
      <div style="text-align:left">
        <Form
          ref="formValidate"
          :model="addData"
          :label-width="100"
          :rules="ruleValidate"
        >
          <Form-item
            :label="$t('system_setting_form_name')"
            prop="name"
          >
            <Row>
              <i-col span="20">
                <Input
                  :placeholder="$t('system_setting_form_input_name')"
                  v-model="addData.name"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_key')"
            prop="key"
          >
            <Row>
              <i-col span="20">
                <Input
                  v-model="addData.key"
                  :placeholder="$t('system_setting_form_input_key')"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_value')"
            prop="value"
          >
            <Row>
              <i-col span="20">
                <Input
                  v-model="addData.value"
                  :placeholder="$t('system_setting_form_input_value')"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_can_delete')"
            prop="can_delete"
          >
            <Row>
              <i-col span="20">
                <Radio-group v-model="addData.can_delete">
                  <Radio
                    label="1"
                    value="1"
                  >{{$t('system_setting_form_yes')}}</Radio>
                  <Radio
                    label="0"
                    value="0"
                  >{{$t('system_setting_form_no')}}</Radio>
                </Radio-group>
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_desc')"
            prop="desc"
          >
            <Row>
              <i-col span="20">
                <Input
                  :placeholder="$t('system_setting_form_input_desc')"
                  type="textarea"
                  v-model="addData.desc"
                />
              </i-col>
            </Row>
          </Form-item>
        </Form>
      </div>
      <div slot="footer">
        <Button
          type="success"
          size="large"
          long
          @click.native="addBatch"
        >
          {{$t('system_setting_form_add')}}
        </Button>
      </div>
    </Modal>
    <Modal v-model="editModal">
      <p
        slot="header"
        style="text-align:center"
      >
        <Icon type="information-circled"></Icon>
        <span>{{$t('system_setting_form_edit')}}</span>
      </p>
      <div style="text-align:left">
        <Form
          ref="formValidate2"
          :model="addData"
          :label-width="100"
          :rules="ruleValidate"
        >
          <Form-item
            :label="$t('system_setting_form_name')"
            prop="name"
          >
            <Row>
              <i-col span="20">
                <Input
                  :placeholder="$t('system_setting_form_input_name')"
                  v-model="addData.name"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_key')"
            prop="key"
          >
            <Row>
              <i-col span="20">
                <Input
                  v-model="addData.key"
                  :placeholder="$t('system_setting_form_input_key')"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_value')"
            prop="value"
          >
            <Row>
              <i-col span="20">
                <Input
                  v-model="addData.value"
                  :placeholder="$t('system_setting_form_input_value')"
                />
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_can_delete')"
            prop="can_delete"
          >
            <Row>
              <i-col span="20">
                <Radio-group v-model="addData.can_delete">
                  <Radio
                    label="1"
                    value="1"
                  >{{$t('system_setting_form_yes')}}</Radio>
                  <Radio
                    label="0"
                    value="0"
                  >{{$t('system_setting_form_no')}}</Radio>
                </Radio-group>
              </i-col>
            </Row>
          </Form-item>
          <Form-item
            :label="$t('system_setting_form_desc')"
            prop="desc"
          >
            <Row>
              <i-col span="20">
                <Input
                  :placeholder="$t('system_setting_form_input_desc')"
                  type="textarea"
                  v-model="addData.desc"
                />
              </i-col>
            </Row>
          </Form-item>
        </Form>
      </div>
      <div slot="footer">
        <Button
          type="success"
          size="large"
          long
          @click.native="saveBatch"
        >
          {{$t('system_setting_form_save')}}
        </Button>
      </div>
    </Modal>
    <Modal
      v-model="delModel"
      :title="$t('system_operation_tip')"
    >
      <p>{{$t('system_sure_delete')}}</p>
      <div slot="footer">
        <Button
          type="text"
          @click="delModel = false"
        >{{$t('system_cancel')}}</Button>
        <Button
          type="error"
          @click="operaDelete"
        >{{$t('system_delete')}}</Button>
      </div>
    </Modal>
  </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
import Vue from 'vue';
export default {
    name: 'system-setting-index',
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: 'desc',
            delId: -1,
            addModal: false,
            delModel: false,
            editModal: false,
            currId: -1,
            addData: {
                key: '',
                name: '',
                value: '',
                can_delete: '1',
                desc: '',
                value_type: ''
            },
            isEdit: false,
            editId: '',
            deleteId: '',
            rulesData: {
                key: '',
                name: '',
                value: '',
                can_delete: '',
                desc: '',
                value_type: ''
            },
            value_types: {},
            addIdentify: false,
            cancelIndentify: false,
            addIsEdit: false,
            MenuText: this.$t('system_select_type'),
            cusBool: true,
            tableOption: [
                {
                    title: 'ID',
                    key: 'id',
                    width: 80,
                    align: 'center'
                },
                {
                    title: this.$t('system_name'),
                    key: 'name',
                    render: (h, params) => {
                        if (this.isEdit && this.editId === params.row.id) {
                            return h('Input', {
                                props: {
                                    value: params.row.name,
                                    placeholder: this.$t('system_input_indentify_name'),
                                    autofocus: true
                                },
                                'class': 'inputName',
                                on: {
                                    'on-blur': (event) => {
                                        this.rulesData.name = event.target.value;
                                    }
                                },
                            });
                        } else {
                            return h('div', params.row.name);
                        }
                    }
                },
                {
                    title: this.$t('system_indentify'),
                    key: 'key',
                    render: (h, params) => {
                        if (this.isEdit && this.editId === params.row.id) {
                            return h('Input', {
                                props: {
                                    value: params.row.key,
                                    placeholder: this.$t('system_input_indentify'),
                                },
                                on: {
                                    'on-blur': (event) => {
                                        this.rulesData.key = event.target.value;
                                    }
                                },
                            });
                        } else {
                            return h('div', params.row.key);
                        }
                    }

                },
                // {
                //     title: '值的类型',
                //     key: 'value_type',
                //     render: (h, params) => {
                //         if (this.isEdit && this.editId === params.row.id) {
                //             return h('Select', {
                //                 props: {
                //                     value: this.rulesData.value_type,
                //                     placeholder: '请选择值的类型'
                //                 },
                //                 on: {
                //                     'on-change': (event) => {
                //                         this.rulesData.value_type = event;
                //                         this.rulesData.value = '';
                //                         if (event === '0') {
                //                             if (params.row.value_type == '0') {
                //                                 this.rulesData.value = params.row.value;
                //                             } else {
                //                                 this.rulesData.value = 'true';
                //                             }
                //                         } else if (event === '1') {
                //                             if (params.row.value_type == '1') {
                //                                 this.rulesData.value = params.row.value;
                //                             } else {
                //                                 this.rulesData.value = 1;
                //                             }
                //                         } else if (event === '2') {
                //                             if (params.row.value_type == '2') {
                //                                 this.rulesData.value = params.row.value;
                //                             } else {
                //                                 this.rulesData.value = '';
                //                             }
                //                         }
                //                     }
                //                 },
                //                 style: {
                //                     width: '125px'
                //                 }
                //             }, [
                //                 h('Option', {props: {value: '0'}}, '布尔类型'),
                //                 h('Option', {props: {value: '1'}}, '数字类型'),
                //                 h('Option', {props: {value: '2'}}, '字符串'),
                //             ]);
                //         } else {
                //             return h('div', this.value_types[+params.row.value_type]);
                //         }
                //     }
                // },
                {
                    title: this.$t('system_value_type'),
                    key: 'value_type',
                    render: (h, params) => {
                        if (this.isEdit && this.editId === params.row.id) {
                            return h('Select', {
                                props: {
                                    value: this.rulesData.value_type,
                                    placeholder: this.$t('system_select_value_type'),
                                    transfer: true
                                },
                                on: {
                                    'on-change': (event) => {
                                        this.rulesData.value_type = event;
                                        this.rulesData.value = '';
                                        if (event === '0') {
                                            if (params.row.value_type == '0') {
                                                this.rulesData.value = params.row.value;
                                            } else {
                                                this.rulesData.value = 'true';
                                            }
                                        } else if (event === '1') {
                                            if (params.row.value_type == '1') {
                                                this.rulesData.value = params.row.value;
                                            } else {
                                                this.rulesData.value = 1;
                                            }
                                        } else if (event === '2') {
                                            if (params.row.value_type == '2') {
                                                this.rulesData.value = params.row.value;
                                            } else {
                                                this.rulesData.value = '';
                                            }
                                        }
                                    }
                                },
                                style: {
                                    width: '125px'
                                }
                            }, Object.keys(this.value_types).map((key) => {
                                return h('Option', { props: { value: key } }, this.value_types[key]);
                            }));
                        } else {
                            return h('div', this.value_types[params.row.value_type]);
                        }
                    }
                },
                {
                    title: this.$t('system_value'),
                    key: 'value',
                    align: 'center',
                    render: (h, params) => {
                        if (this.isEdit && this.editId === params.row.id) {
                            if (this.rulesData.value_type === '0') {
                                return h('i-switch', {
                                    props: {
                                        value: this.rulesData.value,
                                        size: 'large',
                                        trueValue: '1',
                                        falseValue: '0',
                                    },
                                    on: {
                                        'on-change': (event) => {
                                            this.rulesData.value = event;
                                        }
                                    }
                                }, [
                                    h('span', { slot: 'open' }, 'true'),
                                    h('span', { slot: 'close' }, 'false'),
                                ]);
                            } else if (this.rulesData.value_type === '1') {
                                return h('InputNumber', {
                                    props: {
                                        value: Number(this.rulesData.value),
                                    },
                                    on: {
                                        'on-change': (event) => {
                                            this.rulesData.value = event;
                                        }
                                    },
                                });
                            } else if (this.rulesData.value_type === '2') {
                                return h('Input', {
                                    props: {
                                        value: this.rulesData.value,
                                    },
                                    on: {
                                        'on-change': (event) => {
                                            this.rulesData.value = event.target.value;
                                        }
                                    },
                                });
                            }
                        } else {
                            return h('div', params.row.value);
                        }
                    }
                },
                // {
                //     title: '是否可删除',
                //     key: 'can_delete',
                //     align: 'center',
                //     render: (h, params) => {
                //         if (this.isEdit && this.editId === params.row.id) {
                //             return h('i-switch', {
                //                 props: {
                //                     value: Boolean(this.rulesData.can_delete) || true,
                //                     size: 'large',
                //                     trueValue: true,
                //                     falseValue: false,
                //                 },
                //                 on: {
                //                     'on-change': (event) => {
                //                         this.rulesData.value = event;
                //                     }
                //                 }
                //             }, [
                //                 h('span', {slot: 'open'}, 'true'),
                //                 h('span', {slot: 'close'}, 'false'),
                //             ]);
                //         } else {
                //             return h('div', this.strShow(params.row.can_delete));
                //         }
                //     }
                // },
                // {
                //     title: '描述',
                //     key: 'desc',
                //     align: 'center',
                //     render: (h, params) => {
                //         if (this.isEdit && this.editId === params.row.id) {
                //             return h('Input', {
                //                 props: {
                //                     value: params.row.desc,
                //                 },
                //                 on: {
                //                     'on-blur': (event) => {
                //                         this.rulesData.desc = event.target.value;
                //                     }
                //                 },
                //             });
                //         } else {
                //             return h('div', params.row.desc);
                //         }
                //     }
                // },
                {
                    key: 'action',
                    title: this.$t('system_handle'),
                    align: 'center',
                    render: (h, params) => {
                        return h('div', this.$refs.userTable.$scopedSlots.action({
                            rulesData: {
                                key: params.row.key,
                                name: params.row.name,
                                value: params.row.value,
                                can_delete: params.row.can_delete,
                                desc: params.row.desc,
                                value_type: params.row.value_type,
                            },
                            id: params.row.id,
                        }));
                    }
                }
                // {
                //     title: '操作',
                //     align: 'center',
                //     render: (h, params) => {
                //         if (params.row.can_delete == '0') {
                //             return h('div', [
                //                 h('Button', {
                //                     props: {
                //                         type: 'primary',
                //                         size: 'small'
                //                     },
                //                     style: {
                //                         margin: '5px'
                //                     },
                //                     nativeOn: {
                //                         click: () => {
                //                             let data = params.row;
                //                             this.currId = params.row.id;
                //                             this.addData = {
                //                                 key: data.key,
                //                                 name: data.name,
                //                                 value: data.value,
                //                                 can_delete: data.can_delete,
                //                                 desc: data.desc,
                //                             };
                //                             this.editModal = true;
                //                         }
                //                     }
                //                 }, '编辑')
                //             ]);
                //         } else {
                //             return h('div', [
                //                 h('Button', {
                //                     props: {
                //                         type: 'primary',
                //                         size: 'small'
                //                     },
                //                     style: {
                //                         margin: '5px'
                //                     },
                //                     nativeOn: {
                //                         click: () => {
                //                             let data = params.row;
                //                             this.currId = params.row.id;
                //                             this.addData = {
                //                                 key: data.key,
                //                                 name: data.name,
                //                                 value: data.value,
                //                                 can_delete: data.can_delete,
                //                                 desc: data.desc,
                //                             };
                //                             this.editModal = true;
                //                         }
                //                     }
                //                 }, '编辑'),
                //                 h('Button', {
                //                     props: {
                //                         size: 'small'
                //                     },
                //                     style: {
                //                         margin: '5px'
                //                     },
                //                     nativeOn: {
                //                         click: () => {
                //                             this.delId = params.row.id;
                //                             this.delModel = true;
                //                         }
                //                     }
                //                 }, '删除'),
                //             ]);
                //         }
                //     }
                // }
            ],
            tableData: [],
            ruleValidate: {
                key: [
                    { required: true, message: this.$t('system_indentify_null'), trigger: 'blur' }
                ],
                name: [
                    { required: true, message: this.$t('system_name_not_empty'), trigger: 'blur' }
                ],
                value: [
                    { required: true, message: this.$t('system_value_not_empty'), trigger: 'blur' }
                ],
                can_delete: [
                    { required: true, message: this.$t('system_select_necessity'), trigger: 'change' }
                ],
                desc: [
                    { required: true, message: this.$t('system_description_not_empty'), trigger: 'blur' }
                ]
            }
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        },
        '$route.query' () {
            this.getData();
        },
    },
    mounted () {
        this.getData();
    },
    methods: {
        customVerify () {
            if (this.rulesData.key == '') {
                this.$Message.warning({
                    content: this.$t('system_indentify_null'),
                    duration: 3
                });
                this.cusBool = false;
            } else if (this.rulesData.name == '') {
                this.$Message.warning({
                    content: this.$t('system_name_not_empty'),
                    duration: 3
                });
                this.cusBool = false;
            } else if (this.rulesData.value_type === '2' && this.rulesData.value == '') {
                this.$Message.warning({
                    content: this.$t('system_value_not_null'),
                    duration: 3
                });
                this.cusBool = false;
            } else {
                this.cusBool = true;
            }
        },
        resetRulesData () {
            this.rulesData = {
                key: '',
                name: '',
                value: '',
                can_delete: '',
                desc: '',
                value_type: ''
            };
            this.addIdentify = false;
            this.isEdit = false;
            this.editId = '';
            this.deleteId = '';
        },
        addIdentifyFun () {
            if (this.tableData.length > 0 && this.tableData[0]['id'] == '') {
                this.$Message.warning({
                    content: this.$t('system_complete_newly_added_identity'),
                    duration: 3
                });
            } else {
                this.resetRulesData();
                this.tableData.unshift({
                    can_delete: '',
                    desc: '',
                    id: '',
                    key: '',
                    name: '',
                    status: '',
                    value: '',
                    value_type: ''
                });
                this.editId = '';
                this.addIdentify = true;
                this.isEdit = true;
                Vue.nextTick(() => {
                    $('.inputName input').focus();
                });
            }
        },
        saveAddIndentity () {
            this.customVerify();
            if (this.cusBool) {
                $.ajax({
                    url: api.setting.create,
                    type: 'post',
                    data: {
                        access_token: this.gmixin_accessToken,
                        key: this.rulesData.key,
                        name: this.rulesData.name,
                        value: this.rulesData.value,
                        can_delete: '1',
                        desc: '',
                        value_type: this.rulesData.value_type
                    },
                    success: (res) => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.info(this.$t('system_new_success'));
                            // this.addIdentify = false;
                            this.keyword = '';
                            this.page = 1;
                            this.getData();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            }
        },
        cancelIndentifyFun () {
            if (this.tableData[0]['id'] == '') {
                this.tableData.shift();
            }
            this.cancelIndentify = true;
        },
        operaEdit (id, rulesData) {
            if (this.tableData[0]['id'] == '') {
                this.$Message.warning({
                    content: this.$t('system_complete_newly_added_identity'),
                    duration: 3
                });
            } else {
                this.cancelIndentifyFun();
                this.resetRulesData();
                this.isEdit = true;
                this.editId = id;
                this.rulesData = rulesData;
            }
        },
        operaSave () {
            this.customVerify();
            if (this.cusBool) {
                $.ajax({
                    url: api.setting.update,
                    type: 'post',
                    data: {
                        access_token: this.gmixin_accessToken,
                        id: this.editId,
                        key: this.rulesData.key,
                        name: this.rulesData.name,
                        value: this.rulesData.value,
                        can_delete: '1',
                        desc: this.rulesData.desc,
                        value_type: this.rulesData.value_type
                    },
                    success: (res) => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.info(this.$t('system_update_success'));
                            this.isEdit = false;
                            this.getData();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            }
        },
        operaCancel () {
            this.resetRulesData();
        },
        operaDeleteModel (id) {
            if (this.tableData[0]['id'] == '') {
                this.$Message.error({
                    content: this.$t('system_complete_newly_added_identity'),
                    duration: 3
                });
            } else {
                this.resetRulesData();
                this.deleteId = id;
                this.delModel = true;
            }
        },
        operaDelete () {
            $.ajax({
                url: api.setting.delete,
                type: 'post',
                data: {
                    access_token: this.gmixin_accessToken,
                    id: this.deleteId,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.delModel = false;
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
        getTableData (data) {
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.setting.list,
                type: 'post',
                data: {
                    access_token: this.gmixin_accessToken,
                    keyword: this.keyword.trim(),
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
                        this.getTableData(res.data);
                        this.value_types = res.data.value_types;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        add () {
            this.addData = {
                key: '',
                name: '',
                value: '',
                can_delete: '1',
                desc: '',
                value_type: ''
            };
            this.addModal = true;
        },
        saveBatch () {
            this.$refs.formValidate2.validate((valid) => {
                if (valid) {
                    $.ajax({
                        url: api.setting.update,
                        type: 'post',
                        data: {
                            access_token: this.gmixin_accessToken,
                            id: this.currId,
                            key: this.addData.key,
                            name: this.addData.name,
                            value: this.addData.value,
                            can_delete: this.addData.can_delete,
                            desc: this.addData.desc,
                        },
                        success: (res) => {
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.info(this.$t('system_update_success'));
                                this.editModal = false;
                                this.getData();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText);
                        }
                    });
                }
            });
        },
        addBatch () {
            this.$refs.formValidate.validate((valid) => {
                if (valid) {
                    $.ajax({
                        url: api.setting.create,
                        type: 'post',
                        data: {
                            access_token: this.gmixin_accessToken,
                            key: this.addData.key,
                            name: this.addData.name,
                            value: this.addData.value,
                            can_delete: this.addData.can_delete,
                            desc: this.addData.desc,
                            value_type: this.addData.value_type
                        },
                        success: (res) => {
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.info(this.$t('system_new_success'));
                                this.addModal = false;
                                this.getData();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText);
                        }
                    });
                }
            });
        },
        remove () {
            $.ajax({
                url: api.setting.delete,
                type: 'post',
                data: {
                    access_token: this.gmixin_accessToken,
                    id: this.delId,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.delModel = false;
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        initRoute () {
            this.$router.push({
                name: 'permission-assign'
            });
        },
        strShow (str) {
            let showStr = '';
            if (str === '0') {
                showStr = this.$t('system_no');
            } else if (str === '1') {
                showStr = this.$t('system_is');
            }
            return showStr;
        },
        reg (str) {
            let reg = new RegExp('"', 'g');
            let endStr = str.replace(reg, '');
            return endStr;
        }
    }
};
</script>

