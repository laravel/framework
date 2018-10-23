<template>
    <div class="col-md-12 text-right custom-info-block">
        <span class="pd-5 text-capitalize user-info">
            <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
            {{ userInfo.userName }}
        </span>
        <span class="pd-5 user-info">
            <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
            {{ userInfo.mobile }}
        </span>
        <span class="pd-5 user-info"> 
            <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
            {{ userInfo.email }}
        </span>
    </div>
</template>

<script>
    export default {
        props: {
            "user-info": {
                type: Object
            }
        }
    }
</script>