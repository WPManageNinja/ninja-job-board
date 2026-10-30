<template>
    <div class="payform_section wpjb_applications">
        <div class="payform_section_header">
            <h1 class="payform_section_title">
                {{ $t('Applications') }} <span v-if="allCount !== null" class="wpjb_count">({{ allCount }})</span>
            </h1>
        </div>

        <div class="wpjb_card">
            <div class="wpjb_toolbar">
                <ul class="wpjb_stages" :aria-label="$t('Filter by application status')">
                    <li v-for="stage in stages" :key="stage.key">
                        <button
                            type="button"
                            :class="{ 'is-active': selected_application_status === stage.key }"
                            :aria-pressed="selected_application_status === stage.key ? 'true' : 'false'"
                            @click="setApplicationStatus(stage.key)">
                            {{ stage.label }} <span class="wpjb_stage_count">{{ stage.count }}</span>
                        </button>
                    </li>
                </ul>
                <div class="wpjb_toolbar_end">
                    <el-select
                        v-model="form_id"
                        class="wpjb_filter_select wpjb_job_select"
                        filterable
                        clearable
                        :placeholder="$t('All jobs')"
                        :aria-label="$t('Job')"
                        @change="changeForm">
                        <el-option
                            v-for="form in available_forms"
                            :key="form.ID"
                            :label="form.post_title"
                            :value="String(form.ID)">
                        </el-option>
                    </el-select>
                    <el-select
                        v-model="selected_internal_status"
                        class="wpjb_filter_select"
                        :placeholder="$t('Internal status')"
                        :aria-label="$t('Internal status')"
                        clearable
                        @change="changeInternalStatus">
                        <el-option
                            v-for="(status, status_key) in internal_statuses"
                            :key="status_key"
                            :label="status"
                            :value="status_key">
                        </el-option>
                    </el-select>
                    <el-input
                        v-model="search_string"
                        class="wpjb_search"
                        :prefix-icon="Search"
                        :placeholder="$t('Search applicants')"
                        :aria-label="$t('Search applicants')"
                        clearable
                        @keyup.enter="performSearch"
                        @clear="performSearch"
                    />
                </div>
            </div>
            <form-entries-table
                :entry_ticker="entry_ticker"
                :form_id="form_id"
                :search_string="search_string"
                :application_status="selected_application_status"
                :status="selected_internal_status"
                @deleted="getReport"
            />
        </div>
    </div>
</template>

<script>
    import { Search } from '@element-plus/icons-vue';
    import formEntriesTable from '../Common/EntriesTable.vue';

    export default {
        name: "Entries",
        components: {
            formEntriesTable
        },
        setup() {
            return { Search };
        },
        data() {
            return {
                form_id: '',
                available_forms: [],
                selected_application_status: '',
                application_statuses: window.wpJobBoardsAdmin.applicationStatuses,
                internal_statuses: window.wpJobBoardsAdmin.internalStatuses,
                search_string: '',
                entry_ticker: 1,
                selected_internal_status: '',
                reports: {}
            }
        },
        computed: {
            stages() {
                const reports = this.reports || {};
                const stages = [{
                    key: '',
                    label: (reports[''] && reports[''].label) || this.$t('All'),
                    count: reports[''] ? reports[''].submission_count : ''
                }];
                const keys = Object.keys(reports).length ? Object.keys(reports) : Object.keys(this.application_statuses || {});
                keys.forEach(key => {
                    if (key === '') {
                        return;
                    }
                    stages.push({
                        key: key,
                        label: reports[key] ? reports[key].label : this.application_statuses[key],
                        count: reports[key] ? reports[key].submission_count : ''
                    });
                });
                return stages;
            },
            allCount() {
                return this.reports[''] ? this.reports[''].submission_count : null;
            }
        },
        methods: {
            performSearch() {
                this.entry_ticker = this.entry_ticker + 1;
            },
            changeForm(value) {
                this.form_id = value ? String(value) : '';
                this.$router.push({ query: { ...this.$route.query, form_id: this.form_id || undefined } });
                this.getReport();
            },
            setApplicationStatus(status) {
                this.selected_application_status = status;
                this.$router.push({ query: { ...this.$route.query, application_status: status || undefined } });
            },
            changeInternalStatus(value) {
                this.selected_internal_status = value || '';
                this.$router.push({ query: { ...this.$route.query, internal_status: this.selected_internal_status || undefined } });
            },
            getFormTitles() {
                this.$api.get('/forms/available')
                    .then(response => {
                        this.available_forms = response.forms;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    });
            },
            getReport() {
                this.$api.get('/applications/report', {
                    form_id: parseInt(this.form_id) || 0
                })
                    .then(response => {
                        this.reports = response.reports || {};
                    })
                    .catch(error => {
                        console.log(error);
                    });
            }
        },
        mounted() {
            if (this.$route.query.form_id && this.$route.query.form_id !== '0') {
                this.form_id = String(this.$route.query.form_id);
            }
            if (this.$route.query.application_status) {
                this.selected_application_status = this.$route.query.application_status;
            }
            if (this.$route.query.internal_status) {
                this.selected_internal_status = this.$route.query.internal_status;
            }
            this.getFormTitles();
            this.getReport();
            this.$setTitle(this.$t('Applications'));
        }
    }
</script>
