<template>
    <div class="wpjb_settings_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">{{ $t('Import & export') }}</h3>
        </div>

        <section class="wpjb_card wpjb_setting_card" aria-labelledby="wpjb_export_title">
            <div class="wpjb_card_head">
                <h3 id="wpjb_export_title">{{ $t('Export a job') }}</h3>
                <p>{{ $t('Download a job and its application form as a .json file. Import it on any site that runs Ninja Job Board.') }}</p>
            </div>
            <div v-loading="fetching_forms" class="wpjb_card_body">
                <div class="wpjb_tool_row">
                    <el-select v-model="export_id" filterable :placeholder="$t('Select a job')" :aria-label="$t('Job to export')">
                        <el-option
                            v-for="item in forms"
                            :key="item.ID"
                            :label="'#'+item.ID+' - '+item.post_title"
                            :value="item.ID">
                        </el-option>
                    </el-select>
                    <el-button @click="exportForm" :disabled="!export_id" type="primary" :icon="Download">
                        {{ $t('Export JSON') }}
                    </el-button>
                </div>
            </div>
        </section>

        <section class="wpjb_card wpjb_setting_card" aria-labelledby="wpjb_import_title">
            <div class="wpjb_card_head">
                <h3 id="wpjb_import_title">{{ $t('Import a job') }}</h3>
                <p>{{ $t('Upload a .json file exported from Ninja Job Board. The import creates a new job.') }}</p>
            </div>
            <div class="wpjb_card_body">
                <form enctype="multipart/form-data" ref="upload_json_form" class="wpjb_tool_row" @submit.prevent="importForm">
                    <input required type="file" name="import_file" accept=".json,application/json"
                           ref="import_file" :aria-label="$t('JSON file to import')"/>
                    <el-button :loading="uploadingForm" @click="importForm" type="primary" :icon="Upload">
                        {{ $t('Import') }}
                    </el-button>
                </form>

                <p v-if="last_uploaded_form" class="wpjb_tool_result" role="status">
                    {{ $t('Job imported.') }}
                    <router-link :to="{name: 'edit_form', params: { form_id: last_uploaded_form.ID }}">{{ $t('Open the job') }}</router-link>
                </p>
            </div>
        </section>
    </div>
</template>

<script>
    import { Download, Upload } from '@element-plus/icons-vue';

    export default {
        name: 'global_tools',
        setup() {
            return { Download, Upload };
        },
        data() {
            return {
                forms: [],
                export_id: '',
                import_file: '',
                fetching_forms: false,
                exportingForm: false,
                uploadingForm: false,
                last_uploaded_form: false
            }
        },
        methods: {
            getForms() {
                this.fetching_forms = true;
                this.$api.get('/forms/available')
                    .then(response => {
                        this.forms = response.forms;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.fetching_forms = false;
                    });
            },
            exportForm() {
                window.location.href = this.$api.downloadUrl('/forms/' + this.export_id + '/export');
                this.$notify.success(this.$t('Downloading the job file.'));
                this.export_id = '';
            },
            importForm() {
                const file = this.$refs.import_file.files[0];
                if (!file) {
                    this.$message.error(this.$t('Select a .json file to import.'));
                    return;
                }
                const formData = new FormData();
                formData.append('import_file', file);
                this.uploadingForm = true;
                this.$api.upload('/forms/import', formData)
                    .then(response => {
                        this.$notify.success(response.message);
                        this.last_uploaded_form = response.form;
                        this.$refs.upload_json_form.reset();
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.uploadingForm = false;
                    });
            }
        },
        mounted() {
            this.getForms();
        }
    }
</script>
