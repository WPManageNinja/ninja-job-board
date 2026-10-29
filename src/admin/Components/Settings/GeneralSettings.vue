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
        <div v-loading="fetching">
            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head"><h3>{{ $t('Privacy') }}</h3></div>
                <el-form class="wpjb_setting_rows" label-position="top" :model="settings">
                    <el-form-item>
                        <template #label>
                            <span class="wpjb_field_label">{{ $t('Log IP addresses') }}</span>
                            <span class="wpjb_field_help">{{ $t('Store the IP address with each application. Advanced analytics need it.') }}</span>
                        </template>
                        <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Log IP addresses')"
                                   v-model="settings.ip_logging_status"/>
                    </el-form-item>
                </el-form>
            </div>

            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head"><h3>{{ $t('Spam protection') }}</h3></div>
                <el-form class="wpjb_setting_rows" label-position="top" :model="settings">
                    <el-form-item>
                        <template #label>
                            <span class="wpjb_field_label">{{ $t('Cloudflare Turnstile') }}</span>
                            <span class="wpjb_field_help">{{ $t('Adds a privacy-friendly security check to application forms. A hidden honeypot field is always on.') }}</span>
                        </template>
                        <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Cloudflare Turnstile')"
                                   v-model="settings.turnstile_enabled"/>
                    </el-form-item>
                    <template v-if="settings.turnstile_enabled === 'yes'">
                        <el-form-item>
                            <template #label>
                                <span class="wpjb_field_label">{{ $t('Site key') }}</span>
                                <span class="wpjb_field_help">
                                    {{ $t('Create a widget in the Cloudflare dashboard (Turnstile) and copy its keys.') }}
                                    <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">{{ $t('Open Cloudflare') }}</a>
                                </span>
                            </template>
                            <el-input v-model="settings.turnstile_site_key" :aria-label="$t('Site key')" autocomplete="off"/>
                        </el-form-item>
                        <el-form-item>
                            <template #label>
                                <span class="wpjb_field_label">{{ $t('Secret key') }}</span>
                                <span class="wpjb_field_help">{{ settings.turnstile_secret_saved ? $t('A secret key is saved. Leave empty to keep it.') : $t('Stored on your server; never shown again.') }}</span>
                            </template>
                            <el-input v-model="turnstile_secret_key" type="password" show-password autocomplete="new-password"
                                      :placeholder="settings.turnstile_secret_saved ? '••••••••' : ''" :aria-label="$t('Secret key')"/>
                        </el-form-item>
                    </template>
                </el-form>
            </div>

            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head"><h3>{{ $t('Data') }}</h3></div>
                <el-form class="wpjb_setting_rows" label-position="top" :model="settings">
                    <el-form-item>
                        <template #label>
                            <span class="wpjb_field_label">{{ $t('Delete all data when the plugin is deleted') }}</span>
                            <span class="wpjb_field_help">{{ $t('Removes jobs, applications, uploaded files and settings when you delete the plugin from the Plugins screen. This can not be undone.') }}</span>
                        </template>
                        <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Delete all data when the plugin is deleted')"
                                   v-model="settings.delete_data_on_uninstall"/>
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'general_settings',
        data() {
            return {
                fetching: false,
                // Defaults match the server, so the switches have valid values before the data loads
                settings: {
                    ip_logging_status: 'yes',
                    delete_data_on_uninstall: 'no',
                    turnstile_enabled: 'no',
                    turnstile_site_key: '',
                    turnstile_secret_saved: false
                },
                // Write-only: sent when filled, never loaded from the server
                turnstile_secret_key: '',
                saving: false
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$api.get('/settings')
                    .then(response => {
                        this.settings = Object.assign({}, this.settings, response);
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
                    ip_logging_status: this.settings.ip_logging_status,
                    delete_data_on_uninstall: this.settings.delete_data_on_uninstall,
                    turnstile_enabled: this.settings.turnstile_enabled,
                    turnstile_site_key: this.settings.turnstile_site_key,
                    turnstile_secret_key: this.turnstile_secret_key
                })
                    .then(response => {
                        this.$message.success(response.message);
                        if (this.turnstile_secret_key) {
                            this.settings.turnstile_secret_saved = true;
                            this.turnstile_secret_key = '';
                        }
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
            this.$setTitle('General settings');
        }
    }
</script>