<template>
    <div v-if="notifications.length" class="wpf_email_notifications">
        <el-table :data="notifications">
            <el-table-column  type="index" width="50" />
            <el-table-column
                width="120"
                label="Status">
                <template #default="scope">
                    <el-switch
                        v-model="scope.row.status"
                        @change="emitClick('saveNotification', scope.$index)"
                        active-color="#13ce66"
                        active-value="active"
                        inactive-value="disabled"
                    >
                    </el-switch>
                </template>
            </el-table-column>
            <el-table-column
                property="title"
                width="220"
                label="Title">
            </el-table-column>
            <el-table-column
                property="email_subject"
                label="Subject">
            </el-table-column>
            <el-table-column
                width="180"
                label="Sending Action">
                <template #default="scope">
                    {{ humanActionName(scope.row.sending_action) }}
                </template>
            </el-table-column>
            <el-table-column
                width="160"
                label="">
                <template #default="scope">
                    <el-button @click="emitClick('editNotification', scope.$index)" type="success" size="small" :icon="Edit"></el-button>
                    <el-button @click="emitClick('deleteNotification', scope.$index)" type="danger" size="small" :icon="Delete"></el-button>
                </template>
            </el-table-column>

        </el-table>
    </div>
    <div v-else>
        <h3 class="text-center">No email notifications configured yet.</h3>
    </div>
</template>
<script>
    import { Edit, Delete } from '@element-plus/icons-vue';

    export default {
        name: 'notifications_table',
        props: ['notifications'],
        emits: ['editNotification', 'deleteNotification', 'saveNotification'],
        setup() {
            return { Edit, Delete };
        },
        methods: {
            emitClick(eventName, dataIndex) {
                this.$emit(eventName, dataIndex);
            },
            humanActionName(action) {
                let names = {
                    'wpjobboard/after_form_submission_complete' : 'After Application'
                }
                if(names[action]) {
                    return names[action];
                }
                return action;
            }
        }
    }
</script>
