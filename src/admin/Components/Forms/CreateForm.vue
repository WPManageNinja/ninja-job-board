<template>
    <el-dialog
        class="wpjb_dialog"
        width="480px"
        :title="$t('Add a job')"
        v-model="isVisible"
    >
        <el-form label-position="top" @submit.prevent="createForm()">
            <el-form-item :label="$t('Job title')" for="wpjb_new_job_title">
                <el-input
                    id="wpjb_new_job_title"
                    ref="titleInput"
                    v-model="form_title"
                    :placeholder="$t('For example: Senior backend engineer')"
                />
            </el-form-item>
            <p class="wpjb_dialog_help">{{ $t('You can add the job details and the application form next.') }}</p>
        </el-form>
        <template #footer>
            <el-button @click="isVisible = false">{{ $t('Cancel') }}</el-button>
            <el-button type="primary" :loading="submitting" @click="createForm()">{{ $t('Create job') }}</el-button>
        </template>
    </el-dialog>
</template>
<script>
    export default {
        name: 'CreateForm',
        props: ['modalVisible'],
        emits: ['update:modalVisible'],
        data() {
            return {
                form_title: '',
                submitting: false
            }
        },
        computed: {
            isVisible: {
                get() {
                    return !!this.modalVisible;
                },
                set(value) {
                    this.$emit('update:modalVisible', !!value);
                }
            }
        },
        methods: {
            createForm() {
                if (this.submitting) {
                    return;
                }
                this.submitting = true;
                this.$api.post('/forms', {
                    post_title: this.form_title
                })
                    .then(response => {
                        this.$message.success(response.message);
                        if(response.redirect_url) {
                            let url = response.redirect_url.replace(/&amp;/g, '&');
                            window.location.href = url;
                        } else {
                            this.$router.push({name: 'edit_form', params: {form_id: response.form_id}})
                        }

                    })
                    .catch(error => {
                        this.$message({
                            message: error.message,
                            type: 'error'
                        });
                    })
                    .finally(() => {
                        this.submitting = false;
                    });

            }
        },
        mounted() {
            this.$nextTick(() => {
                setTimeout(() => this.$refs.titleInput && this.$refs.titleInput.focus(), 200);
            });
        }
    }
</script>
