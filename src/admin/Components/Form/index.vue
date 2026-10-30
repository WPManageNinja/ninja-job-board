<template>
    <div class="wppaymform_editor">
        <div class="wpjb_subbar">
            <div class="wpjb_subbar_title">
                <span :title="form.post_title">{{ form.post_title }}</span>
                <status-badge v-if="form.post_status" type="post" :status="form.post_status"/>
            </div>
            <nav :aria-label="$t('Job sections')">
                <ul class="wpjb_tabs">
                    <li v-for="formMenu in form_menus" :key="formMenu.route">
                        <router-link :to="{ name: formMenu.route, params: { form_id: form_id } }"
                                     :class="['wpjb_tab', { 'is-active': isActiveMenu(formMenu) }]">
                            {{ formMenu.title }}
                        </router-link>
                    </li>
                    <li v-if="form.edit_url">
                        <a class="wpjb_tab" :href="form.edit_url">
                            {{ $t('Edit job post') }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div :class="['payform_editor_wrapper', { wpjb_page: !isSettingsRoute }]">
            <router-view :form_id="form_id"></router-view>
        </div>
    </div>
</template>

<script>
    import WpEditor from '../Common/_wp_editor.vue';
    import StatusBadge from '../Common/StatusBadge.vue';
    export default {
        name: 'global_wrapper',
        components: {WpEditor, StatusBadge},
        data() {
            return {
                form_id: this.$route.params.form_id,
                form_menus: [],
                editFormModalShow: false,
                form: {},
                fetching: false,
                saving: false,
            }
        },
        computed: {
            // The settings screens bring their own full-height layout
            isSettingsRoute() {
                return ['confirmation_settings', 'design_options', 'scheduling_settings'].indexOf(this.$route.name) !== -1;
            }
        },
        methods: {
            getForm() {
                this.fetching = true;
                this.$api.get('/forms/' + this.form_id)
                    .then(response => {
                        this.form = response.form;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
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
                this.$api.post('/forms/' + this.form.ID, {
                    post_title: this.form.post_title,
                    post_content: this.form.post_content,
                    show_title_description: this.form.show_title_description
                })
                    .then(response => {
                        this.$message.success(response.message);
                        this.editFormModalShow = false;
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            },
            setFormMenu() {
                // match: the route names that keep the tab highlighted
                this.form_menus = this.applyFilters('wpf_set_form_menus', [
                    {
                        route: 'form_entries',
                        title: this.$t('Applications'),
                        match: ['form_entries', 'entry']
                    },
                    {
                        route: 'edit_form',
                        title: this.$t('Application Form'),
                        match: ['edit_form']
                    },
                    {
                        route: 'confirmation_settings',
                        title: this.$t('Form Settings'),
                        match: ['confirmation_settings', 'design_options', 'scheduling_settings']
                    },
                    {
                        route: 'email_settings',
                        title: this.$t('Email Notifications'),
                        match: ['email_settings']
                    },
                    {
                        route: 'email_campaigns',
                        title: this.$t('Email Broadcast'),
                        match: ['email_campaigns', 'new_email_campaign', 'view_email_campaign']
                    }
                ], this.form_id);
            },
            isActiveMenu(menu) {
                return (menu.match || [menu.route]).indexOf(this.$route.name) !== -1;
            }
        },
        watch: {
            // Duplicating a job opens the new job in the same view
            '$route.params.form_id'(formId) {
                if (formId && formId !== this.form_id) {
                    this.form_id = formId;
                    this.getForm();
                }
            }
        },
        mounted() {
            this.setFormMenu();
            this.getForm();
        }
    }
</script>
