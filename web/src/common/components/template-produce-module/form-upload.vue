<!--组件：文件上传-->
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
        <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
        <h5 class="instance-tips" v-if="config.fileFormat.length>0">{{$t('template_support_file_type')}}：{{config.fileFormat}}</h5>
        <!--选择文件:-->
        <Upload
            ref = "upload"
            :data="uploadData"
            :show-upload-list="showUploadList"
            :default-file-list="defaultFileList"
            :on-remove="delUploadResult"
            :on-success="uploadResult"
            :action="serverUrl"
            :multiple="multiple"
            :accept="acceptStr"
            :before-upload="beforeUploadFun">
            <Button :disabled="isDisabled" type="primary" icon="ios-cloud-upload-outline">{{$t('tool_select_file')}}</Button>
        </Upload>
        <!--预览:-->
        <div id="J-preview-1615" class="preview-1615" v-if="fileList.length>0">
             <fieldset> <legend>{{$t('template_file_view')}}：</legend>
                <div class="files-list">
                    <div :class="[util.geFileTypeByExt(item.name) , 'file-box']" v-for="(item,i) in fileList">
                        <span class="file-name">
                            <em> {{item.name}} </em>
                            <span class="bficonfont bf-icon-trash" @click="handleRemove(i)"></span>
                        </span>
                        <div v-html="util.getHtmlByFileExt(item.name,item.uri)"></div>
                    </div>
                </div>
             </fieldset>
        </div>
        <!--调试：
        <button @click="testShowFileList">调试:查看已上传的文件</button>
        -->
      </div>
	</div>
</template>

