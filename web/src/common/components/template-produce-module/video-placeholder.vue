<template>
  <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type" :data-target="config.anchor">
      <div class="template-info" v-if="mode=== 'icon'">
          <span class="bficonfont bf-icon-video"></span>
          <span class="template-name">{{$t('template_video_file_placeholder')}}</span>
      </div>
      <div class="template-delete" v-if="mode=== 'edit'">
        <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
      </div>
      <div class="instance-container" v-if="mode!== 'icon'">
        <h2 class="instance-header">{{config.header}}</h2>
        <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
        <div class="file-placeholder" :style="'background-image:url('+ staticBase + '/images/template/avplaceholder.png)'"></div>
      </div>
	</div>
</template>
<script>
import mixin from "../mixins/template-mixin";
import api from '@/api';
export default {
    mixins: [mixin],
    name: 'video-file-placeholder',
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
    created () {},
    mounted () {
        this.mode = this.scene;
        if (this.mode === "edit") {
            this.$store.commit("updatePlaceHolderCounter", {
                type: "video",
                add: true,
            });
        }
    },
    destroyed () {
        if (this.mode === "edit") {
            this.$store.commit("updatePlaceHolderCounter", {
                type: "video",
                add: false
            });
        }
    },
    methods: {}
};
</script>
<style lang="scss" scoped>
 .file-placeholder {
        min-height: 220px;
        background-repeat: no-repeat;
        background-size: contain;
        background-position-x: center;
    }
</style>


