<template>
    <div class="wpjb_settings_page wpjb_email_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">
                {{ campaign.title || 'Broadcast' }}
                <span v-if="campaign.status" :class="['wpjb_badge', 'is-' + (isSending ? 'amber' : 'green')]">{{ isSending ? 'Sending' : 'Sent' }}</span>
            </h3>
            <div class="payform_section_actions">
                <router-link class="el-button" :to="{ name: 'email_campaigns', params: { form_id: form_id } }">All broadcasts</router-link>
            </div>
        </div>

        <div v-loading="loading" class="wpjb_card">
            <dl class="wpjb_campaign_stats">
                <div>
                    <dt>Sent</dt>
                    <dd>{{ stats.sent || 0 }}</dd>
                </div>
                <div>
                    <dt>Remaining</dt>
                    <dd>{{ stats.remaining || 0 }}</dd>
                </div>
                <div>
                    <dt>Total recipients</dt>
                    <dd>{{ stats.all || 0 }}</dd>
                </div>
            </dl>
            <div v-if="stats.remaining" class="wpjb_campaign_progress" role="status">
                <p>Sending emails. Keep this tab open until all emails are sent.</p>
                <el-progress :percentage="progress" :stroke-width="8" :aria-label="'Sent ' + progress + '%'"/>
            </div>
        </div>

        <div class="wpjb_card">
            <div class="wpjb_card_head">
                <h3>Email</h3>
            </div>
            <div class="wpjb_card_body">
                <dl class="wpjb_campaign_meta">
                    <dt>Subject</dt>
                    <dd>{{ campaign.subject }}</dd>
                    <dt>Created</dt>
                    <dd class="wpjb_num">{{ $dateFormat(campaign.created_at, 'MMM D, YYYY h:mm A') }}</dd>
                </dl>
            </div>
            <div class="wpjb_email_preview">
                <div class="wpjb_email_preview_body" v-html="campaign.body"></div>
            </div>
        </div>

        <div v-if="emails.length" class="wpjb_card">
            <div class="wpjb_card_head">
                <h3>Recipients <span class="wpjb_count wpjb_num">({{ emails.length }})</span></h3>
            </div>
            <el-table :data="emails">
                <el-table-column label="Email" min-width="240" prop="email_to"></el-table-column>
                <el-table-column label="Subject" min-width="240" prop="subject"></el-table-column>
                <el-table-column label="Status" width="130">
                    <template #default="scope">
                        <span :class="['wpjb_badge', 'is-' + (scope.row.status === 'sent' ? 'green' : 'amber')]">{{ scope.row.status === 'sent' ? 'Sent' : 'Pending' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Sent at" width="200">
                    <template #default="scope">
                        <span class="wpjb_num">{{ $dateFormat(scope.row.sent_at, 'MMM D, YYYY h:mm A') || '' }}</span>
                    </template>
                </el-table-column>
            </el-table>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'NinjaMassEmailView',
        data() {
            return {
                form_id: this.$route.params.form_id,
                loading: false,
                campaign: {},
                campaign_id: this.$route.params.campaign_id,
                total: 0,
                stats: {},
                emails: [],
                sending_email: false,
                isUnmounted: false
            }
        },
        computed: {
            isSending() {
                return this.campaign.status !== 'completed';
            },
            progress() {
                if (!this.stats.all) {
                    return 0;
                }
                return Math.round((this.stats.sent / this.stats.all) * 100);
            }
        },
        methods: {
            getCampaign() {
                this.loading = true;
                this.$api.get('/campaigns/' + this.campaign_id)
                    .then(response => {
                        if (this.isUnmounted) {
                            return;
                        }
                        let campaign = response.campaign;
                        this.campaign = campaign;
                        this.stats = response.stats;
                        if(campaign.status != 'completed') {
                            this.maybeSendEmailKnock();
                        } else {
                            this.loadEmails();
                        }
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            maybeSendEmailKnock() {
                if (this.isUnmounted) {
                    return;
                }
                this.sending_email = true;
                this.$api.post('/campaigns/' + this.campaign_id + '/send')
                    .then(response => {
                        if (this.isUnmounted) {
                            return;
                        }
                        this.stats = response.stats;
                        if(response.stats.remaining) {
                            this.maybeSendEmailKnock();
                        } else {
                            this.sending_email = false;
                            this.getCampaign();
                        }
                    })
                    .catch(error => {
                        // Stop the loop on error
                        this.sending_email = false;
                        if (!this.isUnmounted) {
                            this.$showAjaxError(error);
                        }
                    });
            },
            loadEmails() {
                this.$api.get('/campaigns/' + this.campaign_id + '/emails')
                    .then(response => {
                        this.emails = response.emails;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    });
            }
        },
        mounted() {
            this.getCampaign();
        },
        beforeUnmount() {
            this.isUnmounted = true;
        }
    }
</script>
