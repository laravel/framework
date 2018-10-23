<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in checklistAttachmentsList" :key="file.id">
            <div :title="file.fileName" class="cursor-pointer">{{ file.fileName }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeCopy(file.renamedFileName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
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

            removeCopy(file) {

                this.$emit("deletechecklist", {
                    "uploadElement": "EditChecklistCopy", "renamedFile": file, 
                    "totalCopies":  this.$root.totalChecklistCopies, 
                    "oldCopies": this.$root.oldChecklistCopies,
                    "newUploadedcopies": this.$root.newChecklistCopies,
                    "deletedCopies" : this.$root.deleteChecklistCopies 
                });
            }
        }
    }
</script>