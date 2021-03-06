<template>
    <div class="element_editor">
        <el-form ref="element_form" :model="element" label-width="220px">
            <div v-for="(item, itemName) in element.editor_elements" :class="item.wrapper_class"
                 class="editor_form_item">
                <template v-if="item.type == 'text'">
                    <el-form-item :label="item.label">
                        <template v-if="itemName == 'default_value'">
                            <el-input :placeholder="item.label" size="mini" v-model="element.field_options[itemName]">
                                <popover
                                    @command="(code) => { element.field_options[itemName] += code }"
                                    slot="suffix" :data="merge_tags"
                                    btnType="text"
                                    buttonText='<i class="el-icon-menu"></i>'>
                                </popover>
                            </el-input>
                        </template>
                        <template v-else>
                            <el-input :placeholder="item.label" size="mini"
                                      v-model="element.field_options[itemName]"></el-input>
                        </template>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'number'">
                    <el-form-item :label="item.label">
                        <el-input-number :placeholder="item.label" size="mini"
                                         v-model="element.field_options[itemName]"></el-input-number>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'textarea'">
                    <el-form-item :label="item.label">
                        <el-input type="textarea" :placeholder="item.label" size="mini"
                                  v-model="element.field_options[itemName]"></el-input>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'checkbox'">
                    <el-form-item :label="item.label">
                        <el-checkbox-group v-model="element.field_options[itemName]">
                            <el-checkbox v-for="(option,optionName) in item.options" :label="optionName"
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
                        <p v-if="item.info" v-html="item.info"></p>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'key_pair'">
                    <el-form-item :label="item.label">
                        <key-pair-options :value.sync="element.field_options[itemName]"/>
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
                                   class="item_full_width" size="small" v-model="element.field_options[itemName]">
                            <el-option v-for="(option_name,option_key) in item.options" :key="option_key"
                                       :label="option_name" :value="option_key"></el-option>
                        </el-select>
                        <p v-if="item.info" v-html="item.info"></p>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'info_html'">
                    <div v-html="item.info"></div>
                </template>
                <template v-else-if="item.type == 'confirm_email_switch'">
                    <el-form-item :label="item.label">
                        <el-switch
                            v-model="element.field_options[itemName]"
                            active-value="yes"
                            inactive-value="no">
                        </el-switch>
                        <p v-if="item.info" v-html="item.info"></p>
                    </el-form-item>
                    <el-form-item v-if="element.field_options[itemName] == 'yes'" label="Confirm Email Label">
                        <el-input placeholder="Confirm Email Label" size="mini"
                                  v-model="element.field_options.confirm_email_label"></el-input>
                    </el-form-item>
                </template>
            </div>
            <el-form-item label="Field ID">
                {{ element.id }}
            </el-form-item>
            <div class="action_right">
                <el-button @click="deleteItem()" size="mini">Delete</el-button>
                <el-button @click="updateItem()" type="success" size="mini">Update</el-button>
            </div>
        </el-form>
        <el-dialog
            title="Default Value is a Pro Feature"
            :visible.sync="showDevaultValuePro"
            :append-to-body="true"
            width="60%">
            <div v-if="showDevaultValuePro" class="modal_body wpf_default_value_modal">
                <img :src="assets_url+'images/default_value_screen.png'" />
                <h3>Add Default Value from dynamic variables from WordPress / URL Parameter</h3>
                <a class="el-button el-button--success" target="_blank" rel="noopener" :href="pro_purchase_url">Upgrade To Pro</a>
            </div>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
    import KeyPairOptions from './_key_pair_options';
    import popover from '../../Common/input-popover-dropdown.vue';

    export default {
        name: 'elementEditor',
        components: {
            KeyPairOptions,
            popover
        },
        props: ['element', 'all_elements'],
        comments: {
            KeyPairOptions
        },
        data() {
            return {
                merge_tags: Object.values(window.wpJobBoardsAdmin.value_placeholders),
                showDevaultValuePro: false,
                assets_url: window.wpJobBoardsAdmin.assets_url
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
                this.$set(this.element, 'field_options', {});
            }
        }
    }
</script>