<template>
    <div id="print_view" class="wpjb_app wpjb_entry_print">
        <div v-loading="fething"
             :class="print_hides"
             :element-loading-text="$t('Loading application...')"
             class="wpjb_entry">
            <template v-if="submission.id">
                <router-link class="wpjb_entry_back"
                             :to="{ name: 'form_entries', params: { form_id: form_id } }">
                    <el-icon><ArrowLeft/></el-icon>
                    {{ $t('Back to applications') }}
                </router-link>

                <div class="wpjb_entry_header">
                    <div class="wpjb_entry_heading">
                        <h1 class="wpjb_entry_title">
                            <!-- translators: %s is the application ID number -->
                            {{ submission.applicant_name || $t('Application #%s', submission.id) }}
                        </h1>
                        <p class="wpjb_entry_meta">
                            <a v-if="submission.applicant_email"
                               :href="'mailto:' + submission.applicant_email">{{ submission.applicant_email }}</a>
                            <!-- translators: %s is the date and time the application was submitted -->
                            <span class="wpjb_num">{{ $t('Submitted %s', formatDate(submission.created_at)) }}</span>
                        </p>
                    </div>
                    <div class="wpjb_entry_actions wpf_header_actions">
                        <div class="wpjb_entry_nav" role="group" :aria-label="$t('Browse applications')">
                            <el-button :icon="ArrowLeft"
                                       :loading="loading"
                                       :aria-label="$t('Previous application')"
                                       :title="$t('Previous application')"
                                       @click="handleNavClick('next')">{{ $t('Previous') }}</el-button>
                            <el-button :aria-label="$t('Next application')"
                                       :title="$t('Next application')"
                                       :disabled="loading"
                                       @click="handleNavClick('prev')">
                                {{ $t('Next') }}
                                <el-icon class="wpjb_icon_after"><ArrowRight/></el-icon>
                            </el-button>
                        </div>
                        <el-button :icon="Download"
                                   :aria-label="$t('Export as JSON')"
                                   :title="$t('Export as JSON')"
                                   @click="exportJSON()"/>
                        <el-button :icon="Printer"
                                   :aria-label="$t('Print application')"
                                   :title="$t('Print application')"
                                   @click="print"/>
                    </div>
                </div>

                <div class="wpjb_entry_grid">
                    <div class="wpjb_entry_main">
                        <section class="wpjb_card entry_input_data">
                            <div class="wpjb_card_head">
                                <h3>{{ $t('Application') }}</h3>
                                <div class="info_box_header_actions">
                                    <el-switch v-model="show_empty"
                                               active-value="yes"
                                               inactive-value="no"
                                               size="small"
                                               :active-text="$t('Show empty fields')"/>
                                </div>
                            </div>
                            <dl class="wpjb_answers">
                                <div v-for="(entry, entry_id) in entry_items"
                                     v-show="show_empty == 'yes' || entry.value"
                                     :key="entry_id"
                                     class="wpjb_answer">
                                    <dt>{{ entry.label }}</dt>
                                    <dd v-if="entry.value" v-html="entry.value"></dd>
                                    <dd v-else class="is-empty">{{ $t('Not provided') }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="wpjb_card entry_submission_activity">
                            <div class="wpjb_card_head">
                                <h3>{{ $t('Activity') }}</h3>
                                <div class="info_box_header_actions">
                                    <el-button size="small"
                                               :icon="Plus"
                                               :aria-expanded="add_note_box ? 'true' : 'false'"
                                               @click="add_note_box = !add_note_box">{{ $t('Add note') }}</el-button>
                                </div>
                            </div>
                            <div class="wpjb_card_body">
                                <div v-if="add_note_box" class="wpjb_note_box">
                                    <el-input type="textarea"
                                              :autosize="{ minRows: 3 }"
                                              :aria-label="$t('Note')"
                                              :placeholder="$t('Write a note for your team')"
                                              v-model="new_note_content"/>
                                    <div class="wpjb_note_actions">
                                        <el-button size="small" @click="add_note_box = false">{{ $t('Cancel') }}</el-button>
                                        <el-button size="small"
                                                   type="primary"
                                                   :loading="adding_note"
                                                   @click="submitNote()">{{ $t('Save note') }}</el-button>
                                    </div>
                                </div>

                                <ol v-if="submission.activities && submission.activities.length"
                                    class="wpjb_timeline">
                                    <li v-for="activity in submission.activities" :key="activity.id">
                                        <div class="wpjb_timeline_meta">
                                            <strong>{{ activity.created_by }}</strong>
                                            <span class="wpjb_num">{{ formatDate(activity.created_at) }}</span>
                                        </div>
                                        <div class="wpjb_timeline_content" v-html="activity.content"></div>
                                    </li>
                                </ol>
                                <p v-else class="wpjb_entry_none">{{ $t('No activity yet. Status changes and notes show here.') }}</p>
                            </div>
                        </section>
                    </div>

                    <aside class="wpjb_entry_side wpf_submission_sidebar">
                        <section class="wpjb_card">
                            <div class="wpjb_card_head">
                                <h3>{{ $t('Status') }}</h3>
                            </div>
                            <div class="wpjb_card_body">
                                <div class="wpjb_status_now">
                                    <status-badge type="application" :title="$t('Application status')" :status="saved_statuses.application_status"/>
                                    <status-badge type="internal" :title="$t('Internal status')" :status="saved_statuses.status"/>
                                </div>
                                <div class="wpjb_status_field">
                                    <label for="wpjb_application_status">{{ $t('Application status') }}</label>
                                    <el-select id="wpjb_application_status" v-model="submission.application_status">
                                        <el-option
                                            v-for="(status, status_value) in available_application_statuses"
                                            :key="status_value"
                                            :value="status_value"
                                            :label="status"
                                        ></el-option>
                                    </el-select>
                                </div>
                                <div class="wpjb_status_field">
                                    <label for="wpjb_internal_status">{{ $t('Internal status') }}</label>
                                    <el-select id="wpjb_internal_status" v-model="submission.status">
                                        <el-option
                                            v-for="(status, status_value) in available_internal_statuses"
                                            :key="status_value"
                                            :value="status_value"
                                            :label="status"
                                        ></el-option>
                                    </el-select>
                                </div>
                                <el-button type="primary" @click="updateStatuses()">{{ $t('Update status') }}</el-button>
                            </div>
                        </section>

                        <section class="wpjb_card">
                            <div class="wpjb_card_head">
                                <h3>{{ $t('Details') }}</h3>
                            </div>
                            <dl class="wpjb_details">
                                <div>
                                    <dt>{{ $t('Submitted at') }}</dt>
                                    <dd class="wpjb_num wpjb_nowrap">{{ formatDate(submission.created_at) }}</dd>
                                </div>
                                <div v-if="submission.ip_address">
                                    <dt>{{ $t('IP address') }}</dt>
                                    <dd class="wpjb_num">
                                        <a target="_blank" rel="noopener"
                                           :href="'https://ipinfo.io/' + submission.ip_address">{{ submission.ip_address }}</a>
                                    </dd>
                                </div>
                                <div v-if="submission.browser">
                                    <dt>{{ $t('Browser') }}</dt>
                                    <dd>{{ submission.browser }}</dd>
                                </div>
                                <div v-if="submission.device">
                                    <dt>{{ $t('Platform') }}</dt>
                                    <dd>{{ submission.device }}</dd>
                                </div>
                                <div v-if="submission.user">
                                    <dt>{{ $t('User') }}</dt>
                                    <dd>
                                        <a target="_blank" rel="noopener"
                                           :href="submission.user.profile_url">{{ submission.user.display_name }}</a>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section v-if="other_entries && other_entries.length" class="wpjb_card">
                            <div class="wpjb_card_head">
                                <h3>{{ $t('Other applications by this applicant') }}</h3>
                            </div>
                            <ul class="wpjb_other_apps">
                                <li v-for="other_entry in other_entries" :key="other_entry.id">
                                    <div class="wpjb_other_app_text">
                                        <a :href="other_entry.permalink">{{ other_entry.post_title }}</a>
                                        <span class="wpjb_num">{{ $dateFormat(other_entry.created_at, 'MMM D, YYYY') }}</span>
                                    </div>
                                    <status-badge type="internal" :status="other_entry.status"/>
                                </li>
                            </ul>
                        </section>
                    </aside>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { ArrowLeft, ArrowRight, Printer, Download, Plus } from '@element-plus/icons-vue';
import StatusBadge from '../../Common/StatusBadge.vue';

export default {
    name: "Entry",
    components: { ArrowLeft, ArrowRight, StatusBadge },
    setup() {
        return { ArrowLeft, ArrowRight, Printer, Download, Plus };
    },
    data() {
        return {
            submission: {},
            entry_id: this.$route.params.entry_id,
            entry_items: {},
            form_id: this.$route.params.form_id,
            fething: false,
            loading: false,
            show_empty: 'no',
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
            other_entries: [],
            // Saved values, so the badges do not change before "Update status"
            saved_statuses: {
                application_status: '',
                status: ''
            }
        }
    },
    watch: {
        show_empty() {
            this.setStoreData('show_empty_entry_field', this.show_empty);
        }
    },
    methods: {
        setSavedStatuses(submission) {
            this.saved_statuses = {
                application_status: submission.application_status || '',
                status: submission.status || ''
            };
        },
        formatDate(date) {
            return this.$dateFormat(date, 'MMM D, YYYY [at] h:mm A') || date;
        },
        getEntry() {
            this.fething = true;
            this.$api.get(`/applications/${parseInt(this.entry_id)}`)
                .then(response => {
                    this.submission = response.submission;
                    this.entry_items = response.entry;
                    this.other_entries = response.other_entries;
                    this.setSavedStatuses(response.submission);
                    // translators: %s is the application ID number
                    this.$setTitle(this.$t('Application #%s', response.submission.id));
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
                    this.setSavedStatuses(response.submission);
                    // translators: %s is the application ID number
                    this.$setTitle(this.$t('Application #%s', response.submission.id));
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
                    message: this.$t('Write a note first.'),
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
        this.show_empty = this.getFromStore('show_empty_entry_field', 'no') === 'yes' ? 'yes' : 'no';
    }
}
</script>
