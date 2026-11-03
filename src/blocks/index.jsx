/**
 * Registers the Ninja Job Board blocks. They are dynamic: PHP renders them (includes/Classes/Blocks.php),
 * so save() returns null and the editor previews the real output with ServerSideRender.
 */
import jobList from './job-list/block.json';
import applicationForm from './application-form/block.json';
import jobDetails from './job-details/block.json';
import JobListEdit from './job-list/edit.jsx';
import ApplicationFormEdit from './application-form/edit.jsx';
import JobDetailsEdit from './job-details/edit.jsx';

const { registerBlockType } = wp.blocks;

registerBlockType(jobList, { edit: JobListEdit, save: () => null });
registerBlockType(applicationForm, { edit: ApplicationFormEdit, save: () => null });
registerBlockType(jobDetails, { edit: JobDetailsEdit, save: () => null });
