import Vue from './elements';
import Router from 'vue-router';
Vue.use(Router);

import { applyFilters, addFilter, addAction, doAction } from '@wordpress/hooks';

// These helpers run as Vue mixin methods, so they can not use `this`.
const withNonce = (options) => {
    options = options || {};
    options.wpjb_nonce = window.wpJobBoardsAdmin.nonce;
    return options;
};

export default class WPJobBoard {
    constructor() {
        this.applyFilters = applyFilters;
        this.addFilter = addFilter;
        this.addAction = addAction;
        this.doAction = doAction;
        this.Vue = Vue;
        this.Router = Router;
    }

    $get(options) {
        return window.jQuery.get(window.wpJobBoardsAdmin.ajaxurl, withNonce(options));
    }

    $adminGet(options) {
        options.action = 'wpjobboard_forms_admin_ajax';
        return window.jQuery.get(window.wpJobBoardsAdmin.ajaxurl, withNonce(options));
    }

    $post(options) {
        return window.jQuery.post(window.wpJobBoardsAdmin.ajaxurl, withNonce(options));
    }

    $adminPost(options) {
        options.action = 'wpjobboard_forms_admin_ajax';
        return window.jQuery.post(window.wpJobBoardsAdmin.ajaxurl, withNonce(options));
    }

    $getJSON(options) {
        return window.jQuery.getJSON(window.wpJobBoardsAdmin.ajaxurl, withNonce(options));
    }
}
