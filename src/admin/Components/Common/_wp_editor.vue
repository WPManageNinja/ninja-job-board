<template>
    <div class="wp_vue_editor_wrapper">
        <popover
            v-if="editorShortcodes.length"
            class="popover-wrapper"
            :class="{'popover-wrapper-plaintext': !hasWpEditor}"
            :data="editorShortcodes"
            @command="handleCommand"></popover>
        <textarea v-if="hasWpEditor" class="wp_vue_editor" :id="editor_id">{{modelValue}}</textarea>
        <textarea v-else
                  class="wp_vue_editor wp_vue_editor_plain"
                  v-model="plain_content"
                  @click="updateCursorPos">
        </textarea>
    </div>
</template>

<script>
    import popover from './input-popover-dropdown.vue'

    export default {
        name: 'wp_editor',
        components: {
            popover
        },
        emits: ['update:modelValue'],
        props: {
            editor_id: {
                type: String,
                default() {
                    return 'wp_editor_' + Date.now() + parseInt(Math.random() * 1000);
                }
            },
            modelValue: {
                type: String,
                default() {
                    return '';
                }
            },
            editorShortcodes: {
                type: Array,
                default() {
                    return []
                }
            },
            height: Number
        },
        data() {
            return {
                hasWpEditor: !!(window.wp && window.wp.editor),
                plain_content: this.modelValue,
                cursorPos: this.modelValue ? this.modelValue.length : 0
            }
        },
        watch: {
            plain_content() {
                this.$emit('update:modelValue', this.plain_content);
            }
        },
        methods: {
            initEditor() {
                if (!window.tinymce) {
                    return;
                }
                wp.editor.remove(this.editor_id);
                const that = this;
                wp.editor.initialize(this.editor_id, {
                    mediaButtons: false,
                    tinymce: {
                        height: that.height,
                        toolbar1: 'bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,strikethrough,forecolor,codeformat,undo,redo',
                        setup(ed) {
                            ed.on('init', (ed) => {
                                    tinyMCE.get(that.editor_id).setContent(that.modelValue);
                                    tinyMCE.execCommand('mceRepaint');
                                }
                            );
                            ed.on('change', function (ed, l) {
                                that.changeContentEvent();
                            });
                        }
                    },
                    quicktags: true
                });

                jQuery('#' + this.editor_id).on('change', function (e) {
                    that.changeContentEvent();
                });
            },
            changeContentEvent() {
                let content = wp.editor.getContent(this.editor_id);
                this.$emit('update:modelValue', content);
            },
            handleCommand(command) {
                if (this.hasWpEditor) {
                    tinymce.activeEditor.insertContent(command);
                } else {
                    var part1 = this.plain_content.slice(0, this.cursorPos);
                    var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                    this.plain_content = part1 + command + part2;
                    this.cursorPos += command.length;
                }
            },
            updateCursorPos() {
                var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
                this.cursorPos = cursorPos;
            },
            reloadEditor() {
                wp.editor.remove(this.editor_id);
                jQuery('#' + this.editor_id).val('');
                this.initEditor();
            }
        },
        mounted() {
            if (this.hasWpEditor) {
                this.initEditor();
            }
        }
    }
</script>
