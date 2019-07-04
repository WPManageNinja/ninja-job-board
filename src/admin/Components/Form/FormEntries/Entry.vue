<template>
    <div id="print_view">
        <div style="min-height: 300px"
             v-loading="fething"
             :class="print_hides"
             element-loading-text="Loading Entry..."
             class="wpf_payment_view">
            <template v-if="submission.id">
                <div class="payment_head_info">
                    <router-link class="payhead_nav_item payhead_back_icon"
                                 :to="{ name: 'form_entries', params: { form_id: form_id } }"><span
                        class="dashicons dashicons-admin-home"></span></router-link>
                    <div class="payhead_title">
                        {{ submission.post_title }} #{{submission.id}}
                    </div>
                    <div class="wpf_header_actions">
                        <el-button-group>
                            <el-button size="mini" @click="handleNavClick('next')" type="info"
                                       icon="el-icon-d-arrow-left">
                                Prev
                            </el-button>
                            <el-button readonly size="mini" disabled type="plain">{{submission.id}}</el-button>
                            <el-button size="mini" @click="handleNavClick('prev')" type="info">Next <i
                                class="el-icon-d-arrow-right el-icon-right"></i></el-button>
                        </el-button-group>
                    </div>
                </div>

                <div class="payment_header">
                    <div class="payment_head_bottom">
                        <div class="info_block">
                            <div class="info_header">Date</div>
                            <div class="info_value">{{submission.created_at}}</div>
                        </div>
                        <div class="info_block">
                            <div class="info_header">Email</div>
                            <div class="info_value">
                                <span v-if="submission.applicant_email">
                                    <a target="_blank" :href="'mailto:'+submission.applicant_email">
                                        {{submission.applicant_email}}
                                    </a>
                                </span>
                                <span v-else>n/a</span>
                            </div>
                        </div>
                        <div class="info_block">
                            <div class="info_header">Name</div>
                            <div class="info_value">
                                <span class="wpf_capitalize" v-if="submission.applicant_name">
                                    <a :href="submission.user_profile_url" target="_blank"
                                       v-if="submission.user_profile_url">
                                        {{submission.applicant_name}}
                                    </a>
                                    <span v-else>
                                        {{submission.applicant_name}}
                                    </span>
                                </span>
                                <span v-else>n/a</span>
                            </div>
                        </div>
                        <div v-if="submission.application_status" class="info_block">
                            <div class="info_header">Application Status</div>
                            <div class="info_value wpf_capitalize">
                                <span>{{submission.application_status}}</span>
                            </div>
                        </div>
                        <div v-if="submission.status" class="info_block">
                            <div class="info_header">Internal Status</div>
                            <div class="info_value wpf_capitalize">
                                <span>{{submission.status}}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wpf_submisson_body" :class="(hide_sidebar == 'yes') ? 'wpf_hide_sidebar' : ''">
                    <div class="wpf_submission_details">
                        <div class="entry_info_box entry_input_data">
                            <div class="entry_info_header">
                                <div class="info_box_header">Application Data</div>
                                <div class="info_box_header_actions">
                                    <el-checkbox true-label="yes" false-label="no" v-model="show_empty">Show empty
                                        fields
                                    </el-checkbox>
                                </div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_details">
                                    <div v-for="(entry, entry_id) in entry_items"
                                         v-show="show_empty == 'yes' || entry.value"
                                         :key="entry_id" class="wpf_each_entry">
                                        <div class="wpf_entry_label">
                                            {{entry.label}}
                                        </div>
                                        <div class="wpf_entry_value" v-html="entry.value"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="entry_info_box entry_submission_activity">
                            <div class="entry_info_header">
                                <div class="info_box_header">Application Activity Events</div>
                                <div class="info_box_header_actions">
                                    <el-button @click="add_note_box = !add_note_box" size="mini" type="info">Add Note
                                    </el-button>
                                </div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_details">
                                    <div v-if="add_note_box" class="wpf_add_note_box">
                                        <el-input
                                            type="textarea"
                                            :autosize="{ minRows: 3}"
                                            placeholder="Please Provide Note Content"
                                            v-model="new_note_content">
                                        </el-input>
                                        <el-button @click="submitNote()" size="small" type="success">Submit Note
                                        </el-button>
                                    </div>
                                    <template v-if="submission.activities && submission.activities.length">
                                        <div v-for="activity in submission.activities" :key="activity.id"
                                             class="wpf_each_entry">
                                            <div class="wpf_entry_label">
                                                {{activity.created_by}} - {{ activity.created_at }}
                                            </div>
                                            <div class="wpf_entry_value" v-html="activity.content"></div>
                                        </div>
                                    </template>

                                    <div class="wpf_each_entry text-center" v-else>
                                        <p>No Activity found</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wpf_submission_sidebar">
                        <div class="entry_info_box">
                            <div class="entry_info_header">
                                <div class="info_box_header">Status</div>
                            </div>
                            <div class="entry_info_body wpf_meta_info">
                                <label>
                                    Application Status
                                    <el-select size="mini">

                                    </el-select>

                                </label>
                            </div>
                        </div>
                        <div class="entry_info_box">
                            <div class="entry_info_header">
                                <div class="info_box_header">Meta Info</div>
                            </div>
                            <div class="entry_info_body wpf_meta_info">
                                <ul>
                                    <li>User Browser: {{submission.browser}}</li>
                                    <li>Platform: {{submission.device}}</li>
                                    <li>IP Address: <a target="_blank" rel="noopener"
                                                       :href="'https://ipinfo.io/'+submission.ip_address">{{submission.ip_address}}</a>
                                    </li>
                                    <li v-if="submission.user">User: <a target="_blank" rel="noopener"
                                                                        :href="submission.user.profile_url">{{submission.user.display_name}}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="entry_info_box">
                            <div class="entry_info_header">
                                <div class="info_box_header">Recommended Plugins</div>
                            </div>
                            <div class="entry_info_body wpf_meta_info">
                                <p>We have developed few awesome plugin that you can give a try too</p>
                                <ul class="support_items">
                                    <li>
                                        <b><a target="_blank"
                                              href="https://wordpress.org/plugins/wp-payment-form/">WPPayForm</a></b> -
                                        WordPress Payments made simple (100% free)
                                    </li>
                                    <li>
                                        <b><a target="_blank"
                                              href="https://wpmanageninja.com/downloads/ninja-tables-pro-add-on/">Ninja
                                            Tables Pro</a></b> - The Fastest and Most Diverse WordPress Table Plugin
                                    </li>
                                    <li>
                                        <b><a target="_blank" href="https://wpmanageninja.com/wp-fluent-form/">WP
                                            Fluent Form</a></b> - Make Effortless Contact Forms In Minutes!
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="print_button_controls">
                <el-button @click="print" type="info" size="mini" icon="el-icon-printer">Print this entry</el-button>
                <el-button @click="exportJSON()" type="info" size="mini" icon="el-icon-document-copy">Export JSON
                </el-button>
                <el-button v-if="hide_sidebar == 'yes'" @click="hide_sidebar = 'no'" size="mini">Show Meta Info
                </el-button>
            </div>
            <!--Edit Application Status Modal-->
            <el-dialog
                title="Edit Payment Status"
                :visible.sync="editApplicationStatusModal"
                width="50%">
                <div class="modal_body">
                    <p>Current Application Status: <b>{{ submission.application_status }}</b></p>
                    <el-form ref="application_status_form" :model="application_status_edit_model" label-width="180px">
                        <el-form-item label="New Application Status">
                            <el-radio-group v-model="application_status_edit_model.status">
                                <el-radio v-for="(status, status_key) in available_application_statuses" :key="status"
                                          :label="status_key">{{status}}
                                </el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="Note">
                            <el-input type="textarea" placeholder="You may add a note for this status change (optional)"
                                      size="mini"
                                      v-model="application_status_edit_model.note"></el-input>
                        </el-form-item>
                    </el-form>
                </div>
                <span slot="footer" class="dialog-footer">
                <el-button @click="editApplicationStatusModal = false">Cancel</el-button>
                <el-button type="primary" @click="changeApplicationStatus()">Confirm</el-button>
            </span>
            </el-dialog>

            <!--Edit Internal Status Modal-->
            <el-dialog
                title="Edit Payment Status"
                :visible.sync="editInternalStatusModal"
                width="50%">
                <div class="modal_body">
                    <p>Current Internal Status: <b>{{ submission.status }}</b></p>
                    <el-form ref="internal_status_form" :model="internal_status_edit_model" label-width="180px">
                        <el-form-item label="New Internal Status">
                            <el-radio-group v-model="internal_status_edit_model.status">
                                <el-radio v-for="(status, status_key) in available_internal_statuses" :key="status"
                                          :label="status_key">{{status}}
                                </el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="Note">
                            <el-input type="textarea" placeholder="You may add a note for this status change (optional)"
                                      size="mini"
                                      v-model="internal_status_edit_model.note"></el-input>
                        </el-form-item>
                    </el-form>
                </div>
                <span slot="footer" class="dialog-footer">
                <el-button @click="editInternalStatusModal = false">Cancel</el-button>
                <el-button type="primary" @click="changeInternalStatus()">Confirm</el-button>
            </span>
            </el-dialog>
        </div>
    </div>
