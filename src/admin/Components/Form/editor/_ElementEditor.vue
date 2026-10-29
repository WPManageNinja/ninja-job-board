<template>
    <div class="element_editor">
        <el-form ref="element_form" :model="element" label-position="top" class="wpjb_editor_form" @submit.prevent>
            <div v-for="(item, itemName) in element.editor_elements" :key="itemName"
                 :class="[item.wrapper_class, 'is-' + item.type]"
                 class="editor_form_item">
                <template v-if="item.type == 'text'">
                    <el-form-item :label="item.label">
                        <template v-if="itemName == 'default_value'">
                            <el-input :placeholder="item.label" v-model="element.field_options[itemName]">
                                <template #suffix>
                                    <popover
                                        @command="(code) => { element.field_options[itemName] += code }"
                                        :data="merge_tags"
                                        btnType="text"
                                        buttonText="">
                                    </popover>
                                </template>
                            </el-input>
                        </template>
                        <template v-else>
                            <el-input :placeholder="item.label"
                                      v-model="element.field_options[itemName]"></el-input>
                        </template>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'number'">
                    <el-form-item :label="item.label">
                        <el-input-number :placeholder="item.label"
                                         v-model="element.field_options[itemName]"></el-input-number>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'textarea'">
                    <el-form-item :label="item.label">
                        <el-input type="textarea" :placeholder="item.label"
                                  v-model="element.field_options[itemName]"></el-input>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'checkbox'">
                    <el-form-item :label="item.label">
                        <el-checkbox-group v-model="element.field_options[itemName]">
                            <el-checkbox v-for="(option,optionName) in item.options" :value="optionName"
                                         :key="optionName">{{option}}
                            </el-checkbox>
                        </el-checkbox-group>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'switch'">
                    <el-form-item :label="item.label">
                        <el-switch
                            v-model="element.field_options[itemName]"
                            active-value="yes"
                            inactive-value="no">
                        </el-switch>
                        <p v-if="item.info" class="wpjb_help" v-html="item.info"></p>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'key_pair'">
                    <el-form-item :label="item.label">
                        <key-pair-options v-model:value="element.field_options[itemName]"/>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'html'">
                    <el-form-item :label="item.label">
                        <el-input
                            type="textarea"
                            rows="5"
                            :placeholder="item.label"
                            v-model="element.field_options[itemName]"></el-input>
                        <div v-if="item.info" class="html_placeholder_instruction" v-html="item.info"></div>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'select_option'">
                    <el-form-item :label="item.label">
                        <el-select :allow-create="item.creatable == 'yes'" filterable default-first-option
                                   class="item_full_width" v-model="element.field_options[itemName]">
                            <el-option v-for="(option_name,option_key) in item.options" :key="option_key"
                                       :label="option_name" :value="option_key"></el-option>
                        </el-select>
                        <p v-if="item.info" class="wpjb_help" v-html="item.info"></p>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'info_html'">
                    <div class="wpjb_help" v-html="item.info"></div>
                </template>
                <template v-else-if="item.type == 'confirm_email_switch'">
                    <el-form-item :label="item.label">
                        <el-switch
                            v-model="element.field_options[itemName]"
                            active-value="yes"
                            inactive-value="no">
                        </el-switch>
                        <p v-if="item.info" class="wpjb_help" v-html="item.info"></p>
                    </el-form-item>
                    <el-form-item v-if="element.field_options[itemName] == 'yes'" label="Confirm email label">
                        <el-input placeholder="Confirm email label"
                                  v-model="element.field_options.confirm_email_label"></el-input>
                    </el-form-item>
                </template>
            </div>
            <div class="wpjb_editor_footer">
                <span class="wpjb_field_id">{{ $t('Field ID') }} <code>{{ element.id }}</code></span>
                <div class="wpjb_editor_actions">
                    <el-button @click="deleteItem()" type="danger" plain :icon="Delete">{{ $t('Delete field') }}</el-button>
                    <el-button @click="updateItem()" type="primary">{{ $t('Save form') }}</el-button>
                </div>
            </div>
        </el-form>

    </div>
</template>

<script>
    import KeyPairOptions from './_key_pair_options.vue';
    import popover from '../../Common/input-popover-dropdown.vue';
    import { Delete } from '@element-plus/icons-vue';

    export default {
        name: 'elementEditor',
        components: {
            KeyPairOptions,
            popover
        },
        props: ['element', 'all_elements'],
        setup() {
            return { Delete };
        },
        data() {
            return {
                merge_tags: Object.values(window.wpJobBoardsAdmin.value_placeholders || {})
            }
        },
        methods: {
            deleteItem() {
                this.$emit('deleteItem', this.element);
            },
            updateItem() {
                this.$emit('updateItem', this.element);
            }
        },
        mounted() {
            if (!this.element.field_options) {
                this.element.field_options = {};
            }
        }
    }
</script>