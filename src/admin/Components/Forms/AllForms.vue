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
                        <el-input @keyup.enter.native="fetchForms()" size="small" placeholder="Search" v-model="search_string" class="input-with-select">
                            <el-button @click="fetchForms()" slot="append" icon="el-icon-search"></el-button>
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

                    <el-table-column :label="$t('ID')" width="70">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                {{ scope.row.ID }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :label="$t('Title')">
                        <template slot-scope="scope">
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
                        <template slot-scope="scope">
                            {{scope.row.post_status}}
                        </template>
                    </el-table-column>
                    <el-table-column label="Applications" width="120">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                {{scope.row.entries_count}}
                            </router-link>

                        </template>
                    </el-table-column>
                    <el-table-column label="Craete Date" width="120">
                        <template slot-scope="scope">
                            {{scope.row.post_date_gmt | dateFormat}}
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
        <create-form v-if="createFormModal" :modalVisible.sync="createFormModal"/>

        <!--Delete form Confimation Modal-->
        <el-dialog
            title="Are You Sure, You want to delete this job?"
            :visible.sync="deleteDialogVisible"
            :before-close="handleDeleteClose"
            width="60%">
            <div class="modal_body">
                <p>All the data assoscilate with this form will be deleted, applications and other
                    associate information</p>
                <p>You are deleting form id: <b>{{ deleteingForm.ID }}</b>. <br/>Job Title: <b>{{
                    deleteingForm.post_title }}</b></p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="deleteDialogVisible = false">Cancel</el-button>
                <el-button type="primary" @click="deleteFormNow()">Confirm</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
    import Welcome from '../Common/Welcome';
    import CreateForm from './CreateForm';

    export default {
        name: 'AllForms',
        components: { CreateForm, Welcome },
        comments: {
            Welcome
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
                this.$adminGet({
                    route: 'get_forms',
                    per_page: this.per_page,
                    page_number: this.page_number,
                    search_string: this.search_string
                })
                    .then(response => {
                        this.paymentForms = response.data.forms;
                        this.hasForms = !!response.data.total;
                        this.total = response.data.total;
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            confirmDeleteForm(form) {
                this.deleteingForm = form;
                this.deleteDialogVisible = true;
            },
            restorePost(id) {
                this.$adminPost({
                    action: 'wpjobboard_forms_admin_ajax',
                    route: 'restore_form',
                    form_id: id
                })
                    .then(response => {
                        this.$message.success({
                            message: response.data.message
                        });
                        this.fetchForms();
                    })
                    .fail(error => {
                        this.$message.error({
                            message: error.responseJSON.data.message
                        });
                    })
                    .always(() => {
                        this.deleteDialogVisible = false;
                        this.deleteingForm = {};
                    });
            },
            escUrl(url) {
                return url.replace('&amp;', '&');
            },
            deleteFormNow() {
                this.$adminPost({
                    action: 'wpjobboard_forms_admin_ajax',
                    route: 'delete_form',
                    form_id: this.deleteingForm.ID
                })
                    .then(response => {
                        this.$message.success({
                            message: response.data.message
                        });
                        this.fetchForms();
                    })
                    .fail(error => {
                        this.$message.error({
                            message: error.responseJSON.data.message
                        });
                    })
                    .always(() => {
                        this.deleteDialogVisible = false;
                        this.deleteingForm = {};
                    });
            },
            handleDeleteClose() {
                this.this.deleteingForm = {};
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
                this.$post({
                    action: 'wpjobboard_forms_admin_ajax',
                    route: 'duplicate_form',
                    form_id: formId
                })
                    .then(response => {
                        if(response.data.form.ID) {
                            this.$notify.success(response.data.message);
                            this.$router.push({
                                name: 'edit_form',
                                params: {
                                    form_id: response.data.form.ID
                                }
                            });
                        } else {
                            this.$notify.error('Something is wrong! Please try again');
                        }
                    })
                    .fail((error) => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.duplicatingForm = false;
                    });
            }
        },
        mounted() {
            this.fetchForms();
            window.WPJobBoardBus.$emit('site_title', 'All Jobs');
        }
    }
</script>