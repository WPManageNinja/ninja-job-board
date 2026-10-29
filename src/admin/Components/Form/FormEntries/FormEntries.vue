<template>
    <div class="payform_section">
        <div class="wpf_entry_actions payform_section_header">
            <h3 class="payform_section_title">
                Job Applications
            </h3>
            <div class="payform_section_actions">
                <div class="wpf_entry_action">
                    <label>
                        <el-select @change="changeApplicationStatus()" size="small" v-model="selected_application_status"
                                   placeholder="All Application Statuses">
                            <el-option label="All Application Statuses" value=""></el-option>
                            <el-option
                                v-for="(status, status_key) in application_statuses"
                                :key="status_key"
                                :label="status"
                                :value="status_key">
                            </el-option>
                        </el-select>
                    </label>
                </div>
                <div class="wpf_entry_action">
                    <label>
                        <el-select @change="changeInternalStatus()" size="small" v-model="selected_internal_status"
                                   placeholder="All Internal Statuses">
                            <el-option label="All Internal Statuses" value=""></el-option>
                            <el-option
                                v-for="(status, status_key) in internal_statuses"
                                :key="status_key"
                                :label="status"
                                :value="status_key">
                            </el-option>
                        </el-select>
                    </label>
                </div>
                <div class="wpf_entry_action">
                    <el-input @keyup.enter="performSearch" size="small" placeholder="Search" v-model="search_string">
                        <template #append>
                            <el-button @click="performSearch" size="small" :icon="Search"></el-button>
                        </template>
                    </el-input>
                </div>
                <div v-if="totalEntries" class="wpf_entry_action">
                    <el-dropdown @command="exportCSV">
                        <el-button type="info" size="small">
                            Export <el-icon style="margin-left: 5px"><ArrowDown /></el-icon>
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item command="csv">As CSV</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </div>
        </div>
        <div>
        <form-entries-table
            :entry_ticker="entry_ticker"
            :form_id="form_id"
            :search_string="search_string"
            :application_status="selected_application_status"
            :status="selected_internal_status"
            @getEntries="handleEntriesRefreshed"
        />
        </div>

        <el-dialog
            v-model="show_pro"
            width="60%">
            <div class="payform_section_body payform_upgrade_wrapper">
                <div class="payform_upgrade_section">
                    <h1><el-icon><Lock /></el-icon></h1>
                    <h3>Export data is a pro feature. Upgrade to pro to unlock this feature.</h3>
                    <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro
                        version</a>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script>
    import { Search, ArrowDown, Lock } from '@element-plus/icons-vue';
    import formEntriesTable from '../../Common/EntriesTable.vue';

    export default {
        name: "Entries",
        components: {
            formEntriesTable,
            ArrowDown,
            Lock
        },
        setup() {
            return { Search };
        },
        data() {
            return {
                form_id: this.$route.params.form_id,
                available_forms: [],
                selected_application_status: '',
                selected_internal_status: '',
                application_statuses: window.wpJobBoardsAdmin.applicationStatuses,
                internal_statuses: window.wpJobBoardsAdmin.internalStatuses,
                show_pro: false,
                pro_purchase_url: 'https://wpmanageninja.com/wppayform-pro-wordpress-payments-form-builder/?utm_source=upgrade&utm_medium=url&utm_campaign=wpjobboard_upgrade',
                search_string: '',
                entry_ticker: 1,
                reports: {},
                is_payment_form: false,
                totalEntries: 0
            }
        },
        methods: {
            performSearch() {
                this.entry_ticker = this.entry_ticker + 1;
            },
            changeApplicationStatus() {
                this.$router.push({query: { application_status: this.selected_application_status}});
            },
            changeInternalStatus() {
                this.$router.push({query: { internal_status: this.selected_internal_status }});
            },
            exportCSV() {
                // CSV is the only export format
                window.location.href = this.$api.downloadUrl('/applications/export', {
                    form_id: parseInt(this.form_id),
                    application_status: this.selected_application_status,
                    status: this.selected_internal_status
                });
            },
            getFormReport() {
                this.$api.get('/applications/report', {
                    form_id: this.form_id
                })
                    .then(response => {
                        this.reports = response.reports;
                    })
                    .catch(error => {
                        console.log(error);
                    });
            },

            handleEntriesRefreshed(response) {
                this.totalEntries = response.total;
            }
        },
        mounted() {
            if (this.$route.query.application_status) {
                this.selected_application_status = this.$route.query.application_status;
            }
            this.getFormReport();
            this.$setTitle('Job Applications');
        }
    }
</script>