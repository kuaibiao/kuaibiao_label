<template>
    <Row>
        <Col span="6">
            <Card>
                <div style="text-align:center">
                    <h1 style="color: #2d8cf0;cursor: pointer" @click="toSystemSetting">系统设置</h1>
                </div>
            </Card>
        </Col>
        <Col span="6">
            <Card>
                <div style="text-align:center">
                    <h1 style="color: #2d8cf0;cursor: pointer" @click="toRoleList">角色管理</h1>
                </div>
            </Card>
        </Col>
        <Col span="6">
            <Card>
                <div style="text-align:center">
                    <h1 style="color: #2d8cf0;cursor: pointer" @click="toPermissionList">权限管理</h1>
                </div>
            </Card>
        </Col>
    </Row>
</template>
<script>
    export default {
        methods: {
            toSystemSetting(){
                this.$router.push({name: 'system-setting'});
            },
            toRoleList(){
                this.$router.push({name: 'role-list'});
            },
            toPermissionList(){
                this.$router.push({name: 'permission-list'});
            },
        }
    }
</script>