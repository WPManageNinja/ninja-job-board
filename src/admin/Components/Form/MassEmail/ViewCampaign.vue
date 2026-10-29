<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            Campaign Overview
                        </h3>
                        <div class="payform_section_actions">
                            <router-link class="el-button payform_action el-button--primary el-button--small" :to="{ name: 'email_campaigns', params: { form_id: form_id } }">View All</router-link>
                        </div>
                    </div>
                    <div style="padding: 10px 20px;" v-loading="loading" id="email_notifications"
                         class="payform_section_body">

                        <div style="text-align: center;" v-if="stats.remaining">
                            <p>We are sending the emails now. Please hold on and keep this tab open</p>
                            <p><b>Stat: </b></p>
                            <pre>{{stats}}</pre>
                        </div>

                        <div class="email_campain_info" v-else>
                            <div class="campaign_info">
                                <ul>
                                    <li>Campaign Title: {{campaign.title }}</li>
                                    <li>Email Subject: {{campaign.subject }}</li>
                                    <li>Sent At: {{campaign.created_at}}</li>
                                    <li>Total Email Sent: {{campaign.total_sent}}</li>
                                </ul>
                            </div>
                            <div class="campaign_email_body">
                                <div class="email_body_actual" v-html="campaign.body"></div>
                            </div>
                        </div>

                        <div v-if="emails.length" class="campaign_emails_table">
                            <el-table border :data="emails">
                                <el-table-column width="100" label="ID" prop="id"></el-table-column>
                                <el-table-column width="120" label="Status" prop="status"></el-table-column>
                                <el-table-column label="Email ID" prop="email_to"></el-table-column>
                                <el-table-column label="Subject" prop="subject"></el-table-column>
                            </el-table>
                        </div>

                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
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

<style lang="scss">
    .email_campain_info {
        display: block;
        overflow: hidden;
        width: 100%;
    }

    .campaign_info {
        width: 48%;
        float: left;
        margin-right: 2%;
    }

    .campaign_email_body {
        float: left;
        width: 50%;
        padding: 20px;
        background: gray;
    }

    .email_body_actual {
        background: white;
        padding: 20px;
    }
</style>
