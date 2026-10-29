<template>
    <div>
        <!--Confirmation Type-->
        <el-form-item>
            <template #label>
                Confirmation Type
                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <template #content><div>
                        <h3>Confirmation Type</h3>
                        <p>
                            After submit, where the page will redirect to.
                        </p>
                    </div></template>
                    <el-icon class="el-text-info"><InfoFilled /></el-icon>
                </el-tooltip>
            </template>
            <el-radio v-for="(redirectOption, optionName) in redirectToOptions"
                      v-model="confirmation.redirectTo" :value="optionName" border :key="optionName"
            >
                {{ redirectOption }}
            </el-radio>
        </el-form-item>

        <!--Additional fields based on the redirect to selection-->
        <!--Same page-->
        <div v-if="confirmation.redirectTo === 'samePage'" class="conditional-items">
            <!--Message to show-->
            <el-form-item>
                <template #label>
                    Message to show
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <template #content><div>
                            <h3>Confirmation Message Text</h3>
                            <p>
                                Enter the text you would like the user to <br>
                                see on the confirmation page of this form.
                            </p>
                        </div></template>

                        <el-icon class="el-text-info"><InfoFilled /></el-icon>
                    </el-tooltip>
                </template>

                <wp-editor editor_id="wp_confirmation_editor_1" :height="250" :editor-shortcodes="editorShortcodes" v-model="confirmation.messageToShow" />
            </el-form-item>

            <!--After form submisssion behavior-->
            <el-form-item>
                <template #label>
                    After Form Submission
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <template #content><div>
                            <h3>After Form Submission Behavior</h3>
                            <p>
                                Select the behavior after form submission, <br>
                                whether you want to hide or reset the form.
                            </p>
                        </div></template>
                        <el-icon class="el-text-info"><InfoFilled /></el-icon>
                    </el-tooltip>
                </template>

                <el-radio v-model="confirmation.samePageFormBehavior"
                          value="hide_form" border>Hide Form
                </el-radio>

                <el-radio v-model="confirmation.samePageFormBehavior"
                          value="reset_form" border>Reset Form
                </el-radio>
            </el-form-item>
        </div>

        <!--Custom page-->
        <el-form-item v-else-if="confirmation.redirectTo === 'customPage'"
                      class="conditional-items"
        >
            <template #label>
                Select Page

                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <template #content><div>
                        <h3>Redirect Form to Page</h3>

                        <p>
                            Select the page you would like the user to be <br>
                            redirected to after they have submitted the form.
                        </p>
                    </div></template>

                    <el-icon class="el-text-info"><InfoFilled /></el-icon>
                </el-tooltip>
            </template>

            <el-select v-model="confirmation.customPage" filterable placeholder="Select">
                <el-option
                    v-for="page in pages"
                    :key="page.ID"
                    :label="page.post_title"
                    :value="page.ID"
                />
            </el-select>
            <p>Add the following shortcode to the page to show Submission Confirmation: <code>[wpjobboard_confirmation]</code></p>
        </el-form-item>

        <!--Custom URL-->
        <el-form-item class="conditional-items" v-else-if="confirmation.redirectTo == 'customUrl'">
            <template #label>
                Custom URL
                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <template #content><div>
                        <h3>Redirect Form to URL</h3>
                        <p>
                            Enter the URL of the webpage you would <br>
                            like the user to be redirected to after <br>
                            they have submitted the form.
                        </p>
                    </div></template>
                    <el-icon class="el-text-info"><InfoFilled /></el-icon>
                </el-tooltip>
            </template>

            <el-input size="small" placeholder="Redirect URL" v-model="confirmation.customUrl">
                <template #suffix>
                    <popover
                        @command="(code) => { confirmation.customUrl += code }"
                        :data="editorShortcodes"
                        btnType="text"
                        buttonText="">
                    </popover>
                </template>
            </el-input>
            <p>Add the following shortcode to the page to show Submission Confirmation: <code>[wpjobboard_confirmation]</code></p>
        </el-form-item>
    </div>
</template>

<script>
    import { InfoFilled } from '@element-plus/icons-vue';
    import wpEditor from '../../Common/_wp_editor.vue';
    import popover from '../../Common/input-popover-dropdown.vue';

    export default {
        name: 'AddConfirmation',
        components: {
            wpEditor,
            popover,
            InfoFilled
        },
        props: ['pages', 'editorShortcodes', 'confirmation'],
        setup() {
            return { InfoFilled };
        },
        computed: {
            inputsFirstShortcodes() {
                return this.editorShortcodes;
            }
        },
        data() {
            return {
                redirectToOptions: {
                    samePage: 'Same Page',
                    customPage: 'To a Page',
                    customUrl: 'To a Custom URL',
                }
            }
        },
        mounted() {
           
        }
    }
</script>