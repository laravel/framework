<th width='20%'></th>
<th class="text-center text-vertical-align" width='20%'>
    @{{ComparisonLaminate.DesignNo}}
</th>
<th class="text-center text-vertical-align" width='20%'>                      
    <div class="input-group input-group-sm ui-front" id="FirstSearchBox">
        <input type="text" 
            class="form-control search" 
            placeholder="Search..." 
            name="SearchLamBox1" 
            id="SearchLamBox1" 
            data-api-end-point="{{ route('catalogues.laminates.search.compare') }}">
        <div class="input-group-btn">
            <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search" aria-hidden="true"></i></button>
        </div>
    </div>
</th>
<th class="text-center text-vertical-align" width='20%'>                      
    <div class="input-group input-group-sm ui-front" id="SecondSearchBox">
        <input 
            type="text" 
            class="form-control search" 
            placeholder="Search..." 
            name="SearchBox2" 
            id="SearchBox2"
            data-api-end-point="{{ route('catalogues.laminates.search.compare') }}">
        <div class="input-group-btn">
            <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search" aria-hidden="true"></i></button>
        </div>
    </div>
</th>
<th class="text-center text-vertical-align" width='20%'>                      
    <div class="input-group input-group-sm ui-front" id="ThirdSearchBox">
        <input
            type="text" 
            class="form-control search" 
            placeholder="Search..." 
            name="SearchBox3" 
            id="SearchBox3"
            data-api-end-point="{{ route('catalogues.laminates.search.compare') }}">
        <div class="input-group-btn">
            <button class="btn btn-default" type="submit">
                <i class="glyphicon glyphicon-search" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</th>