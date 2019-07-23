<template>
    <div class="wppaymform_editor">
        <el-menu mode="horizontal"
                 :router="true"
                 :default-active="current_route"
        >
            <li role="menuitem" class="el-menu-item">
                <a :href="form.edit_url">
                    <i class="dashicons dashicons-edit"></i>
                    <span>Post Details</span>
                </a>
            </li>
            <el-menu-item
                v-for="formMenu in form_menus"
                :key="formMenu.route"
                :route="{ name: formMenu.route, params: { form_id: form_id } }"
                :index="formMenu.route">
                <i :class="formMenu.icon"></i>
                <span>{{  formMenu.title }}</span>
            </el-menu-item>
            <el-menu-item
                :route="{ name: 'form_entries', params: { form_id: form_id } }"
                index="entries"
            >
                <i class="dashicons dashicons-text"></i>
                <span>{{  $t('Job Applications') }}</span>
            </el-menu-item>
        </el-menu>
        <div class="payform_editor_wrapper">
            <router-view :form_id="form_id"></router-view>
        </div>
    </div>
</template>

<script type="text/babel">
    import WpEditor from '../Common/_wp_editor';
    import Clipboard from 'clipboard';
    export default {
        name: 'global_wrapper',
        components: {WpEditor},
        data() {
            return {
                form_id: this.$route.params.form_id,
                current_route: this.$route.name,
                form_menus: [],
                editFormModalShow: false,
                form: {},
                fetching: false,
                saving: false,
            }
        },
        methods: {
            getForm() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_form',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.form = response.data.form;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            editForm() {
                // validate first
                if (!this.form.post_title) {
                    this.$message.error('Please provide form title');
                    return;
                }
                this.saving = true;
                this.$adminPost({
                    route: 'update_form',
                    form_id: this.form.ID,
                    post_title: this.form.post_title,
                    post_content: this.form.post_content,
                    show_title_description: this.form.show_title_description
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                        this.editFormModalShow = false;
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            },
            setFormMenu() {
                this.form_menus = this.applyFilters('wpf_set_form_menus', [
                    {
                        route: 'edit_form',
                        title: 'Application Form',
                        icon: 'dashicons dashicons-lightbulb'
                    },
                    {
                        route: 'confirmation_settings',
                        title: 'Form Settings',
                        icon: 'dashicons dashicons-admin-settings'
                    },
                    {
                        route: 'email_settings',
                        title: 'Email Notifications',
                        icon: 'dashicons dashicons-email-alt'
                    },
                    {
                        route: 'email_campaigns',
                        title: 'Email Broadcast',
                        icon: 'dashicons dashicons-email-alt2'
                    },
                ], this.form_id);
            }
        },
        mounted() {
            this.setFormMenu();
            this.getForm();
            var clipboard = new Clipboard('.copy');
            clipboard.on('success', (e) => {
                this.$message({
                    message: 'Copied to Clipboard!',
                    type: 'success'
                });
            });
        }
    }
</script>
