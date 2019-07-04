<template>
    <div style="margin-top: 20px" class="payform_section">
        <div class="wpf_entry_actions payform_section_header">
            <div class="wpf_entry_action">
                <label>
                    <span class="item_title">Filter By Form</span>
                    <el-select @change="changeForm()" filterable size="small" v-model="form_id" placeholder="All Forms">
                        <el-option label="All Forms" value="0"></el-option>
                        <el-option
                            v-for="form in available_forms"
                            :key="form.ID"
                            :label="form.post_title + ' (ID: '+form.ID+')'"
                            :value="form.ID">
                        </el-option>
                    </el-select>
                </label>
            </div>
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
            <div v-if="form_id && form_id != '0'" class="wpf_entry_action">
                <router-link :to="{ name: 'form_entries', params: { form_id: form_id } }">View Reports</router-link>
            </div>
        </div>

        <div class="has_payments_table">
            <form-entries-table
                :entry_ticker="entry_ticker"
                :form_id="form_id"
                :search_string="search_string"
                :application_status="selected_application_status"
                :status="selected_internal_status"
            />
        </div>

    </div>
</template>

<script>
    import formEntriesTable from '../Common/EntriesTable';

    export default {
        name: "Entries",
        components: {
            formEntriesTable
        },
        data() {
            return {
                form_id: '0',
                available_forms: [],
                selected_application_status: '',
                application_statuses: window.wpJobBoardsAdmin.applicationStatuses,
                internal_statuses: window.wpJobBoardsAdmin.internalStatuses,
                search_string: '',
                entry_ticker: 1,
                selected_internal_status: ''
            }
        },
        methods: {
            performSearch() {
                this.entry_ticker = this.entry_ticker + 1;
            },
            changeForm() {
                this.$router.push({query: { form_id: this.form_id }});
            },
            changeApplicationStatus() {
                this.$router.push({query: { application_status: this.selected_application_status}});
            },
            changeInternalStatus() {
                this.$router.push({query: { internal_status: this.selected_internal_status }});
            },
            getFormTitles() {
                this.$get({
                    action: 'wpjb_submission_endpoints',
                    route: 'get_available_forms'
                })
                    .then(response => {
                        this.available_forms = response.data.available_forms;
                    });
            }
        },
        mounted() {
            if (this.$route.query.form_id) {
                this.form_id = this.$route.query.form_id;
            }
            if (this.$route.query.application_status) {
                this.selected_application_status = this.$route.query.application_status;
            }
            if (this.$route.query.internal_status) {
                this.selected_internal_status = this.$route.query.internal_status;
            }
            this.getFormTitles();
            window.WPJobBoardBus.$emit('site_title', 'Form Entries');
        }
    }
</script>