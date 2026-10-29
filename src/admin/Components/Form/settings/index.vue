<template>
    <div class="wpjb_settings_layout">
        <nav class="wpjb_settings_nav" :aria-label="$t('Settings')">
            <router-link
                v-for="formMenu in form_menus"
                :key="formMenu.route"
                :to="{ name: formMenu.route, params: { form_id: form_id } }"
                :class="{ 'is-active': $route.name === formMenu.route }">
                <el-icon v-if="typeof formMenu.icon !== 'string'"><component :is="formMenu.icon" /></el-icon>
                <i v-else :class="formMenu.icon"></i>
                <span>{{ formMenu.title }}</span>
            </router-link>
        </nav>
        <main class="wpjb_settings_main">
            <router-view :form_id="form_id"></router-view>
        </main>
    </div>
</template>

<script>
    import { markRaw } from 'vue';
    import { CircleCheck, Brush, Calendar } from '@element-plus/icons-vue';

    export default {
        name: 'form_settings',
        data() {
            return {
                form_id: this.$route.params.form_id,
                form_menus: [
                    {
                        route: 'confirmation_settings',
                        title: this.$t('Confirmation'),
                        icon: markRaw(CircleCheck)
                    },
                    {
                        route: 'design_options',
                        title: this.$t('Form design'),
                        icon: markRaw(Brush)
                    },
                    {
                        route: 'scheduling_settings',
                        title: this.$t('Scheduling & restrictions'),
                        icon: markRaw(Calendar)
                    }
                ]
            }
        }
    }
</script>
