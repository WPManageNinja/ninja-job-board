<template>
    <div class="wpjb_settings_page wpjb_email_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">
                Email broadcasts
                <span v-if="total" class="wpjb_count wpjb_num">({{ total }})</span>
            </h3>
            <div v-if="campaigns.length" class="payform_section_actions">
                <router-link class="el-button el-button--primary" :to="{ name: 'new_email_campaign', params: { form_id: form_id } }">New broadcast</router-link>
            </div>
        </div>
        <div v-loading="loading" class="wpjb_card">
            <div v-if="!campaigns.length && !loading" class="wpjb_empty">
                <h3>No email broadcasts yet</h3>
                <p>A broadcast sends one email to the applicants of this job. You can pick them by application status.</p>
                <router-link class="el-button el-button--primary" :to="{ name: 'new_email_campaign', params: { form_id: form_id } }">New broadcast</router-link>
            </div>
            <template v-else-if="campaigns.length">
                <el-table :data="campaigns">
                    <el-table-column label="Broadcast" min-width="280">
                        <template #default="scope">
                            <router-link class="wpjb_cell_primary" :to="{name: 'view_email_campaign', params: { campaign_id: scope.row.id, form_id: form_id }}">{{ scope.row.subject || scope.row.title }}</router-link>
                            <span v-if="scope.row.title && scope.row.title !== scope.row.subject" class="wpjb_cell_secondary">{{ scope.row.title }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Status" width="140">
                        <template #default="scope">
                            <span :class="['wpjb_badge', 'is-' + campaignTone(scope.row.status)]">{{ campaignLabel(scope.row.status) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Emails" width="110" align="right">
                        <template #default="scope">
                            <span class="wpjb_num">{{ scope.row.total_sent }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Created" width="200">
                        <template #default="scope">
                            <span class="wpjb_num">{{ $dateFormat(scope.row.created_at, 'MMM D, YYYY h:mm A') }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="" width="100" align="right">
                        <template #default="scope">
                            <router-link class="el-button el-button--small" :to="{name: 'view_email_campaign', params: { campaign_id: scope.row.id, form_id: form_id }}">View</router-link>
                        </template>
                    </el-table-column>
                </el-table>
                <div class="wpjb_pagination" v-if="total > per_page">
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
            campaignLabel(status) {
                const labels = { completed: 'Sent', ready: 'Sending' };
                return labels[status] || String(status || '').charAt(0).toUpperCase() + String(status || '').slice(1);
            },
            campaignTone(status) {
                const tones = { completed: 'green', ready: 'amber' };
                return tones[status] || 'neutral';
            },
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