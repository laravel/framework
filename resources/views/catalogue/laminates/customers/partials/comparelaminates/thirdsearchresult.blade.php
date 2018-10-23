<div v-if="!_.isNull(SearchBoxThreeResult)">
    <div class="pd-8">
        <div class="image-link"> 
            <a :href="CdnUrl+JSON.parse(SearchBoxThreeResult.FullSheetImage)[0].Path">
                <img :src="CdnUrl+JSON.parse(SearchBoxThreeResult.FullSheetImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxThreeResult.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxThreeResult.FullSheetImage)">
            </a>
        </div>
    </div>
    <div class="pd-8">@{{ SearchBoxThreeResult.DesignNo}}</div>
    <div class="pd-8"><span v-html="getBrand(SearchBoxThreeResult.Brand)"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxThreeResult.SubBrand) ? SearchBoxThreeResult.SubBrand : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxThreeResult.CategoryName) ? SearchBoxThreeResult.CategoryName : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxThreeResult.SurfaceRange) ? SearchBoxThreeResult.SurfaceRange : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxThreeResult.SurfaceFinish) ? SearchBoxThreeResult.SurfaceFinish : '<small>N/A</small>'"></span></div>
    <div class="pd-8">@{{(SearchBoxThreeResult.TexturedSurface === '1' ? "Yes" : "No")}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.Glossy === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.Edgeband === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.ScratchResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.ColorFast != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.HeatResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.StainResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.GlossLevel != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.ThickTolerance != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.SurfaceWaterRes != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.BoilingWaterResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxThreeResult.HighTemperatureResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8"><span v-html="(SearchBoxThreeResult.Price) ? SearchBoxThreeResult.Price : '<small>N/A</small>'"></span></div>
    <div class="pd-8">
        <div v-if="SearchBoxThreeResult.SampleImage">
            <div class="image-link"> 
                <a :href="CdnUrl+JSON.parse(SearchBoxThreeResult.SampleImage)[0].Path">
                    <img :src="CdnUrl+JSON.parse(SearchBoxThreeResult.SampleImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxThreeResult.SampleImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxThreeResult.SampleImage)">
                </a>
            </div>
        </div>
        <div class="no-sample-img" v-else><small>N/A</small></div>
    </div>
    <div class="pd-8 box-header">
        <button 
            class="btn btn-custom mr-rt-8 mr-bt-8 mr-lt-8 shortlist-laminate" 
            id="ThirdShortListBtn" 
            :data-laminate-id="SearchBoxThreeResult.LaminateId"
            style="width: 11em;">
            <i 
                :class="(isThirdShortlisted == 'Shortlist') ? 'fa fa-fw fa-plus-square' : 'fa fa-check check-icon'" 
                aria-hidden="true">
            </i> 
            @{{isThirdShortlisted == 'Shortlist' ? isThirdShortlisted+' laminate': isThirdShortlisted}}                       
        </button>
        <button 
            class="btn btn-custom remove-laminate mr-bt-8" 
            id="ThirdRemoveBtn" 
            :data-laminate-id="SearchBoxThreeResult.LaminateId">
            <i class="fa fa-fw fa-remove" aria-hidden="true"></i> Remove Laminate
        </button>
    </div>
</div>