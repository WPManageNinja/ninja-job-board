<template>
    <div class="wpjb_card wpjb_welcome">
        <div class="wpjb_empty">
            <h3>{{ $t('You have no jobs yet') }}</h3>
            <p>{{ $t('Add a job post, then build its application form and start to collect applications.') }}</p>
            <el-button type="primary" :icon="Plus" @click="create">
                {{ $t('Add your first job') }}
            </el-button>
        </div>
    </div>
</template>

<script>
    import { Plus } from '@element-plus/icons-vue';

    export default {
        name: 'Welcome',
        emits: ['create'],
        setup() {
            return { Plus };
        },
        methods: {
            create() {
                this.$emit('create', true);
            }
        }
    }
</script>
