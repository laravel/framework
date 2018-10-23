<template>
    <ul class="list-group create-site">
        <li class="list-group-item attachments" v-for="(file,index) in scannedAttachmentsList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click="removeScannedCopies(index)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {

        props: {

            "scanned-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeScannedCopies(index) {

                this.$emit("deletescannedcopies", {"UploadElement": "SMCopy", "FileIndex": index});
            }
        }
    }
</script>