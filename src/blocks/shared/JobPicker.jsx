// value 0 = "Current job" (the job post the block is in), offered only inside a job post.
const { SelectControl, Spinner } = wp.components;
const { useSelect } = wp.data;
const { __ } = wp.i18n;

export function useJobs() {
    return useSelect((select) => select('core').getEntityRecords('postType', 'wp_job_board', {
        per_page: 100,
        status: 'publish,draft,future,private',
        orderby: 'date',
        order: 'desc',
        _fields: 'id,title,status'
    }), []);
}

export function isInJobPost(context) {
    return context && context.postType === 'wp_job_board';
}

export default function JobPicker({ value, onChange, context }) {
    const jobs = useJobs();

    if (jobs === null) {
        return <Spinner />;
    }

    const options = [
        isInJobPost(context)
            ? { value: 0, label: __('Current job (this post)', 'ninja-job-board') }
            : { value: 0, label: __('Choose a job', 'ninja-job-board'), disabled: true }
    ].concat((jobs || []).map((job) => ({
        value: job.id,
        label: (job.title && job.title.rendered ? job.title.rendered : __('(no title)', 'ninja-job-board'))
            + (job.status !== 'publish' ? ' (' + job.status + ')' : '')
    })));

    return (
        <SelectControl
            label={__('Job', 'ninja-job-board')}
            value={value}
            options={options}
            onChange={(next) => onChange(parseInt(next, 10) || 0)}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
        />
    );
}
