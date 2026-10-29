<template>
    <div class="wpjb_settings_page wpjb_email_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">
                {{ title }}
                <span v-if="editingNotificationIndex === null && notifications.length" class="wpjb_count wpjb_num">({{ notifications.length }})</span>
            </h3>
            <div class="payform_section_actions">
                <el-button v-if="editingNotificationIndex == null" @click="addNotidication()"
                           type="primary" :icon="Plus">
                    {{ $t('Add notification') }}
                </el-button>
                <template v-else>
                    <el-button @click="editingNotificationIndex = null" :icon="ArrowLeft">
                        {{ $t('All notifications') }}
                    </el-button>
                    <el-button @click="saveCurrent()" :loading="saving" type="primary">
                        {{ $t('Save notification') }}
                    </el-button>
                </template>
            </div>
        </div>

        <div v-if="editingNotificationIndex === null" v-loading="loading" class="wpjb_card">
            <notification-table @editNotification="editNotification"
                                @deleteNotification="deleteNotification"
                                @saveNotification="saveNotifications"
                                :notifications="notifications"/>
        </div>
        <div v-else class="wpjb_notification_layout">
            <nav class="wpjb_card wpjb_notification_nav" :aria-label="$t('Notifications')">
                <h3>{{ $t('Notifications') }}</h3>
                <button
                    v-for="(notification, notificationIndex) in notifications"
                    :key="notificationIndex"
                    type="button"
                    :title="notification.title"
                    :class="{ 'is-active': notificationIndex === editingNotificationIndex }"
                    :aria-current="notificationIndex === editingNotificationIndex ? 'true' : null"
                    @click="editNotification(notificationIndex)">
                    {{ notification.title || $t('Untitled notification') }}
                </button>
            </nav>
            <edit-notification
                ref="notificationEditor"
                :notification_actions="notification_actions"
                @saveNotifications="saveNotifications"
                :editingNotificationIndex="editingNotificationIndex"
                v-model:notification="notifications[editingNotificationIndex]"
                :merge_tags="merge_tags"
            />
        </div>
    </div>
</template>
<script>
    import { Plus, ArrowLeft } from '@element-plus/icons-vue';
    import NotificationTable from './Notifications.vue';
    import EditNotification from './EditNotification.vue'

    export default {
        name: 'FormEmailSettings',
        components: {
            NotificationTable,
            EditNotification
        },
        setup() {
            return { Plus, ArrowLeft };
        },
        data() {
            return {
                form_id: this.$route.params.form_id,
                notifications: [],
                loading: false,
                merge_tags: [],
                notification_actions: [],
                editingNotificationIndex: null,
                saving: false
            }
        },
        computed: {
            title() {
                let index = this.editingNotificationIndex;
                if (index != null && this.notifications.length) {
                    return 'Edit notification';
                }
                return 'Email notifications';
            }
        },
        methods: {
            getEmailNotifications() {
                this.loading = true;
                this.$api.get('/forms/' + this.form_id + '/notifications')
                    .then(response => {
                        this.notifications = response.notifications;
                        this.merge_tags = Object.values(response.merge_tags);
                        this.notification_actions = response.notification_actions;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            addNotidication() {
                this.notifications.push({
                    title: 'Email Notification',
                    email_to: '',
                    reply_to: '',
                    email_subject: 'Job Application Confirmation',
                    email_body: '',
                    from_name: '',
                    from_email: '',
                    format: 'html',
                    email_template: 'default',
                    cc_to: '',
                    bcc_to: '',
                    conditions: '',
                    sending_action: 'wpjobboard/after_form_submission_complete',
                    status: 'active'
                });
                this.editingNotificationIndex = this.notifications.length - 1;
            },
            // The save button sits in the title row; the editor validates and emits as before
            saveCurrent() {
                const editor = this.$refs.notificationEditor;
                if (editor) {
                    editor.update('notificationForm');
                }
            },
            editNotification(notificationIndex) {
                this.editingNotificationIndex = null;
                this.$nextTick(() => {
                    this.editingNotificationIndex = notificationIndex;
                });
            },
            deleteNotification(notificationIndex) {
                this.notifications.splice(notificationIndex, 1);
                this.saveNotifications();
            },
            saveNotifications() {
                this.saving = true;
                this.$api.post('/forms/' + this.form_id + '/notifications', {
                    notifications: this.notifications
                })
                    .then(response => {
                        this.$notify.success(response.message);
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            this.getEmailNotifications();
        }
    }
</script>
