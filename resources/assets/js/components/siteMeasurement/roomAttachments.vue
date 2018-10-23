<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in roomAttachmentsList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeRoomAttachment(index)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>

    export default {

        props: {

            "room-attachments-list": {

                type: Array
            }

        },

        methods: {

            removeRoomAttachment(index) {

                this.$emit("deleteroomattachment", {"UploadElement": "UploadRoomPics", "FileIndex": index});

            }
        }
    }

</script>