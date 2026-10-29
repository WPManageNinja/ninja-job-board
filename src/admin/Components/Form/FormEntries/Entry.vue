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
                        {{ submission.post_title }} #{{ submission.id }}
                    </div>
                    <div class="wpf_header_actions">
                        <el-button-group>
                            <el-button size="small" @click="handleNavClick('next')" type="info"
                                       :icon="DArrowLeft">
                                Prev
                            </el-button>
                            <el-button readonly size="small" disabled>{{ submission.id }}</el-button>
                            <el-button size="small" @click="handleNavClick('prev')" type="info">Next <el-icon style="margin-left: 5px"><DArrowRight /></el-icon></el-button>
                        </el-button-group>
                    </div>
                </div>

                <div class="payment_header">
                    <div class="payment_head_bottom">
                        <div class="info_block">
                            <div class="info_header">Date</div>
                            <div class="info_value">{{ submission.created_at }}</div>
                        </div>
                        <div class="info_block">
                            <div class="info_header">Email</div>
                            <div class="info_value">
                                <span v-if="submission.applicant_email">
                                    <a target="_blank" :href="'mailto:'+submission.applicant_email">
                                        {{ submission.applicant_email }}
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
                                        {{ submission.applicant_name }}
                                    </a>
                                    <span v-else>
                                        {{ submission.applicant_name }}
                                    </span>
                                </span>
                                <span v-else>n/a</span>
                            </div>
                        </div>
                        <div v-if="submission.application_status" class="info_block">
                            <div class="info_header">Application Status</div>
                            <div class="info_value wpf_capitalize">
                                <span>{{ submission.application_status }}</span>
                            </div>
                        </div>
                        <div v-if="submission.status" class="info_block">
                            <div class="info_header">Internal Status</div>
                            <div class="info_value wpf_capitalize">
                                <span>{{ submission.status }}</span>
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
                                    <el-checkbox true-value="yes" false-value="no" v-model="show_empty">Show empty
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
                                            {{ entry.label }}
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
                                    <el-button @click="add_note_box = !add_note_box" size="small" type="info">Add Note
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
                                                {{ activity.created_by }} - {{ activity.created_at }}
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
                                    <div class="wpjb_field">
                                        <el-select v-model="submission.application_status" size="small">
                                            <el-option
                                                v-for="(status, status_value) in available_application_statuses"
                                                :key="status_value"
                                                :value="status_value"
                                                :label="status"
                                            ></el-option>
                                        </el-select>
                                    </div>
                                </label>
                                <label>
                                    Internal Status
                                    <div class="wpjb_field">
                                        <el-select v-model="submission.status" size="small">
                                            <el-option
                                                v-for="(status, status_value) in available_internal_statuses"
                                                :key="status_value"
                                                :value="status_value"
                                                :label="status"
                                            ></el-option>
                                        </el-select>
                                    </div>
                                </label>
                                <br/>
                                <el-button size="small" type="primary" @click="updateStatuses()">Update</el-button>
                            </div>
                        </div>
                        <div v-if="other_entries && other_entries.length" class="entry_info_box">
                            <div class="entry_info_header">
                                <div class="info_box_header">Other Job Applications</div>
                            </div>
                            <div class="entry_info_body wpf_meta_info">
                                <ul>
                                    <li v-for="other_entry in other_entries" :key="other_entry.id">
                                        <a :href="other_entry.permalink">{{other_entry.post_title}}</a>
                                        <span class="wpf_card_badge">{{other_entry.status}}</span>
                                        <span>{{ $dateFormat(other_entry.created_at) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="entry_info_box">
                            <div class="entry_info_header">
                                <div class="info_box_header">Meta Info</div>
                            </div>
                            <div class="entry_info_body wpf_meta_info">
                                <ul>
                                    <li>User Browser: {{ submission.browser }}</li>
                                    <li>Platform: {{ submission.device }}</li>
                                    <li>IP Address: <a target="_blank" rel="noopener"
                                                       :href="'https://ipinfo.io/'+submission.ip_address">{{ submission.ip_address }}</a>
                                    </li>
                                    <li v-if="submission.user">User: <a target="_blank" rel="noopener"
                                                                        :href="submission.user.profile_url">{{ submission.user.display_name }}</a>
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
                <el-button @click="print" type="info" size="small" :icon="Printer">Print this entry</el-button>
                <el-button @click="exportJSON()" type="info" size="small" :icon="DocumentCopy">Export JSON
                </el-button>
                <el-button v-if="hide_sidebar == 'yes'" @click="hide_sidebar = 'no'" size="small">Show Meta Info
                </el-button>
            </div>
        </div>
    </div>
</template>

<script>
import { DArrowLeft, DArrowRight, Printer, DocumentCopy } from '@element-plus/icons-vue';

export default {
    name: "Entry",
    components: { DArrowRight },
    setup() {
        return { DArrowLeft, DArrowRight, Printer, DocumentCopy };
    },
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
            hide_sidebar: 'no',
            other_entries: []
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
            this.$api.get(`/applications/${parseInt(this.entry_id)}`)
                .then(response => {
                    this.submission = response.submission;
                    this.entry_items = response.entry;
                    this.other_entries = response.other_entries;
                    this.$setTitle('Entry#' + response.submission.id);
                })
                .catch(error => {
                    this.$showAjaxError(error);
                })
                .finally(() => {
                    this.fething = false;
                });
        },
        handleNavClick(type) {
            this.loading = true;
            this.$api.get(`/applications/${parseInt(this.entry_id)}/adjacent`, {
                form_id: parseInt(this.form_id),
                direction: type
            })
                .then(response => {
                    this.submission = response.submission;
                    this.entry_items = response.entry;
                    this.entry_id = response.submission.id;
                    this.other_entries = response.other_entries;
                    this.$setTitle('Entry#' + response.submission.id);
                    this.$router.push({
                        name: 'entry',
                        params: {entry_id: response.submission.id},
                        query: {form_id: this.form_id}
                    })
                })
                .catch(error => {
                    this.$message.error({
                        message: error.message
                    });
                })
                .finally(() => {
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
            this.$api.post(`/applications/${this.submission.id}/notes`, {
                note: this.new_note_content
            })
                .then(response => {
                    this.submission.activities = response.activities;
                    this.$message({
                        message: response.message,
                        type: 'success'
                    });
                })
                .catch(error => {
                    this.$message({
                        message: error.message,
                        type: 'error'
                    });
                })
                .finally(() => {
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
            this.$api.post(`/applications/${this.submission.id}/status`, {
                application_status: this.application_status_edit_model.status,
                note: this.application_status_edit_model.note
            })
                .then(response => {
                    this.editApplicationStatusModal = false;
                    this.$message.success(response.message);
                    this.application_status_edit_model = {
                        status: '',
                        note: ''
                    };
                    this.getEntry();
                })
                .catch(error => {
                    this.$message.error(error.message);
                });
        },
        changeInternalStatus() {
            this.$api.post(`/applications/${this.submission.id}/status`, {
                status: this.internal_status_edit_model.status,
                note: this.internal_status_edit_model.note
            })
                .then(response => {
                    this.editInternalStatusModal = false;
                    this.$message.success(response.message);
                    this.internal_status_edit_model = {
                        status: '',
                        note: ''
                    };
                    this.getEntry();
                })
                .catch(error => {
                    this.$message.error(error.message);
                });
        },
        // Small localStorage helpers (the old global mixin had these)
        setStoreData(key, value) {
            try {
                window.localStorage.setItem('wppayforms_' + key, value);
            } catch (e) {
                // storage is not available
            }
        },
        getFromStore(key, defaultValue) {
            try {
                const itemValue = window.localStorage.getItem('wppayforms_' + key);
                if (itemValue) {
                    return itemValue;
                }
            } catch (e) {
                // storage is not available
            }
            return defaultValue;
        },
        isUrl(maybeUrl) {
            var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
            return regexp.test(maybeUrl);
        },
        print() {
            this.$printElement(document.getElementById('print_view'));
        },
        exportJSON() {
            let submission = JSON.parse(JSON.stringify(this.submission));
            delete submission.currencySetting;
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
        },
        updateStatuses() {
            this.$api.post(`/applications/${this.submission.id}/status`, {
                application_status: this.submission.application_status,
                status: this.submission.status
            })
                .then(response => {
                    this.$message.success(response.message);
                    this.getEntry();
                })
                .catch(error => {
                    this.$message.error(error.message);
                });
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
