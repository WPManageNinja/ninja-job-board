<template>
    <div v-if="notifications.length" class="wpjb_notification_table">
        <el-table :data="notifications">
            <el-table-column label="Title" min-width="240">
                <template #default="scope">
                    <span class="wpjb_cell_primary">{{ scope.row.title }}</span>
                    <div class="row-actions">
                        <button type="button" @click="emitClick('editNotification', scope.$index)">Edit</button>
                        <button type="button" class="is-danger" @click="emitClick('deleteNotification', scope.$index)">Delete</button>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="Subject" min-width="280">
                <template #default="scope">
                    {{ scope.row.email_subject }}
                </template>
            </el-table-column>
            <el-table-column label="When it sends" width="180">
                <template #default="scope">
                    {{ humanActionName(scope.row.sending_action) }}
                </template>
            </el-table-column>
            <el-table-column label="Status" width="140">
                <template #default="scope">
                    <span class="wpjb_status_cell">
                        <el-switch
                            v-model="scope.row.status"
                            @change="emitClick('saveNotification', scope.$index)"
                            active-value="active"
                            inactive-value="disabled"
                            :aria-label="'Send ' + scope.row.title"
                        />
                        {{ scope.row.status === 'active' ? 'On' : 'Off' }}
                    </span>
                </template>
            </el-table-column>
        </el-table>
    </div>
    <div v-else class="wpjb_empty">
        <h3>No email notifications yet</h3>
        <p>Notifications email you or the applicant when someone applies. Use "Add notification" to create one.</p>
    </div>
</template>
<script>
    export default {
        name: 'notifications_table',
        props: ['notifications'],
        emits: ['editNotification', 'deleteNotification', 'saveNotification'],
        methods: {
            emitClick(eventName, dataIndex) {
                this.$emit(eventName, dataIndex);
            },
            humanActionName(action) {
                let names = {
                    'wpjobboard/after_form_submission_complete' : 'After someone applies'
                }
                if(names[action]) {
                    return names[action];
                }
                return action;
            }
        }
    }
</script>
