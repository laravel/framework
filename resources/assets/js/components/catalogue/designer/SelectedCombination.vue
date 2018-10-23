<template> 
    <table class="table table-bordered table-striped">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
            <tr>
                <th class="text-center text-vertical-align pd-10" width="5%">#</th>
                <th class="text-center text-vertical-align" width="12%">Image</th> 
                <th class="text-center text-vertical-align" width="15%">Design Name</th>
                <th class="text-center text-vertical-align" width="15%">Design Number</th>
                <th class="text-center text-vertical-align" width="10%">Textured Surface</th>
                <th class="text-center text-vertical-align" width="10%">Room</th>
                <th class="text-center text-vertical-align" width="10%">Glossy</th>
                <th class="text-center text-vertical-align" width="15%">Edgeband Availability</th>
                <th class="text-center text-vertical-align" width="8%">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(laminate, index) in selectedCombination">
                <td class="text-center text-vertical-align" width="5%">{{ index+1 }}</td>
                <td class="text-center text-vertical-align" width="12%"> 
                    <div class="image-link">
                        <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                            <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="fullimage-thumbnail" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName">
                        </a>
                    </div>
                </td>
                <td class="text-center text-vertical-align" width="15%">{{ laminate.DesignName }}</td>
                <td class="text-center text-vertical-align" width="15%">{{ laminate.DesignNo }}</td>
                <td class="text-vertical-align text-center" width="10%">{{ laminate.SurfaceTexture === '1' ? "Yes" : "No" }}</td>
                <td class="text-vertical-align text-center" width="10%">{{ laminate.Room}}</td>
                <td class="text-vertical-align text-center" width="10%">{{ laminate.Glossy === '1' ? "Yes" : "No" }}</td>
                <td class="text-vertical-align text-center" width="15%">{{ laminate.Edgeband === '1' ? "Yes" : "No" }}</td>
                <td class="text-vertical-align text-center" width="8%">
                    <a target="_self">
                        <button type="button" class="btn btn-block btn-custom btn-sm" title="Remove laminate" @click.prevent="removeLaminate(laminate.LaminateId)">Delete</button>
                    </a>
                </td>
            </tr>
        </tbody>
    </table> 
</template>

<script>
    // Child component
    export default {
        props: {
            "selected-combination": {
                type: Array
            }
        },
        data() {
            return {
                CdnUrl: ''
            };
        },
        created() {
            this.CdnUrl = this.$root.CdnUrl;
        },
        methods: {
            // emit an event to inform parent(Vue) element about activity
            removeLaminate(id) {
                this.$emit("deletelaminate", {
                    "Id": id,
                    "ShortlistedCombinations": this.$root.ShortlistedCombinations,
                    "GenerelSearchcombinations": this.$root.SearchResult 
                });
            }
        }
    }
</script>