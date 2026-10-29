<template>
    <div class="wpjb_settings_page wpjb_email_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">New broadcast</h3>
            <div class="payform_section_actions">
                <router-link class="el-button" :to="{ name: 'email_campaigns', params: { form_id: form_id } }">Cancel</router-link>
                <el-button @click="sendEmail()" :loading="sending" type="primary" :icon="Promotion">Send broadcast</el-button>
            </div>
        </div>
        <el-form ref="new_campaign" class="wpjb_setting_rows" label-position="top" :model="campaign">
            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>Email</h3>
                </div>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Broadcast name</span>
                        <span class="wpjb_field_help">Helps you find this broadcast later. Applicants do not see it.</span>
                    </template>
                    <el-input placeholder="For example: Interview invitations, May" aria-label="Broadcast name" v-model="campaign.title"></el-input>
                </el-form-item>

                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Subject</span>
                        <span class="wpjb_field_help">The subject line applicants see in their inbox.</span>
                    </template>
                    <el-input placeholder="Email subject" aria-label="Subject" v-model="campaign.subject"></el-input>
                </el-form-item>

                <el-form-item class="is-stacked">
                    <template #label>
                        <span class="wpjb_field_label">Email body</span>
                        <span class="wpjb_field_help">Use <code>{applicant_name}</code> and <code>{applicant_email}</code> to add each applicant's details.</span>
                    </template>
                    <wp-editor v-model="campaign.body" />
                </el-form-item>
            </div>

            <div v-loading="loading" class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>Recipients</h3>
                </div>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Who gets this email</span>
                        <span class="wpjb_field_help">Leave both empty to email every applicant of this job.</span>
                    </template>
                    <div class="wpjb_select_stack">
                        <div>
                            <label for="wpjb_bc_app_status">Application status</label>
                            <el-select id="wpjb_bc_app_status" @change="getCount" multiple v-model="campaign.campaign_settings.application_statuses"
                                       placeholder="All application statuses">
                                <template v-for="(status, status_key) in application_statuses" :key="status_key">
                                    <el-option
                                        v-if="status_key"
                                        :label="status"
                                        :value="status_key">
                                    </el-option>
                                </template>
                            </el-select>
                        </div>
                        <div>
                            <label for="wpjb_bc_internal_status">Internal status</label>
                            <el-select id="wpjb_bc_internal_status" @change="getCount" multiple v-model="campaign.campaign_settings.internal_statuses"
                                       placeholder="All internal statuses">
                                <template v-for="(status, status_key) in internal_statuses" :key="status_key">
                                    <el-option
                                        v-if="status_key"
                                        :label="status"
                                        :value="status_key">
                                    </el-option>
                                </template>
                            </el-select>
                        </div>
                        <p class="wpjb_field_note" role="status">
                            <strong class="wpjb_num">{{ total }}</strong> {{ total == 1 ? 'applicant' : 'applicants' }} will get this email.
                        </p>
                    </div>
                </el-form-item>
            </div>
        </el-form>
    </div>
</template>

<script>
    import { Promotion } from '@element-plus/icons-vue';
    import wpEditor from '../../Common/_wp_editor_ext.vue';

    export default {
        name: 'NewNinjaMassEmail',
        components: {
            wpEditor
        },
        setup() {
            return { Promotion };
        },
        data() {
            return {
                form_id: this.$route.params.form_id,
                loading: false,
                sending: false,
                campaign: {
                    title: '',
                    subject: '',
                    body: '',
                    campaign_settings: {
                        internal_statuses: [],
                        application_statuses: []
                    }
                },
                total: 0,
                application_statuses: window.wpJobBoardsAdmin.applicationStatuses,
                internal_statuses: window.wpJobBoardsAdmin.internalStatuses
            }
        },
        methods: {
            sendEmail() {
                this.sending = true;
                this.$api.post('/forms/' + this.form_id + '/campaigns', {
                    campaign: this.campaign
                })
                    .then(response => {
                        this.$message.success(response.message);
                        this.$router.push( { name: 'view_email_campaign', params: { campaign_id: response.campaign_id, form_id: this.form_id } })
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.sending = false;
                    });
            },
            getCount() {
                this.loading = true;
                this.$api.get('/forms/' + this.form_id + '/campaigns/count', {
                    campaign_settings: this.campaign.campaign_settings
                })
                    .then(response => {
                        this.total = response.count;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        },
        mounted() {
            this.getCount();
        }
    }
</script>
