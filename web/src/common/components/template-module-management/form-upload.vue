<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-upload-file"></span>
            <span class="template-name">{{$t('template_form_upload')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">{{config.header}}</h2>
            <h5 class="instance-tips">{{config.tips}}</h5>
            <Upload
                    :show-upload-list="showUploadList"
                    :action="serverUrl"
                    :multiple="multiple"
                    :data="uploadData"
                    :accept="config.fileFormat.toString()">
                <Button :disabled="isDisabled" type="default" icon="ios-cloud-upload-outline">
                    {{$t('template_select_file')}}
                </Button>
            </Upload>
        </div>
    </div>
</template>
<script>
    import api from '@/api';
    import mixin from '../mixins/module-mixin';

    export default {
        mixins: [mixin],
        name: 'form-upload',
        props: {
            config: {
                type: Object,
                require: true
            },
            path: {
                type: String,
                required: false
            },
            scene: {
                type: String,
                required: true
            }
        },
        data () {
            return {
                mode: 'icon',
                serverUrl: api.upload.projectFiles,
                isDisabled: this.mode !== 'execute',
                showUploadList: true,
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        computed: {
            multiple () {
                return this.config.fileNumber > 1;
            },
            uploadData () {
                return {
                    access_token: this.$store.state.user.userInfo.accessToken,
                };
            },
        },
        created () {
        },
        mounted () {
            this.mode = this.scene;
        },
        methods: {}
    };
</script>

