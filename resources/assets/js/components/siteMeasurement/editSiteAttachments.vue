<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in siteAttachmentsList" :key="file.id">
            <div :title="file.fileName" class="cursor-pointer">{{ file.fileName }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeSiteAttachment(file.renamedFileName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
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

            removeSiteAttachment(file) {

                this.$emit("deletesiteattachment", {
                    "uploadElement": "UploadFiles", "renamedFile": file, 
                    "totalCopies":  this.$root.totalSiteAttachments, 
                    "oldCopies": this.$root.oldSiteAttachments,
                    "newUploadedcopies": this.$root.newSiteAttachments,
                    "deletedCopies" : this.$root.deletedSiteAttachments 
                });
            }
        }
    }
</script>