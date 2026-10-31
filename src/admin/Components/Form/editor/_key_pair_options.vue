<template>
    <div class="key_pair_table_wrapper wpjb_options_editor">
        <el-checkbox v-model="value_option">{{ $t('Provide values separately') }}</el-checkbox>
        <table class="ninja_filter_table wpjb_options_table">
            <thead>
            <tr>
                <th class="wpjb_options_handle"><span class="screen-reader-text">{{ $t('Order') }}</span></th>
                <th>{{ $t('Label') }}</th>
                <th v-show="value_option">{{ $t('Value') }}</th>
                <th class="wpjb_options_actions"><span class="screen-reader-text">{{ $t('Actions') }}</span></th>
            </tr>
            </thead>
            <draggable
                :list="item"
                :item-key="getItemKey"
                tag="tbody"
                handle=".handle"
            >
                <template #item="{ element: filter, index }">
                    <tr>
                        <td class="wpjb_options_handle">
                            <span class="handle" :title="$t('Drag to reorder')" :aria-label="$t('Drag to reorder')" role="img">
                                <el-icon><Rank/></el-icon>
                            </span>
                        </td>
                        <td>
                            <el-input @blur="changeFilterLabel(filter)" v-model="filter.label"
                                      :aria-label="$t('Option label')" type="text"></el-input>
                        </td>
                        <td v-show="value_option">
                            <el-input v-model="filter.value" :aria-label="$t('Option value')" type="text"></el-input>
                        </td>
                        <td class="wpjb_options_actions">
                            <el-button :disabled="item.length == 1" @click="deleteItem(index)"
                                       :icon="Minus" :aria-label="$t('Remove option')" :title="$t('Remove option')"/>
                            <el-button @click="add()" v-show="(index + 1) == item.length"
                                       :icon="Plus" :aria-label="$t('Add option')" :title="$t('Add option')"/>
                        </td>
                    </tr>
                </template>
            </draggable>
        </table>
    </div>
</template>
<script>
    import draggable from 'vuedraggable'
    import { Rank, Minus, Plus } from '@element-plus/icons-vue';

    // Options have no id, so give each option object a stable key for vuedraggable
    const optionKeys = new WeakMap();
    let optionCounter = 0;

    export default {
        name: 'key_pair_options',
        components: { draggable, Rank },
        props: ['value'],
        setup() {
            return { Minus, Plus };
        },
        data() {
            return {
                value_option: false,
                item: [{}]
            }
        },
        watch: {
            item: {
                handler() {
                    this.$emit('update:value', JSON.parse(JSON.stringify(this.item)));
                },
                deep: true
            }
        },
        methods: {
            getItemKey(option) {
                if (!optionKeys.has(option)) {
                    optionCounter++;
                    optionKeys.set(option, 'kp_' + optionCounter);
                }
                return optionKeys.get(option);
            },
            deleteItem(index) {
                this.item.splice(index, 1);
            },
            add() {
                this.item.push({
                    label: '',
                    value: ''
                });
            },
            changeFilterLabel(filter) {
                if (!this.value_option) {
                     filter.value = JSON.parse(JSON.stringify(filter.label));
                }
            }
        },
        mounted() {
            if (this.value) {
                this.item = JSON.parse(JSON.stringify(this.value));
            }
        }
    }
</script>
