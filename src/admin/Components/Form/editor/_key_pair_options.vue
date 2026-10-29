<template>
    <div class="key_pair_table_wrapper">
        <el-checkbox v-model="value_option">Provide Value Separately</el-checkbox>
        <table class="ninja_filter_table">
            <thead>
            <tr>
                <th></th>
                <th>Label</th>
                <th v-show="value_option">Value</th>
                <th></th>
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
                        <td>
                            <span style="margin-top: 10px" class="dashicons dashicons-editor-justify handle"></span>
                        </td>
                        <td>
                            <el-input @blur="changeFilterLabel(filter)" size="small" v-model="filter.label"
                                      type="text"></el-input>
                        </td>
                        <td v-show="value_option">
                            <el-input size="small" v-model="filter.value" type="text"></el-input>
                        </td>
                        <td>
                            <el-button :disabled="item.length == 1" @click="deleteItem(index)" type="danger" size="small">-
                            </el-button>
                            <el-button @click="add()" v-show="(index + 1) == item.length" type="success" size="small">+
                            </el-button>
                        </td>
                    </tr>
                </template>
            </draggable>
        </table>
    </div>
</template>
<script>
    import draggable from 'vuedraggable'

    // Options have no id, so give each option object a stable key for vuedraggable
    const optionKeys = new WeakMap();
    let optionCounter = 0;

    export default {
        name: 'key_pair_options',
        components: { draggable },
        props: ['value'],
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

<style lang="scss">
    table.ninja_filter_table {
        width: 100%;
        text-align: left;
        border-collapse: collapse;

        tr, td, th {
            border: 1px solid #eaeaea;
            padding: 2px 10px;
        }
    }
</style>
