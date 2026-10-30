<template>
    <div class="wpjb_settings_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">{{ $t('Confirmation') }}</h3>
            <div class="payform_section_actions">
                <el-button @click="saveSettings()" :loading="saving" type="primary">
                    {{ $t('Save settings') }}
                </el-button>
            </div>
        </div>
        <div v-loading="fetching" class="wpjb_card wpjb_setting_card">
            <el-form ref="payment_settings" class="wpjb_setting_rows" label-position="top" :model="payment_settings">
                <confirmation-settings
                    :pages="pages"
                    :editorShortcodes="editorShortcodes"
                    :confirmation="confirmation_settings">
                </confirmation-settings>
            </el-form>
        </div>
    </div>
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
                saving: false
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
            this.$setTitle(this.$t('Confirmation'));
        }
    }
</script>