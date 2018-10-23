<template>
    <div class="modal fade" id="CompareLaminateModal" role="dialog" ref="CompareModal">
        <div class="modal-dialog modal-md">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" @click="$emit('close')">&times;</button>
                    <h4 class="modal-title">Compare Laminate</h4>
                </div>
                <div class="modal-body">                 
                    <table class="table table-bordered" id="ComparisonTable">
                        <thead style="border-top: 1px solid #f4f4f4;border-left: 1px solid #f4f4f4" class="bg-light-blue text-center">
                        <th class="text-center text-vertical-align">
                            {{currentLaminate.DesignNo}}
                        </th>
                        <th class="text-center text-vertical-align">                      
                            <div class="input-group input-group-sm ui-front" id="FirstSearchBox">
                                <input type="text" class="form-control search" placeholder="Search..." name="SearchLamBox1" id="SearchLamBox1">
                                <div class="input-group-btn">
                                    <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                                </div>
                            </div>
                        </th>
                         <th class="text-center text-vertical-align">                      
                            <div class="input-group input-group-sm ui-front" id="SecondSearchBox">
                                <input type="text" class="form-control search" placeholder="Search..." name="SearchBox2" id="SearchBox2">
                                <div class="input-group-btn">
                                    <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                                </div>
                            </div>
                        </th>
                         <th class="text-center text-vertical-align">                      
                            <div class="input-group input-group-sm ui-front" id="ThirdSearchBox">
                                <input type="text" class="form-control search" placeholder="Search..." name="SearchBox3" id="SearchBox3">
                                <div class="input-group-btn">
                                    <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                                </div>
                            </div>
                        </th>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center text-vertical-align">
                                    <div class="pd-8"> 
                                        <a href="javascript:void(0);">
                                            <img :src="CdnUrl+JSON.parse(currentLaminate.FullSheetmage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(currentLaminate.FullSheetmage)[0].UserFileName">
                                        </a>
                                    </div>
                                    <div class="pd-8">{{currentLaminate.TexturedSurface === "1" ? "Yes" : "No"}}</div>
                                    <div class="pd-8">{{ currentLaminate.Glossy === "1" ? "Yes" : "No" }}</div>
                                    <div class="pd-8">{{getBrand(currentLaminate.Brand)}}</div>
                                    <div class="pd-8">           
                                        <a :href="CreateCombRoute+'/'+currentLaminate.Id" target="_self">
                                            <button type="button" class="btn btn-primary">Select</button>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-center text-vertical-align">
                                    <div v-if="SearchBoxOneResult!== null">
                                        <div class="pd-8"> 
                                            <a href="javascript:void(0);">
                                                <img :src="CdnUrl+JSON.parse(SearchBoxOneResult.FullSheetmage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(SearchBoxOneResult.FullSheetmage)[0].UserFileName">
                                            </a>
                                        </div>
                                        <div class="pd-8">{{SearchBoxOneResult.TexturedSurface === "1" ? "Yes" : "No"}}</div>
                                        <div class="pd-8">{{SearchBoxOneResult.Glossy === "1" ? "Yes" : "No" }}</div>
                                        <div class="pd-8">{{getBrand(SearchBoxOneResult.Brand)}}</div>
                                        <div class="pd-8">           
                                            <a :href="CreateCombRoute+'/'+SearchBoxOneResult.Id" target="_self">
                                                <button type="button" class="btn btn-primary">Select</button>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center text-vertical-align">
                                    <div v-if="SearchBoxTwoResult!== null">
                                        <div class="pd-8"> 
                                            <a href="javascript:void(0);">
                                                <img :src="CdnUrl+JSON.parse(SearchBoxTwoResult.FullSheetmage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(SearchBoxTwoResult.FullSheetmage)[0].UserFileName">
                                            </a>
                                        </div>
                                        <div class="pd-8">{{SearchBoxTwoResult.TexturedSurface === "1" ? "Yes" : "No"}}</div>
                                        <div class="pd-8">{{SearchBoxTwoResult.Glossy === "1" ? "Yes" : "No" }}</div>
                                        <div class="pd-8">{{getBrand(SearchBoxTwoResult.Brand)}}</div>
                                        <div class="pd-8">           
                                            <a :href="CreateCombRoute+'/'+SearchBoxTwoResult.Id" target="_self">
                                                <button type="button" class="btn btn-primary">Select</button>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center text-vertical-align">
                                    <div v-if="SearchBoxThreeResult!== null">
                                        <div class="pd-8"> 
                                            <a href="javascript:void(0);">
                                                <img :src="CdnUrl+JSON.parse(SearchBoxThreeResult.FullSheetmage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(SearchBoxThreeResult.FullSheetmage)[0].UserFileName">
                                            </a>
                                        </div>
                                        <div class="pd-8">{{SearchBoxThreeResult.TexturedSurface === "1" ? "Yes" : "No"}}</div>
                                        <div class="pd-8">{{SearchBoxThreeResult.Glossy === "1" ? "Yes" : "No" }}</div>
                                        <div class="pd-8">{{getBrand(SearchBoxThreeResult.Brand)}}</div>
                                        <div class="pd-8">           
                                            <a :href="CreateCombRoute+'/'+SearchBoxThreeResult.Id" target="_self">
                                                <button type="button" class="btn btn-primary">Select</button>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-dismiss="modal" @click="$emit('close')">Close</button>
                </div>
            </div>
        </div>
    </div> 
