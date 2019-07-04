<template>
    <div class="wppaymform_global">
        <div class="wpjobboard_main_nav">
            <span class="plugin-name">WPJobBoard</span>
            <router-link v-for="menuItem in topMenus" :key="menuItem.route" active-class="ninja-tab-active" exact :class="['ninja-tab']" :to="{ name: menuItem.route }">
                {{ menuItem.title }}
            </router-link>
        </div>
        <router-view></router-view>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'global_wrapper',
        data() {
            return {
                topMenus: []
            }
        },
        methods: {
            setTopmenu() {
                this.topMenus = this.applyFilters('wpf_top_level_menu', [
                    {
                        route: 'forms',
                        title: 'All Jobs'
                    },
                    {
                        route: 'entries',
                        title: 'All Applications'
                    },
                    {
                        route: 'general_settings',
                        title: 'Settings'
                    },
                    // {
                    //     route: 'support',
                    //     title: 'Support'
                    // }
                ])
            }
        },
        mounted() {
            this.setTopmenu();
        }
    }
</script>