<template>
    <el-container>
        <el-main class="no_shadow">
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Confirmation Settings') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                {{ $t( 'Save Confirmation Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div v-loading="fetching" class="payform_section_body">
                        <el-form ref="payment_settings" :label-position="labelPosition" :model="payment_settings" label-width="220px">
                            <confirmation-settings
                                :pages="pages"
                                :editorShortcodes="editorShortcodes"
                                :confirmation="confirmation_settings">
                            </confirmation-settings>
                        </el-form>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script>
    import ConfirmationSettings from './_AddConfirmation.vue';
    export default {
        name: 'confirmation_settings',
        props: ['form_id'],
        components: {
            ConfirmationSettings
        },
        data() {
            return {
                payment_settings: {},
                fetching: false,
                pages: [],
                editorShortcodes: [],
                confirmation_settings: {},
                app_ready: false,
                saving: false,
                labelPosition: 'right'
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$api.get('/forms/' + this.form_id + '/confirmation')
                    .then(response => {
                        this.confirmation_settings = response.confirmation_settings;
                        this.editorShortcodes = Object.values(response.editor_shortcodes);
                        this.pages = response.pages;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.fetching = false;
                        this.app_ready = true;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$api.post('/forms/' + this.form_id + '/confirmation', {
                    confirmation_settings: this.confirmation_settings
                })
                    .then(response => {
                        this.$message({
                            message: response.message,
                            type: 'success'
                        });
                    })
                    .catch(error => {
                        this.$message({
                            message: error.message,
                            type: 'error'
                        });
                    })
                    .finally(() => {
                        this.saving = false;
                    })
            }
        },
        mounted() {
            this.getSettings();
            this.$setTitle('Form Confirmation Settings');
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>