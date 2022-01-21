<template>
    <div class="wrap">
        <div class="payform_admin">
            <router-view></router-view>
        </div>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'WPJobBoardApp',
        mounted() {
            jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();
        }
    }
</script>
