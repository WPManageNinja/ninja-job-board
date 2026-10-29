<template>
    <div v-loading="loading" class="wppayforms">
        <welcome v-if="!forms_count && !hasForms" @create="createFormModal = true"/>
        <!--We Have forms Now-->
        <div class="all_payforms_wrapper payform_section" v-else>
            <div class="payform_section_header all_payment_form_wrapper">
                <h1 class="payform_section_title">
                    {{ $t('All Jobs') }}
                </h1>
                <div class="payform_section_actions">
                    <div class="payform_action search_action">
                        <el-input @keyup.enter="fetchForms()" size="small" placeholder="Search" v-model="search_string" class="input-with-select">
                            <template #append>
                                <el-button @click="fetchForms()" :icon="Search"></el-button>
                            </template>
                        </el-input>
                    </div>
                    <el-button class="payform_action" @click="createFormModal = true" size="small" type="primary">
                        {{ $t( 'Add New Job' ) }}
                    </el-button>
                </div>
            </div>
            <div v-loading.fullscreen.lock="duplicatingForm" element-loading-text="Duplicating the form.. Please wait..." class="payform_section_body">
                <el-table
                    class="payform_tables"
                    v-loading.body="loading"
                    :data="paymentForms"
                    border>

                    <el-table-column :label="$t('ID')" width="90">
                        <template #default="scope">
                            <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                {{ scope.row.ID }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :label="$t('Title')">
                        <template #default="scope">
                            <strong>
                                {{ scope.row.post_title }}
                            </strong>
                            <div class="row-actions">
                                <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                    {{ $t('Edit Form') }}
                                </router-link>
                                |
                                <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                    {{ $t('Applications') }}
                                </router-link>
                                |
                                <a v-if="scope.row.post_status != 'trash'" :href="escUrl(scope.row.edit_url)">{{ $t('Edit Post') }}</a>
                                <a v-else @click="restorePost(scope.row.ID)" href="#">{{ $t('Restore') }}</a>
                                |
                                <a @click.prevent="confirmDeleteForm(scope.row)" href="#">{{ $t('Delete') }}</a>
                                |
                                <a href="#" @click.prevent="duplicateForm(scope.row.ID)">{{ $t('Duplicate Job Post') }}</a>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="Status" width="120">
                        <template #default="scope">
                            {{scope.row.post_status}}
                        </template>
                    </el-table-column>
                    <el-table-column label="Applications" width="120">
                        <template #default="scope">
                            <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                {{scope.row.entries_count}}
                            </router-link>

                        </template>
                    </el-table-column>
                    <el-table-column label="Create Date" width="120">
                        <template #default="scope">
                            {{ $dateFormat(scope.row.post_date_gmt) }}
                        </template>
                    </el-table-column>
                </el-table>
                <div class="wpf_pagination">
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
        <!-- Load Modals-->
        <create-form v-if="createFormModal" v-model:modalVisible="createFormModal"/>

        <!--Delete form Confimation Modal-->
        <el-dialog
            title="Are You Sure, You want to delete this job?"
            v-model="deleteDialogVisible"
            :before-close="handleDeleteClose"
            width="60%">
            <div class="modal_body">
                <p>All the data assoscilate with this form will be deleted, applications and other
                    associate information</p>
                <p>You are deleting form id: <b>{{ deleteingForm.ID }}</b>. <br/>Job Title: <b>{{
                    deleteingForm.post_title }}</b></p>
            </div>
            <template #footer>
                <span class="dialog-footer">
                    <el-button @click="deleteDialogVisible = false">Cancel</el-button>
                    <el-button type="primary" @click="deleteFormNow()">Confirm</el-button>
                </span>
            </template>
        </el-dialog>
    </div>
</template>

<script>
    import { Search } from '@element-plus/icons-vue';
    import Welcome from '../Common/Welcome.vue';
    import CreateForm from './CreateForm.vue';

    export default {
        name: 'AllForms',
        components: { CreateForm, Welcome },
        setup() {
            return { Search };
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
            this.$setTitle('All Jobs');
        }
    }
</script>
