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
            <h4 class="editor-header">{{$t('template_enable_text_search')}}:
                <Checkbox v-model="module.searchEnable"
                          @on-change="saveChange">{{module.searchEnable ? this.$t('template_opened'): this.$t('template_off')}}
                </Checkbox>
            </h4>
        </div>
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
        name: 'text-placeholder-editor',
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
            // 兼容之前没有 searchEnable 属性配置的模板 默认为true
            this.module.searchEnable = typeof this.module.searchEnable === 'undefined'
                ? true : this.module.searchEnable;
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
