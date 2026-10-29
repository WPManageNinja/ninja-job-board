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
                                    <template #default="scope">
                                        <router-link class="el-button payform_action el-button--primary el-button--small" :to="{name: 'view_email_campaign', params: { campaign_id: scope.row.id, form_id: form_id }}">Detail</router-link>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <div class="payform_pagination" v-if="total > per_page" style="margin-top: 20px; text-align: right;">
                                <el-pagination
                                    background
                                    layout="total, prev, pager, next"
                                    @current-change="getCampaigns"
                                    v-model:current-page="page"
                                    :page-size="per_page"
                                    :total="total"
                                />
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script>
    export default {
        name: 'NinjaMassEmail',
        data() {
            return {
                form_id: this.$route.params.form_id,
                loading: false,
                campaigns: [],
                total: 0,
                page: 1,
                per_page: 20
            }
        },
        methods: {
            getCampaigns() {
                this.loading = true;
                this.$api.get('/forms/' + this.form_id + '/campaigns', {
                    page: this.page,
                    per_page: this.per_page
                })
                    .then(response => {
                        this.campaigns = response.campaigns.data;
                        this.total = Number(response.campaigns.total);
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
            this.getCampaigns();
        }
    }
</script>