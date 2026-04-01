<template>
    <div class="module-editor">
        <div class="editor-item">
            <Button type="default" @click.native="visible=true;">{{$t('template_open_text_editor')}}</Button>
            <Modal v-model="visible"
                   :width="85"
                   :class="'center-modal edit-modal'"
                   :transition-names="['fade','ease']"
                   :mask-closable="false"
                   :closable="false"
                   @on-ok="saveChange"
            >
                <quill-editor v-model="config.text"
                              :options="editorConf"
                >
                </quill-editor>
            </Modal>
        </div>
    </div>
</template>
<script>
    // import api from '@/api';
    import 'quill/dist/quill.core.css';
    import 'quill/dist/quill.snow.css';
    import 'quill/dist/quill.bubble.css';
    import {quillEditor} from 'vue-quill-editor';

    export default {
        name: 'show-text-editor',
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
                visible: false,
                editorConf: {
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{'header': 1}, {'header': 2}],
                            [{'list': 'ordered'}, {'list': 'bullet'}],
                            [{'script': 'sub'}, {'script': 'super'}],
                            [{'indent': '-1'}, {'indent': '+1'}],
                            [{'direction': 'rtl'}],
                            [{'header': [1, 2, 3, 4, 5, 6, false]}],
                            [{'font': []}],
                            [{'color': []}, {'background': []}],
                            [{'align': []}],
                            ['clean'],
                            ['link', 'image']
                        ],
                    }
                }
            };
        },
        mounted () {
            this.module = this.config;
            this.visible = true;
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
            handleView () {
                this.visible = true;
            },
        },
        components: {
            quillEditor
        }
    };
</script>
<style lang="scss">
    @import './style';

    .edit-modal .ivu-modal {
        max-width: 1080px;
    }
    .ql-editor {
        min-height: 420px;
        max-height: 640px;
        overflow: auto;
    }
</style>
