import JobPicker, { isInJobPost } from '../shared/JobPicker.jsx';

const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, CheckboxControl, Placeholder, Disabled } = wp.components;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

const FIELDS = [
    { key: 'type', label: __('Job type', 'ninja-job-board') },
    { key: 'location', label: __('Location', 'ninja-job-board') },
    { key: 'vacancies', label: __('Number of vacancies', 'ninja-job-board') },
    { key: 'experience', label: __('Experience', 'ninja-job-board') },
    { key: 'posted', label: __('Posted date', 'ninja-job-board') }
];

export default function Edit({ attributes, setAttributes, name, context }) {
    const { jobId, fields } = attributes;
    const picker = <JobPicker value={jobId} context={context} onChange={(value) => setAttributes({ jobId: value })} />;
    const hasJob = jobId > 0 || isInJobPost(context);

    const toggle = (key, checked) => {
        const next = checked ? fields.concat(key) : fields.filter((field) => field !== key);
        // Keep the original order
        setAttributes({ fields: FIELDS.map((field) => field.key).filter((field) => next.indexOf(field) !== -1) });
    };

    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Job details settings', 'ninja-job-board')}>
                    {picker}
                    <fieldset className="wpjb_block_fields">
                        <legend>{__('Show', 'ninja-job-board')}</legend>
                        {FIELDS.map((field) => (
                            <CheckboxControl
                                key={field.key}
                                label={field.label}
                                checked={fields.indexOf(field.key) !== -1}
                                onChange={(checked) => toggle(field.key, checked)}
                                __nextHasNoMarginBottom
                            />
                        ))}
                    </fieldset>
                </PanelBody>
            </InspectorControls>
            {hasJob ? (
                <Disabled>
                    <ServerSideRender
                        block={name}
                        attributes={attributes}
                        urlQueryArgs={context && context.postId ? { post_id: context.postId } : {}}
                    />
                </Disabled>
            ) : (
                <Placeholder
                    icon="id-alt"
                    label={__('Job Details', 'ninja-job-board')}
                    instructions={__('Choose the job whose details you want to show.', 'ninja-job-board')}
                >
                    {picker}
                </Placeholder>
            )}
        </div>
    );
}
