<template> 
    <table class="table table-bordered table-striped" id="GenerelSuggestionsTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
            <tr>
                <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                <th class="text-center text-vertical-align pd-10" width="8%">Image</th> 
                <th class="text-center text-vertical-align" width="8%">Brand</th>
                <th class="text-center text-vertical-align" width="10%">Design Name</th>
                <th class="text-center text-vertical-align" width="12%">Design Number</th>
                <th class="text-center text-vertical-align" width="13%">Type</th>
                <th class="text-center text-vertical-align" width="11%">Surface Finish</th>
                <th class="text-center text-vertical-align" width="6%">Glossiness</th>
                <th class="text-center text-vertical-align" width="14%">Edgeband availibility</th>
                <th class="text-center text-vertical-align" width="16%">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(laminate, index) in suggestionsList">
                <td class="text-center text-vertical-align" width="2%">{{ index+1 }}</td>
                <td class="text-center text-vertical-align" width="8%"> 
                    <div class="image-link">
                        <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                            <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="fullimage-thumbnail cursor-zoom-in" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" @click.prevent="$parent.$options.methods.initializeFSheetThumbnailsPopup(laminate.FullSheetImage, CdnUrl)">
                        </a>
                    </div>
                </td>
                <td class="text-center text-vertical-align" width="8%">{{getBrand(laminate.Brand)}}</td>
                <td class="text-center text-vertical-align" width="10%">{{ laminate.DesignName }}</td>
                <td class="text-center text-vertical-align" width="12%">{{ laminate.DesignNo }}</td>
                <td class="text-center text-vertical-align" width="13%" v-html="(laminate.CategoryName) ? laminate.CategoryName : '<small>N/A</small>'"></td>
                <td class="text-center text-vertical-align" width="11%" v-html="(laminate.SurfaceFinish) ? laminate.SurfaceFinish : '<small>N/A</small>'"></td>
                <td class="text-center text-vertical-align" width="6%">{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                <td class="text-center text-vertical-align" width="14%">{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                <td class="text-vertical-align text-center pd-0" width="16%">
                    <span title="Add" class="cursor-pointer" @click.prevent="addLamToCombination(laminate.LaminateId, laminate)" :id="laminate.LaminateId" v-if="!laminate.Active">
                        <i class="fa fa-fw fa-plus-square" aria-hidden="true"></i>&nbsp;Add to Combination
                    </span>
                    <span title="Added" class="cursor-pointer" v-if="laminate.Active">
                        <i class="fa fa-check check-icon" aria-hidden="true"></i>&nbsp;Added to Combination
                    </span>
                </td>
            </tr>
        </tbody>
    </table> 
</template>

<script>
    // Child component
    export default {
        props: {
            "suggestions-list": {
                type: Array
            }
        },
        data() {
            return {
                CdnUrl: '',
                brands: []
            };
        },
        created() {      
            this.CdnUrl = this.$root.CdnUrl;
            this.brands = this.$root.brands;
        },
        methods: {
            // emit an event to inform parent(Vue) element about activity
            addLamToCombination(id, laminate) {
                this.$emit("selectlaminate", {
                    "Id": id,
                    "Laminate": laminate,
                    "Suggestions": this.$root.SearchResult
                });
            },
            getBrand($brandId) {
                let brand = _.find(this.brands, {'Id': $brandId});
                if (typeof brand !== "undefined") {
                    return brand.Name;
                }
                return 'N/A';
            }
        }
    }
</script>