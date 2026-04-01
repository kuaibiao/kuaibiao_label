<template>
    <div>
        <Tabs value="password" v-if="!isValidate">
            <TabPane :label="$t('user_login_pwd_val')" name="password">
                <Form ref="validatePasswordForm" :model="validatePasswordForm" :label-width="100" label-position="right" :rules="validatePasswordFormValid" @submit.native.prevent>
                    <FormItem :label="$t('user_account')+'：'" style="width: 400px">
                        <span style="font-size: 14px">{{userForm.id}}</span>
                    </FormItem>
                    <FormItem :label="$t('user_login_password')+'：'" style="width: 400px" prop="password">
                        <Input type="password" v-model="validatePasswordForm.password" :placeholder="$t('user_enter_login_pwd')"/>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="editMobileValidatePasswordNext" :loading="editEmailValidatePasswordLoading">下一步</Button>
                    </FormItem>
                </Form>
            </TabPane>
            <TabPane v-if="userForm.email" :label="$t('user_email_val')" name="email">
                <Form ref="validateEmailForm" :model="validateEmailForm" :label-width="100" label-position="right" :rules="validateEmailFormValid" @submit.native.prevent>
                    <FormItem :label="$t('user_email')+'：'" style="width: 400px">
                        <span style="font-size: 14px">{{userForm.email}}</span>
                    </FormItem>
                    <FormItem :label="$t('user_verification_code')+'：'" style="width: 400px" prop="mobileCode">
                        <Row>
                            <Col span="16">
                                <Input size="large" v-model="validateEmailForm.mobileCode" :placeholder="$t('user_enter_verification_code')"></Input>
                            </Col>
                            <Col span="7" offset="1">
                                <Button type="primary" @click="validEmailGetCode" :disabled="isEmailGetCodeDisabled" :type="isEmailGetCodeDisabled? 'default' : 'primary'">{{ btnEmailGetCodeText }}</Button>
                            </Col>
                        </Row>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="editMobileValidateEmailNext" :loading="editMobileValidateEmailLoading">{{$t('user_next')}}</Button>
                    </FormItem>
                </Form>
            </TabPane>
            <TabPane v-if="userForm.mobile" :label="$t('user_phone_val')" name="mobile">
                <Form ref="validateMobileForm" :model="validateMobileForm" :label-width="100" label-position="right" :rules="validateMobileFormValid" @submit.native.prevent>
                    <FormItem :label="$t('user_mobile') + '：'" style="width: 400px">
                        <span style="font-size: 14px">{{userForm.mobile}}</span>
                    </FormItem>
                    <FormItem :label="$t('user_verification_code')+'：'" style="width: 400px" prop="mobileCode">
                        <Row>
                            <Col span="16">
                                <Input size="large" v-model="validateMobileForm.mobileCode" :placeholder="$t('user_enter_verification_code')"></Input>
                            </Col>
                            <Col span="7" offset="1">
                                <Button type="primary" @click="validMobileGetCode" :disabled="isMobileGetCodeDisabled" :type="isMobileGetCodeDisabled? 'default' : 'primary'">{{ btnMobileGetCodeText }}</Button>
                            </Col>
                        </Row>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="editMobileValidateMobileNext" :loading="editMobileValidateMobileLoading">{{$t('user_next')}}</Button>
                    </FormItem>
                </Form>
            </TabPane>
        </Tabs>
        <div style="margin-top: 10px">
            <Form 
                v-if="isValidate" 
                ref="updateMobileForm" 
                :model="updateMobileForm" 
                :label-width="100" 
                label-position="right" 
                :rules="updateMobileFormValid"
                @submit.native.prevent>
                    <FormItem :label="$t('user_new_phone')+'：'" style="width: 400px;margin-top：40px" prop="mobile">
                        <Input v-model="updateMobileForm.mobile" :placeholder="$t('user_enter_new_phone')"/>
                    </FormItem>
                    <FormItem :label="$t('user_verification_code')+'：'" style="width: 400px">
                        <Row>
                            <Col span="16">
                                <Input size="large" v-model="updateMobileForm.mobileCode" :placeholder="$t('user_enter_verification_code')"></Input>
                            </Col>
                            <Col span="7" offset="1">
                                <Button type="primary" @click="newEmailGetCode" :disabled="isNewDisabled" :type="isNewDisabled? 'default' : 'primary'">{{ btnNewText }}</Button>
                            </Col>
                        </Row>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="updateMobileFormSave" :loading="updateMobileFormLoading">{{$t('user_save')}}</Button>
                    </FormItem>
            </Form>
        </div>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util.js';
