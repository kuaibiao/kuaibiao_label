<template>
    <Poptip trigger="click" :title="$t('template_label_attr_edit')" width="360" placement="top-end"
            @on-popper-show="show = true"
            @on-popper-hide="show = false"
    ><Icon type="ios-cog" :class="show ? 'setting-trigger dragable-ignore icon-color': 'setting-trigger dragable-ignore' " size="18" style="margin-right:8px;"></Icon>
        <div class="label-attr-wrapper" slot="content" v-if="show">
            <div class="tag-info-item">
                <span>{{$t('template_color')}}:   </span>
                <color-picker :color="item.color" @input="colorChange"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_short_code')}}:   </span>
                <Input v-model="item.shortValue"
                       @on-change="saveChange"
                       :min="0"
                       size="small" style="width:200px;"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_min_width')}}: </span>
                <InputNumber v-model="item.minWidth"
                             @on-change="checkValueIsValid"
                             :min="0"
                             size="small" style="width:200px;"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_maxi_width')}}:   </span>
                <InputNumber v-model="item.maxWidth"
                             :min="0"
                             @on-change="checkValueIsValid" size="small" style="width:200px;"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_min_height')}}:   </span>
                <InputNumber v-model="item.minHeight"
                             :min="0"
                             @on-change="checkValueIsValid" size="small" style="width:200px;"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_max_height')}}:   </span>
                <InputNumber v-model="item.maxHeight"
                             :min="0"
                             @on-change="checkValueIsValid" size="small" style="width:200px;"/>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_label_included_submitted_job_result')}}:</span>
                <RadioGroup v-model="item.isRequired"
                            @on-change="saveChange">
                    <Radio :label="0">
                        <span>{{$t('template_not_must')}}</span>
                    </Radio>
                    <Radio :label="1">
                        <span>{{$t('template_must')}}</span>
                    </Radio>
                </RadioGroup>
            </div>
            <div class="tag-info-item">
                <span>{{$t('template_figure')}}: </span>
                <Upload 
                        :data="uploadConfig.data"
                        :action="uploadConfig.action"
                        :name="uploadConfig.name"
                        :show-upload-list="uploadConfig['show-upload-list']"
                        :accept="uploadConfig.accept"
                        :max-size="uploadConfig['max-size']"
                        :on-success="handleUploadSuccess"
                        :on-error="handleUploadError"
                        :on-progress="handleProgress"
                        :before-upload="startUpload"
                >
                    <Button type="default">{{$t('template_update_figure')}}</Button>
                </Upload>
                <div class="img-preview" v-if="item.exampleImageSrc">
                    <img :src="formatUrl(item.exampleImageSrc)" v-if="item.exampleImageSrc" width="96"
                         class="example-image"/>
                    <div class="img-preview-cover">
                        <Icon type="ios-trash-outline" @click.native="removeImage"></Icon>
                    </div>
                </div>
            </div>
            <Progress :percent="uploadProgress" status="active" v-if="visible"></Progress>
        </div>
    </Poptip>
</template>
<script>
    import api from '@/api';
    import Util from '@/libs/util';
    import ColorPicker from '../color-picker.vue';

    export default {
        name: 'label-attr',
        props: {
            item: {
                type: Object,
                required: true
            }
        },
        data () {
            return {
                uploadConfig: {
                    action: api.upload.image,
                    name: 'image',
                    'show-upload-list': false,
                    accept: '.jpeg, .jpg, .png,',
                    'max-size': 10240,
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    }
                },
                uploadProgress: 0,
                visible: false,
                viewModal: false,
                show: false,
            };
        },
        mounted () {
            if (typeof this.item.isRequired === 'undefined') {
                this.$set(this.item, 'isRequired', 0);
                this.$emit('saveChange');
            }
        },
        methods: {
            formatUrl (url) {
                return api.staticBase + Util.replaceUrl(url);
            },
            colorChange (val) {
                this.item.color = val;
                this.saveChange();
            },
            removeImage () {
                this.$set(this.item, 'exampleImageSrc', '');
                this.saveChange();
            },
            checkValueIsValid () {
                // TODO这里有问题, 管理员配置时注意下
                // let item = this.item;
                // // 验证宽
                // if (item.minWidth && item.maxWidth) { // 两者均不为0
                //     if (item.minWidth >= item.maxWidth) {
                //         item.maxWidth = item.minWidth + 10;
                //     }
                // }
                // // 验证高
                // if (item.minHeight && item.maxHeight) { // 两者均不为0
                //     if (item.minHeight >= item.maxHeight) {
                //         item.maxHeight = item.minHeight + 10;
                //     }
                // }
                this.saveChange();
            },
            saveChange () {
                this.$emit('saveChange');
            },
            handleUploadSuccess (res, file) {
                if (res.error) {
                    this.$Notice.error({
                        // title: '文件 ' + file.name + ' 上传失败',
                        title: this.$t('template_file_upload_failes', {fileName: file.name}),
                    });
                } else {
                    this.$set(this.item, 'exampleImageSrc', res.data.url);
                    this.saveChange();
                }
                this.visible = false;
            },
            handleUploadError (err, file) {
                this.$Notice.error({
                    title: this.$t('template_file_upload_failes', {fileName: file.name}),
                });
                this.visible = false;
            },
            handleProgress (e) {
                this.uploadProgress = Math.round(e.percent);
            },
            startUpload () {
                this.visible = true;
                return true;
            }
        },
        components: {
            ColorPicker
        }
    };
</script>

<style lang="scss">
    @import "style";
    .label-attr-wrapper {
        min-height: 300px;
    }

    .ivu-poptip-content {
        border: 1px solid #d7d7d7;
    }
    .icon-color {
        color: #2d8cf0
    }
</style>


