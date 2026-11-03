<template>
    <div class="wpjb_app">
        <app-header/>
        <div class="payform_admin">
            <router-view></router-view>
        </div>
    </div>
</template>
<script>
    import AppHeader from './Components/Common/AppHeader.vue';

    export default {
        name: 'WPJobBoardApp',
        components: { AppHeader },
        mounted() {
            document.querySelectorAll('.update-nag, .notice, #wpbody-content > .updated, #wpbody-content > .error').forEach((el) => el.remove());
        }
    }
</script>
