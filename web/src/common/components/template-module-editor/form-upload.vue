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
            <h4 class="editor-header">{{$t('template_upload_type')}}</h4>
            <h6 class="editor-subheader">{{$t('template_set_file_format_receive')}}</h6>
            <Select v-model="module.subType" @on-change="handleSelectChange">
                <Option value="image">{{$t('template_picture')}}</Option>
                <Option value="audio">{{$t('template_audio')}}</Option>
                <Option value="video">{{$t('template_video')}}</Option>
                <Option value="other">{{$t('template_other')}}</Option>
            </Select>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_file_format')}}</h4>
            <CheckboxGroup v-model="module.fileFormat" @on-change="saveChange">
                <Checkbox :label="item" v-for="item in fileFormat[module.subType]" :key="item"> {{item}}</Checkbox>
            </CheckboxGroup>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_limit_size_file')}}</h4>
            {{$t('template_min')}}：
            <InputNumber size="small"
                         :max="module.fileMaxSize"
                         :min="0"
                         v-model="module.fileMinSize"
                         :formatter="value => `${value}M`"
                         :parser="value => value.replace('M', '')"
                         @on-change="saveChange"></InputNumber>
            {{$t('template_max')}}：
            <InputNumber size="small"
                         :min="module.fileMinSize"
                         v-model="module.fileMaxSize"
                         :formatter="value => `${value}M`"
                         :parser="value => value.replace('M', '')"
                         @on-change="saveChange"></InputNumber>
        </div>
        <div class="editor-item" v-if="timeSetting">
            <h4 class="editor-header">{{$t('template_limit_length_file')}}</h4>
            {{$t('template_shortest')}}：
            <InputNumber size="small"
                         :max="module.fileMaxLength"
                         :min="0"
                         v-model="module.fileMinLength"
                         :formatter="value => `${value}s`"
                         :parser="value => value.replace('s', '')"
                         @on-change="saveChange"></InputNumber>
            {{$t('template_longest')}}：
            <InputNumber size="small"
                         :min="module.fileMinLength"
                         v-model="module.fileMaxLength"
                         :formatter="value => `${value}s`"
                         :parser="value => value.replace('s', '')"
                         @on-change="saveChange"></InputNumber>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_number_file_upload')}}:
                <InputNumber size="small"
                             :min="1" v-model="module.fileNumber"
                             @on-change="saveChange"></InputNumber>
            </h4>
            <h6 class="editor-subheader">{{$t('template_upload_one_file_default_modify')}}</h6>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'form-upload-editor',
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
                fileFormat: {
                    audio: ['mp3', 'aac', 'wav', 'flac', 'ape'],
                    image: ['jpg', 'jpeg', 'png', 'bmp', 'gif'],
                    video: ['avi', 'mp4', 'wmv', 'rmvb'],
                    other: ['txt', 'pdf', 'csv']
                },
                timeSetting: false
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
            handleSelectChange (subType) {
                this.module.fileFormat = [];
                if (subType === 'video' || subType === 'audio') {
                    this.timeSetting = true;
                    this.module.fileMinLength = 0;
                    this.module.fileMaxLength = 300;
                } else {
                    this.timeSetting = false;
                    delete this.module.fileMinLength;
                    delete this.module.fileMaxLength;
                }
            },
            saveChange () {
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            }
        }
    };
</script>
<style lang="scss">
    @import './style';
</style>
