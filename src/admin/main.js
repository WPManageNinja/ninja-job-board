import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
// Per-component paths: the package root would bundle all of Element Plus
import { ElLoading } from 'element-plus/es/components/loading/index.mjs';
import { ElMessage } from 'element-plus/es/components/message/index.mjs';
import { ElMessageBox } from 'element-plus/es/components/message-box/index.mjs';
import { ElNotification } from 'element-plus/es/components/notification/index.mjs';
import dayjs from 'dayjs';
import { $t, $_n, $_x } from './utils/i18n.js';
import { elementLocale } from './utils/elementLocale.js';
import { provideGlobalConfig } from 'element-plus/es/components/config-provider/index.mjs';

import 'element-plus/theme-chalk/base.css';
import 'element-plus/theme-chalk/el-loading.css';
import 'element-plus/theme-chalk/el-message.css';
import 'element-plus/theme-chalk/el-message-box.css';
import 'element-plus/theme-chalk/el-notification.css';
import '../scss/admin/app.scss';

import api from './utils/api';
import { printElement } from './utils/print';
import { getRoutes } from './routes';
import App from './App.vue';

const WPJobBoard = window.WPJobBoard;

const router = createRouter({
    history: createWebHashHistory(),
    routes: WPJobBoard.applyFilters('wpf_global_vue_routes', getRoutes()),
    linkActiveClass: 'active'
});

const app = createApp(App);

Object.assign(app.config.globalProperties, {
    $api: api,
    $notify: ElNotification,
    $message: ElMessage,
    $confirm: ElMessageBox.confirm,
    $msgbox: ElMessageBox,
    $printElement: printElement,
    $t,
    $_n,
    $_x,
    $showAjaxError(error) {
        const message = (error && error.message) ? error.message : $t('Something went wrong. Please try again.');
        ElNotification.error({ title: $t('Error'), message });
    },
    $setTitle(title) {
        // translators: %s is the title of the current admin screen, for example a job title
        document.title = $t('%s :: Ninja Job Board', title);
    },
    $ucFirst(string) {
        return string ? string.charAt(0).toUpperCase() + string.slice(1) : '';
    },
    // Per-browser UI preferences. Storage can be blocked, so failures are ignored.
    setStoreData(key, value) {
        try {
            window.localStorage.setItem('wpjobboard_' + key, value);
        } catch (e) {
            // Ignore
        }
    },
    getFromStore(key, defaultValue) {
        try {
            const value = window.localStorage.getItem('wpjobboard_' + key);
            return value ? value : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    $dateFormat(date, format = 'MMM DD, YYYY') {
        const dateObj = dayjs(date === undefined ? null : date);
        return dateObj.isValid() ? dateObj.format(format) : null;
    },
    applyFilters: WPJobBoard.applyFilters,
    addFilter: WPJobBoard.addFilter,
    addAction: WPJobBoard.addAction,
    doAction: WPJobBoard.doAction
});

app.directive('loading', ElLoading.directive);
provideGlobalConfig({ locale: elementLocale() }, app, true);

app.use(router);

WPJobBoard.app = app;
WPJobBoard.router = router;
WPJobBoard.doAction('wpjobboard_admin_app_init', app, router);

app.mount('#wpjobboardsapp');
