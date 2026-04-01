<template>
    <div>
        <Button type="text" icon="ios-search" :loading="loading" @click="handleClick"></Button>
    </div>
</template>
<script>
export default {
    data () {
        return {
            loading: false
        };
    },
    methods: {
        handleClick () {
            this.loading = true;
            setTimeout(() => {
                this.loading = false;
            }, 300);
        }
    }
};
</script>

