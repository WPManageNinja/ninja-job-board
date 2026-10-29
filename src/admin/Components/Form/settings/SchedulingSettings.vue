<template>
    <el-container>
        <el-main class="no_shadow">
            <div v-loading="fetching" class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Scheduling & Restrictions') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                                       type="primary">
                                {{ $t( 'Save Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div class="payform_section_body">
                        <form-restrictions v-if="loaded" :current_date_time="current_date_time" :data="settings"/>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script>
    import FormRestrictions from './_Restrictions.vue';

    export default {
        name: 'form_schedule_restriction_settings',
        props: ['form_id'],
        components: {FormRestrictions},
        data() {
            return {
                settings: {
                    limitNumberOfEntries: {},
                    scheduleForm: {},
                    requireLogin: {}
                },
                // The switches need the saved yes/no values on first render
                loaded: false,
                fetching: false,
                saving: false,
                current_date_time: ''
            }
        },
        methods: {
            saveSettings() {
                this.saving = true;
                this.$api.post('/forms/' + this.form_id + '/scheduling', {
                    settings: this.settings
                })
                    .then(response => {
                        this.$message.success(response.message);
                    })
                    .catch((error) => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            },
            getSettings() {
                this.fetching = true;
                this.$api.get('/forms/' + this.form_id + '/scheduling')
                    .then((response) => {
                        this.settings = response.scheduling_settings;
                        this.loaded = true;
                        this.current_date_time = response.current_date_time
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.fetching = false;
                    });
            }
        },
        mounted() {
            this.getSettings();
            this.$setTitle('Scheduling Settings');
        }
    }
</script>