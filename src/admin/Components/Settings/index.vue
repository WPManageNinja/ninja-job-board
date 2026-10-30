<template>
    <div class="wpjb_settings_layout">
        <nav class="wpjb_settings_nav" :aria-label="$t('Settings')">
            <router-link
                v-for="formMenu in form_menus"
                :key="formMenu.route"
                :to="{ name: formMenu.route }"
                :class="{ 'is-active': $route.name === formMenu.route }">
                <el-icon v-if="typeof formMenu.icon !== 'string'"><component :is="formMenu.icon" /></el-icon>
                <i v-else :class="formMenu.icon"></i>
                <span>{{ formMenu.title }}</span>
            </router-link>
        </nav>
        <main class="wpjb_settings_main">
            <router-view></router-view>
        </main>
    </div>
</template>

<script>
    import { markRaw } from 'vue';
    import { Setting, Tools } from '@element-plus/icons-vue';

    export default {
        name: 'global_settings',
        data() {
            return {
                form_menus: []
            }
        },
        methods: {
            setMenu() {

                let menu = [
                    {
                        route: 'general_settings',
                        title: this.$t('General'),
                        icon: markRaw(Setting)
                    },
                    {
                        route: 'tools',
                        title: this.$t('Import & export'),
                        icon: markRaw(Tools)
                    }
                ];
                this.form_menus = this.applyFilters('global_settings_menu', menu);
            }
        },
        mounted() {
            this.setMenu();
        }
    }
</script>
