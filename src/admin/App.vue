<template>
    <div class="wrap">
        <div class="payform_admin">
            <router-view></router-view>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'WPJobBoardApp',
        mounted() {
            document.querySelectorAll('.update-nag, .notice, #wpbody-content > .updated, #wpbody-content > .error').forEach((el) => el.remove());
        }
    }
</script>
