<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-type"></span>
            <span class="template-name">{{$t('template_layout')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'" @click="handleDelete">
            <span class="bficonfont bf-icon-del2"></span>
        </div>
        <Row v-if="mode !== 'icon'" class="instance-container layout" type="flex">
            <i-col :span="config.column0.span" class="children-con backclip-content">
            <template v-if="mode === 'edit'">
               <draggable v-model="config.column0.children"
                       tag="div"
                       v-bind="dropOptions"
                       @choose="handleChoose"
                       @change="handleChange"
                       @end="handleEnd"
                       class="layout-list-draggable"
              >
                <component :is="component.type"
                           v-for="(component, index) in config.column0.children" :key="index"
                           :path="path + 'column0,children,'+index +','"
                           :config="component"
                           :scene="mode"
                >
                </component>
            </draggable>
            </template>
            <template v-else>
              <component :is="component.type"
                            v-for="(component, index) in config.column0.children" :key="index"
                            :path="path + 'column0,children,'+index +','"
                            :config="component"
                            :scene="mode"
                />
            </template>

            </i-col>
            <i-col :span="config.column1.span" class="children-con backclip-content">
            <template v-if="mode === 'edit'">
                 <draggable v-model="config.column1.children"
                       tag="div"
                       v-bind="dropOptions"
                       @choose="handleChoose"
                       @change="handleChange"
                       @end="handleEnd"
                       class="layout-list-draggable"
                 >
                <component :is="component.type"
                           v-for="(component, index) in config.column1.children" :key="index"
                           :path="path + 'column1,children,'+index +','"
                           :config="component"
                           :scene="mode"
                >
                </component>
            </draggable>
            </template>
            <template v-else>
              <component :is="component.type"
                           v-for="(component, index) in config.column1.children" :key="index"
                           :path="path + 'column1,children,'+index +','"
                           :config="component"
                           :scene="mode"
                />
            </template>
            </i-col>
        </Row>
    </div>
</template>
<script>
import uuid from "uuid/v4";
import mixin from "../mixins/template-mixin";

export default {
    name: "layout",
    mixins: [mixin],
    props: {
        config: {
            type: Object,
            required: true
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
            dropOptions: {
                animation: 200,
                group: {
                    name: "description",
                    pull: true,
                    put: true
                },
                sort: true,
                filter: ".template-delete",
                ghostClass: "ghost",
                scrollSensitivity: 15,
                scrollSpeed: 20
            },
            mode: "icon",
            withAnchorType: [
                "task-file-placeholder",
                "video-file-placeholder",
                "audio-file-placeholder",
                "text-file-placeholder",
            ]
        };
    },
    computed: {
        currentId: function () {
            return this.$store.state.template.currentModuleData.data.id;
        }
    },
    watch: {
        currentId: function (id) {
            $(".selected-module").removeClass("selected-module");
            $("[data-id=" + id + "]").addClass("selected-module");
        },
        scene: function (scene) {
            this.mode = scene;
        }
    },
    mounted () {
        this.mode = this.scene;
    },
    methods: {
        handleEnd (evt) {
            // 处理从布局容器内拖出到最外层时，元素重复的bug;
            let to = evt.to;
            let item = evt.item;
            let from = evt.from;
            $(".selected-module").removeClass("selected-module");
            $("[data-id=" + this.currentId + "]").addClass("selected-module");

            if (~to.className.indexOf("layout-list-draggable") || to === from) {
                return;
            }
            let path = item.getAttribute("path");
            this.$store.commit("deleteTemplateModule", path);
        },
        handleChoose (e) {
            let path = e.item.getAttribute("path");
            this.$store.commit("chooseModule", path);
        },
        handleChange (e) {
            if (e.added) {
                if (e.added.element.id === "") {
                    e.added.element.id = uuid();
                }
                // 提交后删除再添加可能导致anchor重复
                if (~this.withAnchorType.indexOf(e.added.element.type)) {
                    let type = '';
                    if (e.added.element.anchor === "") {
                        switch (e.added.element.type) {
                            case 'task-file-placeholder' : type = 'image'; break;
                            case 'audio-file-placeholder' : type = 'audio'; break;
                            case 'video-file-placeholder' : type = 'video'; break;
                        }
                        let counter = this.$store.state.template.placeholderCounter[type];
                        e.added.element.anchor = type + '_url' + (counter > 1 ? counter - 1 : '');
                    }
                }
            }
        }
    },
    components: {
        'draggable': () => import('vuedraggable'),
        // 引入所有模板组件 异步加载
        "form-group": () => import('./form-group.vue'),
        "single-input": () => import('./single-input.vue'),
        "multi-input": () => import('./multi-input.vue'),
        "form-radio": () => import('./form-radio.vue'),
        "audio-file-placeholder": () => import('./audio-placeholder.vue'),
        "external-link": () => import('./external-link.vue'),
        "form-checkbox": () => import('./form-checkbox.vue'),
        "form-select": () => import('./form-select.vue'),
        "form-upload": () => import('./form-upload.vue'),
        "task-file-placeholder": () => import('./image-placeholder.vue'),
        "key-point": () => import('./key-point.vue'),
        "key-point-group": () => import('./key-point-group.vue'),
        "show-img": () => import('./show-img.vue'),
        "show-text": () => import('./show-text.vue'),
        "video-file-placeholder": () => import('./video-placeholder.vue'),
        'text-file-placeholder': () => import('./text-placeholder.vue'),
        'layout': () => import('./layout.vue'),
        'ocr': () => import('./ocr.vue'),
        'tag': () => import('./tag.vue'),
        'data-is-valid': () => import('./data-is-valid.vue'),
        'image-label-tool': () => import('./image-label-tool.vue'),
    }
};
</script>
<style lang="scss">
.layout.instance-container {
  background-color: #edf0f6;
  margin: 0;
}
.children-con {
  background-color: #fff;
  & + .children-con {
    border-left: 2px solid #edf0f6;
  }
  .layout-list-draggable {
    min-height: 360px;
  }
}
</style>
