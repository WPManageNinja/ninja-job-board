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

const POST_LABELS = {
    publish: 'Published',
    draft: 'Draft',
    pending: 'Pending review',
    future: 'Scheduled',
    private: 'Private',
    trash: 'Trash'
};

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
    if (type === 'post' && POST_LABELS[key]) {
        return POST_LABELS[key];
    }
    return humanize(key);
}

export function statusTone(type, key) {
    if (type === 'post' && key === 'pending') {
        return 'amber';
    }
    return TONES[key] || 'neutral';
}
