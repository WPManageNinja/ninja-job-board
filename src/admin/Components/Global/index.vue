<template>
    <div :class="{ wpjb_page: $route.path.indexOf('/settings') !== 0 }">
        <router-view></router-view>
    </div>
</template>

<script>
    export default {
        name: 'global_wrapper'
    }
</script>
