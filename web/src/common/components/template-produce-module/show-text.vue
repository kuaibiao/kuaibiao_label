<template>
  <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
      <div class="template-info" v-if="mode === 'icon'">
          <span class="bficonfont bf-icon-word"></span>
          <span class="template-name">{{$t('template_show_text')}}</span>
      </div>
      <div class="template-delete" v-if="mode === 'edit'">
        <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
      </div>
      <div class="instance-container" v-if="mode !== 'icon'" v-html="config.text">
      </div>
	</div>
</template>
<script>
import mixin from "../mixins/template-mixin";
export default {
    name: "show-text",
    mixins: [mixin],
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
    created () {},
    mounted () {
        this.mode = this.scene;
    },
    methods: {}
};
</script>

