<template>
    <ul class="list-group create-site">
        <li class="list-group-item attachments" v-for="(file,index) in siteAttachmentsList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeSiteAttachment(index)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {

        props: {

            "site-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeSiteAttachment(index) {

                this.$emit("deletefile", {"UploadElement": "SiteImages", "FileIndex": index});

            }
        }
    }
</script>