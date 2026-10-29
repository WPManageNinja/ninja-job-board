<template>
    <div class="wp_vue_editor_wrapper" :class="'editor_wrapper_'+ninja_editor_id">
        <template v-if="hasWpEditor">
            <button @click="showPro" v-if="!has_pro" type="button" class="button ninja_demo_media_button"><span class="dashicons dashicons-admin-media"></span> Add Media (pro)</button>
            <textarea :style="{ minHeight: height }" class="wp_vue_editor" :id="ninja_editor_id">{{modelValue}}</textarea>
        </template>
        <template v-else>
            <p style="font-style: italic"><small>WP Editor is only available on WordPress version 4.8 or later. Please Upgrade Your WordPress Core</small></p>
            <textarea
                      class="wp_vue_editor wp_vue_editor_plain"
                      v-model="plain_content">
            </textarea>
        </template>

    </div>
</template>

<script>
    export default {
        name: 'wp_editor',
        emits: ['update:modelValue'],
        props: {
            editor_id: {
                type: String,
                default() {
                    return 'wp_editor_'+Date.now();
                }
            },
            modelValue: {
                type: String,
                default() {
                    return '';
                }
            },
            height: {
                type: String,
                default() {
                    return '200px';
                }
            }
        },
        data() {
            return {
                hasWpEditor: !!(window.wp && window.wp.editor),
                plain_content: this.modelValue,
                has_pro: true,
            }
        },
        computed: {
          ninja_editor_id() {
              return 'ninja_editor_'+this.slugify(this.editor_id);
            }
        },
        watch: {
            plain_content() {
                this.$emit('update:modelValue', this.plain_content);
            },
            modelValue() {
                if(!this.modelValue) {
                    this.reloadEditor();
                }
            }
        },
        methods: {
            initEditor() {
                if(this.hasWpEditor) {
                    wp.editor.remove(this.ninja_editor_id);
                    const that = this;
                    wp.editor.initialize(this.ninja_editor_id, {
                        mediaButtons: this.has_pro,
                        mode : "none",
                        tinymce: {
                            toolbar1: 'formatselect,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,strikethrough,underline,forecolor,codeformat,removeformat,undo,redo',
                            valid_elements: "*[*]",
                            forced_root_block : "",
                            setup(ed) {
                                ed.on('change', function (ed, l) {
                                    that.changeContentEvent();
                                });
                            }
                        },
                        quicktags: true,
                    });
                    jQuery('#'+this.ninja_editor_id).on('change', function(e) {
                        that.changeContentEvent();
                    });
                }
            },
            slugify(text)
            {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')           // Replace spaces with -
                    .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                    .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                    .replace(/^-+/, '')             // Trim - from start of text
                    .replace(/-+$/, '');            // Trim - from end of text
            },
            reloadEditor() {
                wp.editor.remove(this.ninja_editor_id);
                jQuery('#'+ this.ninja_editor_id).val('');
                this.initEditor();
            },
            changeContentEvent() {
                let content = wp.editor.getContent(this.ninja_editor_id);
                this.$emit('update:modelValue', content);
            },
            showPro() {
                // has_pro is always true, so this button never shows. The old event bus is gone.
                this.$message.info('Add Media is a pro feature');
            }
        },
        mounted() {
            this.initEditor();
        },
        beforeUnmount() {

        }
    }
</script>
<style lang="scss">
    button.button.ninja_demo_media_button {
        position: absolute;
        z-index: 9999999999;
        cursor: pointer;
    }
    .wp_vue_editor {
        width: 100%;
        min-height: 100px;
    }
    .wp_vue_editor_wrapper {
        position: relative;

        .popover-wrapper {
            z-index: 2;
            position: absolute;
            top: 0;
            right: 0;
            &-plaintext {
                left: auto;
                right: 0;
                top: -32px;
            }
        }
        button.wp-switch-editor {
            margin: 0px !important;
            padding-bottom: 14px;
            line-height: 22px;
            height: 28px;
        }
    }
</style>
