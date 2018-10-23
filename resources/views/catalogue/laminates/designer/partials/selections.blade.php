<div class="box-header with-border">
    <div v-if='TempCombinationsArray.length > 0'>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed" id="ShortlistedCombinations">     
                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                    <tr>
                    <th class="text-center text-vertical-align pd-10 sno-col" width="2%">#</th>
                    <th class="text-center text-vertical-align room-col" width="8%">Room</th>
                    <th class="text-center text-vertical-align selection-col" width="8%">Selection Status</th>
                    <th class="text-center text-vertical-align" width="8%">Image</th>
                    <th class="text-center text-vertical-align" width="8%">Brand</th>
                    <th class="text-center text-vertical-align" width="8%">Sub Brand</th>
                    <th class="text-center text-vertical-align" width="10%">Design Name</th>
                    <th class="text-center text-vertical-align" width="8%">Design Number</th>
                    <th class="text-center text-vertical-align" width="8%">Type</th>
                    <th class="text-center text-vertical-align" width="7%">Surface Finish</th>
                    <th class="text-center text-vertical-align" width="6%">Glossiness</th>
                    <th class="text-center text-vertical-align" width="9%">Edgeband availibility</th>
                    <th class="text-center text-vertical-align" width="2%">Compare</th>
                    <th class="text-center text-vertical-align action-col" width="8%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="laminate in TempCombinationsArray" :class="laminate.BackgroundColor">
                    <td class="text-center text-vertical-align">@{{laminate.CatalogueId}}</td>
                    <td class="text-center text-vertical-align" v-html="!_.isNull(laminate.Room) ? laminate.Room : '<small><i>No Room Selected</i></small>'"></td>
                    <td class="text-center text-vertical-align">
                    <span :class="'label label-' + StatusLabels[laminate.Status]">@{{getStatus(laminate.Status, laminate.RoleSlug)}}</span>
                    </td>
                    <td class="text-center text-vertical-align">
                        <div class="image-link"> 
                            <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="full-image" data-toggle="tooltip" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" @click.prevent="initializeGallery(laminate.FullSheetImage)">
                            </a>
                        </div>
                    </td>
                    <td class="text-center text-vertical-align"><span v-html="getBrand(laminate.Brand)"></span></td>
                    <td class="text-center text-vertical-align"><span v-html="getSubBrand(laminate.SubBrand)"></span></td>
                    <td class="text-center text-vertical-align">
                        <a 
                            class="cursor-pointer ViewLaminate" 
                            data-toggle="tooltip" 
                            title="View Laminate Details" 
                            @click.prevent="openFullViewPopup(laminate.LaminateId)"
                            data-toggle="tooltip" 
                            title="View Laminate Details"
                            data-api-end-point="{{ route('catalogue.laminate.get', ["id" => '']) }}"
                            >
                            @{{laminate.DesignName}}
                        </a>
                    </td>
                    <td class="text-center text-vertical-align">
                        @{{laminate.DesignNo}}
                    </td>
                    <td class="text-center text-vertical-align"><span v-html="getCategory(laminate.SurfaceCategory)"></span></td>
                    <td class="text-center text-vertical-align"><span v-html="getFinish(laminate.SurfaceFinish)"></span></td>
                    <td class="text-center text-vertical-align">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                    <td class="text-center text-vertical-align">@{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                    <td class="text-center text-vertical-align">
                        @if(!auth()->user()->isSales())
                        <a :href="CompareCatalogueRoute + '/'+ laminate.LaminateId + '/' + ProjectId"  data-toggle="tooltip" title="Compare Laminates">
                            <i class="fa fa-fw fa-search"></i>
                        </a>
                        <a 
                            class="cursor-pointer ViewLaminate" 
                            data-toggle="tooltip" 
                            title="View Laminate Details" 
                            @click.prevent="openFullViewPopup(laminate.LaminateId)"
                            data-toggle="tooltip" 
                            title="View Laminate Details"
                            data-api-end-point="{{ route('catalogue.laminate.get', ["id" => '']) }}"
                            >
                            <i class="fa fa-eye text-black" aria-hidden="true"></i>
                        </a>
                        @else
                        N/A
                        @endif
                    </td>
                    <td class="text-center text-vertical-align">
                        @if(!auth()->user()->isSales())
                        <a :href="EditCatalogueRoute+'/'+laminate.CatalgId" target="_self" class="cursor-pointer"  data-toggle="tooltip" title="Edit Selection" v-if="laminate.Status != 4">
                            <i class="fa fa-fw fa-pencil-square-o"></i>
                        </a>
                        <a :href="FinalizeCatalogueRoute+'/'+laminate.CatalgId" target="_self" class="cursor-pointer"  data-toggle="tooltip" title="Finalize Selection" v-if="laminate.Status != 4" style="margin-left:-3px;">
                            <i class="fa fa-fw fa-check-circle"></i>
                        </a>
                        @endif
                        <a href='javascript:void(0)' class="view-notes" data-toggle="tooltip" title="View Notes" v-if="!_.isNull(laminate.Notes)" @click.prevent="openNotesPopup(laminate.CatalgId)">
                            <i class="ion ion-clipboard text-black"></i>
                        </a>
                        @if(!auth()->user()->isSales())
                        <a href='javascript:void(0)' :data-combination-id="laminate.CatalgId" class='cursor-pointer' id='DeleteCombination' data-toggle="tooltip" title="Delete Selection" v-if="laminate.Status != 4">
                            <i class="fa fa-fw fa-trash"></i>
                        </a>
                        @endif
                    </td>
                    </tr>
                </tbody>
            </table> 
        </div>
    </div>
    <div v-else> 
        <div class="callout callout-info">
            <p><i class="fa fa-fw fa-info-circle"></i> Laminates selections are not available for selected project. Would you like to create <a @click.prevent="showSelectLamSearch" class="cursor-pointer">New Selection?</a></p>
        </div>
    </div>
</div>