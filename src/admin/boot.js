/**
 * Loads before the admin app. Extensions can use window.WPJobBoard to add filters and actions
 * (for example extra routes or menu items) before the app starts.
 */
import { createHooks } from '@wordpress/hooks';

const hooks = createHooks();

window.WPJobBoard = {
    hooks,
    applyFilters: hooks.applyFilters,
    addFilter: hooks.addFilter,
    addAction: hooks.addAction,
    doAction: hooks.doAction
};
