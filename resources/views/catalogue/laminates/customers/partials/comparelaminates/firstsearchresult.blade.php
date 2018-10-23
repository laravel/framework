<div v-if="!_.isNull(SearchBoxOneResult)">
    <div class="pd-8">
        <div class="image-link"> 
            <a :href="CdnUrl+JSON.parse(SearchBoxOneResult.FullSheetImage)[0].Path">
                <img :src="CdnUrl+JSON.parse(SearchBoxOneResult.FullSheetImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxOneResult.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxOneResult.FullSheetImage)">
            </a>
        </div>
    </div>
    <div class="pd-8">@{{ SearchBoxOneResult.DesignNo}}</div>
    <div class="pd-8"><span v-html="SearchBoxOneResult.BrandName"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxOneResult.SubBrand) ? SearchBoxOneResult.SubBrand : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxOneResult.CategoryName) ? SearchBoxOneResult.CategoryName : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxOneResult.SurfaceRange) ? SearchBoxOneResult.SurfaceRange : '<small>N/A</small>'"></span></div>
    <div class="pd-8"><span v-html="(SearchBoxOneResult.SurfaceFinish) ? SearchBoxOneResult.SurfaceFinish : '<small>N/A</small>'"></span></div>
    <div class="pd-8">@{{(SearchBoxOneResult.TexturedSurface === '1' ? "Yes" : "No")}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.Glossy === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.Edgeband === '1') ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.ScratchResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.ColorFast != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.HeatResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.StainResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.GlossLevel != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.ThickTolerance != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.SurfaceWaterRes != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.BoilingWaterResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8">@{{(SearchBoxOneResult.HighTemperatureResistant != null) ? "Yes" : "No"}}</div>
    <div class="pd-8"><span v-html="(SearchBoxOneResult.Price) ? SearchBoxOneResult.Price : '<small>N/A</small>'"></span></div>
    <div class="pd-8">
        <div v-if="SearchBoxOneResult.SampleImage">
            <div class="image-link"> 
                <a :href="CdnUrl+JSON.parse(SearchBoxOneResult.SampleImage)[0].Path">
                    <img :src="CdnUrl+JSON.parse(SearchBoxOneResult.SampleImage)[0].Path" alt="Sample Laminate" class="note-thumbnail cursor-zoom-in" data-toggle="tooltip" :title="JSON.parse(SearchBoxOneResult.SampleImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(SearchBoxOneResult.SampleImage)">
                </a>
            </div>
        </div>
        <div class="no-sample-img" v-else><small>N/A</small></div>
    </div>
    <div class="pd-8 box-header">
        <button 
            class="btn btn-custom mr-rt-8 mr-bt-8 mr-lt-8 shortlist-laminate" 
            id="FirstShortListBtn" 
            :data-laminate-id="SearchBoxOneResult.LaminateId" 
            style="width: 11em;">
            <i 
                :class="(isFirstShortlisted == 'Shortlist') ? 'fa fa-fw fa-plus-square' : 'fa fa-check check-icon'" 
                aria-hidden="true">
            </i> 
            @{{isFirstShortlisted == 'Shortlist' ? isFirstShortlisted+' laminate': isFirstShortlisted}}                      
        </button>
        <button 
            class="btn btn-custom remove-laminate mr-bt-8" 
            id="FirstRemoveBtn" 
            :data-laminate-id="SearchBoxOneResult.LaminateId">
            <i class="fa fa-fw fa-remove" aria-hidden="true"></i> Remove Laminate
        </button>
    </div>
</div>