<template>
    <header class="wpjb_topbar">
        <router-link :to="{ name: 'forms' }" class="wpjb_brand">
            <img class="wpjb_brand_mark" :src="logoUrl" alt="" width="32" height="32">
            <span>Ninja Job Board</span>
        </router-link>
        <nav aria-label="Ninja Job Board">
            <ul class="wpjb_menu">
                <li v-for="item in menu" :key="item.route">
                    <router-link :to="{ name: item.route }"
                                 :class="['wpjb_menu_item', { 'is-active': isActive(item) }]">
                        {{ item.title }}
                    </router-link>
                </li>
            </ul>
        </nav>
    </header>
</template>

<script>
    export default {
        name: 'AppHeader',
        computed: {
            logoUrl() {
                return window.wpJobBoardsAdmin.assets_url + 'images/logo.svg';
            },
            menu() {
                // Each item: route name, title, and the route names that keep it highlighted
                return this.applyFilters('wpf_top_level_menu', [
                    { route: 'forms', title: this.$t('Jobs'), active: ['forms', 'edit-form'] },
                    { route: 'entries', title: this.$t('Applications'), active: ['entries'] },
                    { route: 'general_settings', title: this.$t('Settings'), active: ['settings'] }
                ]);
            }
        },
        methods: {
            isActive(item) {
                const path = this.$route.path;
                if (item.route === 'forms') {
                    return path === '/' || path.indexOf('/edit-form/') === 0;
                }
                if (item.route === 'entries') {
                    return path.indexOf('/entries') === 0;
                }
                if (item.route === 'general_settings') {
                    return path.indexOf('/settings') === 0;
                }
                return this.$route.name === item.route;
            }
        }
    }
</script>
