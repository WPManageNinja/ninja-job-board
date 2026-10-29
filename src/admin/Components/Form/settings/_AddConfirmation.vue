<template>
    <div>
        <!--Confirmation Type-->
        <el-form-item>
            <template #label>
                <span class="wpjb_field_label">After submission</span>
                <span class="wpjb_field_help">What the applicant sees after sending the form.</span>
            </template>
            <el-radio-group v-model="confirmation.redirectTo">
                <el-radio-button v-for="(redirectOption, optionName) in redirectToOptions"
                                 :value="optionName" :key="optionName">
                    {{ redirectOption }}
                </el-radio-button>
            </el-radio-group>
        </el-form-item>

        <!--Same page: message to show-->
        <el-form-item v-if="confirmation.redirectTo === 'samePage'" class="is-stacked">
            <template #label>
                <span class="wpjb_field_label">Confirmation message</span>
                <span class="wpjb_field_help">Shown on the same page after the form is sent.</span>
            </template>
            <wp-editor editor_id="wp_confirmation_editor_1" :height="250" :editor-shortcodes="editorShortcodes" v-model="confirmation.messageToShow" />
        </el-form-item>

        <!--Same page: after form submission behavior-->
        <el-form-item v-if="confirmation.redirectTo === 'samePage'">
            <template #label>
                <span class="wpjb_field_label">Form after submission</span>
                <span class="wpjb_field_help">Hide the form, or clear it for a new application.</span>
            </template>
            <el-radio-group v-model="confirmation.samePageFormBehavior">
                <el-radio-button value="hide_form">Hide form</el-radio-button>
                <el-radio-button value="reset_form">Reset form</el-radio-button>
            </el-radio-group>
        </el-form-item>

        <!--Custom page-->
        <el-form-item v-else-if="confirmation.redirectTo === 'customPage'">
            <template #label>
                <span class="wpjb_field_label">Redirect page</span>
                <span class="wpjb_field_help">The page the applicant goes to after sending the form.</span>
            </template>
            <el-select v-model="confirmation.customPage" filterable placeholder="Select a page" aria-label="Redirect page">
                <el-option
                    v-for="page in pages"
                    :key="page.ID"
                    :label="page.post_title"
                    :value="page.ID"
                />
            </el-select>
            <p class="wpjb_field_note">To show the confirmation on that page, add <code>[wpjobboard_confirmation]</code> to it.</p>
        </el-form-item>

        <!--Custom URL-->
        <el-form-item v-else-if="confirmation.redirectTo == 'customUrl'">
            <template #label>
                <span class="wpjb_field_label">Redirect URL</span>
                <span class="wpjb_field_help">The web address the applicant goes to after sending the form.</span>
            </template>
            <el-input placeholder="https://" v-model="confirmation.customUrl" aria-label="Redirect URL">
                <template #suffix>
                    <popover
                        @command="(code) => { confirmation.customUrl += code }"
                        :data="editorShortcodes"
                        btnType="text"
                        buttonText="">
                    </popover>
                </template>
            </el-input>
            <p class="wpjb_field_note">To show the confirmation on that page, add <code>[wpjobboard_confirmation]</code> to it.</p>
        </el-form-item>
    </div>
</template>

<script>
    import wpEditor from '../../Common/_wp_editor.vue';
    import popover from '../../Common/input-popover-dropdown.vue';

    export default {
        name: 'AddConfirmation',
        components: {
            wpEditor,
            popover
        },
        props: ['pages', 'editorShortcodes', 'confirmation'],
        computed: {
            inputsFirstShortcodes() {
                return this.editorShortcodes;
            }
        },
        data() {
            return {
                redirectToOptions: {
                    samePage: 'Show a message',
                    customPage: 'Go to a page',
                    customUrl: 'Go to a URL',
                }
            }
        },
        mounted() {
           
        }
    }
</script>