export default {
    props: {
        userForm: {
            type: Object,
            required: true
        },
        isValidate: {
            type: Boolean,
            required: true
        },
        loadData: {
            type: Boolean
        },
    },
    data () {
        const valideMobile = (rule, value, callback) => {
            var re = /^1[3456789]{1}\d{9}$/;
            if (!re.test(value)) {
                callback(new Error(this.$t('user_incorrect_phone_format')));
            } else {
                callback();
            }
        };
        const validemobileCode = (rule, value, callback = function () {
        }) => {
            var re = /^\d{6}$/;
            if (!value) {
                callback(new Error(this.$t('user_enter_verification_code')));
            } else if (!re.test(value)) {
                callback(new Error(this.$t('user_6_num_code')));
            } else {
                callback();
            }
        };
        return {
            isEmailGetCodeDisabled: false,
            isMobileGetCodeDisabled: false,
            isNewDisabled: false,
            updateMobileFormLoading: false,
            editMobileValidateEmailLoading: false,
            editMobileValidateMobileLoading: false,
            editEmailValidatePasswordLoading: false,
            staticBase: api.staticBase,
            editMobileKey: '',
            btnNewText: this.$t('user_get_verification_code'),
            btnEmailGetCodeText: this.$t('user_get_verification_code'),
            btnMobileGetCodeText: this.$t('user_get_verification_code'),
            editPasswordForm: {
                oldPass: '',
                newPass: '',
                rePass: ''
            },
            validatePasswordForm: {
                password: ''
            },
            validateEmailForm: {
                mobileCode: ''
            },
            validateMobileForm: {
                mobileCode: ''
            },
            updateMobileForm: {
                mobile: '',
                mobileCode: ''
            },
            validatePasswordFormValid: {
                password: [
                    { required: true, message: this.$t('user_input_text_password'), trigger: 'blur' }
                ],
            },
            updateMobileFormValid: {
                mobile: [
                    { required: true, message: this.$t('user_input_phone'), trigger: 'blur' },
                    { validator: valideMobile, trigger: 'blur' }
                ],
                mobileCode: [
                    { validator: validemobileCode, trigger: 'blur' }
                ],
            },
            validateEmailFormValid: {
                mobileCode: [
                    { validator: validemobileCode, trigger: 'blur' }
                ],
            },
            validateMobileFormValid: {
                mobileCode: [
                    { validator: validemobileCode, trigger: 'blur' }
                ],
            }
        };
    },
    watch: {
        loadData () {
            if (this.loadData) {
                this.initForm();
            }
        },
    },
    methods: {
        initForm () {
            this.validatePasswordForm = {
                password: ''
            };
            this.updateMobileForm = {
                mobile: '',
                mobileCode: ''
            };
            this.validateMobileForm = {
                mobileCode: ''
            };
            this.validateEmailForm = {
                mobileCode: ''
            };
            clearInterval(window.mobileTimer);
            clearInterval(window.emailTimer);
            clearInterval(window.newTimer);
            this.btnNewText = this.$t('user_get_verification_code');
            this.btnEmailGetCodeText = this.$t('user_get_verification_code');

            this.btnMobileGetCodeText = this.$t('user_get_verification_code');
            this.isNewDisabled = false;
            this.isEmailGetCodeDisabled = false;
            this.isMobileGetCodeDisabled = false;
            this.$emit('modalUpdated');
        },
        editMobileValidatePasswordNext () {
            this.$refs.validatePasswordForm.validate((valid) => {
                if (valid) {
                    this.editEmailValidatePasswordLoading = true;
                    $.ajax({
                        url: api.user.updateMobile,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyPassword',
                            'password': this.validatePasswordForm.password,
                        },
                        success: (res) => {
                            this.editEmailValidatePasswordLoading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$emit('updataIsValidate', true);
                                // this.isValidate = true;
                                this.editMobileKey = res.data.key;
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editEmailValidatePasswordLoading = true;
                            });
                        }
                    });
                }
            });
        },
        editMobileValidateEmailNext () {
            this.$refs.validateEmailForm.validate((valid) => {
                if (valid) {
                    this.editMobileValidateEmailLoading = true;
                    $.ajax({
                        url: api.user.updateMobile,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyEmail',
                            emailCode: this.validateEmailForm.mobileCode,
                        },
                        success: (res) => {
                            this.editMobileValidateEmailLoading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$emit('updataIsValidate', true);
                                this.editMobileKey = res.data.key;
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editMobileValidateEmailLoading = true;
                            });
                        }
                    });
                }
            });
        },
        editMobileValidateMobileNext () {
            this.$refs.validateMobileForm.validate((valid) => {
                if (valid) {
                    this.editMobileValidateMobileLoading = true;
                    $.ajax({
                        url: api.user.updateMobile,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyMobile',
                            mobileCode: this.validateMobileForm.mobileCode,
                        },
                        success: (res) => {
                            this.editMobileValidateMobileLoading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$emit('updataIsValidate', true);
                                this.editMobileKey = res.data.key;
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editMobileValidateMobileLoading = true;
                            });
                        }
                    });
                }
            });
        },
        updateMobileFormSave () {
            this.$refs.updateMobileForm.validate((valid) => {
                if (valid) {
                    let re = /^\d{6}$/;
                    let value = this.updateMobileForm.mobileCode;
                    let lock = false;
                    if (!value) {
                        this.$Message.warning({
                            content: this.$t('user_enter_verification_code'),
                            duration: 2
                        });
                        lock = true;
                    } else if (!re.test(value)) {
                        this.$Message.warning({
                            content: this.$t('user_6_num_code'),
                            duration: 2
                        });
                        lock = true;
                    }
                    if (lock) {
                        return;
                    }
                    this.updateMobileFormLoading = true;
                    $.ajax(
                        {
                            url: api.user.updateMobile,
                            type: 'post',
                            data: {
                                access_token: this.$store.state.user.userInfo.accessToken,
                                'op': 'submitMobileNew',
                                mobile: this.updateMobileForm.mobile,
                                mobileCode: this.updateMobileForm.mobileCode,
                            },
                            success: (res) => {
                                this.updateMobileFormLoading = false;
                                if (res.error) {
                                    this.$Message.destroy();
                                    this.$Message.warning({
                                        content: res.message,
                                        duration: 3
                                    });
                                } else {
                                    this.$emit('modalSaved');
                                }
                            },
                            error: (res, textStatus, responseText) => {
                                util.handleAjaxError(this, res, textStatus, responseText, () => {
                                    this.updateMobileFormLoading = false;
                                });
                            }
                        }
                    );
                }
            });
        },
        validEmailGetCode () {
            this.isEmailGetCodeDisabled = true;
            this.btnEmailGetCodeText = this.$t('user_sending');
            $.ajax(
                {
                    url: api.user.updateMobile,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        'op': 'sendEmail',
                    },
                    success: (res) => {
                        if (res.error) {
                            this.btnEmailGetCodeText = this.$t('user_get_verification_code');
                            this.isEmailGetCodeDisabled = false;
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            let timeLast = 59;
                            this.btnEmailGetCodeText = timeLast + this.$t('user_again');
                            window.emailTimer = setInterval(() => {
                                if (timeLast >= 0) {
                                    this.btnEmailGetCodeText = timeLast + this.$t('user_again');
                                    timeLast -= 1;
                                } else {
                                    clearInterval(window.emailTimer);
                                    this.btnEmailGetCodeText = this.$t('user_get_verification_code');
                                    this.isEmailGetCodeDisabled = false;
                                }
                            }, 1000);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.btnEmailGetCodeText = this.$t('user_get_verification_code');
                            this.isEmailGetCodeDisabled = false;
                        });
                    }
                }
            );
        },
        validMobileGetCode () {
            this.isMobileGetCodeDisabled = true;
            this.btnMobileGetCodeText = this.$t('user_sending');
            $.ajax(
                {
                    url: api.user.updateMobile,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        'op': 'sendMobile',
                    },
                    success: (res) => {
                        if (res.error) {
                            this.btnMobileGetCodeText = this.$t('user_get_verification_code');
                            this.isMobileGetCodeDisabled = false;
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            let timeLast = 59;
                            this.btnMobileGetCodeText = timeLast + this.$t('user_again');
                            window.mobileTimer = setInterval(() => {
                                if (timeLast >= 0) {
                                    this.btnMobileGetCodeText = timeLast + this.$t('user_again');
                                    timeLast -= 1;
                                } else {
                                    clearInterval(window.mobileTimer);
                                    this.btnMobileGetCodeText = this.$t('user_get_verification_code');
                                    this.isMobileGetCodeDisabled = false;
                                }
                            }, 1000);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.btnMobileGetCodeText = this.$t('user_get_verification_code');
                            this.isMobileGetCodeDisabled = false;
                        });
                    }
                }
            );
        },
        newEmailGetCode () {
            this.$refs.updateMobileForm.validate((valid) => {
                if (valid) {
                    this.isNewDisabled = true;
                    this.btnNewText = this.$t('user_sending');
                    $.ajax(
                        {
                            url: api.user.updateMobile,
                            type: 'post',
                            data: {
                                access_token: this.$store.state.user.userInfo.accessToken,
                                'op': 'sendMobileNew',
                                key: this.editMobileKey,
                                mobile: this.updateMobileForm.mobile
                            },
                            success: (res) => {
                                if (res.error) {
                                    this.btnNewText = this.$t('user_get_verification_code');
                                    this.isNewDisabled = false;
                                    this.$Message.warning({
                                        content: res.message,
                                        duration: 3
                                    });
                                } else {
                                    let timeLast = 59;
                                    this.btnNewText = timeLast + this.$t('user_again');
                                    window.newTimer = setInterval(() => {
                                        if (timeLast >= 0) {
                                            this.btnNewText = timeLast + this.$t('user_again');
                                            timeLast -= 1;
                                        } else {
                                            clearInterval(window.newTimer);
                                            this.btnNewText = this.$t('user_get_verification_code');
                                            this.isNewDisabled = false;
                                        }
                                    }, 1000);
                                }
                            },
                            error: (res, textStatus, responseText) => {
                                util.handleAjaxError(this, res, textStatus, responseText, () => {
                                    this.btnNewText = this.$t('user_get_verification_code');
                                    this.isNewDisabled = false;
                                });
                            }
                        }
                    );
                }
            });
        },
    }

};
</script>

