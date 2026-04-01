<template>
  <div class="module-editor">
    <div class="editor-item">
      <h4 class="editor-header">{{$t('template_image_basic_tool')}}</h4>
      <div class="support-shape-list">
        <div
          class="shape-item selected"
          v-for="shape in shapeList"
          @click="selectShape(shape)"
          :key="shape"
          :title="$t('operator_shape_' + shape )"
        >
          <div class="shape-image">
            <Icon
              :custom="`annotation annotation-${shapeIconName[shape]}`"
              size="40"
              :color="(module.supportShapeType && ~module.supportShapeType.indexOf(shape)) ? '#2d8cf0' : '#dcdee2'"
            />
          </div>
          <div class="shape-name">{{$t('operator_shape_' + shape )}}</div>
        </div>
      </div>
    </div>
    <hr>
    <div class="editor-item">
      <h4 class="editor-header">{{$t('template_image_special_tool')}}</h4>
      <div class="advance-editor">
          <div
            class="shape-item"
            @click="selectShape('rectS')"
            :title="$t('operator_shape_rectS')"
          >
            <div class="shape-image">
              <Icon
                :custom="`annotation annotation-${shapeIconName['rectS']}`"
                size="40"
                :color="(module.supportShapeType && ~module.supportShapeType.indexOf('rectS')) ? '#2d8cf0' : '#dcdee2'"
              />
            </div>
            
          </div>
          <div class="shape-config" :style="unSelectedStyle">
             <div class="shape-name">{{$t('operator_shape_rectS' )}}</div>
              <div class="shape-config-item"> 
                <span>{{$t('template_width')}}:</span>
                <InputNumber v-model="module.advanceTool.rectS.width"
                    @on-change="saveChange"
                    :min="4"
                    size="small" style="width:56px;"
                    :readonly ='!hasRectS'
                    :active-change= "false"/>
              </div>
              <div class="shape-config-item"> 
                <span>{{$t('template_height')}}: </span>
                <InputNumber v-model="module.advanceTool.rectS.height"
                    @on-change="saveChange"
                    :min="4"
                    size="small" style="width:56px;"
                    :readonly ='!hasRectS'
                    :active-change= "false"/>
              </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script>
//    bonepoint: "骨骼点"
//    closedcurve: "闭合曲线"
//    cuboid: "长方体"
//    line: "线"
//    pencilline: "钢笔线"
//    point: "点"
//    polygon: "多边形"
//    quadrangle: "四边形"
//    rect: "矩形"
//    splinecurve: "三次样条曲线"
//    trapezoid: "梯形"
//    triangle: "三角形"
//    unclosedpolygon: "折线"
//    circler  : "圆"
//    ellipse : "椭圆"
//    rectP : "矩形+点"
//    rects  '固定大小的矩形'

export default {
    name: 'image-label-tool-editor',
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
            shapeIconName: {
                'line': 'shape-line',
                'circler': 'shape-circler',
                'ellipse': 'shape-ellipse',
                'unclosedpolygon': 'cutoffline',
                'rect': 'shape-rectangle',
                'rectP': 'shape-rectp',
                'rectS': 'shape-rects',
                'polygon': 'shape-polygon',
                'trapezoid': 'shape-trapezoid',
                'triangle': 'shape-triangle',
                'quadrangle': 'shape-quadrangle',
                'cuboid': 'shape-cuboid',
                'bonepoint': 'shape-bonepoint',
                'point': 'shape-dot',
                'closedcurve': 'shape-closecurve',
                'splinecurve': 'shape-curve',
                'pencilline': 'shape-pencilline'
            },
            shapeList: [
                'line',
                'circler',
                'ellipse',
                'unclosedpolygon',
                'rect',
                'rectP',
                'polygon',
                'trapezoid',
                'triangle',
                'quadrangle',
                'cuboid',
                'bonepoint',
                'point',
                'closedcurve',
                'splinecurve',
                'pencilline'
            ]
        };
    },
    created () {
        this.module = this.config;
        if (Array.isArray(this.module.advanceTool)) {
          this.module.advanceTool = {
            rectS: {
              width: 4,
              height: 4
            }
          }
        }
    },
    watch: {
        config: {
            handler: function (config) {
                this.module = config;
            },
            deep: true,
        }
    },
    computed: {
        hasRectS: function() {
          return this.module.supportShapeType.indexOf('rectS') > -1;
        },
        unSelectedStyle: function() {
          return  {
            opacity: this.hasRectS ? 1: 0.4,
          }
        }
    },
    methods: {
        selectShape (shape) {
            let index = this.module.supportShapeType.indexOf(shape);
            if (~index) {
                this.module.supportShapeType.splice(index, 1);
            } else {
                this.module.supportShapeType.push(shape);
            }
            this.saveChange();
        },
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
@import "./style";

.support-shape-list {
  display: flex;
  flex-wrap: wrap;
  padding: 10px 0;
}
.shape-item {
    width: 72px;
    text-align: center;
    margin-bottom: 15px;
    cursor: pointer;
  }

.shape-image {
  width: 56px;
  height: 56px;
  margin: 0 auto;
  border-radius: 50%;
  background-color: #d7d7d7;
  position: relative;
  .annotation {
    &::before {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
    }
  }
}

.shape-name {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  margin-top: 10px;
  font-size: 14px;
  color: #333;
}
.advance-editor {
  display: flex;
  align-items: flex-end;
  padding-top: 10px;
  .shape-item {
    margin-bottom: 0;
  }
}
.shape-config {
  padding-left: 10px;
  .shape-config-item {
    display: inline-block;
    margin-top: 10px;
  }
}
</style>
