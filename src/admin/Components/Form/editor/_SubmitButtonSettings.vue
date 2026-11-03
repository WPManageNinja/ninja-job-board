<template>
    <div class="submit_btn_settings">
        <el-form ref="submit_button_element_form" :model="submit_button" label-position="top" class="wpjb_editor_form" @submit.prevent>
            <div class="editor_form_item">
                <el-form-item :label="$t('Button text')">
                    <el-input :placeholder="$t('For example: Submit application')" v-model="submit_button.button_text"></el-input>
                </el-form-item>
            </div>
            <div class="editor_form_item">
                <el-form-item :label="$t('Button text while submitting')">
                    <el-input :placeholder="$t('For example: Please wait…')" v-model="submit_button.processing_text"></el-input>
                </el-form-item>
            </div>
            <div class="editor_form_item">
                <el-form-item :label="$t('Button style')">
                    <el-select class="item_full_width" v-model="submit_button.button_style">
                        <el-option :label="$t('Default style')" value="wpjb_default_btn"></el-option>
                        <el-option :label="$t('Full width button')" value="wpjb_full_block_btn"></el-option>
                        <el-option :label="$t('No style (theme default)')" value=""></el-option>
                    </el-select>
                </el-form-item>
            </div>
            <div class="editor_form_item">
                <el-form-item :label="$t('CSS class (optional)')">
                    <el-input :placeholder="$t('Extra CSS class for the button')" v-model="submit_button.css_class"></el-input>
                </el-form-item>
            </div>
            <div class="wpjb_editor_footer">
                <div class="wpjb_editor_actions">
                    <el-button @click="updateItem()" type="primary">{{ $t('Save form') }}</el-button>
                </div>
            </div>
        </el-form>
    </div>
</template>

<script>
    export default {
        name: 'submit_button_settings',
        props: ['submit_button'],
        methods: {
            updateItem() {
                this.$emit('updateSettings', this.submit_button);
            }
        }
    }
</script>