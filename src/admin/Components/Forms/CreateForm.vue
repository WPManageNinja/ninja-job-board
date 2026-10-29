<template>
    <el-dialog
        top="40px"
        width="70%"
        :append-to-body="true"
        title="Create a new Job Post"
        v-model="isVisible"
    >
        <div class="pay_form_modal_body">
            <h4>Please Provide a title for the job post</h4>
            <p>
                <el-input v-model="form_title" placeholder="Job Title" />

            </p>
            <p>
                <el-button type="primary" :loading="submitting" @click="createForm()">Continue</el-button>
            </p>
        </div>
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
                this.submitting = true;
                // Send Request now
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
        }
    }
</script>
