// Block editor additions for job posts: the "Job details" meta panel and the job tab bar.
const { registerPlugin } = wp.plugins;
const PluginDocumentSettingPanel = (wp.editor && wp.editor.PluginDocumentSettingPanel) || wp.editPost.PluginDocumentSettingPanel;
const { TextControl, SelectControl } = wp.components;
const { createPortal, useEffect, useState } = wp.element;
const { useSelect } = wp.data;
const { useEntityProp } = wp.coreData;
const { __ } = wp.i18n;

const settings = window.wpJobBoardJobEditor || { jobTypes: [], appUrl: '', canManageApp: false };

function JobDetailsPanel() {
    const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);
    const [meta, setMeta] = useEntityProp('postType', 'wp_job_board', 'meta');

    if (postType !== 'wp_job_board' || !meta) {
        return null;
    }

    const update = (key) => (value) => setMeta({ ...meta, [key]: value });

    return (
        <PluginDocumentSettingPanel name="wpjb-job-details" title={__('Job details', 'ninja-job-board')}>
            <SelectControl
                label={__('Job type', 'ninja-job-board')}
                value={meta.job_type || ''}
                options={[{ value: '', label: __('Select job type', 'ninja-job-board') }].concat(settings.jobTypes)}
                onChange={update('job_type')}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
            <TextControl
                label={__('Location', 'ninja-job-board')}
                placeholder={__('For example: Remote, or Dhaka', 'ninja-job-board')}
                value={meta.job_location || ''}
                onChange={update('job_location')}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
            <TextControl
                label={__('Number of vacancies', 'ninja-job-board')}
                type="number"
                min={0}
                value={meta.job_vacancies || ''}
                onChange={update('job_vacancies')}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
            <TextControl
                label={__('Experience', 'ninja-job-board')}
                placeholder={__('For example: 1-2 years', 'ninja-job-board')}
                value={meta.job_experince_text || ''}
                onChange={update('job_experince_text')}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
        </PluginDocumentSettingPanel>
    );
}

// The app's job tab bar, above the editor toolbar. The classic editor prints it in PHP (Menu::pushJobNav).
function JobTabBar() {
    const { postType, postId } = useSelect((select) => ({
        postType: select('core/editor').getCurrentPostType(),
        postId: select('core/editor').getCurrentPostId()
    }), []);
    const [container, setContainer] = useState(null);

    const show = postType === 'wp_job_board' && settings.canManageApp && settings.appUrl && postId;

    useEffect(() => {
        if (!show) {
            return undefined;
        }

        const node = document.createElement('div');
        node.className = 'wpjb_editor_nav_slot';

        // The editor renders its skeleton after the plugins, so wait for the header
        const mount = () => {
            const header = document.querySelector('.interface-interface-skeleton__header');
            if (!header || !header.parentNode) {
                return false;
            }
            header.parentNode.insertBefore(node, header);
            setContainer(node);
            return true;
        };

        let observer = null;
        if (!mount()) {
            observer = new MutationObserver(() => {
                if (mount()) {
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        return () => {
            if (observer) {
                observer.disconnect();
            }
            node.remove();
            setContainer(null);
        };
    }, [show]);

    if (!show || !container) {
        return null;
    }

    const link = (route) => settings.appUrl + '#/edit-form/' + postId + '/' + route;
    const tabs = [
        { route: 'form_entries', title: __('Applications', 'ninja-job-board') },
        { route: 'form-builder', title: __('Application Form', 'ninja-job-board') },
        { route: 'settings/confirmation_settings', title: __('Form Settings', 'ninja-job-board') },
        { route: 'email_settings', title: __('Email Notifications', 'ninja-job-board') },
        { route: 'email_broadcast', title: __('Email Broadcast', 'ninja-job-board') }
    ];

    return createPortal(
        <nav className="wpjb_edit_nav is-block-editor" aria-label={__('Job sections', 'ninja-job-board')}>
            <ul className="wpjb_edit_tabs">
                {tabs.map((tab) => (
                    <li key={tab.route}>
                        <a className="wpjb_edit_tab" href={link(tab.route)}>{tab.title}</a>
                    </li>
                ))}
                <li>
                    <span className="wpjb_edit_tab is-active" aria-current="page">{__('Edit job post', 'ninja-job-board')}</span>
                </li>
            </ul>
        </nav>,
        container
    );
}

registerPlugin('ninja-job-board-job-editor', {
    render: () => (
        <>
            <JobDetailsPanel />
            <JobTabBar />
        </>
    )
});
