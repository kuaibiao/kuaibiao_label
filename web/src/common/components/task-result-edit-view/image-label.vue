<template>
    <div>
        <div slot="header" class="edit-modal-header">
            <task-progress
                    :total="0"
                    :current="0"
                    :timeout="timeout"
                    :noticeAble="false"
            ></task-progress>
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <div class="edit-btn-group">
                <Button type="primary" size="small" @click="submitEditTask()">{{$t('tool_submit')}}</Button>
                <Button type="info" size="small" @click="editModal = false">{{$t('tool_cancel')}}</Button>
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        <code>X </code> {{$t('tool_label_mode')}}<br>
                        <code>D </code> {{$t('tool_two_modes')}}<br>
                        <code>G </code> {{$t('tool_curve')}}<br>
                        <code>H </code> {{$t('tool_closed_curve')}}<br>
                        <code>P </code> {{$t('tool_line')}}<br>
                        <code>R </code> {{$t('tool_rectangle')}}<br>
                        <code>T </code> {{$t('tool_polygon_frame')}}<br>
                        <code>U</code>  {{$t('tool_quadrilateral')}}<br>
                        <code>F</code> {{$t('tool_polyline')}}<br>
                        <code>Y</code>  {{$t('tool_cuboid')}}<br>
                        <code>I</code> {{$t('tool_trapezoid')}}<br>
                        <code>O</code> {{$t('tool_triangle')}}<br>
                        <code>J</code> {{$t('tool_select_point')}}<br>
                        <code>K </code> {{$t('tool_auxiliary_line')}}<br>
                        <code>M </code> {{$t('tool_switch_label')}}<br>
                        <code>E </code> {{$t('tool_press_lift')}}<br>
                        <code>C </code> {{$t('tool_narrow_picture')}}<br>
                        <code>V </code> {{$t('tool_zoom_picture')}}<br>
                        <code>B </code> {{$t('tool_diaplasis')}}<br>
                        <code>< </code> {{$t('tool_tilt_left')}} <code>shift + <</code> {{$t('tool_greatly')}}<br>
                        <code>> </code> {{$t('tool_tilt_right')}} <code>shift + ></code> {{$t('tool_greatly')}}<br>
                        <code>? </code> {{$t('tool_angle_reset')}}<br>
                        <code>N </code> {{$t('tool_polygon_share_N')}}<br>
                        <code>: </code> {{$t('tool_side_share')}}<br>
                        <code>= </code> {{$t('tool_rect_size')}}<br>
                        <code>A </code> {{$t('tool_switch_mask')}}<br>
                        <code> ESC </code> {{$t('tool_cancel_mark')}}<br>
                        <code>Alt +{{$t('tool_left_mouse')}} </code> <br>&nbsp;&nbsp;{{$t('tool_key_point')}}<br>&nbsp;&nbsp;{{$t('tool_delete_group')}}<br>
                        <code>UP </code> <code>Down </code> {{$t('tool_switch_picture')}}<br>
                        <code>{{$t('tool_right_mouse')}} </code> {{$t('tool_delete_selection_label')}}<br>
                        <code>Shift + {{$t('tool_right_mouse')}} </code> {{$t('tool_delete_all_in_adjust_mode')}}
                    </div>
                </Tooltip>
            </div>
        </div>
        <template-view
                :config="templateInfo"
                scene="execute"
                ref="templateView"
                v-if="editModal"
        >
        </template-view>
    </div>
</template>

<script>
    import api from '@/api';
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import EventBus from '@/common/event-bus';
    import TemplateView from '../template-view/index';
    import TaskInfo from 'src/views/task-perform/components/task-info.vue';
    import TaskProgress from 'src/views/task-perform/components/taskprogress.vue';

    export default {
        name: "image-label",
        props: {
            templateInfo: {
                type: Array,
                require: true,
            },
            taskInfo: {
                type: Object,
                require: true,
            },
            taskItem: {
                type: Object,
                require: true,
            }
        },
        computed: {
            taskItemInfo() {
                return this.$t('tool_job_id') + ':' + this.taskItem.data.id;
            },
            taskItemInfoMore() {
                let parentWorks = this.taskItem.parentWorks;
                let taskData = this.taskItem.data;
                this.taskItemInfoMore = {
                    ...this.taskInfo,
                    dataName: taskData.name,
                    dataId: taskData.id,
                    user: (parentWorks && parentWorks[0] && parentWorks[0].user) || {}
                };
            }
        },
        components: {
            TemplateView,
            TaskProgress,
            TaskInfo,
        }
    };
</script>

<style scoped>

</style>
