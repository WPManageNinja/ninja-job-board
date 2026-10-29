<template>
    <div class="wpjb_settings_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">{{ $t('General settings') }}</h3>
            <div class="payform_section_actions">
                <el-button :loading="saving" @click="saveSettings()" type="primary">
                    {{ $t('Save settings') }}
                </el-button>
            </div>
        </div>
        <div v-loading="fetching" class="wpjb_card wpjb_setting_card">
            <el-form class="wpjb_setting_rows" label-position="top" :model="settings">
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Log IP addresses') }}</span>
                        <span class="wpjb_field_help">{{ $t('Store the IP address with each application. Advanced analytics need it.') }}</span>
                    </template>
                    <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Log IP addresses')"
                               v-model="ip_logging_status"/>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'global_currency_settings',
        data() {
            return {
                fetching: false,
                settings: {},
                saving: false,
                ip_logging_status: 'yes'
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$api.get('/settings')
                    .then(response => {
                        this.ip_logging_status = response.ip_logging_status
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.fetching = false;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$api.post('/settings', {
                    ip_logging_status: this.ip_logging_status
                })
                    .then(response => {
                        this.$message.success(response.message);
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            },
        },
        mounted() {
            this.getSettings();
            this.$setTitle('General Settings');
        }
    }
</script>