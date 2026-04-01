<template>
    <div class="module-editor">
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_set_layout_width_ratio')}}</h4>
            <Select v-model="module.ratio" @on-change="caluWRatio">
                <Option :value="1">{{$t('template_ratio_width_two_columns')}}：1:1</Option>
                <Option :value="2">{{$t('template_ratio_width_two_columns')}}：2:1</Option>
                <Option :value="3">{{$t('template_ratio_width_two_columns')}}：3:1</Option>
                <Option :value="5">{{$t('template_ratio_width_two_columns')}}：5:1</Option>
            </Select>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'layout-editor',
        props: {
            config: {
                type: Object,
                required: true
            },
            path: {
                type: String,
                required: true
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
            },
            caluWRatio (ratio) {
                let total = 24;
                let left = total / (ratio + 1) * ratio;
                let right = total - left;
                this.module.ratio = ratio;
                this.module.column0.span = left;
                this.module.column1.span = right;
                this.saveChange();
            }
        }
    };
</script>
<style lang="scss">
    @import "./style";
</style>
