<template>
    <div class="wpjb_builder">
        <div class="payform_section_header">
            <h2 class="payform_section_title">{{ $t('Application form') }}</h2>
            <div class="payform_section_actions">
                <el-button @click="saveSettings()" :loading="saving" class="payform_action" type="primary">
                    {{ $t('Save form') }}
                </el-button>
            </div>
        </div>

        <div class="wpjb_builder_layout">
            <div class="wpjb_builder_main">
                <div id="payform_builder"
                     v-loading="fetching"
                     :element-loading-text="$t('Loading fields...')"
                     class="wpjb_card wpjb_builder_card">
                    <div class="wpjb_card_head">
                        <h3>
                            {{ $t('Fields') }}
                            <span v-if="builder_elements.length" class="wpjb_count wpjb_num">({{ builder_elements.length }})</span>
                        </h3>
                        <span v-if="builder_elements.length" class="wpjb_builder_hint">{{ $t('Drag the handle to reorder fields') }}</span>
                    </div>

                    <div v-if="validationErrors" class="wpjb_builder_errors">
                        <el-alert v-for="(error, error_key) in validationErrors"
                                  :key="error_key"
                                  :title="error"
                                  :closable="false"
                                  type="error"
                                  show-icon/>
                    </div>

                    <draggable
                        :list="builder_elements"
                        :item-key="getElementKey"
                        tag="div"
                        class="wpjb_field_list"
                        handle=".wpjb_drag_handle"
                        :animation="150"
                        ghost-class="ghost"
                        group="components"
                    >
                        <template #item="{ element }">
                            <div :class="['wpjb_field', 'payform_builder_' + element.type, {
                                     'is-open': current_editing == element.id,
                                     'is-step': element.type == 'step_component'
                                 }]">
                                <div class="wpjb_field_head">
                                    <button type="button"
                                            class="wpjb_drag_handle"
                                            :aria-label="$t('Drag to reorder')"
                                            :title="$t('Drag to reorder')">
                                        <el-icon><Rank/></el-icon>
                                    </button>
                                    <button type="button"
                                            class="wpjb_field_toggle"
                                            :aria-expanded="current_editing == element.id ? 'true' : 'false'"
                                            @click="toggleEditing(element.id)">
                                        <span class="wpjb_field_label">{{ fieldLabel(element) }}</span>
                                        <span class="wpjb_field_type">{{ paletteLabel(element) }}</span>
                                        <el-icon class="wpjb_field_chevron"><ArrowDown/></el-icon>
                                    </button>
                                </div>
                                <transition name="slide-down">
                                    <div v-if="current_editing == element.id" class="wpjb_field_settings">
                                        <element-editor @deleteItem="deleteItem(element)"
                                                        @updateItem="saveSettings"
                                                        :element="element" :all_elements="builder_elements"/>
                                    </div>
                                </transition>
                            </div>
                        </template>
                        <template #footer>
                            <div v-if="!builder_elements.length && !fetching" class="wpjb_empty wpjb_builder_empty">
                                <h3>{{ $t('No fields yet') }}</h3>
                                <p>{{ $t('Add fields from the list on the right. Click a field type, or drag it here. Applicants fill in these fields when they apply.') }}</p>
                            </div>
                        </template>
                    </draggable>

                    <div v-if="builder_elements.length"
                         :class="['wpjb_field', 'wpjb_field_submit', { 'is-open': current_editing == '_submit_button' }]">
                        <div class="wpjb_field_head">
                            <span class="wpjb_drag_handle is-static" aria-hidden="true">
                                <el-icon><Setting/></el-icon>
                            </span>
                            <button type="button"
                                    class="wpjb_field_toggle"
                                    :aria-expanded="current_editing == '_submit_button' ? 'true' : 'false'"
                                    @click="toggleEditing('_submit_button')">
                                <span class="wpjb_field_label">{{ $t('Submit button') }}</span>
                                <span class="wpjb_field_type">{{ $t('Button') }}</span>
                                <el-icon class="wpjb_field_chevron"><ArrowDown/></el-icon>
                            </button>
                        </div>
                        <transition name="slide-down">
                            <div v-if="current_editing == '_submit_button'" class="wpjb_field_settings">
                                <submit-button-settings @updateSettings="saveSettings"
                                                        :submit_button="submit_button_settings"/>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>

            <aside class="wpjb_builder_aside">
                <div class="wpjb_card wpjb_palette_card">
                    <div class="wpjb_card_head">
                        <h3>{{ $t('Add a field') }}</h3>
                    </div>
                    <div v-loading="fetching" class="wpjb_card_body">
                        <p class="wpjb_builder_hint">{{ $t('Click a field type to add it, or drag it into the form.') }}</p>
                        <div v-for="(componentGroup, componentGroupIndex) in fieldGroups"
                             :key="componentGroupIndex"
                             class="wpjb_palette_group">
                            <h4 v-if="Object.keys(fieldGroups).length > 1" class="wpjb_palette_title">{{ componentGroup.title }}</h4>
                            <draggable :list="componentGroup.elements"
                                       item-key="type"
                                       tag="div"
                                       class="wpjb_palette"
                                       :clone="cloneItem"
                                       draggable=".item_active"
                                       :group="{ name: 'components', pull: 'clone', put: false }"
                                       :sort="false">
                                <template #item="{ element: component }">
                                    <button type="button"
                                            :data-item_name="component.type"
                                            class="wpjb_palette_item"
                                            :class="getItemClasses(component)"
                                            @click="addComponent(component)">
                                        <el-icon><component :is="fieldIcon(component.type)"/></el-icon>
                                        <span :title="component.editor_title">{{ paletteLabel(component) }}</span>
                                    </button>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<script>
    // Iterate an array or an object like lodash/each
    const each = (collection, callback) => {
        Object.keys(collection || {}).forEach((key) => callback(collection[key], key));
    };
    import {
        Setting, ArrowDown, Rank, User, Message, Paperclip, EditPen, Document, Odometer,
        List, CircleCheck, Select, Calendar, Hide, Postcard, Guide
    } from '@element-plus/icons-vue';
    import ElementEditor from './editor/_ElementEditor.vue';
    import draggable from 'vuedraggable'
    import SubmitButtonSettings from './editor/_SubmitButtonSettings.vue';

    // Icon for each field type in the "Add a field" list
    const fieldIcons = {
        applicant_name: User,
        applicant_email: Message,
        file_upload_input: Paperclip,
        text: EditPen,
        textarea: Document,
        number: Odometer,
        select: List,
        radio: CircleCheck,
        checkbox: Select,
        date: Calendar,
        hidden_input: Hide,
        custom_html: Postcard,
        step_component: Guide
    };

    // Fallback keys for builder elements that have no id (should not happen)
    const fallbackKeys = new WeakMap();
    let fallbackCounter = 0;

    export default {
        name: 'form_builder',
        components: {
            ElementEditor,
            draggable,
            SubmitButtonSettings,
            // Icons used as tags in the template must be registered here
            Setting,
            ArrowDown,
            Rank
        },
        props: ['form_id'],
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
                showNotice: true,
                validationErrors: false
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
                        title: 'Input fields',
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
            fieldLabel(element) {
                return (element.field_options && element.field_options.label) ? element.field_options.label : element.editor_title;
            },
            // Short type name: "Dropdown Field" reads as "Dropdown"
            paletteLabel(component) {
                return (component.editor_title || '').replace(/ Field$/, '');
            },
            fieldIcon(type) {
                return fieldIcons[type] || EditPen;
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
                        message: 'Select a valid field type',
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
                        message: 'This field is already on your form. You can add it only once.',
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
            this.$setTitle('Application form');
        }
    }
</script>
