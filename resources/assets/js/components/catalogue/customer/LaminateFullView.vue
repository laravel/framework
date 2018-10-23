<template>
    <div class="modal fade" id="FullViewModal" role="dialog">
        <div class="modal-dialog modal-lg">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Full View</h4>
                </div>
                <div class="modal-body">                 
                    <h4 class="no-text-transform mr-tp-0 full-view-heading">{{getBrand(currentLaminate.Brand)}} <span>|</span> {{currentLaminate.DesignName}} <span>|</span> {{currentLaminate.DesignNo}}</h4>
                    <div class="row">
                        <div class="col-xs-2">
                            <div class="form-group">
                                <a href="javascript:void(0);">
                                    <img :src="CdnUrl+JSON.parse(currentLaminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="laminate-full-image" :title="JSON.parse(currentLaminate.FullSheetImage)[0].UserFileName">
                                </a>
                            </div>
                        </div>
                        <div class="col-xs-10">
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Sub Brand</label>   
                                        <p>{{currentLaminate.SubBrand}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Category</label> 
                                       <p v-html="(currentLaminate.CategoryName) ? currentLaminate.CategoryName : '<small>N/A</small>'"></p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Range</label> 
                                       <p v-html="(currentLaminate.SurfaceRange) ? currentLaminate.SurfaceRange : '<small>N/A</small>'"></p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Surface Finish</label> 
                                       <p v-html="(currentLaminate.SurfaceFinish) ? currentLaminate.SurfaceFinish : '<small>N/A</small>'"></p>
                                    </div> 
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Textured Surface</label> 
                                       <p>{{(currentLaminate.TexturedSurface === '1' ? "Yes" : "No")}}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Glossiness</label> 
                                       <p>{{(currentLaminate.Glossy === '1') ? "Yes" : "No"}}</p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label>Swatch Image
                                            <a :href="this.$root.SwatchImageZipDownloadRoute+'/'+currentLaminate.LaminateId" data-toggle="tooltip" title="Download all Swatch images" v-if="currentLaminate.SampleImage">
                                                <i class="fa fa-fw fa-download"></i>
                                            </a>
                                        </label> 
                                        <p v-if="currentLaminate.SampleImage">
                                            <span class="element-container">
                                                <a href="javascript:void(0);">
                                                    <img :src="CdnUrl+JSON.parse(currentLaminate.SampleImage)[0].Path" alt="Swatch Image" class="note-thumbnail overlay-img" :title="JSON.parse(currentLaminate.SampleImage)[0].UserFileName">
                                                </a>
                                                <div title="Download" class="middle cursor-pointer">
                                                  <a :href="this.$root.SwatchImageDownloadRoute+'/'+currentLaminate.LaminateId">
                                                    <i class="fa fa-fw fa-download download-icon"></i>
                                                  </a>
                                                </div>
                                            </span>
                                        </p>
                                        <p v-else><small>N/A</small></p>
                                    </div> 
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                       <label>Usage Image</label> 
                                       <p v-if="currentLaminate.UsageImage">
                                            <a href="javascript:void(0);">
                                                <img :src="CdnUrl+JSON.parse(currentLaminate.UsageImage)[0].Path" alt="Sample Laminate" class="note-thumbnail" :title="JSON.parse(currentLaminate.UsageImage)[0].UserFileName">
                                            </a>
                                        </p>
                                        <p v-else><small>N/A</small></p>
                                    </div> 
                                </div>
                            </div>
                            <h4 class="text-primary">EdgeBand Availability: <span class="EdgebandStatus">{{(currentLaminate.Edgeband === '1') ? "Yes" : "No"}}</span></h4>
                            <div class="row" v-if= "currentLaminate.Edgeband === '1'">
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Exact Match</label>
                                        <p v-html="getEdgeBand(currentLaminate.ExactMatch)"></p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Nearest Match</label>
                                        <p v-html="getEdgeBand(currentLaminate.NearestMatch)"></p>    
                                    </div>
                                </div> 
                                <div class="col-md-3 col-xs-3">
                                    <div class="form-group">
                                        <label for="">Contrast Match</label>
                                        <p v-html="getEdgeBand(currentLaminate.ContrastMatch)"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Scratch Resistant</label>   
                                        <p>{{(currentLaminate.ScratchResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                     <div class="form-group">
                                         <label for="">Color Fast</label>   
                                         <p>{{currentLaminate.ColorFast != null ? "Yes" : "No"}}</p>
                                     </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Heat Resistant</label>   
                                        <p>{{currentLaminate.HeatResistant != null ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Stain Resistant</label>   
                                        <p>{{currentLaminate.StainResistant != null ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Superior Gloss Level</label>   
                                        <p>{{(currentLaminate.GlossLevel != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Thickness Tolerance</label>   
                                        <p>{{(currentLaminate.ThickTolerance != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Surface Water Resistance</label>   
                                        <p>{{(currentLaminate.SurfaceWaterRes != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Boiling Water Resistance</label>   
                                        <p>{{(currentLaminate.BoilingWaterResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Hight Temperature Resistance</label>   
                                        <p>{{(currentLaminate.HighTemperatureResistant != null) ? "Yes" : "No"}}</p>
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Suggested Pairing</label>   
                                        <p v-html="getSuggPairing(currentLaminate.SuggestedPairing)"></p>
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label for="">Suggested Usage</label>   
                                        <p v-html="getSuggUsage(currentLaminate.SuggestedUsage)"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">No of Sheets available</label> 
                                        <p v-html="(currentLaminate.Sheets) ? currentLaminate.Sheets : '<small>N/A</small>'"></p>  
                                    </div>
                                </div>
                                <div class="col-xs-3">
                                    <div class="form-group">
                                        <label for="">Status as on Date</label> 
                                        <p v-html="(currentLaminate.Status) ? currentLaminate.Status : '<small>N/A</small>'"></p>  
                                    </div>
                                </div>
                            </div>
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
            "current-laminate": {
                type: Object
            }
        },
        data() {
            return {
                CdnUrl: '',
                brands: [],
                laminates: []
            };
        },
        created() {
            this.CdnUrl = this.$root.CdnUrl;
            this.brands = this.$root.brands;
            this.laminates = this.$root.laminates;
            this.edgeBands = this.$root.edgeBands;
        },
        methods: {
            getBrand($brandId) {
                let brand = _.find(this.brands, {'Id': $brandId});
                if (typeof brand !== "undefined") {
                    return brand.Name;
                }
                return 'N/A';
            },
            // Get Suggested laminates pairing
            getSuggPairing(pairingJSON) {
                let suggNames = "<small>N/A</small>";
                if(pairingJSON) {
                    let pairings = JSON.parse(pairingJSON);
                    suggNames = "";
                    if (pairings.length > 0) {
                        for (let i = 0; i < pairings.length; i++) {
                            for (let j = 0; j < this.laminates.length; j++) {
                                if (this.laminates[j].LaminateId === pairings[i].Id) {
                                    let laminate = this.laminates[j];
                                    if (laminate !== "undefined") {
                                        suggNames = (suggNames != "") ?  (suggNames + ", " + laminate.DesignName) : laminate.DesignName;
                                    }
                                }
                            }
                        }
                    }
                }
                return suggNames;
            },
            getSuggUsage(usageJSON) {
                let usages = JSON.parse(usageJSON); 
                let usagesList = "<small>N/A</small>";
                if(usages.length > 0) {
                    usagesList = "";
                    for(let usage = 0; usage < usages.length; usage++) {
                        usagesList = usagesList + "<p><strong>" + (usage + 1) + ".</strong> " + usages[usage] + "</p>";
                    }
                }
                return usagesList;
            },
            getEdgeBand(pairingJSON) {
                let matchNames = "<small>N/A</small>";
                if(pairingJSON) {
                    let pairings = JSON.parse(pairingJSON);
                    matchNames = "";
                    if (pairings.length > 0) {
                        for (let i = 0; i < pairings.length; i++) {
                           let edgeband = _.find(this.edgeBands, {'Id': pairings[i].Id});
                            if (edgeband !== "undefined") {
                                matchNames = (matchNames != "") ?  (matchNames + ", " + edgeband.Name) : edgeband.Name;
                            }
                        }
                    }
                }
                return matchNames;
            },
        }
    }
</script>