</template>
<script>
    // Child component
    export default {
        props: {
            "current-laminate": {
                type: Object
            }
        },
        data() {
            return {
                CdnUrl: '',
                CreateCombRoute: '',
                laminates: this.$root.laminates,
                DesignNos: [],
                SearchBoxOneResult: null,
                SearchBoxTwoResult: null,
                SearchBoxThreeResult: null               
            };
        },
        created() {
            this.CdnUrl = this.$root.CdnUrl; // S3 Storage URL
            this.brands = this.$root.brands; // Get brands from system
            this.CreateCombRoute = this.$root.ShortlistLaminatesRoute; // Shortlist Laminates Route
        },
        mounted() {
            // Pluck Only Design Numbers from laminates for search
            this.DesignNos = this.pluckDesignNo();
            // Initialize autocomplete search
            $("#SearchLamBox1").autocomplete({
                appendTo: '#FirstSearchBox',
                source: this.DesignNos
            });
            $("#SearchBox2").autocomplete({
                appendTo: '#SecondSearchBox',
                source: this.DesignNos
            });
            $("#SearchBox3").autocomplete({
                appendTo: '#ThirdSearchBox',
                source: this.DesignNos
            });
            // Initialize autocomplete select event
            $("#SearchLamBox1").on('autocompleteselect', function (e, ui) {
                // Get laminate data
                this.SearchBoxOneResult = this.getLaminate(this.laminates, ui.item.value);
            }.bind(this));           
            $('#SearchBox2').on('autocompleteselect', function (e, ui) {
                // Get laminate data
                this.SearchBoxTwoResult = this.getLaminate(this.laminates, ui.item.value);
            }.bind(this));
            $('#SearchBox3').on('autocompleteselect', function (e, ui) {
                // Get laminate data
                this.SearchBoxThreeResult = this.getLaminate(this.laminates, ui.item.value);
            }.bind(this));
            // Clear search values on modal hide event
            $("#CompareLaminateModal").on("hidden.bs.modal", this.clearSearchValues());
        },
        methods: {
            clearSearchValues() {
               this.SearchBoxOneResult, this.SearchBoxTwoResult, this.SearchBoxThreeResult = null; 
            },
            getLaminate(laminates, designNo) {
                if(laminates.length > 0) {
                    let laminate = _.find(laminates, ["DesignNo", designNo]);
                    if(laminate !== "undefined") {
                        return laminate;
                    }
                }
                return null;
            },
            getBrand($brandId) {
                let brand = _.find(this.brands, {'Id': $brandId});
                if (typeof brand !== "undefined") {
                    return brand.Name;
                }
                return 'N/A';
            },
            pluckDesignNo() {
                var laminates = [];
                if(this.laminates.length > 0) {
                    for(let lam = 0; lam < this.laminates.length; lam++) {
                        laminates.push(this.laminates[lam].DesignNo);
                    }
                }
                return laminates;
            }
        }
    }
</script>