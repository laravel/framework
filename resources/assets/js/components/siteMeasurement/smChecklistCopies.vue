<template>
    <ul class="list-group create-site">
        <li class="list-group-item attachments" v-for="(file,index) in checklistAttachmentsList" :key="file.id">
            <div :title="file.name" class="cursor-pointer">{{ file.name }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click="removeCheckListCopies(index)" class="cursor-pointer" title="Remove attachment">X</span></strong>
        </li>
    </ul>
</template>

<script>
    export default {

        props: {

            "checklist-attachments-list": {

                type: Array
            }
        },

        methods: {

            removeCheckListCopies(index) {

                this.$emit("deletechecklistcopies", {"UploadElement": "ChecklistCopy", "FileIndex": index});
            }
        }
    }
</script>