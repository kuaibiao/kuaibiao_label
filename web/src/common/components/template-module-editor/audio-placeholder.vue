<template>
    <div class="module-editor">
        <!--<div class="editor-item">-->
        <!--<h4 class="editor-header">{{$t('template_title_optional')}}</h4>-->
        <!--<Input v-model="module.header" :placeholder="$t('template_enter_title')"-->
        <!--@on-change="saveChange"/>-->
        <!--</div>-->
        <!--<div class="editor-item">-->
        <!--<h4 class="editor-header">{{$t('template_remarks_optional')}}</h4>-->
        <!--<Input v-model="module.tips" :placeholder="$t('template_enter_comments')"-->
        <!--@on-change="saveChange"/>-->
        <!--</div>-->
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_data_source_anchor_point')}}</h4>
            <h6 class="editor-subheader" style="color:#666;">{{$t('template_editable_not_repeat')}}</h6>
            <Input v-model="module.anchor"
                   @on-change="saveChange"/>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'audio-placeholder-editor',
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
