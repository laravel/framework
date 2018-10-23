<template>
    <div class="modal fade" id="SearchModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content SearchLaminateModal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Laminate Selection</h4>
                </div>
                <div class="modal-body">
                    <h4>Select Laminate</h4>
                    <div class="row">
                        <div class="col-md-5 col-sm-6 col-xs-12">
                            <input type="text" class="form-control search" placeholder="Search..." onfocus="this.placeholder = ''" onblur="this.placeholder = 'Search...'" name="SearchLaminates" v-model="SearchString" id="SearchLaminates">
                        </div>
                        <div class="col-md-7 col-sm-6 col-xs-12 search-btn">
                            <button class="btn btn-primary button-search" @click.prevent="searchLaminates" >Search</button>
                            <button class="btn btn-custom button-search"  SearchString=null; SearchedLaminates=[]; data-dismiss="modal">Close</button>
                        </div>
                    </div>
                    <div class="table-responsive pd-tp-14" v-if="SearchedLaminates.length > 0">
                        <table class="table table-bordered table-striped">
                            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                <tr>
                                <th class="text-center text-vertical-align pd-10" width="5%">#</th>
                                <th class="text-center text-vertical-align" width="12%">Image</th>
                                <th class="text-center text-vertical-align" width="10%">Brand</th>
                                <th class="text-center text-vertical-align" width="10%">Type</th>  
                                <th class="text-center text-vertical-align" width="10%">Design Name</th>
                                <th class="text-center text-vertical-align" width="10%">Design Number</th>
                                <th class="text-center text-vertical-align" width="12%">Surface Finish</th>
                                <th class="text-center text-vertical-align" width="10%">Glossy</th> 
                                <th class="text-center text-vertical-align" width="10%">Textured Surface</th> 
                                <th class="text-center text-vertical-align" width="11%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(laminate, index) in filteredLaminates">
                                <td class="text-center text-vertical-align" width="5%">{{ index+1 }}</td>
                                <td class="text-center text-vertical-align" width="12%"> 
                                    <div class="image-link">
                                        <a :href="CdnUrl+JSON.parse(laminate.FullSheetmage)[0].Path">
                                            <img :src="CdnUrl+JSON.parse(laminate.FullSheetmage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(laminate.FullSheetmage)[0].UserFileName">
                                        </a>
                                    </div>
                                </td>
                                <td class="text-vertical-align" width="10%">{{ getBrandName(laminate.Brand) }}</td>
                                <td class="text-vertical-align" width="10%">{{ laminate.Category ? laminate.Category : "N/A" }}</td>
                                <td class="text-vertical-align" width="10%">{{ laminate.DesignName }}</td>
                                <td class="text-vertical-align" width="10%">{{ laminate.DesignNo }}</td>
                                <td class="text-center text-vertical-align" width="12%">{{ laminate.Finish ? laminate.Finish : "N/A" }}</td>
                                <td class="text-vertical-align" width="10%">{{ laminate.Glossy ? "Yes" : "No" }}</td>
                                <td class="text-vertical-align" width="10%">{{ laminate.TexturedSurface ? "Yes" : "No" }}</td>
                                <td class="text-vertical-align text-center" width="11%">
 <a :href="CreateCatalogueRoute+'/'+laminate.Id" target="_self">
                                            <button type="button" class="btn btn-custom mr-7">Select</button>
                                        <button type="button" id="FullViewBtn" class="btn btn-custom mr-7" :data-laminateid="laminate.Id">FullView</button>
<button type="button" id="CompareLaminate" class="btn btn-block btn-custom btn-sm pull-left" @click.prevent="compareLaminate(laminate.Id)" :data-laminateid="laminate.Id">Compare</button>                                        
</a>
                                       
                                </td>
                                </tr>
                            </tbody>
                        </table> 
                    </div>
                    <div v-else class="pd-tp-14"> 
                        <div class="callout callout-info">
                            <p>No search results found.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> 
</template>
<script>
    // Child component
    export default {
        props: {
            "current-project": {
                type: String
            }
        },
        data() {
            return {
                SearchString: null,
                CdnUrl: '',
                SearchedLaminates : [],
CreateCatalogueRoute : this.$root.CreateCatalogueRoute,
                Laminates: this.$root.Laminates,
 brands : this.$root.brand,
            };
        },
        computed: {
        // Remove duplicate objects
            filteredLaminates() {
                $(".button-search").trigger("blur");
            return _.uniqBy(this.SearchedLaminates, function (e) {
                return e.DesignName || e.DesignNo || e.SearchTags;
            });
        }
    }, 
        created() {
            this.CdnUrl = this.$root.CdnUrl;
        },
        methods: {
             searchLaminates() {
            if (this.SearchString !== null) {
                this.SearchString = this.SearchString.trim();
            }
            if (!this.SearchString || 0 === this.SearchString.length) {
                return this.SearchedLaminates = [];
            }

            let result = _.filter(this.Laminates, function (laminate) {
                return ((laminate.DesignName.toLowerCase().indexOf(this.SearchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.DesignNo.toLowerCase().indexOf(this.SearchString.replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.SearchTags.replace(/\s\s+/g, ' ').toLowerCase().indexOf(this.SearchString.toLowerCase()) !== -1));
            }.bind(this));
           //If Search string matches
            if (result.length > 0) {
                this.SearchedLaminates = result;
this.$emit("imagePopup");
            } else {
               // If does not match split string into words and search match  
                let splittedString = this.SearchString.replace(/\s\s+/g, ' ').toLowerCase().split(" ");
                var filteredData = [], tempData = [];
                for (let i = 0; i < splittedString.length; i++) {
                    tempData = ((_.filter(this.laminates, function (laminate) {
                        return ((laminate.DesignName.toLowerCase().indexOf(splittedString[i].replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.DesignNo.toLowerCase().indexOf(splittedString[i].replace(/\s\s+/g, ' ').toLowerCase()) !== -1) || (laminate.SearchTags.replace(/\s\s+/g, ' ').toLowerCase().indexOf(splittedString[i].toLowerCase()) !== -1));
                    })));
                    filteredData = tempData.concat(filteredData);
                }
                this.SearchedLaminates = filteredData;
console.log(this.SearchedLaminates);
                this.$emit("imagePopup");
        }
},
 getBrandName($brandId) {
                let brand = _.find(this.brands, {'Id': $brandId});
                if (typeof brand !== "undefined") {
                    return brand.name;
                }
                return 'N/A';
            },
compareLaminate(id) {
                this.$emit("comparelaminate",id);
            }

        
    }
}
</script>