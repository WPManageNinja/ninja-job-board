const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, ToggleControl, RangeControl, SelectControl, FormTokenField, Disabled } = wp.components;
const { useSelect } = wp.data;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

export default function Edit({ attributes, setAttributes, name }) {
    const { title, categories, showFilter, currentOnly, postsPerPage, order } = attributes;

    const terms = useSelect((select) => select('core').getEntityRecords('taxonomy', 'wpjb-job-categories', { per_page: 100, hide_empty: false })) || [];
    const nameBySlug = {};
    const slugByName = {};
    terms.forEach((term) => {
        nameBySlug[term.slug] = term.name;
        slugByName[term.name] = term.slug;
    });

    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Job list settings', 'ninja-job-board')}>
                    <TextControl
                        label={__('Title', 'ninja-job-board')}
                        value={title}
                        onChange={(value) => setAttributes({ title: value })}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <FormTokenField
                        label={__('Categories', 'ninja-job-board')}
                        value={categories.map((slug) => nameBySlug[slug] || slug)}
                        suggestions={terms.map((term) => term.name)}
                        onChange={(names) => setAttributes({ categories: names.map((label) => slugByName[label]).filter(Boolean) })}
                        __experimentalExpandOnFocus
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <p className="components-base-control__help">{__('Leave empty to show jobs from all categories.', 'ninja-job-board')}</p>
                    <ToggleControl
                        label={__('Show the category filter', 'ninja-job-board')}
                        checked={showFilter}
                        onChange={(value) => setAttributes({ showFilter: value })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Hide expired jobs', 'ninja-job-board')}
                        checked={currentOnly}
                        onChange={(value) => setAttributes({ currentOnly: value })}
                        __nextHasNoMarginBottom
                    />
                    <RangeControl
                        label={__('Number of jobs', 'ninja-job-board')}
                        value={postsPerPage}
                        min={1}
                        max={100}
                        onChange={(value) => setAttributes({ postsPerPage: value || 1 })}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <SelectControl
                        label={__('Order', 'ninja-job-board')}
                        value={order}
                        options={[
                            { value: 'DESC', label: __('Newest first', 'ninja-job-board') },
                            { value: 'ASC', label: __('Oldest first', 'ninja-job-board') }
                        ]}
                        onChange={(value) => setAttributes({ order: value })}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                </PanelBody>
            </InspectorControls>
            <Disabled>
                <ServerSideRender block={name} attributes={attributes} />
            </Disabled>
        </div>
    );
}
