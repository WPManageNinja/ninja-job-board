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
                    <el-input @keyup.enter.native="performSearch" size="mini" placeholder="Search" v-model="search_string">
                        <el-button @click="performSearch" size="mini" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>
                <div v-if="totalEntries" class="wpf_entry_action">
                    <el-dropdown @command="exportCSV">
                        <el-button type="info" size="mini">
                            Export <i class="el-icon-arrow-down el-icon--right"></i>
                        </el-button>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item command="csv">As CSV</el-dropdown-item>
                            <!-- <el-dropdown-item command="xlsx">Export as Excel (xlsv)</el-dropdown-item>
                            <el-dropdown-item command="ods">Export as ODS</el-dropdown-item>
                            <el-dropdown-item command="json">Export as JSON Data</el-dropdown-item> -->
                        </el-dropdown-menu>
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
            :visible.sync="show_pro"
            width="60%">
            <div class="payform_section_body payform_upgrade_wrapper">
                <div class="payform_upgrade_section">
                    <h1><i class="el-icon-lock"></i></h1>
                    <h3>Export data is a pro feature. Upgrade to pro to unlock this feature.</h3>
                    <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro
                        version</a>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script>
    import formEntriesTable from '../../Common/EntriesTable';
    import fromatPrice from '../../../../common/formatPrice';

    export default {
        name: "Entries",
        components: {
            formEntriesTable
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
                search_string: '',
                entry_ticker: 1,
                reports: {},
                currencySettings: {},
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
            exportCSV(doc_type) {
                // if (!this.has_pro) {
                //     this.show_pro = true;
                //     return;
                // }
                let query = jQuery.param({
                    action: 'wpjb_export_endpoints',
                    route: 'export_data',
                    type: doc_type,
                    search_string: this.search_string,
                    form_id: parseInt(this.form_id),
                    application_status: this.selected_application_status,
                    status: this.selected_internal_status,
                });

                window.location.href = window.wpJobBoardsAdmin.ajaxurl + '?' + query;
            },
            getFormReport() {
                this.$get({
                    action: 'wpjb_submission_endpoints',
                    route: 'get_form_report',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.reports = response.data.reports;
                        this.currencySettings = response.data.currencySettings;
                    })
                    .fail(error => {
                        console.log(error);
                    })
                    .always(() => {

                    });
            },
            formatMoney(price) {
                return fromatPrice(price, this.currencySettings);
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
            window.WPJobBoardBus.$emit('site_title', 'Job Applications');
        }
    }
</script>