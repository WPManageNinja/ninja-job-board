<template>
<div>
    <el-input size="small" v-if="fieldType != 'textarea'" v-model="model" :type="fieldType">
        <template #append>
            <el-popover
                placement="bottom"
                :width="200"
                popper-class="el-dropdown-list-wrapper wpjb_shortcode_popper"
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
                    <el-button :icon="MoreFilled" :aria-label="$t('Insert shortcode')" :title="$t('Insert shortcode')"></el-button>
                </template>
            </el-popover>
        </template>
    </el-input>

    <div v-if="fieldType == 'textarea'" class="input-textarea-value">
        <el-popover
            placement="bottom"
            :width="200"
            popper-class="el-dropdown-list-wrapper wpjb_shortcode_popper"
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
                <button type="button" class="icon wpjb_shortcode_trigger" :aria-label="$t('Insert shortcode')" :title="$t('Insert shortcode')"><el-icon><Tickets /></el-icon></button>
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
