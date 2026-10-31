<template>
    <el-form ref="form-bottom" class="wpjb_setting_rows" label-position="top">
        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>{{ $t('Application limit') }}</h3>
            </div>
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Limit applications') }}</span>
                    <span class="wpjb_field_help">{{ $t('Close the form when it reaches a set number of applications.') }}</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Limit applications')"
                           v-model="form.limitNumberOfEntries.status"></el-switch>
            </el-form-item>

            <el-form-item v-if="form.limitNumberOfEntries.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Maximum applications') }}</span>
                    <span class="wpjb_field_help">{{ $t('Set the maximum number of applications, the period, and the application statuses that count toward the limit.') }}</span>
                </template>
                <div class="wpjb_inline_controls">
                    <el-input-number :min="0" controls-position="right" :aria-label="$t('Maximum applications')"
                                     v-model="form.limitNumberOfEntries.number_of_entries"
                    ></el-input-number>
                    <el-select v-model="form.limitNumberOfEntries.limit_type" :aria-label="$t('Limit period')">
                        <el-option v-for="(label, period) in entryPeriodOptions" :key="period"
                                   :label="label" :value="period"
                        ></el-option>
                    </el-select>
                    <el-select class="wpjb_inline_full" multiple :placeholder="$t('All application statuses')"
                               :aria-label="$t('Application statuses that count')"
                               v-model="form.limitNumberOfEntries.limit_application_statuses">
                        <el-option v-for="(label, label_value) in applicationStatuses" :key="label_value"
                                   :label="label" :value="label_value"
                        ></el-option>
                    </el-select>
                </div>
            </el-form-item>
            <el-form-item v-if="form.limitNumberOfEntries.status == 'yes'" key="limit-reached-msg">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Limit reached message') }}</span>
                    <span class="wpjb_field_help">{{ $t('Shown instead of the form when the limit is reached.') }}</span>
                </template>
                <el-input v-model="form.limitNumberOfEntries.limit_exceeds_message" type="textarea" :rows="3"
                          :aria-label="$t('Limit reached message')"></el-input>
            </el-form-item>
        </div>

        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>{{ $_x('Schedule', 'Settings section heading') }}</h3>
            </div>
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Schedule the form') }}</span>
                    <span class="wpjb_field_help">{{ $t('Accept applications only between two dates.') }}</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Schedule the form')"
                           v-model="form.scheduleForm.status"></el-switch>
            </el-form-item>

            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Opens on') }}</span>
                    <!-- translators: %s is the current date and time on the server -->
                    <span class="wpjb_field_help">{{ $t('Uses server time. The time now is %s.', current_date_time) }}</span>
                </template>
                <el-date-picker
                    value-format="YYYY-MM-DD HH:mm:ss"
                    v-model="form.scheduleForm.start_date"
                    type="datetime"
                    :aria-label="$t('Opens on')"
                    :placeholder="$t('Select date and time')">
                </el-date-picker>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Closes on') }}</span>
                    <span class="wpjb_field_help">{{ $t('The form stops accepting applications at this time.') }}</span>
                </template>
                <el-date-picker
                    value-format="YYYY-MM-DD HH:mm:ss"
                    v-model="form.scheduleForm.end_date"
                    type="datetime"
                    :aria-label="$t('Closes on')"
                    :placeholder="$t('Select date and time')"
                    :shortcuts="dateShortcuts">
                </el-date-picker>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Before opening message') }}</span>
                    <span class="wpjb_field_help">{{ $t('Shown before the form opens.') }}</span>
                </template>
                <el-input v-model="form.scheduleForm.before_start_message" type="textarea" :rows="3"
                          :aria-label="$t('Before opening message')"></el-input>
            </el-form-item>
            <el-form-item v-if="form.scheduleForm.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('After closing message') }}</span>
                    <span class="wpjb_field_help">{{ $t('Shown after the form closes.') }}</span>
                </template>
                <el-input v-model="form.scheduleForm.expire_message" type="textarea" :rows="3"
                          :aria-label="$t('After closing message')"></el-input>
            </el-form-item>
        </div>

        <div class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>{{ $_x('Login', 'Settings section heading') }}</h3>
            </div>
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Require login') }}</span>
                    <span class="wpjb_field_help">{{ $t('Only logged-in users can see the form.') }}</span>
                </template>
                <el-switch active-value="yes" inactive-value="no" :aria-label="$t('Require login')"
                           v-model="form.requireLogin.status"></el-switch>
            </el-form-item>

            <el-form-item v-if="form.requireLogin.status == 'yes'">
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Login required message') }}</span>
                    <span class="wpjb_field_help">{{ $t('Shown to logged-out visitors. Shortcodes and HTML work.') }}</span>
                </template>
                <el-input type="textarea" :rows="3" v-model="form.requireLogin.message"
                          :aria-label="$t('Login required message')"></el-input>
            </el-form-item>
        </div>

        <div v-if="hasAnyRestriction" class="wpjb_card wpjb_setting_card">
            <div class="wpjb_card_head">
                <h3>{{ $t('When a restriction applies') }}</h3>
            </div>
            <el-form-item>
                <template #label>
                    <span class="wpjb_field_label">{{ $t('Restricted form') }}</span>
                    <span class="wpjb_field_help">{{ $t('What happens to the form while a restriction is active.') }}</span>
                </template>
                <el-radio-group v-model="form.restriction_applied_type" class="wpjb_radio_list">
                    <el-radio value="hide_form">{{ $t('Hide the form') }}</el-radio>
                    <el-radio value="hide_submit_button">{{ $t('Hide the submit button') }}</el-radio>
                    <el-radio value="validation_failed">{{ $t('Show a validation error after submit') }}</el-radio>
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
                    total: this.$t('In total'),
                    day: this.$t('Per day'),
                    week: this.$t('Per week'),
                    month: this.$t('Per month'),
                    year: this.$t('Per year'),
                },
                applicationStatuses: window.wpJobBoardsAdmin.applicationStatuses,
                dateShortcuts: [
                    {
                        text: this.$t('Today'),
                        value: () => new Date()
                    },
                    {
                        text: this.$t('Yesterday'),
                        value: () => {
                            const date = new Date();
                            date.setTime(date.getTime() - 3600 * 1000 * 24);
                            return date;
                        }
                    },
                    {
                        text: this.$t('A week ago'),
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
