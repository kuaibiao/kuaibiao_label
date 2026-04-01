//列表左侧操作按钮组件
<template>
    <div id="pro-dropdown">
        <span v-if="statusMap[status]"> 
            <Button 
                size="small"
                style="margin: 3px" 
                type="primary" 
                v-if="status && ((status == '1') || (status == '2') || (status == '3') || (status == '4'))" 
                @click="configurationProject"
                >{{$t('project_configuration')}}</Button>
            <Button 
                size="small"
                style="margin: 3px" 
                v-if="status && (status == '0')" 
                type="primary"
                @click="editProject" 
                >{{$t('project_edit')}}</Button>
            <Button 
                size="small"
                style="margin: 3px" 
                v-if="status && ((status == '0') || (status == '7') || (status == '1'))" 
                type="default" 
                @click="deleteProject" 
                >{{$t('project_delete')}}</Button>
            <Dropdown
                v-if="(status != '0') && (statusMap[status].length > 0)"
                style="margin: 3px"
                transfer 
                @on-click="handleClick">
                <Button size="small">
                    {{$t('project_operation')}}
                    <Icon type="md-arrow-dropdown"></Icon>
                </Button>
                <DropdownMenu
                    slot="list">
                    <DropdownItem
                        v-for="(item, index) in statusMap[status]" 
                        :name="item.action"
                        :key="index">{{item.text}}</DropdownItem>
                </DropdownMenu>
            </Dropdown>
        </span>
    </div>
</template>

<script>
import api from '@/api';
export default {
    name: 'project-op',
    props: {
        status: {
            type: String,
            required: true
        },
        projectId: {
            type: String,
            required: true
        },
        template_id: {
            type: String,
            required: true
        },
    },
    data () {
        const statusMap = {
            // 发布中
            '0': [],
            '1': [// 审核中
                // {
                //     text: this.$t('project_configuration'),
                //     action: 'configuration'
                // },
                // {
                //     text: this.$t('project_edit'),
                //     action: 'edit'
                // },
                // {
                //     text: this.$t('project_delete'),
                //     action: 'delete'
                // },
            ],
            '2': [// 作业准备中
                // {
                //     text: this.$t('project_edit'),
                //     action: 'edit'
                // },
                // {
                //     text: this.$t('project_copy'),
                //     action: 'copy'
                // },
                // {
                //     text: this.$t('project_pause'),
                //     action: 'pause'
                // },
                // {
                //     text: this.$t('project_configuration'),
                //     action: 'configuration'
                // },
                // {
                //     text: this.$t('project_complete'),
                //     action: 'finish'
                // },
            ],
            '3': [// 作业中
                // {
                //     text: this.$t('project_edit'),
                //     action: 'edit'
                // },
                {
                    text: this.$t('project_copy'),
                    action: 'copy'
                },
                {
                    text: this.$t('project_pause'),
                    action: 'pause'
                },
                // {
                //     text: this.$t('project_configuration'),
                //     action: 'configuration'
                // },
                {
                    text: this.$t('project_complete'),
                    action: 'finish'
                },
            ],
            '4': [// 已暂停
                // {
                //     text: this.$t('project_edit'),
                //     action: 'edit'
                // },
                {
                    text: this.$t('project_copy'),
                    action: 'copy'
                },
                {
                    text: this.$t('project_recover'),
                    action: 'recover'
                },
                // {
                //     text: this.$t('project_configuration'),
                //     action: 'configuration'
                // },
                {
                    text: this.$t('project_delete'),
                    action: 'delete'
                },
            ],
            '6': [// 已完成
                {
                    text: this.$t('project_copy'),
                    action: 'copy'
                },
                {
                    text: this.$t('project_restart'),
                    action: 'reopen'
                },
                {
                    text: this.$t('project_delete'),
                    action: 'delete'
                },
            ],
        };
        return {
            statusMap: statusMap
        };
    },
    methods: {
        handleClick (name) {
            let action = name;
            let projectId = this.projectId;
            if (action == 'configuration') {
                this[action + 'Project']({
                    projectId: projectId,
                });
            } else {
                this[action + 'Project']({
                    projectId: projectId
                });
            }
        },
        copyProject ({ projectId }) {
            this.$emit('copy-project', projectId);
        },
        editProject ({ projectId }) {
            // 去设置项目信息，根据步骤信息
            this.$router.push({
                name: 'project-create',
                params: {
                    id: projectId || this.projectId
                }
            });
        },
        configurationProject () {
            // 去配置项目信息，根据步骤信息
            this.$router.push({
                name: 'project-create',
                params: {
                    id: this.projectId,
                }
            });
        },
        editTemplateProject ({ projectId }) {
            this.$router.push({
                name: 'template-edit',
                params: {
                    id: this.template_id,
                }
            });
        },
        detailProject ({ projectId }) {
            this.$router.push({
                name: 'project-detail',
                params: {
                    id: projectId,
                    tab: 'overview'
                },
            });
        },
        recordProject ({projectId}) {
            this.$router.push({
                name: 'operation-record',
                params: {
                    id: this.projectId
                }
            });
        },
        pauseProject ({ projectId }) {
            this.$emit('pause-project', projectId);
        },
        recoverProject ({ projectId }) {
            this.$emit('recover-project', projectId);
        },
        stopProject ({ projectId }) {
            this.$emit('stop-project', projectId);
        },
        reopenProject ({ projectId }) {
            this.$emit('reopen-project', projectId);
        },
        deleteProject ({ projectId }) {
            this.$emit('delete-project', this.projectId);
        },
        finishProject ({projectId}) {
            this.$emit('finish-project', projectId);
        }
    }
};
</script>

<style scoped>
#pro-dropdown .ivu-dropdown-menu {
    min-width: 0
}
</style>
