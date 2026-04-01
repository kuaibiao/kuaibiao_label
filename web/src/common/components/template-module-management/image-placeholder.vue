<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode=== 'icon'">
            <span class="bficonfont bf-icon-img"></span>
            <span class="template-name">{{$t('template_task_file_placeholder')}}</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'">
            <h2 class="instance-header">{{$t('template_task_file_placeholder')}}</h2>
            <!--<h5 class="instance-tips">{{config.tips}}</h5>-->
            <div class="file-placeholder"
                 :style="'background-image:url('+ getBgImage() + ')'">
                <span>{{$t('template_image_file_shown_here_processed_operator')}}</span>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-mixin';
    import api from '@/api';

    export default {
        mixins: [mixin],
        name: 'task-file-placeholder',
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
                staticBase: api.staticBase,
                mode: 'icon'
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        created () {
        },
        mounted () {
            this.mode = this.scene;
            if (this.mode === 'edit') {
                this.$store.commit('updatePlaceHolderCounter', {
                    type: 'image',
                    add: true,
                });
            }
        },
        methods: {
            getBgImage () {
                return this.staticBase + '/images/template/icon-data@2x.png';
            }
        },
        destroyed () {
            if (this.mode === 'edit') {
                this.$store.commit('updatePlaceHolderCounter', {
                    type: 'image',
                    add: false
                });
            }
        },
    };
</script>
<style lang="scss" scoped>
    .file-placeholder {
        min-height: 180px;
        text-align: center;
        padding-top: 35%;
        padding-bottom: 15px;
        background-repeat: no-repeat;
        background-position: center 20px;
        background-color: #edf0f5;
        background-size: 30%;
        font-size: 14px;
    }
</style>


