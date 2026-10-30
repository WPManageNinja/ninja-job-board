<template>
    <div class="notification_edit">
        <el-form v-if="editingNotificationIndex != null" ref="notificationForm" class="payform_hide_valdate_messages wpjb_setting_rows" :show-message="showErrorMessage" :rules="rules" label-position="top" :model="form">
            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>{{ $t('Email') }}</h3>
                </div>
                <el-form-item prop="title">
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Title') }}</span>
                        <span class="wpjb_field_help">{{ $t('Helps you find this notification in the list.') }}</span>
                    </template>
                    <el-input :placeholder="$t('For example: Notify the hiring team')" :aria-label="$t('Title')" v-model="form.title"></el-input>
                </el-form-item>
                <el-form-item prop="email_to">
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Send to') }}</span>
                        <span class="wpjb_field_help">{{ $t('Email addresses, separated by commas. Merge tags work.') }}</span>
                    </template>
                    <el-input placeholder="name@example.com" :aria-label="$t('Send to')" v-model="form.email_to">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.email_to += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Reply-to') }}</span>
                        <span class="wpjb_field_help">{{ $t('Where replies go. Leave empty to use the sender.') }}</span>
                    </template>
                    <el-input placeholder="{submission.applicant_email}" :aria-label="$t('Reply-to')" v-model="form.reply_to">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.reply_to += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item prop="email_subject">
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Subject') }}</span>
                        <span class="wpjb_field_help">{{ $t('The subject line of the email.') }}</span>
                    </template>
                    <el-input :placeholder="$t('Email subject')" :aria-label="$t('Subject')" v-model="form.email_subject">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.email_subject += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item prop="email_body" class="is-stacked">
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('Email body') }}</span>
                        <span class="wpjb_field_help">{{ $t('Use the merge tag button to add application data.') }}</span>
                    </template>
                    <wp-editor
                        :editor_id="'wp_email_editor_'+editingNotificationIndex"
                        v-model="form.email_body"
                        :editorShortcodes="merge_tags"
                    />
                </el-form-item>
                <el-form-item prop="sending_action">
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('When it sends') }}</span>
                        <span class="wpjb_field_help">{{ $t('The event that sends this email.') }}</span>
                    </template>
                    <el-radio-group v-model="form.sending_action" class="wpjb_radio_list">
                        <el-radio v-for="sending_hook in notification_actions" :key="sending_hook.hook_name"
                                  :value="sending_hook.hook_name">
                            {{sending_hook.hook_title}}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
            </div>

            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>{{ $t('Sender and copies') }}</h3>
                    <p>{{ $t('Optional. Empty fields use the WordPress defaults.') }}</p>
                </div>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('From name') }}</span>
                        <span class="wpjb_field_help">{{ $t('The sender name in the inbox. Works only with a From email.') }}</span>
                    </template>
                    <el-input :placeholder="$t('Your company')" :aria-label="$t('From name')" v-model="form.from_name">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.from_name += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('From email') }}</span>
                        <span class="wpjb_field_help">{{ $t('Use an address on your domain so the email is delivered.') }}</span>
                    </template>
                    <el-input placeholder="jobs@yourdomain.com" :aria-label="$t('From email')" v-model="form.from_email">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.from_email += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('CC') }}</span>
                        <span class="wpjb_field_help">{{ $t('Send a copy to these addresses, separated by commas.') }}</span>
                    </template>
                    <el-input placeholder="name@example.com" :aria-label="$t('CC')" v-model="form.cc_to">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.cc_to += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">{{ $t('BCC') }}</span>
                        <span class="wpjb_field_help">{{ $t('Send a hidden copy to these addresses, separated by commas.') }}</span>
                    </template>
                    <el-input placeholder="name@example.com" :aria-label="$t('BCC')" v-model="form.bcc_to">
                        <template #suffix>
                            <popover
                                @command="(code) => { form.bcc_to += code }"
                                :data="merge_tags"
                                btnType="text"
                                buttonText="">
                            </popover>
                        </template>
                    </el-input>
                </el-form-item>
            </div>
        </el-form>
    </div>
</template>

<script>
    import wpEditor from '../../Common/_wp_editor.vue';
    import popover from '../../Common/input-popover-dropdown.vue'

    export default {
        name: 'editNotification',
        components: {
            wpEditor,
            popover
        },
        props: ['notification', 'editingNotificationIndex', 'merge_tags', 'notification_actions'],
        emits: ['update:notification', 'saveNotifications'],
        data() {
            return {
                form: {},
                rules: {
                    title: [
                        {
                            required: true, message: this.$t('Enter a title for this email notification.'),
                        }
                    ],
                    email_to: [
                        {
                            required: true, message: this.$t('Enter at least one email address to send to.'), trigger: 'change'
                        }
                    ],
                    email_subject: [
                        {
                            required: true, message: this.$t('Enter an email subject.'),
                        }
                    ],
                    email_body: [
                        {
                            required: true, message: this.$t('Enter the email body.'),
                        }
                    ],
                    from_email: [
                        {
                            required: true, message: this.$t('Enter a from email address.')
                        }
                    ],
                    sending_action: [
                        {
                            required: true, message: this.$t('Choose when this email sends.')
                        }
                    ]
                },
                showErrorMessage: false
            }
        },
        watch: {
            editingNotificationIndex() {
                if (this.editingNotificationIndex != null) {
                    this.showErrorMessage = false;
                    this.form = JSON.parse(JSON.stringify(this.notification));
                }
            }
        },
        methods: {
            update(formName) {
                // validate first please
                this.showErrorMessage = true;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        this.$emit('update:notification', JSON.parse(JSON.stringify(this.form)));
                        this.$emit('saveNotifications');
                    } else {
                        this.$notify.error(this.$t('Fill in all required fields.'));
                        return false;
                    }
                });
            },
            handleInsert() {
                console.log(arguments);
            },
            getFormErrors() {

            }
        },
        mounted() {
            this.showErrorMessage = false;
            this.form = JSON.parse(JSON.stringify(this.notification));
        }
    }
</script>
