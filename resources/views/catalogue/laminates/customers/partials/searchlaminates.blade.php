<div class="box-header with-border pd-bt-0" :class="{hidden: ShowSearchLamBody}">
     <h4>Select Laminate</h4>
    <div class="row">
        <div class="col-md-5 col-sm-6 col-xs-12" id="SearchLaminatesBox">
            <input type="text" 
                class="form-control search" 
                placeholder="Type Design Name, Number, Brand..." 
                ref="SearchLaminates" 
                onblur="this.placeholder = 'Type Design Name, Number, Brand...'" 
                name="SearchLaminates" 
                v-model="SearchString" 
                id="SearchLaminates" 
                @keyup.enter="searchLaminates"
                data-api-end-point="{{ route('catalogues.laminates.search.compare') }}">
        </div>
        <div class="col-md-7 col-sm-6 col-xs-12 search-btn">
            <button 
                class="btn btn-primary button-search pd-rt-20 pd-lt-20" 
                @click.prevent="searchLaminates"
                id="SearchLamsBtn"
                data-api-end-point="{{ route('catalogues.laminates.search') }}"
                >Search
            </button>
            <button class="btn btn-custom button-search pd-rt-20 pd-lt-20" @click.prevent="ShowSearchLamBody=true; SearchString=''; laminates=[];">Close</button>
        </div>
    </div>
    <div class="table-responsive pd-tp-14" v-if="fileteredLaminates.length > 0">
        <table class="table table-bordered table-striped" id="ShortlistSelectionTable">
            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                <tr>
                    <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                    <th class="text-center text-vertical-align pd-10" width="8%">Image</th> 
                    <th class="text-center text-vertical-align" width="10%">Brand</th>
                    <th class="text-center text-vertical-align" width="10%">Design Name</th>
                    <th class="text-center text-vertical-align" width="10%">Design Number</th>
                    <th class="text-center text-vertical-align" width="4%">Type</th>
                    <th class="text-center text-vertical-align" width="9%">Surface Finish</th>
                    <th class="text-center text-vertical-align" width="6%">Glossiness</th>
                    <th class="text-center text-vertical-align" width="9%">Edgeband availibility</th>
                    <th class="text-center text-vertical-align" width="14%">Room</th>
                    <th class="text-center text-vertical-align" width="10%">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(laminate, index) in fileteredLaminates">
                    <td class="text-center text-vertical-align" width="2%">@{{ index+1 }}</td>
                    <td class="text-center text-vertical-align" width="8%"> 
                        <div class="image-link">
                            <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="full-image cursor-zoom-in" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" @click.prevent="initializeFSheetThumbnailsPopup(laminate.FullSheetImage)">
                            </a>
                        </div>
                    </td>
                    <td class="text-center text-vertical-align" width="10%">@{{laminate.BrandName}}</td>
                    <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignName }}</td>
                    <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignNo }}</td>
                    <td class="text-center text-vertical-align" width="8%" v-html="(laminate.CategoryName) ? laminate.CategoryName : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="9%" v-html="(laminate.SurfaceFinish) ? laminate.SurfaceFinish : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="6%">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                    <td class="text-center text-vertical-align" width="9%">@{{laminate.Edgeband === "1" ? "Yes" : "No"}}</td>
                    <td class="text-center text-vertical-align" width="14%">
                        <select name="Room" id="Room" class="form-control room-area" @change="getRoomId($event)">
                            <option value="">Select Room</option>
                            <option v-for="room in YetToFinalizedRooms" :value="room.Id">@{{room.Name}}</option>
                        </select>
                    </td>
                    <td class="text-vertical-align text-center" width="10%">                             
                        <a 
                            href="javascript:void(0)"
                            :data-laminateid="laminate.LaminateId" 
                            target="_self" 
                            class="cursor-pointer" 
                            data-toggle="tooltip" 
                            title="Add to Shortlist" 
                            id="ShrortListLaminateLink"
                        >
                        <i class="fa fa-fw fa-plus-square text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                            href="javascript:void(0)" 
                           :data-laminateid="laminate.LaminateId" 
                           target="_self" 
                           class="cursor-pointer" 
                           data-toggle="tooltip" 
                           title="Add to Shortlist with Room / Combination" 
                           id="AddToShortlist"
                        >
                        <i class="fa fa-fw fa-cart-plus text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                            class="cursor-pointer full-view-popup" 
                            data-toggle="tooltip" 
                            title="View Laminate Details" 
                            @click.prevent="openFullViewPopup(laminate.LaminateId)"
                            data-api-end-point="{{ route('catalogues.laminate.get', ['id' => '']) }}"
                        >
                        <i class="fa fa-eye text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                            :href="CompareLaminatesRoute + '/'+ ProjectId + '/' + laminate.LaminateId" 
                            class="cursor-pointer" 
                            id="CompareLaminate" 
                            data-toggle="tooltip" 
                            title="Compare Laminates"
                        >
                        <i class="fa fa-fw fa-search text-black" aria-hidden="true"></i>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table> 
    </div>
    <div class="pd-tp-14" v-if="(laminates.length < 1)"> 
        <div class="callout callout-info">
            <p><i class="fa fa-fw fa-info-circle" aria-hidden="true"></i> No search results found.</p>
        </div>
    </div>
    <div id="ShortListLmainateNotificationArea" class="hidden">
        <div class="alert alert-dismissible"></div>
    </div>
</div> 