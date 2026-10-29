<template>
    <el-form ref="form-bottom" class="wpjb_setting_rows" label-position="top">
        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>Application limit</h3>
            </div>
            <!--Limit Number of Entries-->
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">Limit applications</span>
                    <span class="wpjb_field_help">Close the form when it reaches a set number of applications.</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" aria-label="Limit applications"
                           v-model="form.limitNumberOfEntries.status"></el-switch>
            </el-form-item>

            <!--Additional fields when limit number of entries enabled-->
            <el-form-item v-if="form.limitNumberOfEntries.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">Maximum applications</span>
                    <span class="wpjb_field_help">The number, the period, and which application statuses count.</span>
                </template>
                <div class="wpjb_inline_controls">
                    <el-input-number :min="0" controls-position="right" aria-label="Maximum applications"
                                     v-model="form.limitNumberOfEntries.number_of_entries"
                    ></el-input-number>
                    <el-select v-model="form.limitNumberOfEntries.limit_type" aria-label="Limit period">
                        <el-option v-for="(label, period) in entryPeriodOptions" :key="period"
                                   :label="label" :value="period"
                        ></el-option>
                    </el-select>
                    <el-select class="wpjb_inline_full" multiple placeholder="All application statuses"
                               aria-label="Application statuses that count"
                               v-model="form.limitNumberOfEntries.limit_application_statuses">
                        <el-option v-for="(label, label_value) in applicationStatuses" :key="label_value"
                                   :label="label" :value="label_value"
                        ></el-option>
                    </el-select>
                </div>
            </el-form-item>
            <el-form-item v-if="form.limitNumberOfEntries.status == 'yes'" key="limit-reached-msg">
                <template #label>
                    <span class="wpjb_field_label">Limit reached message</span>
                    <span class="wpjb_field_help">Shown instead of the form when the limit is reached.</span>
                </template>
                <el-input v-model="form.limitNumberOfEntries.limit_exceeds_message" type="textarea" :rows="3"
                          aria-label="Limit reached message"></el-input>
            </el-form-item>
        </div>

        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>Schedule</h3>
            </div>
            <!--Schedule Form-->
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">Schedule the form</span>
                    <span class="wpjb_field_help">Accept applications only between two dates.</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" aria-label="Schedule the form"
                           v-model="form.scheduleForm.status"></el-switch>
            </el-form-item>

            <!--Additional fields when form scheduling enabled-->
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">Opens on</span>
                    <span class="wpjb_field_help">Uses server time. Now: <code>{{ current_date_time }}</code></span>
                </template>
                <el-date-picker
                    value-format="YYYY-MM-DD HH:mm:ss"
                    v-model="form.scheduleForm.start_date"
                    type="datetime"
                    aria-label="Opens on"
                    placeholder="Select date and time">
                </el-date-picker>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">Closes on</span>
                    <span class="wpjb_field_help">The form stops accepting applications at this time.</span>
                </template>
                <el-date-picker
                    value-format="YYYY-MM-DD HH:mm:ss"
                    v-model="form.scheduleForm.end_date"
                    type="datetime"
                    aria-label="Closes on"
                    placeholder="Select date and time"
                    :shortcuts="dateShortcuts">
                </el-date-picker>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">Before opening message</span>
                    <span class="wpjb_field_help">Shown before the start date.</span>
                </template>
                <el-input v-model="form.scheduleForm.before_start_message" type="textarea" :rows="3"
                          aria-label="Before opening message"></el-input>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">After closing message</span>
                    <span class="wpjb_field_help">Shown after the end date.</span>
                </template>
                <el-input v-model="form.scheduleForm.expire_message" type="textarea" :rows="3"
                          aria-label="After closing message"></el-input>
            </el-form-item>
        </div>

        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>Login</h3>
            </div>
            <!--Require user to be logged in-->
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">Require login</span>
                    <span class="wpjb_field_help">Only logged-in users can see the form.</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" aria-label="Require login"
                           v-model="form.requireLogin.status"></el-switch>
            </el-form-item>

            <!--Additional fields when user logged in is enabled-->
            <el-form-item v-if="form.requireLogin.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">Login required message</span>
                    <span class="wpjb_field_help">Shown to logged-out visitors. Shortcodes and HTML work.</span>
                </template>
                <el-input type="textarea" :rows="3" v-model="form.requireLogin.message"
                          aria-label="Login required message"></el-input>
            </el-form-item>
        </div>

        <div v-if="hasAnyRestriction" class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>When a restriction applies</h3>
            </div>
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">Restricted form</span>
                    <span class="wpjb_field_help">What happens to the form while a restriction is active.</span>
                </template>
                <el-radio-group v-model="form.restriction_applied_type" class="wpjb_radio_list">
                    <el-radio value="hide_form">Hide the form</el-radio>
                    <el-radio value="hide_submit_button">Hide the submit button</el-radio>
                    <el-radio value="validation_failed">Show a validation error after submit</el-radio>
                </el-radio-group>
            </el-form-item>
        </div>
    </el-form>
</template>

<script>
    export default {
        name: 'FormRestrictions',
        props: {
            data: {
                required: true
            },
            current_date_time: {
                required: false,
                type: String
            }
        },
        data() {
            return {
                entryPeriodOptions: {
                    total: 'In total',
                    day: 'Per day',
                    week: 'Per week',
                    month: 'Per month',
                    year: 'Per year',
                },
                applicationStatuses: window.wpJobBoardsAdmin.applicationStatuses,
                dateShortcuts: [
                    {
                        text: 'Today',
                        value: () => new Date()
                    },
                    {
                        text: 'Yesterday',
                        value: () => {
                            const date = new Date();
                            date.setTime(date.getTime() - 3600 * 1000 * 24);
                            return date;
                        }
                    },
                    {
                        text: 'A week ago',
                        value: () => {
                            const date = new Date();
                            date.setTime(date.getTime() - 3600 * 1000 * 24 * 7);
                            return date;
                        }
                    }
                ]
            }
        },
        computed: {
            form() {
                return this.data;
            },
            hasAnyRestriction() {
                if(
                    this.data.limitNumberOfEntries.status == 'yes' ||
                    this.data.scheduleForm.status == 'yes' ||
                    this.data.requireLogin.status == 'yes'
                ) {
                    return true;
                }
                return false;
            }
        }
    }
</script>
