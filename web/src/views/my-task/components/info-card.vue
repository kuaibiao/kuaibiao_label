<template>
    <Card>  
        <p slot="title" style="color:#999;font-size:15px;">
            {{item.title}}
        </p>
        <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-poptip">
            <Icon type="ios-help-circle-outline" class="card-help"/>
            <div slot="content">
                <p v-for="(value,index) in item.dasc" :key="index">{{value}}</p>
            </div>
        </Poptip>
        <Row type="flex" align="middle" justify="space-around" style="height:100px;">
            <div :class="hoverClass(value.skip)" class="card-info" v-for="(value,index) in item.info" :key="index" @click="getDetails(value.status)">
                <Tooltip  placement="top-start" v-if="value.poptip.length > 0">
                    <div slot="content">
                        <p v-for="(item,index) in value.poptip" :key="index">{{item}}</p>
                    </div>
                    <span class="infor-intro-icon"></span>
                    <span>{{value.name}}</span>
                    <p style="font-size:30px;" class="padding-top-10">{{value.num}}</p>
                </Tooltip >
                <div v-if="value.poptip.length == 0">
                    <span class="infor-intro-icon"></span>
                    <span>{{value.name}}</span>
                    <p style="font-size:30px;" class="padding-top-10">{{value.num}}</p>
                </div>
            </div>
        </Row>
    </Card>
</template>

<script>
export default {
    props: {
        item: {
            type: Object
        }
    },
    data () {
        return {
            
        }
    },
    computed: {
        hoverClass () {
            return function (skip) {
                if (skip == 1) {
                    return "card-info-hover"
                }
            }
        }
    },
    methods: {
        getDetails (status) {
            if (status != "-1") {
                this.$emit("show-detail",status)
            }
        }
    }

}
</script>

<style>

</style>