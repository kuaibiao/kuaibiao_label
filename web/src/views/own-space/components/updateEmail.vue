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
                        <Button type="primary" @click="editEmailValidatePasswordNext" :loading="editEmailValidatePasswordLoading">{{$t('user_next')}}</Button>
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
                        <Button type="primary" @click="editEmailValidateEmailNext" :loading="editEmailValidateEmailLoading">{{$t('user_next')}}</Button>
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
                        <Button type="primary" @click="editEmailValidateMobileNext" :loading="editEmailValidateMobileLoading">{{$t('user_next')}}</Button>
                    </FormItem>
                </Form>
            </TabPane>
        </Tabs>
        <div style="margin-top: 10px">
            <Form 
                v-if="isValidate" 
                ref="updateEmailForm" 
                :model="updateEmailForm" 
                :label-width="100" 
                label-position="right" 
                :rules="updateEmailFormValid"
                @submit.native.prevent>
                    <FormItem :label="$t('user_new_email')+'：'" style="width: 400px;margin-top：40px" prop="email">
                        <Input v-model="updateEmailForm.email" :placeholder="$t('user_enter_new_email')"/>
                    </FormItem>
                    <FormItem :label="$t('user_verification_code')+'：'" style="width: 400px">
                        <Row>
                            <Col span="16">
                                <Input size="large" v-model="updateEmailForm.mobileCode" :placeholder="$t('user_enter_verification_code')"></Input>
                            </Col>
                            <Col span="7" offset="1">
                                <Button type="primary" @click="newEmailGetCode" :disabled="isNewDisabled" :type="isNewDisabled? 'default' : 'primary'">{{ btnNewText }}</Button>
                            </Col>
                        </Row>
                    </FormItem>
                    <FormItem label="">
                        <Button type="primary" @click="updateEmailFormSave" :loading="updateEmailFormLoading">{{$t('user_save')}}</Button>
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
            updateEmailFormLoading: false,
            editEmailValidateEmailLoading: false,
            editEmailValidateMobileLoading: false,
            editEmailValidatePasswordLoading: false,
            staticBase: api.staticBase,
            editEmailKey: '',
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
            updateEmailForm: {
                email: '',
                mobileCode: ''
            },
            validatePasswordFormValid: {
                password: [
                    { required: true, message: this.$t('user_input_text_password'), trigger: 'blur' }
                ],
            },
            updateEmailFormValid: {
                email: [
                    { required: true, message: this.$t('user_input_email'), trigger: 'blur' },
                    {type: 'email', message: this.$t('user_form_email_format'), trigger: 'blur'}
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
            this.updateEmailForm = {
                email: '',
                mobileCode: ''
            };
            this.validateMobileForm = {
                mobileCode: ''
            };
            this.validateEmailForm = {
                mobileCode: ''
            };
            clearInterval(window.mobileTimer);
            clearInterval(window.newTimer);
            clearInterval(window.emailTimer);

            this.btnNewText = this.$t('user_get_verification_code');
            this.btnEmailGetCodeText = this.$t('user_get_verification_code');
            this.btnMobileGetCodeText = this.$t('user_get_verification_code');
            this.isNewDisabled = false;
            this.isEmailGetCodeDisabled = false;
            this.isMobileGetCodeDisabled = false;
            this.$emit('modalUpdated');
        },
        editEmailValidatePasswordNext () {
            this.$refs.validatePasswordForm.validate((valid) => {
                if (valid) {
                    this.editEmailValidatePasswordLoading = true;
                    $.ajax({
                        url: api.user.updateEmail,
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
                                this.editEmailKey = res.data.key;
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
        editEmailValidateEmailNext () {
            this.$refs.validateEmailForm.validate((valid) => {
                if (valid) {
                    this.editEmailValidateEmailLoading = true;
                    $.ajax({
                        url: api.user.updateEmail,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyEmail',
                            emailCode: this.validateEmailForm.mobileCode,
                        },
                        success: (res) => {
                            this.editEmailValidateEmailLoading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$emit('updataIsValidate', true);
                                this.editEmailKey = res.data.key;
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editEmailValidateEmailLoading = true;
                            });
                        }
                    });
                }
            });
        },
        editEmailValidateMobileNext () {
            this.$refs.validateMobileForm.validate((valid) => {
                if (valid) {
                    this.editEmailValidateMobileLoading = true;
                    $.ajax({
                        url: api.user.updateEmail,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyMobile',
                            mobileCode: this.validateMobileForm.mobileCode,
                        },
                        success: (res) => {
                            this.editEmailValidateMobileLoading = false;
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$emit('updataIsValidate', true);
                                this.editEmailKey = res.data.key;
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editEmailValidateMobileLoading = true;
                            });
                        }
                    });
                }
            });
        },
        updateEmailFormSave () {
            this.$refs.updateEmailForm.validate((valid) => {
                if (valid) {
                    let re = /^\d{6}$/;
                    let value = this.updateEmailForm.mobileCode;
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
                    $.ajax(
                        {
                            url: api.user.updateEmail,
                            type: 'post',
                            data: {
                                access_token: this.$store.state.user.userInfo.accessToken,
                                'op': 'submitEmailNew',
                                email: this.updateEmailForm.email,
                                emailCode: this.updateEmailForm.mobileCode,
                            },
                            success: (res) => {
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
                                });
                            }
                        }
                    );
                }
            });
        },
        validEmailGetCode () {
            this.btnEmailGetCodeText = this.$t('user_sending');
            this.isEmailGetCodeDisabled = true;
            $.ajax(
                {
                    url: api.user.updateEmail,
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
                            this.isEmailGetCodeDisabled = true;
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
            this.btnMobileGetCodeText = this.$t('user_sending');
            this.isMobileGetCodeDisabled = true;
            $.ajax(
                {
                    url: api.user.updateEmail,
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
                            this.isMobileGetCodeDisabled = true;
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
            this.$refs.updateEmailForm.validate((valid) => {
                if (valid) {
                    this.btnNewText = this.$t('user_sending');
                    this.isNewDisabled = true;
                    $.ajax(
                        {
                            url: api.user.updateEmail,
                            type: 'post',
                            data: {
                                access_token: this.$store.state.user.userInfo.accessToken,
                                'op': 'sendEmailNew',
                                key: this.editEmailKey,
                                email: this.updateEmailForm.email
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
                                    this.isNewDisabled = true;
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

