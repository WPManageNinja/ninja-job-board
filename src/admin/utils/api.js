/**
 * REST client for ninja-job-board/v1 with WordPress cookie auth (the wp_rest nonce goes with every request).
 * Methods return a Promise with the response JSON; a failed request rejects with { status, message, data }.
 */
import { $t } from './i18n.js';

const config = () => window.wpJobBoardsAdmin.rest;

// Serialize nested params like jQuery.param(): a[b][]=1
function toQuery(params, prefix = '') {
    const parts = [];
    Object.keys(params || {}).forEach((key) => {
        const value = params[key];
        const name = prefix ? `${prefix}[${key}]` : key;
        if (value === undefined || value === null) {
            return;
        }
        if (typeof value === 'object') {
            parts.push(toQuery(value, name));
        } else {
            parts.push(`${encodeURIComponent(name)}=${encodeURIComponent(value)}`);
        }
    });
    return parts.filter(Boolean).join('&');
}

function buildUrl(path, params) {
    // rest_url() can be "/wp-json/ns" or "/?rest_route=/ns" (plain permalinks)
    let url = config().root.replace(/\/$/, '') + path;
    const query = toQuery(params);
    if (query) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + query;
    }
    return url;
}

async function request(method, path, data) {
    const headers = { 'X-WP-Nonce': config().nonce };
    const options = { method: method === 'GET' ? 'GET' : 'POST', headers, credentials: 'same-origin' };

    // Some servers block DELETE, so send it as POST with the method override header
    if (method !== 'GET' && method !== 'POST') {
        headers['X-HTTP-Method-Override'] = method;
    }

    let url;
    if (method === 'GET') {
        url = buildUrl(path, data);
    } else {
        url = buildUrl(path);
        if (data instanceof FormData) {
            options.body = data;
        } else if (data) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (e) {
        throw { status: 0, message: $t('Network error. Please check your connection and try again.'), data: {} };
    }

    let json = null;
    try {
        json = await response.json();
    } catch (e) {
        json = null;
    }

    if (!response.ok) {
        throw {
            status: response.status,
            message: (json && json.message) || response.statusText || $t('Something went wrong. Please try again.'),
            data: (json && json.data) || {}
        };
    }

    return json;
}

export default {
    get: (path, params) => request('GET', path, params),
    post: (path, data) => request('POST', path, data),
    delete: (path, data) => request('DELETE', path, data),
    upload: (path, formData) => request('POST', path, formData),
    /**
     * The nonce goes in the query string because the browser can not send headers.
     */
    downloadUrl: (path, params = {}) => buildUrl(path, { ...params, _wpnonce: config().nonce })
};
