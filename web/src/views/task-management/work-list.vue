<template>
    <div>
        <div class="layout">
            <ButtonGroup style="position:relative;z-index:4">
                <Button :type="currentIndex == 'all' ? 'primary' : 'default'" @click="tabClick('all')">{{$t('admin_all_job')}}</Button>
                <Button :type="currentIndex == '1' ? 'primary' : 'default'" @click="tabClick('1')">{{$t('admin_unclaimed')}}</Button>
                <Button :type="currentIndex == '2' ? 'primary' : 'default'" @click="tabClick('2')">{{$t('admin_active')}}</Button>
                <Button :type="currentIndex == '3' ? 'primary' : 'default'" @click="tabClick('3')">{{$t('admin_submitted')}}</Button>
                <Button :type="currentIndex == '4' ? 'primary' : 'default'" @click="tabClick('4')">{{$t('admin_passed')}}</Button>
                <Button :type="currentIndex == '5' ? 'primary' : 'default'" @click="tabClick('5')">{{$t('admin_rejected')}}</Button>
                <Button v-if="step_type != '0'" :type="currentIndex == '6' ? 'primary' : 'default'" @click="tabClick('6')">{{$t('admin_rework_work')}}</Button>
                <Button :type="currentIndex == '7' ? 'primary' : 'default'" @click="tabClick('7')">{{$t('admin_difficult_work')}}</Button>
                <Button :type="currentIndex == '8' ? 'primary' : 'default'" @click="tabClick('8')">{{$t('admin_expired')}}</Button>
            </ButtonGroup>
        </div>
        <div>
            <component :is="currentView" :image_label="image_label" :step_type="step_type" :task_view="task_view" :templateInfo="templateInfo"></component>
        </div>
    </div>
</template>
<script>
import unresive from "./components/work_unresive";
import doing from "./components/work_doing";
import submit from "./components/work_submit";
import passed from "./components/work_passed";
import refused from "./components/work_refused";
import revised from "./components/work_revised";
import defficulty from "./components/work_defficulty";
import notpass from "./components/work_notpass";
import all from "./components/work_all";
export default {
    props: {
        image_label: {
            type: String
        },
        step_type: {
            type: String
        },
        task_view: {
            type: String
        },
        templateInfo: {
            type: Array
        },
    },
    data () {
        return {
            currentIndex: this.$route.params.index || 'all',
            ViewMap: {
                all: all,
                1: unresive,
                2: doing,
                3: submit,
                4: passed,
                5: refused,
                6: revised,
                7: defficulty,
                8: notpass
            },
        };
    },
    computed: {
        currentView () {
            return this.ViewMap[this.$route.params.index];
        }
    },
    methods: {
        tabClick (index) {
            this.$router.push({
                name: 'task-management-detail',
                params: {
                    id: this.$route.params.id,
                    tab: 'work-list',
                    index: index
                },
            });
            this.currentIndex = index;
        }
    },
    components: {
        all,
        unresive,
        doing,
        submit,
        passed,
        refused,
        revised,
        defficulty,
        notpass
    }
};
</script>
<style scoped>
.layout{
    margin-top: 20px;
    position: relative;
    background: #ffffff;
    padding: 20px 0 0 20px
}
</style>


