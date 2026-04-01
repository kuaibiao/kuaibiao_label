<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
      <div class="template-info" v-if="mode === 'icon'">
          <span class="bficonfont bf-icon-img"></span>
          <span class="template-name">{{$t('tool_pictures_show')}}</span>
      </div>
      <div class="template-delete" v-if="mode === 'edit'">
        <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
      </div>
      <div class="instance-container" v-if="mode !== 'icon'">
        <h2 class="instance-header">{{config.header}}: </h2>
        <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
        <div class="image-container" :class="config.imgSrc === ''? '': 'hasImage'"
             :style="'background-image:url('+ staticBase + '/images/template/icon-img@2x.png)'">
            <span v-if="config.imgSrc === ''">{{$t('template_show_img')}}</span>
            <img :src="config.imgSrc | formatUrl" v-else >
        </div>
      </div>
	</div>
</template>
<script>
import mixin from "../mixins/template-mixin";
import api from '@/api';
export default {
    name: "show-image",
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
    },
    methods: {
    },
};
</script>
<style lang="scss" scoped>
.image-container {
    text-align: center;
    padding-top: 90px;
    padding-bottom: 15px;
    background-repeat: no-repeat;
    background-position: center 15px;
    background-color: #edf0f5;
    overflow: auto;
    &.hasImage {
      padding-top: 15px;
      background: #fff;
      img {
          max-height: 320px;
      }
    }
}
</style>

