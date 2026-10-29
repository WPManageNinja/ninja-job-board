<template>
    <div>
        <div class="all_payforms_wrapper payform_section wpf_min_width">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('General Settings') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                               type="primary">
                        {{ $t( 'Save Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div v-loading="fetching" class="payform_section_body">
                <el-form rel="currency_settings" :label-position="labelPosition" :model="settings" label-width="220px">
                    <div class="wpf_settings_section">
                        <div class="sub_section_header">
                            <h3>{{ $t('Other Settings') }}</h3>
                        </div>
                        <div class="sub_section_body">
                            <el-checkbox true-value="no" false-value="yes" v-model="ip_logging_status">Disable IP Address Logging (If you check this then advanced analytics can not be performed)</el-checkbox>
                        </div>
                    </div>
                </el-form>

                <div class="action_right">
                    <el-button @click="saveSettings()" type="primary" size="small">{{$t('Save Settings')}}</el-button>
                </div>
            </div>
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
                labelPosition: 'right',
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
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>