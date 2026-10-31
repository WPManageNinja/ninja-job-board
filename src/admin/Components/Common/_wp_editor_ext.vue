<template>
    <div class="wp_vue_editor_wrapper" :class="'editor_wrapper_'+ninja_editor_id">
        <template v-if="hasWpEditor">
            <button @click="showPro" v-if="!has_pro" type="button" class="button ninja_demo_media_button"><span class="dashicons dashicons-admin-media"></span> {{ $t('Add media (Pro)') }}</button>
            <textarea :style="{ minHeight: height }" class="wp_vue_editor" :id="ninja_editor_id">{{modelValue}}</textarea>
        </template>
        <template v-else>
            <p class="wpjb_help">{{ $t('The WordPress editor needs WordPress 4.8 or later. Update WordPress to use it.') }}</p>
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
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
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
                this.$message.info(this.$t('Adding media is a Pro feature.'));
            }
        },
        mounted() {
            this.initEditor();
        },
        beforeUnmount() {

        }
    }
</script>
