<template>
    <div>
        <el-row :gutter="20">
            <el-col :xs="24" :md="12">
                <div class="wpf_support_block">
                    <div class="support_block_header">
                        <h3>Documentation and User Guide</h3>
                    </div>
                    <div class="support_block_body">
                        <ul class="support_block_lists">
                            <li v-for="(list, list_key) in docs" :key="list_key">
                                <h4>{{ list.title }}</h4>
                                <ul>
                                    <li v-for="doc in list.docs" :key="doc.url">
                                        <a target="_blank" rel="noopener" :href="doc.url">
                                            {{ doc.title }}
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </el-col>
            <el-col :xs="24" :md="12">
                <div v-if="!has_pro" class="wpf_support_block">
                    <div class="support_block_header">
                        <h3>WPJobBoard Pro Features</h3>
                    </div>
                    <div class="support_block_body">
                        <p>Upgrade to WPJobBoard Pro and get more information about your submission, Plus you will get the following features</p>
                        <ul class="wpf_no_style">
                            <li><el-icon><CircleCheck /></el-icon> Accept Subscription/Recurring Payments</li>
                            <li><el-icon><CircleCheck /></el-icon> Accept Payment via PayPal</li>
                            <li><el-icon><CircleCheck /></el-icon> Collect Tax with Payment Items</li>
                            <li><el-icon><CircleCheck /></el-icon> Tabular Product Order Field</li>
                            <li><el-icon><CircleCheck /></el-icon> Get Email Notification on Form Submission</li>
                            <li><el-icon><CircleCheck /></el-icon> Send Customized Email to form submitter</li>
                            <li><el-icon><CircleCheck /></el-icon> Poweful Email Notification System</li>
                            <li><el-icon><CircleCheck /></el-icon> File Upload Field</li>
                            <li><el-icon><CircleCheck /></el-icon> Export and Print Your Data (Excel/CSV/JSON)</li>
                            <li><el-icon><CircleCheck /></el-icon> Form Scheduling and Subsmission Restriction Module</li>
                            <li><el-icon><CircleCheck /></el-icon> Get Powerful reporting features</li>
                        </ul>
                        <a target="_blank" href="https://wpmanageninja.com/wppayform-pro-wordpress-payments-form-builder/?utm_source=upgrade&amp;utm_medium=url&amp;utm_campaign=wpjobboard_upgrade" class="el-button payform_action el-button--danger el-button--small">
                            Upgrade To Pro
                        </a>
                    </div>
                </div>
                <div class="wpf_support_block">
                    <div class="support_block_header">
                        <h3>Feedback and Support us</h3>
                    </div>
                    <div class="support_block_body">
                        <p>We'd love your help, feedback and support.</p>
                        <ul class="support_items">
                            <li>
                                <a target="_blank" href="https://wordpress.org/plugins/wp-payment-form/#reviews">Rate our plugin</a> and help spread the word!
                            </li>
                            <li>
                                Report bugs (with steps to reproduce) or <a target="_blank" href="https://github.com/WPManageNinja/wp-payment-form">submit pull requests on GitHub</a>
                            </li>
                            <li>
                                Have a question? <a target="_blank" href="https://wpmanageninja.com/docs/wppayform/frequently-asked-question-for-wppayform/">Read or Frequently asked question</a>
                            </li>
                            <li>
                                Need Expert Support? <a target="_blank" href="https://wpmanageninja.com/support-tickets/">Open a support Ticket</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="wpf_support_block">
                    <div class="support_block_header">
                        <h3>Recommended Plugins</h3>
                    </div>
                    <div class="support_block_body">
                        <p>We have developed few awesome plugin that you can give a try too</p>
                        <ul class="support_items">
                            <li>
                                <b><a target="_blank" href="https://wpmanageninja.com/downloads/ninja-tables-pro-add-on/">Ninja Tables Pro</a></b> - The Fastest and Most Diverse WordPress Table Plugin
                            </li>
                            <li>
                                <b><a target="_blank" href="https://wpmanageninja.com/wp-fluent-form/">WP Fluent Form</a></b> - Make Effortless Contact Forms In Minutes!
                            </li>
                            <li>
                                <b><a target="_blank" href="https://wpmanageninja.com/azonpress/">AzonPress</a></b> - The Most Intuitive WordPress Plugin for Amazon Affiliates
                            </li>
                        </ul>
                    </div>
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script>
    import { CircleCheck } from '@element-plus/icons-vue';

    export default {
        name: 'support_documentation',
        components: { CircleCheck },
        data() {
            return {
                // has_pro was never set by PHP, so the upgrade block always showed
                has_pro: false,
                docs: {
                    getting_started: {
                        title: 'Getting Started with WPJobBoard',
                        docs: [
                            {
                                title: 'Install and Activate - WPJobBoard',
                                url: 'https://wpmanageninja.com/docs/wppayform/getting-started-with-wppayform/install-and-activate-wppayform/'
                            },
                            {
                                title: 'Configure Payment Methods and Currency',
                                url: 'https://wpmanageninja.com/docs/wppayform/getting-started-with-wppayform/configure-payment-methods-and-currency/'
                            }
                        ]
                    },
                    form_configaration: {
                        title: 'Form Configuration',
                        docs: [
                            {
                                'title': 'Create your first Payment Form under a minute',
                                'url' : 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/create-your-first-payment-form-under-a-minute-and-accept-payments/'
                            },
                            {
                                title: 'Available input fields',
                                url: 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/form-input-fields-wppayform/'
                            },
                            {
                                title: 'Form Confirmation Settings',
                                url: 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/form-confirmation-settings-wppayform/'
                            },
                            {
                                title: 'Custom Currency Settings',
                                url: 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/custom-currency-settings-wppayform/'
                            },
                            {
                                title: 'Form Design Settings',
                                url: 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/form-design-settings-in-wppayform/'
                            },
                            {
                                title: 'Form Scheduling and Restriction Settings',
                                url: 'https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/form-scheduling-and-restriction-settings-in-wppayform/'
                            }
                        ]
                    },
                    form_entries: {
                        title: 'Managing Form Entries',
                        docs: [
                            {
                                title: 'View and Manage all Form Entries',
                                url: 'https://wpmanageninja.com/docs/wppayform/managing-form-entries-in-wppayform/view-and-manage-all-form-entries-in-wppayform/'
                            },
                            {
                                title: 'View Single Submission Data and Managing Payments',
                                url: 'https://wpmanageninja.com/docs/wppayform/managing-form-entries-in-wppayform/view-single-submission-data-and-managing-payments-in-wppayform/'
                            }
                        ]
                    }
                }
            }
        }
    }
</script>