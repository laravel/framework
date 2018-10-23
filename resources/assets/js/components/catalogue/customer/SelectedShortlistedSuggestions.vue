<template>
    <div v-if="selectedLaminates.length > 0"> 
        <strong>Selected Laminates:</strong>
        <ul class="list-group">
            <li class="list-group-item attachmentList" v-for="(record,index) in selectedLaminates" :key="record.LaminateId">
                <div :title="record.DesignName+' ('+getBrand(record.Brand)+ ')'" class="cursor-pointer">{{record.DesignNo}}</div>
                <strong class="removeFile mr-lt-12 pull-right"><span class="cursor-pointer" title="Remove from Combination" @click.prevent="removeLaminate(record.LaminateId)">X</span></strong>
            </li>
        </ul>
    </div>
</template>

<script>
    // Child component
    export default {
        props: {
            "selected-laminates": {
                type: Array
            }
        },
        created() {
            this.brands = this.$root.brands;
        },
        methods: {
            // emit an event to inform parent(Vue) element about activity
            removeLaminate(id) {
                this.$emit("deletelaminate", {
                    "Id": id,
                    "Suggestions": this.$root.ShortlistedSuggestions
                });
            },
            // Get brand for provided id 
            getBrand(brandId) {
                if (this.brands.length > 0) {
                    let brand = _.find(this.brands, ["Id", brandId]);
                    if (brand !== "undefined") {
                        return brand.Name;
                    }
                }
                return 'N/A';
            }
        }
    }
</script>