<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in editAcAttachmentsList" :key="file.id">
            <div :title="file.title" class="cursor-pointer">{{ file.title }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeEditAcAttachment(file.renamedFileName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {
            
        props: {

            "edit-ac-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeEditAcAttachment(file) {

                this.$emit("deleteacattachment", {
                    "uploadElement": "EditWallDirectionAttachments", "renamedFile": file, 
                    "totalCopies": this.$root.TotalEditAcAttachments, 
                    "oldCopies": this.$root.OldEditAcAttachments,
                    "newUploadedcopies": this.$root.NewEditAcAttachments,
                    "deletedCopies" : this.$root.DeletedEditAcAttachments 
                });
            }
        }
    }
</script>