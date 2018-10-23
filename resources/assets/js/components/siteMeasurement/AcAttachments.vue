<template>
   <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in acAttachmentsList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeAcAttachment(index)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {

        props: {

            "ac-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeAcAttachment(index) {

                this.$emit("deleteacattachment", {"UploadElement": "WallDirectionAttachments", "FileIndex": index});
            }
        }
    }
</script>