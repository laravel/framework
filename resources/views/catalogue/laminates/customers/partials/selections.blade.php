<div class="box-header with-border">
    <div v-if='TempCombinationsArray.length > 0'>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed" id="ShortlistedCombinations">     
                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                    <tr>
                        <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                        <th class="text-center text-vertical-align" width="8%">Room</th>
                        <th class="text-center text-vertical-align pd-rt-17" width="2%">Selection Status</th>
                        <th class="text-center text-vertical-align pd-10" width="8%">Image</th> 
                        <th class="text-center text-vertical-align" width="8%">Brand</th>
                        <th class="text-center text-vertical-align" width="9%">Sub Brand</th>
                        <th class="text-center text-vertical-align" width="8%">Design Name</th>
                        <th class="text-center text-vertical-align" width="8%">Design Number</th>
                        <th class="text-center text-vertical-align" width="8%">Type</th>
                        <th class="text-center text-vertical-align pd-9" width="1%">Surface Finish</th>
                        <th class="text-center text-vertical-align pd-9" width="6%">Glossiness</th>
                        <th class="text-center text-vertical-align pd-9" width="7%">Edgeband availibility</th>
                        <th class="text-center text-vertical-align pd-9" width="2%">Compare / View</th>
                        <th class="text-center text-vertical-align pd-rt-1" width="8%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="laminate in TempCombinationsArray" :class="laminate.BackgroundColor">
                        <td class="text-center text-vertical-align">@{{laminate.CatalogueId}}</td>
                        <td class="text-center text-vertical-align no-room-text" v-html="!_.isNull(laminate.Room) ? laminate.Room : '<small><i>No Room Selected</i></small>'"></td>
                        <td class="text-center text-vertical-align">
                            <span :class="'label label-' + StatusLabels[laminate.Status]">@{{getStatus(laminate.Status, laminate.RoleSlug)}}</span>
                        </td>
                        <td class="text-center text-vertical-align">
                            <div class="image-link"> 
                                <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                    <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="full-image cursor-zoom-in" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName"  @click.prevent="initializeFSheetThumbnailsPopup(laminate.FullSheetImage)">
                                </a>
                            </div>
                        </td>
                        <td class="text-center text-vertical-align" v-html="getBrand(laminate.Brand)"></td>
                        <td class="text-center text-vertical-align" v-html="getSubBrand(laminate.SubBrand)"></td>
                        <td class="text-center text-vertical-align">@{{laminate.DesignName}}</td>   
                        <td class="text-center text-vertical-align">
                            @{{laminate.DesignNo}}
                        </td>
                        <td class="text-center text-vertical-align"><span v-html="getCategory(laminate.SurfaceCategory)"></span></td>
                        <td class="text-center text-vertical-align"><span v-html="getFinish(laminate.SurfaceFinish)"></span></td>
                        <td class="text-center text-vertical-align">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                        <td class="text-center text-vertical-align">@{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                        <td class="text-center text-vertical-align">
                            <a :href="CompareLaminatesRoute + '/'+ ProjectId + '/' + laminate.LaminateId" class="cursor-pointer" id="CompareLaminate" data-toggle="tooltip" title="Compare Laminates">
                                <i class="fa fa-fw fa-search text-black" aria-hidden="true"></i>
                            </a>
                            <a 
                                class="cursor-pointer full-view-popup" 
                                data-toggle="tooltip" 
                                title="View Laminate Details"
                                id="LaminateView"
                                data-api-end-point="{{ route('catalogues.laminate.get', ['id' => '']) }}"
                                @click.prevent="openFullViewPopup(laminate.LaminateId)">
                                <i class="fa fa-eye text-black"></i>
                            </a>
                        </td>                                             
                        <td class="text-center text-vertical-align">                                              
                            <a :href="'/catalogues/laminates/selection/'+laminate.CatalgId+'/edit'" target="_self" data-toggle="tooltip" title="Edit Room or Combination for the Selection" v-if="laminate.Status < 2">
                                <i class="fa fa-fw fa-edit text-black"></i>
                            </a>
                            <a :href="FinalizeCombinationRoute+'/'+laminate.CatalgId" target="_self" data-toggle="tooltip" title="Finalize a shortlisted selection and submit to HECHPE Designer for Review" v-if="laminate.Status < 2" style="margin-left:-3px;">
                                <i class="fa fa-fw fa-check-circle text-black" aria-hidden="true"></i>
                            </a>
                            <a href='javascript:void(0)' class="view-notes" data-toggle="tooltip" title="View Notes" v-if="!_.isNull(laminate.Notes)" @click.prevent="openNotesPopup(laminate.CatalgId)">
                                <i class="ion ion-clipboard text-black"></i>
                            </a>
                            <a href='javascript:void(0)' :data-combination-id="laminate.CatalgId" id='DeleteCombination' data-toggle="tooltip" title="Delete Selection"  v-if="laminate.Status < 2">
                                <i class="fa fa-fw fa-trash text-black" aria-hidden="true"></i>
                            </a>
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