<template>
    <div class="wpjb_settings_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">{{ $t('Scheduling & restrictions') }}</h3>
            <div class="payform_section_actions">
                <el-button :loading="saving" @click="saveSettings()" type="primary">
                    {{ $t('Save settings') }}
                </el-button>
            </div>
        </div>
        <div v-loading="fetching">
            <form-restrictions v-if="loaded" :current_date_time="current_date_time" :data="settings"/>
        </div>
    </div>
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
            this.$setTitle(this.$t('Scheduling & restrictions'));
        }
    }
</script>