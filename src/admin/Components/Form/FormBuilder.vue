<template>
    <div class="form_builder">
        <el-container>
            <el-main>
                <div class="edit_form_warpper">
                    <div class="all_payforms_wrapper payform_section">
                        <div class="payform_section_header">
                            <h3 class="payform_section_title">
                                {{ $t('Application Form Fields') }}
                            </h3>
                            <div class="payform_section_actions">
                                <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                    {{ $t( 'Update Fields' ) }}
                                </el-button>
                            </div>
                        </div>
                        <div
                            v-loading="fetching"
                            style="min-height: 200px"
                            element-loading-background="rgb(255, 255, 255)"
                            element-loading-text="Loading Builder..."
                            id="payform_builder" class="payform_section_body">
                            <div v-if="validationErrors" class="validation_errors_block">
                                <el-alert v-for="(error,error_key) in validationErrors" :key="error_key" type="error">
                                    {{error}}
                                </el-alert>
                            </div>

                            <div class="payform_builder_items">
                                <draggable
                                    :list="builder_elements"
                                    :item-key="getElementKey"
                                    tag="div"
                                    handle=".handler"
                                    :animation="0"
                                    ghost-class="ghost"
                                    group="components"
                                >
                                    <template #item="{ element }">
                                        <div :class="'payform_builder_'+element.type"
                                             class="payform_builder_item"
                                        >
                                            <div class="payform_builder_header">
                                                <div class="payform_head_left">
                                                    <div class="handler payform_inline_item">
                                                        <span class="dashicons dashicons-menu"></span>
                                                    </div>
                                                    <div class="element_title payform_inline_item">
                                                        {{ (element.field_options && element.field_options.label) ?
                                                        element.field_options.label : element.editor_title }}
                                                    </div>
                                                </div>
                                                <div @click="toggleEditing(element.id)" class="payform_head_right">
                                                    <div class="element_type payform_inline_item">
                                                        {{ element.editor_title }}
                                                    </div>
                                                    <div class="element_control payform_inline_item">
                                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <transition name="slide-down">
                                                <div v-if="current_editing == element.id"
                                                     class="payform_builder_item_settings">
                                                    <element-editor @deleteItem="deleteItem(element)"
                                                                    @updateItem="saveSettings"
                                                                    :element="element" :all_elements="builder_elements"/>
                                                </div>
                                            </transition>
                                        </div>
                                    </template>
                                    <template #footer>
                                        <div v-if="!builder_elements.length" class="empty_builder_items">
                                            <img style="max-width: 100%; max-height: 300px;" :src="empty_form_image"/>
                                        </div>
                                    </template>
                                </draggable>
                                <div v-if="builder_elements.length"
                                     class="payform_submit_button_settings payform_builder_item">
                                    <div class="payform_builder_header">
                                        <div class="payform_head_left">
                                            <div class="payform_inline_item">
                                                <el-icon><Setting /></el-icon>
                                            </div>
                                            <div class="element_title payform_inline_item">
                                                Submit Button Settings
                                            </div>
                                        </div>
                                        <div @click="toggleEditing('_submit_button')" class="payform_head_right">
                                            <div class="element_type payform_inline_item">
                                                button
                                            </div>
                                            <div class="element_control payform_inline_item">
                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <transition name="slide-down">
                                        <div v-if="current_editing == '_submit_button'"
                                             class="payform_builder_item_settings">
                                            <submit-button-settings @updateSettings="saveSettings"
                                                                    :submit_button="submit_button_settings"/>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </el-main>
            <el-aside width="250px">
                <div v-for="(componentGroup,componentGroupIndex) in fieldGroups" :key="componentGroupIndex" class="wpf_field_group" :class="isActiveGroup(componentGroupIndex)">
                    <div @click="toggleActiveGroup(componentGroupIndex)" class="field_group_header">
                        <i :class="componentGroup.icon"></i> {{componentGroup.title}}
                        <span v-if="isActiveGroup(componentGroupIndex)" class="field_group_icon_nav"><el-icon><CaretTop /></el-icon></span>
                        <span v-else class="field_group_icon_nav"><el-icon><CaretBottom /></el-icon></span>
                    </div>
                    <div style="min-height: 50px" v-loading="fetching" class="field_group_body">
                        <draggable :list="componentGroup.elements"
                                   item-key="type"
                                   class="dragArea"
                                   :clone="cloneItem"
                                   draggable=".item_active"
                                   :group="{ name:'components',  pull:'clone', put:false }"
                                   :sort="false">
                            <template #item="{ element: component }">
                                <el-button
                                    size="small"
                                    :data-item_name="component.type"
                                    class="wpf_element_items"
                                    :class="getItemClasses(component)"
                                    @click="addComponent(component)"
                                >
                                    {{ component.editor_title }}
                                </el-button>
                            </template>
                        </draggable>
                    </div>
                </div>
            </el-aside>
        </el-container>
    </div>

