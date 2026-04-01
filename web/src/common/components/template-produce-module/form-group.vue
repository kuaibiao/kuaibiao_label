<template>
  <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
      <div class="template-info" v-if="mode=== 'icon'">
          <span class="bficonfont"><Icon type="ios-switch"></Icon></span>
          <span class="template-name">{{$t('template_form_group')}}</span>
      </div>
      <div class="template-delete" v-if="mode=== 'edit'">
        <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
      </div>
      <div class="instance-container" v-if="mode!== 'icon'">
        <h2 class="instance-header">{{config.header}}</h2>
        <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
            <div class="group-container">
              <template v-if="mode === 'edit'">
                 <draggable v-model="config.children"
                       tag="div"
                       v-bind="dropOptions"
                       @choose="handleChoose"
                       @change="handleChange"
                       @end="handleEnd"
                       class="group-list-draggable"
                 >
                    <component :is="component.type"
                          v-for="(component, index) in config.children" :key="index"
                          :path="path + 'children,'+index +','"
                          :config="component"
                          :scene="mode"
                    >
                </component>
                </draggable>
            </template>
            <template v-else>
              <component :is="component.type"
                    v-for="(component, index) in config.children" :key="index"
                    :path="path + 'children,'+index +','"
                    :config="component"
                    :scene="mode"
                />
            </template>
            </div>
      </div>
	</div>
</template>
<script>
import uuid from "uuid/v4";
import mixin from "../mixins/template-mixin";
export default {
    mixins: [mixin],
    name: "form-group",
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
                "text-file-placeholder"
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
    created () {},
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

            // fix 布局元素内拖出后导致的 bug

            let curPath = $("[data-id=" + this.currentId + "]").attr("path");
            this.$store.commit("chooseModule", curPath);
            // if (~to.className.indexOf("layout-list-draggable") || to === from) {

            // }
            // let path = item.getAttribute("path");
            // this.$store.commit("deleteTemplateModule", path);
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
                    let type = "";
                    if (e.added.element.anchor === "") {
                        switch (e.added.element.type) {
                            case "task-file-placeholder":
                                type = "image";
                                break;
                            case "audio-file-placeholder":
                                type = "audio";
                                break;
                            case "video-file-placeholder":
                                type = "video";
                                break;
                            case "text-file-placeholder":
                                type = "text";
                                break;
                        }
                        let counter = this.$store.state.template.placeholderCounter[type];
                        e.added.element.anchor =
              type + "_url" + (counter > 1 ? counter - 1 : "");
                    }
                }
            }
        }
    },
    components: {
        draggable: () => import("vuedraggable"),
        // 引入所有模板组件
        "form-group": () => import("./form-group.vue"),
        "single-input": () => import("./single-input.vue"),
        "multi-input": () => import("./multi-input.vue"),
        "form-radio": () => import("./form-radio.vue"),
        "audio-file-placeholder": () => import("./audio-placeholder.vue"),
        "external-link": () => import("./external-link.vue"),
        "form-checkbox": () => import("./form-checkbox.vue"),
        "form-select": () => import("./form-select.vue"),
        "form-upload": () => import("./form-upload.vue"),
        "task-file-placeholder": () => import("./image-placeholder.vue"),
        "key-point": () => import("./key-point.vue"),
        "show-img": () => import("./show-img.vue"),
        "show-text": () => import("./show-text.vue"),
        "video-file-placeholder": () => import("./video-placeholder.vue"),
        "text-file-placeholder": () => import("./text-placeholder.vue"),
        'data-is-valid': () => import('./data-is-valid.vue'),
        'image-label-tool': () => import('./image-label-tool.vue'),
        layout: () => import("./layout.vue"),
        ocr: () => import("./ocr.vue"),
        tag: () => import("./tag.vue")
    }
};
</script>
<style lang="scss">
.group-container {
    background-color: #fff;
    border: 2px solid #edf0f6;
    .group-list-draggable {
       min-height: 240px;
    }
}
</style>