<script>
import api from '@/api';
import mixin from '../mixins/template-mixin';
import cloneDeep from 'lodash.clonedeep';
import EventBus from '@/common/event-bus';
import util from '@/libs/util.js';
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
            serverUrl: api.upload.resourceFiles,
            isDisabled: this.mode == 'execute',
            showUploadList: true,
            fileList: [],
            defaultFileList: [],
            util: util,
            alreadyUploadFiles: 0, // 已上传的文件数量
            acceptStr: ''// 获取可上传文件的类型
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
        uploadData: function () {
            return {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                type: 'uploadfile'
            };
        }
    },
    created () {},
    mounted () {
        // 获取可上传文件的类型
        this.acceptStr = '';
        var arr = [];
        for (var i = 0; i < this.config.fileFormat.length; i++) {
            arr.push('.' + this.config.fileFormat[i]);
        }
        this.acceptStr = arr.join(',');
        // 当前操作模式：执行或编辑
        this.mode = this.scene;
        // 清空组件的数据
        EventBus.$on('clearDataFormUpload', this.clearData);
        // 编辑作业时结果回显
        EventBus.$on('setValue', this.set);
    },
    methods: {
        // 文件上传前进行检测
        beforeUploadFun (file) {
            if (this.alreadyUploadFiles >= parseInt(this.config.fileNumber)) {
                this.$Message.destroy();
                this.$Message.info({
                    // content: '提示：可最多上传' + String(this.config.fileNumber) + '个文件！',
                    content: this.$t('template_upload_file_tip', {num: String(this.config.fileNumber)}),
                    duration: 2
                });
                return false;
            } else {
                this.alreadyUploadFiles++;
            }
        },
        // 删除文件
        handleRemove (num) {
            this.$refs.upload.fileList.splice(num, 1);// 移除已上传的文件
            this.fileList = this.processResult(cloneDeep(this.$refs.upload.fileList));
            this.config.value = cloneDeep(this.fileList);
            this.updateAlreadyUploadFiles(this.fileList.length);
        },
        // 回显数据
        set (data) {
            this.defaultFileList = cloneDeep([]);// 回显前清空上一作业的回显数据
            if (data.id === this.config.id && data.scope.contains(this.$el)) {
                // 1.
                if (data.value && data.value.length > 0) {
                    let objs = data.value;
                    for (var i = 0; i < objs.length; i++) {
                        let name = objs[i].name;
                        let key = objs[i].key;
                        let urlpath = objs[i].urlpath;
                        let uri = objs[i].uri;
                        if (objs[i].response && objs[i].response.data) {
                            if (objs[i].response.data.key) {
                                key = objs[i].response.data.key;
                            }
                            if (objs[i].response.data.urlpath) {
                                urlpath = objs[i].response.data.urlpath;
                            }
                            if (objs[i].response.data.uri) {
                                uri = objs[i].response.data.uri;
                            }
                        }
                        this.defaultFileList.push({
                            name: name,
                            key: key,
                            urlpath: urlpath,
                            uri: uri,
                            url: uri
                        });
                    }
                }
                // 2.
                this.fileList = cloneDeep(this.defaultFileList);
                this.config.value = cloneDeep(this.defaultFileList);
                this.updateAlreadyUploadFiles(this.fileList.length);
            }
        },
        // 清空数据
        clearData (id) {
            if (this.config.id == id) {
                this.fileList = cloneDeep([]);
                delete this.config.value;
                this.$refs.upload.clearFiles();
                this.updateAlreadyUploadFiles(this.fileList.length);
            }
        },
        // 文件上传成功时调用
        uploadResult (response, file, fileList) {
            this.fileList = this.processResult(cloneDeep(fileList));
            this.config.value = cloneDeep(this.fileList);
            this.updateAlreadyUploadFiles(this.fileList.length);
        },
        // 删除已上传的文件
        delUploadResult (file, fileList) {
            this.fileList = this.processResult(cloneDeep(fileList));
            this.config.value = cloneDeep(this.fileList);
            this.updateAlreadyUploadFiles(this.fileList.length);
        },
        // 更新已上传的文件数量
        updateAlreadyUploadFiles (num) {
            this.alreadyUploadFiles = num;
        },
        // 处理结果的格式为
        processResult (objs) {
            let arr = [];
            for (var i = 0; i < objs.length; i++) {
                let name = objs[i].name;
                let key = objs[i].key;
                let urlpath = objs[i].urlpath;
                let uri = objs[i].uri;
                if (objs[i].response && objs[i].response.data) {
                    if (objs[i].response.data.key) {
                        key = objs[i].response.data.key;
                    }
                    if (objs[i].response.data.urlpath) {
                        urlpath = objs[i].response.data.urlpath;
                    }
                    if (objs[i].response.data.uri) {
                        uri = objs[i].response.data.uri;
                    }
                }
                arr.push({
                    name: name,
                    key: key,
                    urlpath: urlpath,
                    uri: uri
                });
            }
            return arr;
        },
        // 查看已上传的文件
        testShowFileList () {
            console.log('');
            console.log('config.id:', this.config.id);
            console.log('showFileList:', this.fileList);
        }
    },
    destroyed () {
        EventBus.$off('clearDataFormUpload', this.clearData);
    }
};
</script>
<style>
.preview-1615{margin-bottom: 1em;}
.preview-1615 fieldset{padding: 0.5em;border: 1px solid #cecece;border-radius: 3px;color: #666;}
.preview-1615 fieldset legend{padding-left: 0.3em;padding-right: 0.3em;background-color: #2d8cf0;color: #fff;border-radius: 3px;}
.preview-1615 fieldset .files-list{align-content: flex-start;display: flex;flex-wrap: wrap;}
.preview-1615 fieldset .file-box{width: 300px;height: 100px;border: 1px solid #cecece;padding: 5px;overflow: hidden;position: relative;border-radius: 3px;margin-bottom: 5px;margin-right: 5px;padding-top: 22px;display: inline-block;}
.preview-1615 fieldset .file-box .file-name{position: absolute;z-index: 99;background-color: rgba(0,0,0,0.1);padding-left: 5px;padding-right: 1px;border-radius: 1px;right: 0px;top: 0px;color: #333;text-align: right;width: 100%;height: 20px;line-height: 20px;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}
.preview-1615 fieldset .file-box .file-name .bf-icon-trash{cursor:pointer;position: absolute;z-index: 115;right: 0px;top: 0px;background-color: #e2e2e2;border-radius: 1px;}
.preview-1615 fieldset .file-box .file-name .bf-icon-trash:hover{font-weight: bold;}
.preview-1615 fieldset .file-box .file-name em{white-space: nowrap;width: 100px;color: #000;margin-right: 18px;}
.preview-1615 fieldset .img-file{width: 100px;}
.preview-1615 fieldset .audio-file{padding-top: 31px;width: 315px;}
.preview-1615 fieldset .video-file{height: 140px;padding-top: 30px;width: 265px;}
.preview-1615 fieldset .other-file{width: 100px;}
.instance-container .ivu-upload ul.ivu-upload-list .ivu-upload-list-file-finish{overflow: visible;margin-left: 2em;list-style-type: decimal;}
</style>
