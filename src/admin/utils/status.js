import { $t } from './i18n.js';

/**
 * Labels and badge colors for job post statuses and application statuses.
 * Colors use the Fluent badge palette (see _fluent.scss).
 */
const TONES = {
    // Application status (what the applicant sees)
    applied: 'blue',
    processing: 'amber',
    pending: 'purple',
    completed: 'green',
    // Internal status (the hiring team's pipeline)
    new: 'neutral',
    audited: 'blue',
    initial_rejected: 'red',
    interviewed: 'purple',
    potential: 'orange',
    short_listed: 'amber',
    hired: 'green',
    // Job post status
    publish: 'green',
    draft: 'neutral',
    future: 'blue',
    private: 'purple',
    trash: 'red'
};

// A function, so the labels are translated when they are shown, after the map is loaded
function postLabels() {
    return {
        publish: $t('Published'),
        draft: $t('Draft'),
        pending: $t('Pending review'),
        future: $t('Scheduled'),
        private: $t('Private'),
        trash: $t('Trash')
    };
}

function humanize(key) {
    const text = String(key || '').replace(/_/g, ' ');
    return text.charAt(0).toUpperCase() + text.slice(1);
}

/**
 * @param {string} type application | internal | post
 * @param {string} key  The stored status key
 */
export function statusLabel(type, key) {
    const vars = window.wpJobBoardsAdmin || {};
    if (type === 'application' && vars.applicationStatuses && vars.applicationStatuses[key]) {
        return vars.applicationStatuses[key];
    }
    if (type === 'internal' && vars.internalStatuses && vars.internalStatuses[key]) {
        return vars.internalStatuses[key];
    }
    const postLabelsByKey = type === 'post' ? postLabels() : {};
    if (postLabelsByKey[key]) {
        return postLabelsByKey[key];
    }
    return humanize(key);
}

export function statusTone(type, key) {
    if (type === 'post' && key === 'pending') {
        return 'amber';
    }
    return TONES[key] || 'neutral';
}
