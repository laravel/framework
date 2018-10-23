<template>
    <div class="site-address-block">
    <h5>{{siteInfo.ProjectCode}} - <b>{{siteInfo.SuperBuildUpArea}}/ft,</b> <b>{{siteInfo.Unit}}</b></h5>
    <h5>{{siteInfo.SiteProjectName}}, {{siteInfo.SiteCity}}</h5>
</div>
</template>

<script>
    export default {
        props: {
            "site-info": {
                type: Object
            }
        }
    }
</script>