<template>
    <div>
        <el-popover
            placement="right"
            popper-class="el-dropdown-list-wrapper wpjb_shortcode_popper"
            :width="'auto'"
            ref="shortcodePopover"
            trigger="click">
            <div class="el_pop_data_group">
                <div  class="el_pop_data_headings">
                    <ul>
                        <li
                            v-for="(item,item_index) in data"
                            :key="item_index"
                            :data-item_index="item_index"
                            :class="(activeIndex == item_index) ? 'active_item_selected' : ''"
                            @click="activeIndex = item_index">
                            {{item.title}}
                        </li>
                    </ul>
                </div>
                <div class="el_pop_data_body">
                    <ul v-for="(item,current_index) in data" :key="current_index" v-show="activeIndex == current_index" :class="'el_pop_body_item_'+current_index">
                        <li @click="insertShortcode(code.tag)" v-for="(code, codeIndex) in item.placeholders" :key="codeIndex">{{code.label}} <span>{{code.tag}}</span></li>
                    </ul>
                </div>
            </div>
            <template #reference>
                <el-button class="editor-add-shortcode"
                           size="small"
                           :type="buttonType"
                           :link="btnType == 'text'"
                           :aria-label="buttonLabel ? undefined : $t('Insert shortcode')"
                           :title="buttonLabel ? undefined : $t('Insert shortcode')">
                    <template v-if="buttonLabel">
                        <span v-html="buttonLabel"></span>
                        <el-icon class="wpjb_icon_right"><ArrowDown /></el-icon>
                    </template>
                    <el-icon v-else><MenuIcon /></el-icon>
                </el-button>
            </template>
        </el-popover>
    </div>
</template>

<script>
    // Menu is renamed: <Menu> would render the native HTML <menu> element
    import { ArrowDown, Menu as MenuIcon } from '@element-plus/icons-vue';

    export default {
        name: 'inputPopoverDropdown',
        // Icons used as tags in the template must be registered here
        components: { ArrowDown, MenuIcon },
        emits: ['command'],
        setup() {
            return { ArrowDown, MenuIcon };
        },
        props: {
            data: Array,
            close_on_insert: {
                type: Boolean,
                default() {
                    return true;
                }
            },
            // null shows the translated default label; '' shows only the icon
            buttonText: {
                type: String,
                default: null
            },
            btnType: {
                type: String,
                default() {
                    return 'success';
                }
            }
        },
        computed: {
            buttonLabel() {
                return this.buttonText === null ? this.$t('Add shortcodes') : this.buttonText;
            },
            // Element Plus has no type="text" button; it is a link button now
            buttonType() {
                return this.btnType == 'text' ? '' : this.btnType;
            }
        },
        data() {
          return {
              activeIndex: 0
          }
        },
        methods: {
            insertShortcode(code) {
                this.$emit('command', code);
                if(this.close_on_insert && this.$refs.shortcodePopover) {
                    this.$refs.shortcodePopover.hide();
                }
            }
        },
        mounted() {
        }
    }
</script>
