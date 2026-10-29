<template>
    <div>
        <el-popover
            placement="right"
            popper-class="el-dropdown-list-wrapper"
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
                           :link="btnType == 'text'">
                    <template v-if="buttonText">
                        <span v-html="buttonText"></span>
                        <el-icon style="margin-left: 5px;"><ArrowDown /></el-icon>
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
            buttonText: {
                type: String,
                default() {
                    return 'Add Shortcodes';
                }
            },
            btnType: {
                type: String,
                default() {
                    return 'success';
                }
            }
        },
        computed: {
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

<style lang="scss">

    .el-dropdown-list-wrapper {
        padding: 0;

        .group-title {
            display: block;
            padding: 5px 10px;
            background-color: gray;
            color: #fff;
        }
    }

    .input-textarea-value {
        position: relative;

        .icon {
            position: absolute;
            right: 0;
            top: -18px;
            cursor: pointer;
        }
    }
    .el_pop_data_group {
        background: #6c757d;
        overflow: hidden;
        .el_pop_data_headings {
            max-width: 150px;
            float: left;
            ul {
                padding: 0;
                margin: 10px 0px;
                li {
                    color: white;
                    padding: 5px 10px 5px 10px;
                    display: block;
                    margin-bottom: 0px;
                    border-bottom: 1px solid #949393;
                    cursor: pointer;
                    &.active_item_selected {
                        background: whitesmoke;
                        color: #6c757d;
                        border-left: 2px solid #6c757d;
                    }
                }
            }
        }

        .el_pop_data_body {
            float: left;
            background: whitesmoke;
            width: 350px;
            ul {
                padding: 10px 0;
                margin: 0;
                li {
                    color: black;
                    padding: 5px 10px 5px 10px;
                    display: block;
                    margin-bottom: 0px;
                    border-bottom: 1px dotted #dadada;
                    cursor: pointer;
                    text-align: left;
                    &:hover {
                        background: white;
                    }
                    span {
                        font-size: 11px;
                        color: #8e8f90;
                    }
                }
            }
        }
    }

</style>