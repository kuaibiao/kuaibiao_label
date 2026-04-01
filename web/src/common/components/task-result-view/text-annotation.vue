<template>
    <div>
        <div class="result-view-header" slot="header">
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name" :width='400'>{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
            </div>
        </div>
        <div class="result-view-content">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="text-result-wrapper">
                <Row type="flex" justify="center" align="top">
                    <i-col span="16">
                        <Row type="flex" justify="center" align="middle" style="min-height:100%;">
                            <div class="data-container-wrapper" ref="textContainer">
                                {{ this.$t('operator_loading') }}
                            </div>
                        </Row>
                    </i-col>
                    <i-col span="8">
                        <result-item-annotation
                                :data="result.data || []"
                                :info="result.info || []"
                                :index="-1"
                                :user="workUser"
                        />
                    </i-col>
                </Row>
            </div>
        </div>
    </div>
</template>

<script>
    import EventBus from '@/common/event-bus';
    import api from '@/api';
    import util from '@/libs/util';
    import Mark from 'mark.js';

    export default {
        name: "text-annotation-result",
        props: {
            result: { // 作业结果
                type: Object,
                required: true,
            },
            projectId: { // 项目Id
                type: [String, Number],
                required: true,
            },
            dataId: { // 资源ID
                type: [String, Number],
                required: true,
            },
            dataInfo: { // 资源信息
                type: Object,
                required: true,
            },
            workUser: {
                type: Object,
                required: false,
            }
        },
        data () {
            return {
                loading: false,
                marker: null,
            };
        },
        mounted () {
            EventBus.$on('highlightRange', this.highlightRange);
            this.getTaskResource();
        },
        methods: {
            highlightRange (range) {
                // todo 文本超多的时候 高亮自动滚到到可视区域
                this.marker && this.marker.unmark({
                    done: () => {
                        this.marker.markRanges([range]);
                    }
                });
            },
            getTaskResource () {
                if (!this.marker) {
                    this.marker = new Mark(this.$refs.textContainer);
                }
                this.loading = true;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        data_id: this.dataId,
                        type: 'ori',
                    },
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                // this.$Message.destroy();
                                // this.$Message.error({
                                //     content: this.$t('tool_request_failed'),
                                //     duration: 2,
                                // });
                                // return;
                                resource = [['subject', {}]];
                            }
                            this.$nextTick(() => {
                                let html = '';
                                resource.forEach((item) => {
                                    let key = item[0];
                                    let value = item[1];
                                    value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                                    html += `<pre class="data-container">${value}</pre>`;
                                });
                                $('.text-result-wrapper .data-container-wrapper').html(html);
                            });
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
        },
        destroyed () {
            EventBus.$on('highlightRange', this.highlightRange);
        },
        components: {
            resultItemAnnotation: () => import('@/views/task-perform/components/text-annotation-result.vue'),
        }
    };
</script>

<style lang="scss">
    .result-view-header {
        line-height: 40px;
        display: flex;
        justify-content: center;
        align-items: center;

        .data-info {
            margin-left: -30px;
            margin-right: 20px;
        }
    }

    .result-view-content {
        overflow-y: auto;
        height: calc(100vh - 55px);
    }
</style>
