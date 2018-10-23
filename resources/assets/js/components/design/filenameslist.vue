<template>
    <ul class="list-group create-site">
        <li class="list-group-item attachments" v-for="(file,index) in attachmentList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeAttachFiles(index, attachmentName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {

        props: {

            "attachment-list": {

                type: Array
            },
            "attachment-name":{
                
                type: String
            }
        },

        methods: {

            removeAttachFiles(index, attachmentName) {

                this.$emit("deletefile", {"UploadElement": attachmentName, "FileIndex": index});

            }
        }
    }
</script>