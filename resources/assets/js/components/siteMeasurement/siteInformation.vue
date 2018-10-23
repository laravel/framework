<template>
    <div class="form-group" id="SiteInfo">
        <div id="SiteDetails" class="pd-rt-5 pd-lt-5"></div>
    </div>
</template>

<script>
    export default {
        props: {
            "site-info": {
                type: String
            }
        },
        mounted() {
            $("#SiteDetails").html(this.siteInfo);
        }
    }
</script>