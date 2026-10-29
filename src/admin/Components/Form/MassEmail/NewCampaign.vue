<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            Compose Email
                        </h3>
                        <div class="payform_section_actions">
                            <router-link class="el-button payform_action el-button--secondary el-button--small" :to="{ name: 'email_campaigns', params: { form_id: form_id } }">Back</router-link>
                        </div>
                    </div>
                    <div style="padding: 20px 20px;" v-loading="loading" id="email_notifications"
                         class="payform_section_body">
                        <el-form ref="new_campaign" :model="campaign" label-width="150px">

                            <el-form-item label="Campaign Name">
                                <el-input placeholder="Campaign Name" size="small" v-model="campaign.title"></el-input>
                            </el-form-item>

                            <el-form-item label="Email Subject">
                                <el-input placeholder="Email Subject" size="small" v-model="campaign.subject"></el-input>
                            </el-form-item>

                            <el-form-item label="Email Body">
                                <wp-editor v-model="campaign.body" />
                                <p>You can use {applicant_name} and {applicant_email} as dynamic applicant data</p>
                            </el-form-item>

                            <el-form-item label="Select Email Recivers">
                                <label>
                                    Application Statuses
                                    <el-select @change="getCount" multiple size="small" v-model="campaign.campaign_settings.application_statuses"
                                               placeholder="All Application Statuses">
                                        <template v-for="(status, status_key) in application_statuses" :key="status_key">
                                            <el-option
                                                v-if="status_key"
                                                :label="status"
                                                :value="status_key">
                                            </el-option>
                                        </template>
                                    </el-select>
                                </label>
                                <label>
                                    Internal Statuses
                                    <el-select @change="getCount" multiple size="small" v-model="campaign.campaign_settings.internal_statuses"
                                               placeholder="All Internal Statuses">
                                        <template v-for="(status, status_key) in internal_statuses" :key="status_key">
                                            <el-option
                                                v-if="status_key"
                                                :label="status"
                                                :value="status_key">
                                            </el-option>
                                        </template>
                                    </el-select>
                                </label>

                                <p>{{total}} reciepients will get this email based your selection</p>
                            </el-form-item>

                            <el-form-item style="text-align: right">
                                <el-button @click="sendEmail()" :loading="sending" type="success">Send Email</el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script>
    import wpEditor from '../../Common/_wp_editor_ext.vue';

    export default {
        name: 'NewNinjaMassEmail',
        components: {
            wpEditor
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
