<div class="pd-8 box-header">
    <div class="image-link"> 
        <a :href="CdnUrl+JSON.parse(ComparisonLaminate.FullSheetImage)[0].Path">
            <img :src="CdnUrl+JSON.parse(ComparisonLaminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" :title="JSON.parse(ComparisonLaminate.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(ComparisonLaminate.FullSheetImage)">
        </a>
    </div>
</div>
<div class="pd-8">@{{ ComparisonLaminate.DesignNo}}</div>
<div class="pd-8"><span v-html="getBrand(ComparisonLaminate.Brand)"></span></div>
<div class="pd-8"><span v-html="(ComparisonLaminate.SubBrand) ? ComparisonLaminate.SubBrand : '<small>N/A</small>'"></span></div>
<div class="pd-8"><span v-html="(ComparisonLaminate.CategoryName) ? ComparisonLaminate.CategoryName : '<small>N/A</small>'"></span></div>
<div class="pd-8"><span v-html="(ComparisonLaminate.SurfaceRange) ? ComparisonLaminate.SurfaceRange : '<small>N/A</small>'"></span></div>
<div class="pd-8"><span v-html="(ComparisonLaminate.SurfaceFinish) ? ComparisonLaminate.SurfaceFinish : '<small>N/A</small>'"></span></div>
<div class="pd-8">@{{(ComparisonLaminate.TexturedSurface === '1' ? "Yes" : "No")}}</div>
<div class="pd-8">@{{(ComparisonLaminate.Glossy === '1') ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.Edgeband === '1') ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.ScratchResistant != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.ColorFast != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.HeatResistant != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.StainResistant != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.GlossLevel != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.ThickTolerance != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.SurfaceWaterRes != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.BoilingWaterResistant != null) ? "Yes" : "No"}}</div>
<div class="pd-8">@{{(ComparisonLaminate.HighTemperatureResistant != null) ? "Yes" : "No"}}</div>
<div class="pd-8">
    <span v-html="(ComparisonLaminate.Price) ? ComparisonLaminate.Price : '<small>N/A</small>'"></span>
</div>
<div class="pd-8">
    <div v-if="ComparisonLaminate.SampleImage">
        <div class="image-link"> 
            <a :href="CdnUrl+JSON.parse(ComparisonLaminate.SampleImage)[0].Path">
                <img :src="CdnUrl+JSON.parse(ComparisonLaminate.SampleImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(ComparisonLaminate.SampleImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(ComparisonLaminate.SampleImage)">
            </a>
        </div>
    </div>
    <div class="no-sample-img" v-else><small>N/A</small></div>
</div>
<div class="pd-8">
    <button class="btn btn-custom mr-rt-8" id="SelectedLaminate" :data-laminate-id="ComparisonLaminate.LaminateId" v-if="!ComparisonLaminate.Active">
        <i class="fa fa-fw fa-plus-square" aria-hidden="true"></i> Shortlist Laminate                        
    </button>
    <button class="btn btn-custom mr-rt-8 mr-bt-8 mr-lt-8" :data-laminate-id="ComparisonLaminate.LaminateId" v-else style="width:11em;">
        <i class="fa fa-check check-icon" aria-hidden="true"></i> Shortlisted
    </button>
    <button class="btn btn-custom mr-bt-8" id="RemoveLaminate" :data-laminate-id="ComparisonLaminate.LaminateId" v-if="ComparisonLaminate.Active">
        <i class="fa fa-fw fa-remove" aria-hidden="true"></i> Remove Laminate
    </button>
</div>