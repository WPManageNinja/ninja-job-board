<template>
    <div class="wpjb_settings_page">
        <div class="payform_section_header">
            <h3 class="payform_section_title">{{ $t('Form design') }}</h3>
            <div class="payform_section_actions">
                <el-button :loading="saving" @click="saveSettings()" type="primary">
                    {{ $t('Save settings') }}
                </el-button>
            </div>
        </div>
        <el-form v-loading="fetching" ref="layout_settings" class="wpjb_setting_rows" label-position="top" :model="layout_settings">
            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>Layout</h3>
                </div>
                <!--Label placement-->
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Label alignment</span>
                        <span class="wpjb_field_help">Where field labels sit: above, left of, or right of the field.</span>
                    </template>
                    <el-radio-group v-model="layout_settings.labelPlacement">
                        <el-radio-button v-for="(labelOption, optionName) in labelPlacementOptions"
                                         :value="optionName" :key="optionName">
                            {{ labelOption }}
                        </el-radio-button>
                    </el-radio-group>
                </el-form-item>

                <!--Required asterisk mark position -->
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Required marker</span>
                        <span class="wpjb_field_help">Where the asterisk shows on required fields.</span>
                    </template>
                    <el-radio-group v-model="layout_settings.asteriskPlacement">
                        <el-radio-button v-for="(option, optionName) in asteriskPlacementMock"
                                         :value="optionName" :key="optionName">
                            {{ option }}
                        </el-radio-button>
                    </el-radio-group>
                </el-form-item>

                <!--Submit Button Position -->
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Submit button position</span>
                        <span class="wpjb_field_help">Alignment of the submit button under the form.</span>
                    </template>
                    <el-radio-group v-model="layout_settings.submit_button_position">
                        <el-radio-button value="left">Left</el-radio-button>
                        <el-radio-button value="center">Center</el-radio-button>
                        <el-radio-button value="right">Right</el-radio-button>
                    </el-radio-group>
                </el-form-item>
            </div>

            <div class="wpjb_card wpjb_setting_card">
                <div class="wpjb_card_head">
                    <h3>Field styles</h3>
                </div>
                <!--Label font weight-->
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Bold labels</span>
                        <span class="wpjb_field_help">Show all field labels in bold.</span>
                    </template>
                    <el-switch active-value="yes" inactive-value="no" aria-label="Bold labels"
                               v-model="layout_settings.extra_styles.wpjb_bold_labels"/>
                </el-form-item>

                <!--Input Styling Option-->
                <el-form-item>
                    <template #label>
                        <span class="wpjb_field_label">Default input styles</span>
                        <span class="wpjb_field_help">Use the plugin's input styles. Turn off to use your theme's styles.</span>
                    </template>
                    <el-switch active-value="yes" inactive-value="no" aria-label="Default input styles"
                               v-model="layout_settings.extra_styles.wpjb_default_form_styles"/>
                </el-form-item>
            </div>
        </el-form>
    </div>
</template>

<script>
    export default {
        name: 'form_design_settings',
        props: ['form_id'],
        data() {
            return {
                // Same defaults as the server, so the switches start with a valid value
                layout_settings: {
                    extra_styles: {
                        wpjb_default_form_styles: 'yes',
                        wpjb_bold_labels: 'no'
                    }
                },
                labelPlacementOptions: {
                    'top': 'Top',
                    'left': 'Left',
                    'right': 'Right'
                },
                asteriskPlacementMock: {
                    'none': 'None',
                    'left': 'Before label',
                    'right': 'After label'
                },
                fetching: false,
                saving: false
            }
        },
        methods: {
            saveSettings() {
                this.saving = true;
                this.$api.post('/forms/' + this.form_id + '/design', {
                    layout_settings: this.layout_settings
                })
                    .then(response => {
                        this.$message.success(response.message);
                    })
                    .catch((error) => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            },
            getSettings() {
                this.fetching = true;
                this.$api.get('/forms/' + this.form_id + '/design')
                    .then((response) => {
                        this.layout_settings = response.layout_settings;
                    })
                    .catch(error => {
                        this.$message.error(error.message);
                    })
                    .finally(() => {
                        this.fetching = false;
                    });
            }
        },
        mounted() {
            this.getSettings();
            this.$setTitle('Design Settings');
        }
    }
</script>