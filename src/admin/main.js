import moment from 'moment';
window.WPJobBoardBus = new window.WPJobBoard.Vue();

window.WPJobBoard.Vue.mixin({
    methods: {
        $t(str) {
            let transString = wpJobBoardsAdmin.i18n[str];
            if(transString) {
                return transString;
            }
            return str;
        },
        setStoreData(key, value) {
            if(window.localStorage) {
                localStorage.setItem("wppayforms_"+key, value);
            }
        },
        deleteStoreData(key) {
            if(window.localStorage) {
                localStorage.removeItem("wppayforms_"+key);
            }
        },
        $showAjaxError(error) {
            if(error.responseJSON && error.responseJSON.message) {
                this.$notify.error(error.responseJSON.message);
            } else if(error.responseText) {
                this.$notify.error(error.responseText);
            } else {
                this.$notify.error('Something is wrong when doing ajax request! Please try again');
            }
        },
        getFromStore(key, defaultValue) {
            if(window.localStorage) {
                let itemValue = localStorage.getItem('wppayforms_'+key);
                if(itemValue) {
                    return itemValue;
                }
            }
            return defaultValue;
        },
        applyFilters: window.WPJobBoard.applyFilters,
        addFilter: window.WPJobBoard.addFilter,
        addAction: window.WPJobBoard.addFilter,
        doAction: window.WPJobBoard.doAction,
        $get: window.WPJobBoard.$get,
        $adminGet: window.WPJobBoard.$adminGet,
        $adminPost: window.WPJobBoard.$adminPost,
        $post: window.WPJobBoard.$post
    },
    data(){
        return {
            has_pro: window.wpJobBoardsAdmin.has_pro,
            pro_purchase_url: 'https://wpmanageninja.com/wppayform-pro-wordpress-payments-form-builder/?utm_source=upgrade&utm_medium=url&utm_campaign=wpjobboard_upgrade'
        }
    },
    filters: {
        ucFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        dateFormat(date, format) {
            if(!format) {
                format = 'MMM DD, YYYY';
            }
            let dateString = (date === undefined) ? null : date;
            let dateObj = moment(dateString);
            return dateObj.isValid() ? dateObj.format(format) : null;
        }
    }
});

import {routes} from './routes'

const router = new window.WPJobBoard.Router({
    routes: window.WPJobBoard.applyFilters('wpf_global_vue_routes', routes),
    linkActiveClass: 'active'
});

import App from './App';

new window.WPJobBoard.Vue({
    el: '#wpjobboardsapp',
    render: h => h(App),
    router: router,
    mounted() {
        window.WPJobBoardBus.$on('site_title', (title) => {
            jQuery(document).attr("title", title + ' :: WPPayFrom');
        });
    }
});
