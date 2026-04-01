<template>
    <div class="module-editor">
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_title_optional')}}</h4>
            <Input v-model="module.header" :placeholder="$t('template_enter_title')"
                   @on-change="saveChange"/>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_remarks_optional')}}</h4>
            <Input v-model="module.tips" :placeholder="$t('template_enter_comments')"
                   @on-change="saveChange"/>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_upload_sample_image')}}</h4>
            <h6 class="editor-subheader">{{$t('template_upload_file_size')}}</h6>
            <Upload 
                    :data="exampleImageConf.data"
                    :action="exampleImageConf.url"
                    :accept="exampleImageConf.accept"
                    :max-size="2048"
                    :show-upload-list="false"
                    :name="exampleImageConf.name"
                    :on-success="imageUploadSuccess"
                    :on-error="imageUploadError"
                    :on-exceeded-size="handleMaxSize"
                    style="display:inline-block; margin-top: 15px">
                <Button icon="md-cloud-upload">{{$t('template_click_select_the_image')}}</Button>
            </Upload>
            <div class="img-preview" v-if="module.imgSrc">
                <img :src="formatUrl(module.imgSrc)">
                <div class="img-preview-cover">
                    <Icon type="ios-eye-outline" @click.native="visible = true"></Icon>
                    <Icon type="ios-trash-outline" @click.native="handleRemove"></Icon>
                </div>
                <Modal title="View Image" v-model="visible">
                    <img :src="formatUrl(module.imgSrc)" v-if="visible" style="width: 100%">
                </Modal>
            </div>
        </div>
    </div>
</template>
<script>
    import api from '@/api';
    import Util from '@/libs/util';

    export default {
        name: 'show-img-editor',
        props: {
            config: {
                type: Object,
                required: true,
            },
            path: {
                type: String,
                required: true,
            }
        },
        data () {
            return {
                module: {},
                exampleImageConf: {
                    url: api.upload.image,
                    accept: '.jpg, .png, .jpeg',
                    name: 'image',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    }
                },
                visible: false,
            };
        },
        mounted () {
            this.module = this.config;
        },
        watch: {
            config: {
                handler: function (config) {
                    this.module = config;
                },
                deep: true,
            }
        },
        methods: {
            formatUrl (url) {
                return api.staticBase + Util.replaceUrl(url);
            },
            saveChange () {
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
            imageUploadSuccess (res) {
                if (res.error) {
                    this.$Message.error({
                        content: this.$t('template_upload_failed_try_again'),
                        duration: 2,
                    });
                } else {
                    this.module.imgSrc = res.data.url;
                    this.saveChange();
                }
            },
            imageUploadError () {
                this.$Message.error({
                    content: this.$t('template_upload_failed_try_again'),
                    duration: 2,
                });
            },
            handleRemove () {
                this.module.imgSrc = '';
                this.saveChange();
            },
            handleMaxSize (file) {
                this.$Notice.warning({
                    title: this.$t('template_file_size_exceeds_limit'),
                    // desc: '文件  ' + file.name + ' 大小超过2M.'
                    desc: this.$t('template_file_size_2M', {fileName: file.name})
                });
            },
        }
    };
</script>
<style lang="scss">
    @import './style';
</style>
