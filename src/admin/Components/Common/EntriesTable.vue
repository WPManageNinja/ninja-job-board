<template>
    <div class="form_entries_wrapper">

        <div v-loading="fetching" class="wpf_entries">
            <el-table
                :data="allEntry"
                style="width: 100%"
                :row-class-name="tableRowClassName"
            >
                <el-table-column
                    label="ID"
                    width="60">
                    <template #default="scope">
                        <router-link
                            :to="{ name: 'entry', params: { entry_id: scope.row.id, form_id: scope.row.form_id }}">
                            {{scope.row.id}}
                        </router-link>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="!router_form_id"
                    label="Form"
                    width="220">
                    <template #default="scope">
                        {{ scope.row.post_title }} ({{ scope.row.form_id }})
                    </template>
                </el-table-column>
                <el-table-column
                    label="Name"
                    width="160"
                    prop="applicant_name">
                </el-table-column>
                <el-table-column
                    label="Email"
                    prop="applicant_email"
                >
                    <template #default="scope">
                        {{ scope.row.applicant_email }}
                    </template>
                </el-table-column>
                <el-table-column
                    label="Application Status">
                    <template #default="scope">
                    <span :class="'wpf_pay_status_'+scope.row.application_status">
                        <el-icon v-if="getApplicationStatusIcon(scope.row.application_status)"><component :is="getApplicationStatusIcon(scope.row.application_status)" /></el-icon>
                        {{ scope.row.application_status }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Internal Status">
                    <template #default="scope">
                    <span :class="'wpf_pay_status_'+scope.row.status">
                        {{ scope.row.status }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Submitted At"
                    prop="created_at">
                </el-table-column>
                <el-table-column
                    label="Actions"
                    width="130"
                    fixed="right">
                    <template #default="scope">
                        <el-button-group>
                            <el-button @click="goToViewRoute(scope.row)" type="primary" :icon="View" size="small"></el-button>
                            <el-button @click="showDeleteConformation(scope.row)" type="danger" size="small" :icon="Delete"></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        <div class="wpf_entry_pagination">
            <el-pagination
                @size-change="pageSizeChange"
                @current-change="changePage"
                v-model:current-page="pagination.current_page"
                :page-sizes="page_sizes"
                :page-size="pagination.per_page"
                layout="total, sizes, prev, pager, next"
                :total="pagination.total">
            </el-pagination>
        </div>

        <!--Delete Entry Confimation Modal-->
        <el-dialog
            title="Are You Sure, You want to delete this Entry?"
            v-model="delete_pop_up"
            :before-close="handleDeleteClose"
            width="60%">
            <div v-if="deleting_row" class="modal_body">
                <p>All the data assoscilate with this entry will be deleted, including payment information and other associate information</p>
                <p>You are deleting entry id: <b>{{ deleting_row.id }}</b>. <br />Form Title: <b>{{ deleting_row.post_title }}</b></p>
            </div>
            <template #footer>
                <span class="dialog-footer">
                    <el-button @click="delete_pop_up = false">Cancel</el-button>
                    <el-button type="primary" @click="deleteEntryNow()">Confirm Delete</el-button>
                </span>
            </template>
        </el-dialog>
    </div>
</template>

<script>
    import { markRaw } from 'vue';
    import { View, Delete, Clock, Check, CircleClose, WarningFilled } from '@element-plus/icons-vue';

    const statusIcons = {
        pending: markRaw(Clock),
        paid: markRaw(Check),
        failed: markRaw(CircleClose),
        refunded: markRaw(WarningFilled)
    };

    export default {
        name: 'entries_table',
        props: ['form_id', 'entry_ticker', 'application_status', 'search_string', 'status'],
        emits: ['getEntries'],
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
            getApplicationStatusIcon(status) {
                return statusIcons[status] || null;
            },
            tableRowClassName({row, rowIndex}) {
                return 'wpf_row_' + row.application_status;
            },
        },
        mounted() {
            this.getEntries();
        }
    }
</script>