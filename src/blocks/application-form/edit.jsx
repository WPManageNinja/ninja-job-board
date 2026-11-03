import JobPicker, { isInJobPost } from '../shared/JobPicker.jsx';

const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, Placeholder, Disabled } = wp.components;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

export default function Edit({ attributes, setAttributes, name, context }) {
    const { jobId, showTitle } = attributes;
    const picker = <JobPicker value={jobId} context={context} onChange={(value) => setAttributes({ jobId: value })} />;
    const hasJob = jobId > 0 || isInJobPost(context);

    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Application form settings', 'ninja-job-board')}>
                    {picker}
                    <ToggleControl
                        label={__('Show the job title above the form', 'ninja-job-board')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                        __nextHasNoMarginBottom
                    />
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
                    icon="feedback"
                    label={__('Job Application Form', 'ninja-job-board')}
                    instructions={__('Choose the job whose application form you want to show.', 'ninja-job-board')}
                >
                    {picker}
                </Placeholder>
            )}
        </div>
    );
}
