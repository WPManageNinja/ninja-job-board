/**
 * Block editor additions for job posts:
 * - "Job details" panel: location, vacancies, experience, job type (registered post meta)
 * - "Ninja Job Board" panel: links to this job's applications, form and settings in the app
 */
const { registerPlugin } = wp.plugins;
const PluginDocumentSettingPanel = (wp.editor && wp.editor.PluginDocumentSettingPanel) || wp.editPost.PluginDocumentSettingPanel;
const { TextControl, SelectControl, ExternalLink } = wp.components;
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

function JobLinksPanel() {
    const { postType, postId, isNew } = useSelect((select) => ({
        postType: select('core/editor').getCurrentPostType(),
        postId: select('core/editor').getCurrentPostId(),
        isNew: select('core/editor').isEditedPostNew()
    }), []);

    if (postType !== 'wp_job_board' || !settings.canManageApp || !settings.appUrl) {
        return null;
    }

    const link = (route) => settings.appUrl + '#/edit-form/' + postId + '/' + route;

    return (
        <PluginDocumentSettingPanel name="wpjb-job-links" title={__('Ninja Job Board', 'ninja-job-board')}>
            {isNew ? (
                <p>{__('Save the job to manage its application form and applications.', 'ninja-job-board')}</p>
            ) : (
                <ul className="wpjb_job_links">
                    <li><ExternalLink href={link('form_entries')}>{__('Applications', 'ninja-job-board')}</ExternalLink></li>
                    <li><ExternalLink href={link('form-builder')}>{__('Application form', 'ninja-job-board')}</ExternalLink></li>
                    <li><ExternalLink href={link('settings/confirmation_settings')}>{__('Form settings', 'ninja-job-board')}</ExternalLink></li>
                    <li><ExternalLink href={link('email_settings')}>{__('Email notifications', 'ninja-job-board')}</ExternalLink></li>
                </ul>
            )}
        </PluginDocumentSettingPanel>
    );
}

registerPlugin('ninja-job-board-job-editor', {
    render: () => (
        <>
            <JobDetailsPanel />
            <JobLinksPanel />
        </>
    )
});
