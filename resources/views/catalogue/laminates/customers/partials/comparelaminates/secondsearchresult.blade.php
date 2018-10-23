<div v-if="!_.isNull(SearchBoxTwoResult)">
    <div class="pd-8">
        <div class="image-link"> 
            <a :href="CdnUrl+JSON.parse(SearchBoxTwoResult.FullSheetImage)[0].Path">
                <img :src="CdnUrl+JSON.parse(SearchBoxTwoResult.FullSheetImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxTwoResult.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxTwoResult.FullSheetImage)">
            </a>
        </div>
    </div>
    <div class="pd-8">@{{ SearchBoxTwoResult.DesignNo}}</div>
    <div class="pd-8"><span v-html="getBrand(SearchBoxTwoResult.Brand)"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxTwoResult.SubBrand) ? SearchBoxTwoResult.SubBrand : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxTwoResult.CategoryName) ? SearchBoxTwoResult.CategoryName : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxTwoResult.SurfaceRange) ? SearchBoxTwoResult.SurfaceRange : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxTwoResult.SurfaceFinish) ? SearchBoxTwoResult.SurfaceFinish : '<small>N/A</small>'"></span></div>
    <div class="pd-8">@{{(SearchBoxTwoResult.TexturedSurface === '1' ? "Yes" : "No")}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.Glossy === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.Edgeband === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.ScratchResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.ColorFast != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.HeatResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.StainResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.GlossLevel != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.ThickTolerance != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.SurfaceWaterRes != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.BoilingWaterResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxTwoResult.HighTemperatureResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">
        <span v-html="(SearchBoxTwoResult.Price) ? SearchBoxTwoResult.Price : '<small>N/A</small>'"></span>
    </div>
    <div class="pd-8">
        <div v-if="SearchBoxTwoResult.SampleImage">
            <div class="image-link"> 
                <a :href="CdnUrl+JSON.parse(SearchBoxTwoResult.SampleImage)[0].Path">
                    <img :src="CdnUrl+JSON.parse(SearchBoxTwoResult.SampleImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxTwoResult.SampleImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxTwoResult.SampleImage)">
                </a>
            </div>
        </div>
        <div class="no-sample-img" v-else><small>N/A</small></div>
    </div>
    <div class="pd-8 box-header">
        <button 
            class="btn btn-custom mr-rt-8 mr-bt-8 mr-lt-8 shortlist-laminate" 
            id="SecondShortListBtn" 
            :data-laminate-id="SearchBoxTwoResult.LaminateId"
            style="width: 11em;">
            <i 
                :class="(isSecondShortlisted == 'Shortlist') ? 'fa fa-fw fa-plus-square' : 'fa fa-check check-icon'" 
                aria-hidden="true">
            </i> 
            @{{isSecondShortlisted == 'Shortlist' ? isSecondShortlisted+' laminate': isSecondShortlisted}}                               
        </button>
        <button 
            class="btn btn-custom remove-laminate mr-bt-8" 
            id="SecondRemoveBtn" 
            :data-laminate-id="SearchBoxTwoResult.LaminateId">
            <i class="fa fa-fw fa-remove" aria-hidden="true"></i> Remove Laminate
        </button>
    </div>
</div>