</template>

<script>
    // Iterate an array or an object like lodash/each
    const each = (collection, callback) => {
        Object.keys(collection || {}).forEach((key) => callback(collection[key], key));
    };
    import { Setting, CaretTop, CaretBottom } from '@element-plus/icons-vue';
    import ElementEditor from './editor/_ElementEditor.vue';
    import draggable from 'vuedraggable'
    import SubmitButtonSettings from './editor/_SubmitButtonSettings.vue';
    import WpEditor from '../Common/_wp_editor_ext.vue'

    // Fallback keys for builder elements that have no id (should not happen)
    const fallbackKeys = new WeakMap();
    let fallbackCounter = 0;

    export default {
        name: 'form_builder',
        components: {
            ElementEditor,
            draggable,
            SubmitButtonSettings,
            WpEditor,
            CaretBottom,
            CaretTop,
            Setting
        },
        props: ['form_id'],
        setup() {
            return { Setting, CaretTop, CaretBottom };
        },
        data() {
            return {
                app_ready: false,
                current_editing: '',
                job_post: [],
                builder_elements: [],
                fetching: false,
                saving: false,
                components: {},
                adding_component: '',
                submit_button_settings: {},
                empty_form_image: window.wpJobBoardsAdmin.assets_url + 'images/form_instruction.png',
                showNotice: true,
                validationErrors: false,
                collapsedGroups: {
                    payment: false,
                    payment_method: false,
                    general: false
                }
            }
        },
        computed: {
            elementIds() {
                let elements = [];
                each(this.builder_elements, (element) => {
                    elements.push(element.id);
                });
                return elements;
            },
            hasPaymentMethodField() {
                let hasField = false;
                each(this.builder_elements, (element) => {
                    if (element.group == 'payment_method_element') {
                        hasField = true;
                        return true;
                    }
                });
                return hasField;
            },
            form_tips() {
                let hasPaymentField = false;
                let hasItemField = false;

                each(this.builder_elements, (element) => {
                    if (element.group == 'payment') {
                        hasPaymentField = true;
                    }
                    if (element.group == 'payment_method_element') {
                        hasItemField = true;
                    }
                });

                if (!hasPaymentField && hasItemField) {
                    return 'You have added payment method field, to accept payments add <b>Product Fields</b>';
                }
                if (hasPaymentField && !hasItemField) {
                    return 'You have added order item field, to accept payments add <b>Payment Method Field</b>';
                }
                if (this.builder_elements.length && !hasPaymentField && !hasItemField) {
                    return 'Add <b>Product Fields</b> and <b>Payment Method Field</b> to accept payment';
                }
                return false;
            },
            fieldGroups() {
                let componentGroups = {
                    general: {
                        title: 'Input Fields',
                        icon: 'dashicons dashicons-feedback',
                        elements: []
                    }
                }
                each(this.components, (component, componentName) => {
                    if (componentGroups[component.postion_group]) {
                        componentGroups[component.postion_group].elements.push(component);
                    }
                });
                return componentGroups;
            }
        },
        methods: {
            getElementKey(element) {
                if (element && element.id) {
                    return element.id;
                }
                if (!fallbackKeys.has(element)) {
                    fallbackCounter++;
                    fallbackKeys.set(element, '_wpjb_el_' + fallbackCounter);
                }
                return fallbackKeys.get(element);
            },
            setStoreData(key, value) {
                if (window.localStorage) {
                    localStorage.setItem('wppayforms_' + key, value);
                }
            },
            deleteStoreData(key) {
                if (window.localStorage) {
                    localStorage.removeItem('wppayforms_' + key);
                }
            },
            getFromStore(key, defaultValue) {
                if (window.localStorage) {
                    let itemValue = localStorage.getItem('wppayforms_' + key);
                    if (itemValue) {
                        return itemValue;
                    }
                }
                return defaultValue;
            },
            isActiveGroup(index) {
                if(this.collapsedGroups[index]) {
                    return 'wpf_collapsed';
                }
                return false;
            },
            toggleActiveGroup(index) {
                this.collapsedGroups[index] = !this.collapsedGroups[index];
            },
            cloneItem(component) {
                if(component.disabled) {
                    this.handlePro(component);
                    return;
                }
                return this.getClonedItem(component);
            },
            getSettings() {
                this.fetching = true;
                this.$api.get('/forms/' + this.form_id + '/builder')
                    .then(response => {
                        this.job_post = response.job_post;
                        this.builder_elements = response.builder_settings || [];
                        this.components = response.components;
                        this.submit_button_settings = response.form_button_settings;
                    })
                    .catch(error => {
                        this.$showAjaxError(error);
                    })
                    .finally(() => {
                        this.fetching = false;
                        this.app_ready = true;
                    })
            },
            addComponent(component, newIndex) {
                let componentName = component.type;
                if (!componentName) {
                    this.$message({
                        message: 'Please Select valid Component',
                        type: 'error'
                    });
                    return;
                }

                if(component.disabled) {
                   return this.handlePro(component);
                }

                // check if it's single only
                if (component.single_only && this.alreadyExistElement(component.group)) {
                    this.$message({
                        message: 'Element already exists on your form. You can not add more of this type',
                        type: 'error'
                    });
                    return;
                }
                let nonMutableElement = this.getClonedItem(component);
                if (newIndex == undefined) {
                    this.builder_elements.push(nonMutableElement);
                } else {
                    this.builder_elements.splice(newIndex, 0, nonMutableElement);
                }
                this.current_editing = nonMutableElement.id;
                this.adding_component = '';
            },
            handlePro(component) {
                let message = 'This item is disabled';
                if(component.disabled_message) {
                    message = component.disabled_message;
                }
                this.$notify.error(message);
                return false;
            },
            getClonedItem(component) {
                let componentName = component.type;
                let nonMutableElement = JSON.parse(JSON.stringify(component));
                // Find an unique name for this elament
                nonMutableElement.id = this.getComponentUID(componentName);
                if (!nonMutableElement.field_options) {
                    nonMutableElement.field_options = {};
                }
                return nonMutableElement;
            },
            alreadyExistElement(type) {
                let status = false;
                each(this.builder_elements, (element) => {
                    if (element.group == type) {
                        status = true;
                    }
                });
                return status;
            },
            getComponentUID(componentName) {
                let counter = 1;
                let originalName = JSON.parse(JSON.stringify(componentName));
                while (this.elementIds.indexOf(componentName) != -1) {
                    componentName = originalName + '_' + counter;
                    counter = counter + 1;
                }
                return componentName;
            },
            saveSettings() {
                this.saving = true;
                this.validationErrors = false;
                if (!this.form_tips) {
                    this.deleteStoreData('hide_form_' + this.form_id);
                }

                this.$api.post('/forms/' + this.form_id + '/builder', {
                    builder_settings: this.builder_elements,
                    submit_button_settings: this.submit_button_settings
                })
                    .then(response => {
                        this.$message({
                            message: response.message,
                            type: 'success'
                        });
                    })
                    .catch(error => {
                        this.$message({
                            message: error.message,
                            type: 'error'
                        });
                        if (error.data && error.data.errors) {
                            this.validationErrors = error.data.errors;
                            jQuery([document.documentElement, document.body]).animate({
                                scrollTop: jQuery('#payform_builder').offset().top - 100
                            }, 200);
                        }
                    })
                    .finally(() => {
                        this.saving = false;
                    })
            },
            toggleEditing(elementId) {
                if (this.current_editing == elementId) {
                    this.current_editing = '';
                } else {
                    this.current_editing = elementId;
                }
            },
            deleteItem(element) {
                const elementIndex = this.builder_elements.indexOf(element);
                if (elementIndex !== -1) {
                    this.builder_elements.splice(elementIndex, 1);
                }
            },
            getItemClasses(component) {
                let isActive = '';
                if (!this.hasPaymentMethodField || component.group != 'payment_method_element') {
                    isActive = ' item_active';
                }
                return 'wpf_item_' + component.group + isActive;
            },
            hideNotices() {
                this.setStoreData('hide_form_' + this.form_id, 'yes');
            }
        },
        mounted() {
            this.getSettings();
            this.showNotice = this.getFromStore('hide_form_' + this.form_id) != 'yes';
            this.$setTitle('Form Builder');
        }
    }
</script>

<style lang="scss">
    .flip-list-move {
        transition: transform 0.5s;
    }

    .no-move {
        transition: transform 0s;
    }

    .ghost {
        opacity: 0.2;
        background: gray;
        color: white;
        width: 100%;
        padding: 15px;
        display: block;
        overflow: hidden;
    }
</style>