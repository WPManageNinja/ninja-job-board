<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            Email Broadcast
                        </h3>
                        <div class="payform_section_actions">
                            <router-link class="el-button payform_action el-button--primary el-button--small" :to="{ name: 'new_email_campaign', params: { form_id: form_id } }">New Email Broadcast</router-link>
                        </div>
                    </div>
                    <div style="padding: 10px 20px;" v-loading="loading" id="email_notifications"
                         class="payform_section_body">
                        <template v-if="!campaigns.length">
                            <p style="text-align: center;">No Email camapigns are found! To send bulk email to your applicants please create a new email campaign</p>
                        </template>
                        <template v-else>
                            <el-table border :data="campaigns">
                                <el-table-column width="100" label="ID" prop="id"></el-table-column>
                                <el-table-column width="120" label="Status" prop="status"></el-table-column>
                                <el-table-column width="200" label="Date" prop="created_at"></el-table-column>
                                <el-table-column width="120" label="Email Count" prop="total_sent"></el-table-column>
                                <el-table-column label="Subject" prop="subject"></el-table-column>
                                <el-table-column  width="120" label="Action">
                                    <template slot-scope="scope">
                                        <router-link class="el-button payform_action el-button--primary el-button--small" :to="{name: 'view_email_campaign', params: { campaign_id: scope.row.id, form_id: form_id }}">Detail</router-link>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </template>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    export default {
        name: 'NinjaMassEmail',
        props: ['form_id'],
        data() {
            return {
                loading: false,
                campaigns: [],
                total: 0
            }
        },
        methods: {
            getCampaigns() {
                this.loading = true;
                this.$get({
                    action: 'wpjobboard_email_campaigns',
                    route: 'get_campaigns',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.campaigns = response.data.campaigns.data;
                        this.total = response.data.campaigns.total;
                    })
                    .always(() => {
                        this.loading = false;
                    });
            }
        },
        mounted() {
            this.getCampaigns();
        }
    }
</script>