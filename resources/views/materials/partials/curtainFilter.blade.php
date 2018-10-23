<div class="row">
    <div class="col-md-3">
        <label for="Category">Material Category</label>
        <select class="form-control" name="Category" id="Category">   
            <option value="">Choose a Category</option>
            @foreach($categories as $Key => $category)
            <option value='{{ $category->Id }}' {{ $category->Slug === $slug ? 'selected="selected"' : '' }}>{{ $category->Name}}</option>
            @endforeach
        </select> 
    </div> 
    <div class="col-md-3">
        <label for="SubBrand">Brand / Sub Brand</label>
        <select class="form-control" name="SubBrand" id="SubBrand">   
            <option value="">Choose a Sub Brand</option>
            @foreach($subbrands as $Key => $subbrand)
            <option value='{{$Key}}'>{{$subbrand}}</option>
            @endforeach
        </select> 
    </div> 
    <div class="form-group col-md-3">
        <label for="DesignName" class="control-label">Design Name</label>                 
        <input class="form-control" name="DesignName" type="text" id="DesignName">
    </div>
    <div class="form-group col-md-3">
        <label for="DesignCode" class="control-label">Design Code</label>                 
        <input class="form-control" name="DesignCode" type="text" id="DesignCode">
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <label for="CurtainType">Type</label>
        <select class="form-control" name="CurtainType" id="CurtainType">   
            <option value="">Choose a Type</option>
            <option value='Main'>Main</option>
            <option value='Sheer'>Sheer</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="CreatedDate">Created Date</label>
            <div class="input-group">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" name="CreatedDate" id="CreatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                <div class="input-group-btn">
                    <button type="button" class="btn btn-addon dropdown-toggle" id="CreatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-filter"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" id="CreatedDateFilters">
                        <li class="active" data-filter-name="eq">
                            <a href="#">
                                <b class="mr-rt-6">=</b> equal to
                            </a>
                        </li>
                        <li data-filter-name="lt">
                            <a href="#">
                                <b class="mr-rt-6">&lt;</b> less than
                            </a>
                        </li>
                        <li data-filter-name="gt">
                            <a href="#">
                                <b class="mr-rt-6">&gt;</b> greater than
                            </a>
                        </li>
                        <li data-filter-name="le">
                            <a href="#">
                                <b class="mr-rt-6">&le;</b> less than or equal to
                            </a>
                        </li>
                        <li data-filter-name="ge">
                            <a href="#">
                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="UpdatedDate">Updated Date</label>
            <div class="input-group">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" name="UpdatedDate" id="UpdatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                <div class="input-group-btn">
                    <button type="button" class="btn btn-addon dropdown-toggle" id="UpdatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-filter"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" id="UpdatedDateFilters">
                        <li class="active" data-filter-name="eq">
                            <a href="#">
                                <b class="mr-rt-6">=</b> equal to
                            </a>
                        </li>
                        <li data-filter-name="lt">
                            <a href="#">
                                <b class="mr-rt-6">&lt;</b> less than
                            </a>
                        </li>
                        <li data-filter-name="gt">
                            <a href="#">
                                <b class="mr-rt-6">&gt;</b> greater than
                            </a>
                        </li>
                        <li data-filter-name="le">
                            <a href="#">
                                <b class="mr-rt-6">&le;</b> less than or equal to
                            </a>
                        </li>
                        <li data-filter-name="ge">
                            <a href="#">
                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
