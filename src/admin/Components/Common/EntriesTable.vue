<template>
    <div class="form_entries_wrapper wpjb_entries_table">
        <el-table
            v-loading="fetching"
            :data="allEntry"
            style="width: 100%"
            :row-class-name="tableRowClassName"
            :empty-text="$t('No applications match these filters.')"
        >
            <el-table-column :label="$t('Applicant')" min-width="190">
                <template #default="scope">
                    <router-link
                        class="wpjb_cell_primary wpjb_cell_link"
                        :to="{ name: 'entry', params: { entry_id: scope.row.id, form_id: scope.row.form_id }}">
                        {{ scope.row.applicant_name || $t('Unnamed applicant') }}
                    </router-link>
                    <span class="wpjb_cell_secondary">{{ scope.row.applicant_email }}</span>
                </template>
            </el-table-column>
            <el-table-column v-if="!router_form_id" :label="$t('Job')" min-width="150">
                <template #default="scope">
                    <router-link
                        class="wpjb_cell_job"
                        :to="{ name: 'form_entries', params: { form_id: scope.row.form_id } }">
                        {{ scope.row.post_title || ('#' + scope.row.form_id) }}
                    </router-link>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Status')" width="120">
                <template #default="scope">
                    <status-badge type="application" :status="scope.row.application_status"/>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Internal')" width="140">
                <template #default="scope">
                    <status-badge type="internal" :status="scope.row.status"/>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Submitted')" width="130">
                <template #default="scope">
                    <span class="wpjb_num">{{ $dateFormat(scope.row.created_at, 'MMM D, YYYY') }}</span>
                    <span class="wpjb_cell_secondary wpjb_num">{{ $dateFormat(scope.row.created_at, 'h:mm A') }}</span>
                </template>
            </el-table-column>
            <el-table-column width="96" align="right" class-name="wpjb_actions_cell">
                <template #header>
                    <span class="screen-reader-text">{{ $t('Actions') }}</span>
                </template>
                <template #default="scope">
                    <div class="wpjb_icon_actions">
                        <el-button
                            text
                            :icon="View"
                            :aria-label="/* translators: %s is the applicant name */ $t('View application from %s', scope.row.applicant_name || scope.row.applicant_email || '')"
                            :title="$t('View application')"
                            @click="goToViewRoute(scope.row)"
                        />
                        <el-button
                            text
                            class="is-danger"
                            :icon="Delete"
                            :aria-label="/* translators: %s is the applicant name */ $t('Delete application from %s', scope.row.applicant_name || scope.row.applicant_email || '')"
                            :title="$t('Delete application')"
                            @click="showDeleteConformation(scope.row)"
                        />
                    </div>
                </template>
            </el-table-column>
        </el-table>
        <div v-if="pagination.total" class="wpjb_pagination">
            <el-pagination
                background
                @size-change="pageSizeChange"
                @current-change="changePage"
                v-model:current-page="pagination.current_page"
                :page-sizes="page_sizes"
                :page-size="pagination.per_page"
                layout="total, sizes, prev, pager, next"
                :total="pagination.total">
            </el-pagination>
        </div>

        <el-dialog
            class="wpjb_dialog"
            :title="$t('Delete this application?')"
            v-model="delete_pop_up"
            :before-close="handleDeleteClose"
            width="480px">
            <template v-if="deleting_row">
                <p v-if="deleting_row.post_title">
                    <!-- translators: %1$s is the applicant name or email, %2$s is the job title -->
                    {{ $t('The application from %1$s for %2$s will be deleted permanently. You cannot undo this.', deleting_row.applicant_name || deleting_row.applicant_email, deleting_row.post_title) }}
                </p>
                <p v-else>
                    <!-- translators: %s is the applicant name or email -->
                    {{ $t('The application from %s will be deleted permanently. You cannot undo this.', deleting_row.applicant_name || deleting_row.applicant_email) }}
                </p>
            </template>
            <template #footer>
                <el-button @click="delete_pop_up = false">{{ $t('Cancel') }}</el-button>
                <el-button type="danger" :loading="deletetingItem" @click="deleteEntryNow()">{{ $t('Delete application') }}</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script>
    import { View, Delete } from '@element-plus/icons-vue';
    import StatusBadge from './StatusBadge.vue';

    export default {
        name: 'entries_table',
        components: { StatusBadge },
        props: ['form_id', 'entry_ticker', 'application_status', 'search_string', 'status'],
        emits: ['getEntries', 'deleted'],
        setup() {
            return { View, Delete };
        },
        watch: {
            form_id() {
                this.pagination.current_page = 1;
                this.getEntries();
            },
            application_status() {
                this.pagination.current_page = 1;
                this.getEntries();
            },
            status() {
                this.pagination.current_page = 1;
                this.getEntries();
            },
            entry_ticker() {
                this.pagination.current_page = 1;
                this.getEntries();
            }
        },
        data() {
            return {
                fetching: false,
                allEntry: [],
                pagination: {
                    current_page: 1,
                    per_page: 20,
                    total: 0
                },
                router_form_id: parseInt(this.$route.params.form_id),
                delete_pop_up: false,
                deleting_row: null,
                deletetingItem: false
            }
        },
        computed: {
            page_sizes() {
                let start = 20;
                let stop = this.pagination.total;
                let step = 20;
                const remainder = stop % step;
                if (remainder) {
                    stop = stop + step;
                }
                var result = [];
                for (var i = start; step > 0 ? i <= stop : i >= stop; i += step) {
                    result.push(i);
                }
                return result;
            }
        },
        methods: {
            getEntries() {
                this.fetching = true;
                let query = {
                    form_id: parseInt(this.form_id) || 0,
                    application_status: this.application_status,
                    status: this.status,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    search: this.search_string
                }

                this.$api.get('/applications', query)
                    .then(response => {
                        this.allEntry = response.submissions;
                        this.pagination.total = response.total;
                        this.$emit('getEntries', response);
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.fetching = false;
                    });
            },
            showDeleteConformation(row) {
                this.deleting_row = row;
                this.delete_pop_up = true;
            },
            handleDeleteClose(done) {
                this.deleting_row = null;
                this.delete_pop_up = false;
                if (typeof done === 'function') {
                    done();
                }
            },
            deleteEntryNow() {
                this.deletetingItem = true;
                this.$api.delete(`/applications/${this.deleting_row.id}`)
                    .then(response => {
                        this.$message.success(response.message);
                        this.getEntries();
                        this.$emit('deleted', response);
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.deletetingItem = false;
                        this.handleDeleteClose();
                    });
            },
            changePage(page) {
                this.pagination.current_page = page;
                this.pagination.page_number = page;
                this.getEntries()
            },
            goToViewRoute(row) {
                this.$router.push({
                    name: 'entry',
                    params: { entry_id: row.id, form_id: row.form_id }
                });
            },
            pageSizeChange(pageSize) {
                this.pagination.per_page = pageSize;
                this.getEntries()
            },
            tableRowClassName({row}) {
                return 'wpf_row_' + row.application_status;
            },
        },
        mounted() {
            this.getEntries();
        }
    }
</script>
