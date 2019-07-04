<template>
    <el-dialog
        top="40px"
        width="70%"
        :append-to-body="true"
        title="Create a new Job Post"
        :visible.sync="isVisible"
    >
        <div class="pay_form_modal_body">
            <h4>Please Provide a title for the job post</h4>
            <p>
                <el-input v-model="form_title" placeholder="Job Title" />

            </p>
            <p>
                <el-button type="primary" @click="createForm()">Continue</el-button>
            </p>
        </div>
    </el-dialog>
</template>
<script type="text/babel">
    export default {
        name: 'CreateForm',
        props: ['modalVisible'],
        data() {
            return {
                form_title: '',
                isVisible: this.modalVisible
            }
        },
        watch: {
            isVisible() {
                this.$emit('update:modalVisible', JSON.parse(this.isVisible))
            }
        },
        methods: {
            createForm() {
                this.submitting = true;
                // Send Request now
                this.$adminPost({
                    route: 'create_form',
                    post_title: this.form_title
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                        if(response.data.redirect_url) {
                            let url = response.data.redirect_url.replace('&amp;', '&');
                            window.location.href = url;
                        } else {
                            this.$router.push({name: 'edit_form', params: {form_id: response.data.form_id}})
                        }

                    })
                    .fail(error => {
                        this.$message({
                            message: error.responseJSON.data.message,
                            type: 'error'
                        });
                    })
                    .always(() => {
                        this.submitting = false;
                    });

            }
        }
    }
</script>
