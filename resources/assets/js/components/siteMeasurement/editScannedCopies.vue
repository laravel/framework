<template>
    <ul class="list-group">
        <li class="list-group-item attachmentList" v-for="(file,index) in scannedAttachmentsList" :key="file.id">
            <div :title="file.fileName" class="cursor-pointer">{{ file.fileName }}</div>
            <strong class="removeFile mr-lt-12 pull-right"><span @click.prevent="removeCopy(file.renamedFileName)" class="cursor-pointer" title="Remove attachment">X</span></strong>
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

            removeCopy(file) {

                this.$emit("deletecopy", {
                    "uploadElement": "EditSMCopy", "renamedFile": file, 
                    "totalCopies":  this.$root.totalScannedCopies, 
                    "oldCopies": this.$root.oldScannedCopies,
                    "newUploadedcopies": this.$root.newScannedCopies,
                    "deletedCopies" : this.$root.deleteScannedCopies 
                });
            }
        }
    }
</script>