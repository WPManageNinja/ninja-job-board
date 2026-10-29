<template>
<div>
    <el-input size="small" v-if="fieldType != 'textarea'" v-model="model" :type="fieldType">
        <template #append>
            <el-popover
                placement="bottom"
                :width="200"
                popper-class="el-dropdown-list-wrapper"
                trigger="click">
                <ul class="el-dropdown-menu el-dropdown-list">
                    <li v-for="(item, itemIndex) in data" :key="itemIndex">
                        <span v-if="data.length > 1" class="group-title">{{ item.title }}</span>
                        <ul>
                            <li v-for="(title, code) in item.shortcodes"
                                :key="code"
                                @click="insertShortcode(code)"
                                class="el-dropdown-menu__item">
                                {{ title }}
                            </li>
                        </ul>
                    </li>
                </ul>
                <template #reference>
                    <el-button :icon="MoreFilled"></el-button>
                </template>
            </el-popover>
        </template>
    </el-input>

    <div v-if="fieldType == 'textarea'" class="input-textarea-value">
        <el-popover
            placement="bottom"
            :width="200"
            popper-class="el-dropdown-list-wrapper"
            trigger="click">
            <ul class="el-dropdown-menu el-dropdown-list">
                <li v-for="(item, itemIndex) in data" :key="itemIndex">
                    <span v-if="data.length > 1" class="group-title">{{ item.title }}</span>
                    <ul>
                        <li v-for="(title, code) in item.shortcodes"
                            :key="code"
                            @click="insertShortcode(code)"
                            class="el-dropdown-menu__item">
                            {{ title }}
                        </li>
                    </ul>
                </li>
            </ul>
            <template #reference>
                <el-icon class="icon"><Tickets /></el-icon>
            </template>
        </el-popover>
        <el-input type="textarea" v-model="model"></el-input>
    </div>
</div>
</template>

<script>
import { MoreFilled, Tickets } from '@element-plus/icons-vue';

export default {
    name: 'inputPopover',
    // Icons used as tags in the template must be registered here
    components: { Tickets },
    emits: ['update:modelValue'],
    setup() {
        return { MoreFilled, Tickets };
    },
    props: {
        modelValue: String,
        fieldType: String,
        data: Array,
        attrName: {
            type: String,
            default: 'attribute_name'
        }
    },
    data() {
        return {
            model: this.modelValue,
        }
    },
    watch: {
        model() {
            this.$emit('update:modelValue', this.model);
        }
    },
    methods: {
        insertShortcode(codeString) {
            if (this.model == undefined) {
                this.model = '';
            }
            this.model += codeString.replace(/param_name/, this.attrName);
        }
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
</style>