<template>
    <div>
        <div class="all_payforms_wrapper payform_section wpf_min_width">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Tools') }}
                </h3>
            </div>
            <div class="payform_section_body">
                <div class="wpf_settings_section">
                    <div class="sub_section_header">
                        <h3>{{ $t('Export From') }}</h3>
                        <p>You can export any form as .json format that you have and then you can import the form in any
                            in
                            WPJobBoard</p>
                    </div>
                    <hr/>
                    <div v-loading="fetching_forms" class="sub_section_body">
                        <br/>
                        <el-form label-position="top">
                            <el-form-item label="Select the form to export">
                                <el-select size="small" v-model="export_id" placeholder="Select Form">
                                    <el-option
                                        v-for="item in forms"
                                        :key="item.ID"
                                        :label="'#'+item.ID+' - '+item.post_title"
                                        :value="item.ID">
                                    </el-option>
                                </el-select>
                                <el-button @click="exportForm" :disabled="!export_id" type="primary" size="small">
                                    Export
                                </el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                </div>
                <div class="wpf_settings_section">
                    <div class="sub_section_header">
                        <h3>{{ $t('Import From') }}</h3>
                        <p>You can import any form that is exported from wppayfrom</p>
                    </div>
                    <hr/>
                    <div class="sub_section_body">
                        <br/>
                        <form enctype="multipart/form-data" ref="upload_json_form">
                            <label>
                                Upload .json file
                                <input required type="file" name="import_file" ref="import_file"/>
                            </label>
                            <el-button v-loading="uploadingForm" @click="importForm" type="primary" size="small">Import Form</el-button>
                        </form>

                        <div v-if="last_uploaded_form">
                            <hr />
                            <p>Form successfully imported. <router-link :to="{name: 'edit_form', params: { form_id: last_uploaded_form.ID }}">Click here to view</router-link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'global_tools',
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
                this.$notify.success('Exporting form');
                this.export_id = '';
            },
            importForm() {
                const file = this.$refs.import_file.files[0];
                if (!file) {
                    this.$message.error('Please select a .json file');
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
