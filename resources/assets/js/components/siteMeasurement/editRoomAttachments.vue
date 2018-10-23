<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in editRoomAttachmentsList" :key="file.id">
            <div :title="file.title" class="cursor-pointer">{{ file.title }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeEditRoomAttachment(file.renamedFileName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {
            
        props: {

            "edit-room-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeEditRoomAttachment(file) {

                this.$emit("deleteroomattachment", {
                    "uploadElement": "UploadEditRoomPics", "renamedFile": file, 
                    "totalCopies": this.$root.totalEditRoomAttachments, 
                    "oldCopies": this.$root.oldEditRoomAttachments,
                    "newUploadedcopies": this.$root.newEditRoomAttachments,
                    "deletedCopies" : this.$root.deletedEditRoomAttachments 
                });
            }
        }
    }
</script>