<template>
    <div id="main" class="app-main">
        <router-view></router-view>
    </div>
</template>
<script>
    import util from '@/libs/util.js';
    export default {
        data () {
            return {
                theme: this.$store.state.app.themeColor,
                checkedUpdateTimerId: 0,
                updateTipsIsOpen: false,
            };
        },
        mounted () {
            setTimeout(() => {
                this.checkUpdate();
            }, 3000);
        },
        beforeDestroy () {
            clearTimeout(this.checkedUpdateTimerId);
        },
        methods: {
            checkUpdate () {
                util.checkUpdate(this);
                this.checkedUpdateTimerId = setTimeout(() => {
                    this.checkUpdate();
                }, 1000 * 60 * 5);
            }
        }
    };
</script>

<style>
html,body{
    width: 100%;
    height: 100%;
    background: #f0f0f0;
    overflow: hidden;
}
.app-main {
    width: 100%;
    height: 100%;
}
</style>
