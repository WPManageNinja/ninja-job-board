<template>
    <div v-loading="loading && !paymentForms.length" class="wppayforms wpjb_jobs">
        <welcome v-if="!forms_count && !hasForms" @create="createFormModal = true"/>
        <div class="all_payforms_wrapper payform_section" v-else>
            <div class="payform_section_header">
                <h1 class="payform_section_title">
                    {{ $t('Jobs') }} <span class="wpjb_count">({{ total }})</span>
                </h1>
                <div class="payform_section_actions">
                    <el-input
                        v-model="search_string"
                        class="wpjb_search"
                        :prefix-icon="Search"
                        :placeholder="$t('Search jobs')"
                        :aria-label="$t('Search jobs')"
                        clearable
                        @keyup.enter="searchForms()"
                        @clear="searchForms()"
                    />
                    <el-button type="primary" :icon="Plus" @click="createFormModal = true">
                        {{ $t('Add job') }}
                    </el-button>
                </div>
            </div>
            <div v-loading.fullscreen.lock="duplicatingForm" :element-loading-text="$t('Duplicating the job...')" class="wpjb_card">
                <el-table
                    class="wpjb_jobs_table"
                    v-loading="loading"
                    :data="paymentForms"
                    :empty-text="$t('No jobs match your search.')">

                    <el-table-column :label="$t('Job')" min-width="320">
                        <template #default="scope">
                            <router-link class="wpjb_cell_primary wpjb_cell_link" :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                {{ scope.row.post_title || $t('(no title)') }}
                            </router-link>
                            <div class="wpjb_job_meta">
                                <span class="wpjb_num">#{{ scope.row.ID }}</span>
                            <div class="row-actions">
                                <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                    {{ $t('Applications') }}
                                </router-link>
                                <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                    {{ $t('Application form') }}
                                </router-link>
                                <a v-if="scope.row.post_status != 'trash'" :href="escUrl(scope.row.edit_url)">{{ $t('Edit job post') }}</a>
                                <a v-else href="#" @click.prevent="restorePost(scope.row.ID)">{{ $t('Restore') }}</a>
                                <a href="#" @click.prevent="duplicateForm(scope.row.ID)">{{ $t('Duplicate') }}</a>
                                <a href="#" class="is-danger" @click.prevent="confirmDeleteForm(scope.row)">{{ $t('Delete') }}</a>
                            </div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Status')" width="130">
                        <template #default="scope">
                            <status-badge type="post" :status="scope.row.post_status"/>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Applications')" width="120">
                        <template #default="scope">
                            <span class="wpjb_num">{{ scope.row.entries_count || 0 }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Created')" width="130">
                        <template #default="scope">
                            <span class="wpjb_num">{{ $dateFormat(scope.row.post_date || scope.row.post_date_gmt, 'MMM D, YYYY') }}</span>
                        </template>
                    </el-table-column>
                </el-table>
                <div v-if="total" class="wpjb_pagination">
                    <el-pagination
                        background
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange"
                        :current-page="page_number"
                        :page-size="per_page"
                        :page-sizes="pageSizes"
                        layout="total, sizes, prev, pager, next"
                        :total="total">
                    </el-pagination>
                </div>
            </div>
        </div>

        <create-form v-if="createFormModal" v-model:modalVisible="createFormModal"/>

        <el-dialog
            class="wpjb_dialog"
            :title="$t('Delete this job?')"
            v-model="deleteDialogVisible"
            :before-close="handleDeleteClose"
            width="480px">
            <p>
                <strong>{{ deleteingForm.post_title }}</strong>
                {{ $t('and all of its applications will be deleted permanently. You cannot undo this.') }}
            </p>
            <template #footer>
                <el-button @click="deleteDialogVisible = false">{{ $t('Cancel') }}</el-button>
                <el-button type="danger" @click="deleteFormNow()">{{ $t('Delete job') }}</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script>
    import { Search, Plus } from '@element-plus/icons-vue';
    import Welcome from '../Common/Welcome.vue';
    import StatusBadge from '../Common/StatusBadge.vue';
    import CreateForm from './CreateForm.vue';

    export default {
        name: 'AllForms',
        components: { CreateForm, Welcome, StatusBadge },
        setup() {
            return { Search, Plus };
        },
        data() {
            return {
                createFormModal: false,
                paymentForms: [],
                hasForms: false,
                per_page: 20,
                page_number: 1,
                search_string: '',
                total: 0,
                loading: false,
                deleteDialogVisible: false,
                deleteingForm: {},
                pageSizes: [10, 20, 30, 40, 50, 100, 200],
                forms_count: parseInt(window.wpJobBoardsAdmin.forms_count),
                duplicatingForm: false
            }
        },
        methods: {
            fetchForms() {
                this.loading = true;
                this.$api.get('/forms', {
                    per_page: this.per_page,
                    page: this.page_number,
                    search: this.search_string
                })
                    .then(response => {
                        this.paymentForms = response.forms;
                        this.hasForms = !!response.total;
                        this.total = response.total;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            searchForms() {
                this.page_number = 1;
                this.fetchForms();
            },
            confirmDeleteForm(form) {
                this.deleteingForm = form;
                this.deleteDialogVisible = true;
            },
            restorePost(id) {
                this.$api.post(`/forms/${id}/restore`)
                    .then(response => {
                        this.$message.success({
                            message: response.message
                        });
                        this.fetchForms();
                    })
                    .catch(error => {
                        this.$message.error({
                            message: error.message
                        });
                    })
                    .finally(() => {
                        this.deleteDialogVisible = false;
                        this.deleteingForm = {};
                    });
            },
            escUrl(url) {
                return (url || '').replace(/&amp;/g, '&');
            },
            deleteFormNow() {
                this.$api.delete(`/forms/${this.deleteingForm.ID}`)
                    .then(response => {
                        this.$message.success({
                            message: response.message
                        });
                        this.fetchForms();
                    })
                    .catch(error => {
                        this.$message.error({
                            message: error.message
                        });
                    })
                    .finally(() => {
                        this.deleteDialogVisible = false;
                        this.deleteingForm = {};
                    });
            },
            handleDeleteClose(done) {
                this.deleteingForm = {};
                done();
            },
            handleCurrentChange(val) {
                this.page_number = val;
                this.fetchForms();
            },
            handleSizeChange(val) {
                this.per_page = val;
                this.fetchForms();
            },
            duplicateForm(formId) {
                this.duplicatingForm = true;
                this.$api.post(`/forms/${formId}/duplicate`)
                    .then(response => {
                        if(response.form && response.form.ID) {
                            this.$notify.success(response.message);
                            this.$router.push({
                                name: 'edit_form',
                                params: {
                                    form_id: response.form.ID
                                }
                            });
                        } else {
                            this.$notify.error('Something is wrong! Please try again');
                        }
                    })
                    .catch((error) => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.duplicatingForm = false;
                    });
            }
        },
        mounted() {
            this.fetchForms();
            this.$setTitle('Jobs');
        }
    }
</script>
