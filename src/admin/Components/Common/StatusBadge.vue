<template>
    <span v-if="status" :class="['wpjb_badge', 'is-' + tone]">{{ label }}</span>
</template>

<script>
    import { statusLabel, statusTone } from '../../utils/status';

    export default {
        name: 'StatusBadge',
        props: {
            status: { type: String, default: '' },
            // application | internal | post
            type: { type: String, default: 'application' }
        },
        computed: {
            label() {
                return statusLabel(this.type, this.status);
            },
            tone() {
                return statusTone(this.type, this.status);
            }
        }
    }
</script>
