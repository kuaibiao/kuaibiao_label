<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode=== 'icon'">
            <span class="bficonfont bf-icon-check"></span>
            <span class="template-name">{{$t('template_form_checkbox')}}</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'">
            <h2 class="instance-header">
                <Icon type="ios-bookmark" size="12" color="red" v-if="config.required"></Icon>
                {{config.header}}
            </h2>
            <h5 class="instance-tips">{{config.tips}}</h5>
            <CheckboxGroup v-model="config.value" :class="config.vertical ? 'ivu-checkbox-group-vertical' :' '">
                <Checkbox :label="item.text" v-for="(item, index) in config.data" :key="item.text"
                          :disabled="mode !== 'execute'">
                    <span>{{item.text }} <span v-if="item.keyBoard">({{item.keyBoard | keyMap}})</span></span>
                </Checkbox>
            </CheckboxGroup>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-mixin';
    import encodeKeyCode from '../../encodeKeyCode';

    export default {
        mixins: [mixin],
        name: 'form-checkbox',
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
                let newKeyInfo = [];
                this.config.data.forEach((item) => {
                    if (item.keyBoard) {
                        let key = encodeKeyCode(item.keyBoard);
                        newKeyInfo.push(key);
                    }
                });
                this.$store.commit('updateUsedKeyMap', {
                    oldKeyInfo: [],
                    newKeyInfo,
                });
            }
        },
        methods: {},
        destroyed () {
            if (this.mode === 'edit') {
                let oldKeyInfo = [];
                this.config.data.forEach((item) => {
                    if (item.keyBoard) {
                        // ctrl alt shift meta 0000 + keyCode
                        let key = encodeKeyCode(item.keyBoard);
                        oldKeyInfo.push(key);
                    }
                });
                this.$store.commit('updateUsedKeyMap', {
                    oldKeyInfo,
                    newKeyInfo: []
                });
            }
        }
    };
</script>
<style lang="scss">
    // 竖直排列补丁。。。
    .ivu-checkbox-group-vertical .ivu-checkbox-wrapper {
        display: block;
        height: 30px;
        line-height: 30px;
    }
</style>