</template>

<script type="text/babel">
    import each from 'lodash/each';
    import fromatPrice from '../../../../common/formatPrice';
    import omit from 'lodash/omit';

    export default {
        name: "Entry",
        components: {},
        data() {
            return {
                submission: {},
                entry_id: this.$route.params.entry_id,
                entry_items: {},
                form_id: this.$route.params.form_id,
                fething: false,
                loading: false,
                show_empty: false,
                add_note_box: false,
                new_note_content: '',
                adding_note: false,
                editApplicationStatusModal: false,
                editInternalStatusModal: false,
                application_status_edit_model: {
                    status: '',
                    note: ''
                },
                internal_status_edit_model: {
                    status: '',
                    note: ''
                },
                available_application_statuses: window.wpJobBoardsAdmin.applicationStatuses,
                available_internal_statuses: window.wpJobBoardsAdmin.internalStatuses,
                print_hides: [
                    'print_hide_activities'
                ],
                show_print_settings: false,
                show_print_pro: false,
                hide_sidebar: 'no'
            }
        },
        watch: {
            show_empty() {
                this.setStoreData('show_empty_entry_field', this.show_empty);
            },
            hide_sidebar() {
                this.setStoreData('entry_sidebar_status', this.hide_sidebar);
            }
        },
        methods: {
            getEntry() {
                this.fething = true;
                const query = {
                    action: 'wpjb_submission_endpoints',
                    route: 'get_submission',
                    form_id: parseInt(this.form_id),
                    submission_id: parseInt(this.entry_id)
                }
                this.$get(query)
                    .then(response => {
                        this.submission = response.data.submission;
                        this.entry_items = response.data.entry;
                        window.WPJobBoardBus.$emit('site_title', 'Entry#' + response.data.submission.id);
                    })
                    .always(() => {
                        this.fething = false;
                    });
            },
            getApplicationStatusIcon(status) {
                if (status == 'pending') {
                    return 'el-icon-time';
                } else if (status == 'paid') {
                    return 'el-icon-check';
                } else if (status == 'failed') {
                    return 'el-icon-error';
                } else if (status == 'refunded') {
                    return 'el-icon-warning';
                }
                return '';
            },
            handleNavClick(type) {
                this.loading = true;
                const query = {
                    action: 'wpjb_submission_endpoints',
                    route: 'get_next_prev_submission',
                    form_id: parseInt(this.form_id),
                    type: type,
                    current_submission_id: parseInt(this.entry_id)
                }
                this.$get(query)
                    .then(response => {
                        this.submission = response.data.submission;
                        this.entry_items = response.data.entry;
                        this.entry_id = response.data.submission.id;
                        window.WPJobBoardBus.$emit('site_title', 'Entry#' + response.data.submission.id);
                        this.$router.push({
                            name: 'entry',
                            params: {entry_id: response.data.submission.id},
                            query: {form_id: this.form_id}
                        })
                    })
                    .fail(error => {
                        this.$message.error({
                            message: error.responseJSON.data.message
                        });
                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            submitNote() {
                if (!this.new_note_content) {
                    this.$message({
                        message: 'Please provide note',
                        type: 'error'
                    });
                    return;
                }
                this.adding_note = true;
                this.$post({
                    action: 'wpjb_submission_endpoints',
                    route: 'add_submission_note',
                    form_id: this.submission.form_id,
                    submission_id: this.submission.id,
                    note: this.new_note_content
                })
                    .then(response => {
                        this.submission.activities = response.data.activities;
                        this.$message({
                            message: response.data.message,
                            type: 'success'
                        });
                    })
                    .fail(error => {
                        this.$message({
                            message: error.responseJSON.data.message,
                            type: 'error'
                        });
                    })
                    .always(() => {
                        this.new_note_content = '';
                        this.adding_note = false;
                    });
            },
            handleActionCommand(command) {
                if (command == 'application_status') {
                    this.editApplicationStatusModal = true;
                } else if (command == 'status') {
                    this.editInternalStatusModal = true;
                }
            },
            changeApplicationStatus() {
                this.$post({
                    action: 'wpjb_submission_endpoints',
                    route: 'change_application_status',
                    form_id: this.submission.form_id,
                    submission_id: this.submission.id,
                    new_application_status: this.application_status_edit_model.status,
                    status_change_note: this.application_status_edit_model.note
                })
                    .then(response => {
                        this.editApplicationStatusModal = false;
                        this.$message.success(response.data.message);
                        this.application_status_edit_model = {
                            status: '',
                            note: ''
                        };
                        this.getEntry();
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {

                    });
            },
            changeInternalStatus() {
                this.$post({
                    action: 'wpjb_submission_endpoints',
                    route: 'change_internal_status',
                    form_id: this.submission.form_id,
                    submission_id: this.submission.id,
                    new_internal_status: this.internal_status_edit_model.status,
                    status_change_note: this.internal_status_edit_model.note
                })
                    .then(response => {
                        this.editInternalStatusModal = false;
                        this.$message.success(response.data.message);
                        this.internal_status_edit_model = {
                            status: '',
                            note: ''
                        };
                        this.getEntry();
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {

                    });
            },
            isUrl(maybeUrl) {
                var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
                return regexp.test(maybeUrl);
            },
            print() {
                this.$htmlToPaper('print_view');
            },
            exportJSON() {
                if (!this.has_pro) {
                    this.$notify.error('Export JSON is a pro feature. Please upgrade');
                }
                let submission = JSON.parse(JSON.stringify(this.submission));
                submission = omit(submission, 'currencySetting');
                this.downloadObjectAsJson(submission, 'entry_' + submission.id);
            },
            downloadObjectAsJson(exportObj, exportName) {
                var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
                var downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", exportName + ".json");
                document.body.appendChild(downloadAnchorNode); // required for firefox
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            }
        },
        mounted() {
            if (this.$route.query.form_id) {
                this.form_id = this.$route.query.form_id;
            }
            this.getEntry();
            this.show_empty = this.getFromStore('show_empty_entry_field', false);

            this.hide_sidebar = this.getFromStore('entry_sidebar_status', 'no');
        }
    }
</script>
