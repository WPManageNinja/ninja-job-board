<template>
    <div class="notification_edit">
        <el-form v-if="editingNotificationIndex != null" ref="notificationForm" class="payform_hide_valdate_messages wpjb_setting_rows" :show-message="showErrorMessage" :rules="rules" label-position="top" :model="form">
            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>Email</h3>
                </div>
                <el-form-item prop="title">
                    <template #label>
                        <span class="wpjb_field_label">Title</span>
                        <span class="wpjb_field_help">Helps you find this notification in the list.</span>
                    </template>
                    <el-input placeholder="For example: Notify the hiring team" aria-label="Title" v-model="form.title"></el-input>
                </el-form-item>
                <el-form-item prop="email_to">
                    <template #label>
                        <span class="wpjb_field_label">Send to</span>
                        <span class="wpjb_field_help">Email addresses, separated by commas. Merge tags work.</span>
                    </template>
                    <el-input placeholder="name@example.com" aria-label="Send to" v-model="form.email_to">
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
                        <span class="wpjb_field_label">Reply-to</span>
                        <span class="wpjb_field_help">Where replies go. Leave empty to use the sender.</span>
                    </template>
                    <el-input placeholder="{submission.applicant_email}" aria-label="Reply-to" v-model="form.reply_to">
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
                        <span class="wpjb_field_label">Subject</span>
                        <span class="wpjb_field_help">The subject line of the email.</span>
                    </template>
                    <el-input placeholder="Email subject" aria-label="Subject" v-model="form.email_subject">
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
                        <span class="wpjb_field_label">Email body</span>
                        <span class="wpjb_field_help">Use the merge tag button to add application data.</span>
                    </template>
                    <wp-editor
                        :editor_id="'wp_email_editor_'+editingNotificationIndex"
                        v-model="form.email_body"
                        :editorShortcodes="merge_tags"
                    />
                </el-form-item>
                <el-form-item prop="sending_action">
                    <template #label>
                        <span class="wpjb_field_label">When it sends</span>
                        <span class="wpjb_field_help">The event that sends this email.</span>
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
                    <h3>Sender and copies</h3>
                    <p>Optional. Empty fields use the WordPress defaults.</p>
                </div>
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">From name</span>
                        <span class="wpjb_field_help">The sender name in the inbox. Works only with a From email.</span>
                    </template>
                    <el-input placeholder="Your company" aria-label="From name" v-model="form.from_name">
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
                        <span class="wpjb_field_label">From email</span>
                        <span class="wpjb_field_help">Use an address on your domain so the email is delivered.</span>
                    </template>
                    <el-input placeholder="jobs@yourdomain.com" aria-label="From email" v-model="form.from_email">
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
                        <span class="wpjb_field_label">CC</span>
                        <span class="wpjb_field_help">Copy these addresses, separated by commas.</span>
                    </template>
                    <el-input placeholder="name@example.com" aria-label="CC" v-model="form.cc_to">
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
                        <span class="wpjb_field_label">BCC</span>
                        <span class="wpjb_field_help">Hidden copy to these addresses.</span>
                    </template>
                    <el-input placeholder="name@example.com" aria-label="BCC" v-model="form.bcc_to">
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
                            required: true, message: 'Please Provide Notification Title',
                        }
                    ],
                    email_to: [
                        {
                            required: true, message: 'Please Provide Email To', trigger: 'change'
                        }
                    ],
                    email_subject: [
                        {
                            required: true, message: 'Please Provide Email Subject',
                        }
                    ],
                    email_body: [
                        {
                            required: true, message: 'Please Provide Email Body',
                        }
                    ],
                    from_email: [
                        {
                            required: true, message: 'Please Provide Email Body'
                        }
                    ],
                    sending_action: [
                        {
                            required: true, message: 'When to send field is required'
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
                        this.$notify.error('Please provide all required fields');
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
