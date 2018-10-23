<li class="{{ in_array($currentRouteName,$materialMasterRoutes) || in_array($currentRouteName, $materialSubRoutes) || in_array($currentRouteName, $referenceDataRoutes) ? 'active':'' }}" >
    <a href="{{URL::route('materials')}}" id="materials">
        <i class="fa fa-shopping-bag"></i> <span>Materials</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ @in_array(Route::currentRouteName(), $materialSubRoutes) ? 'active' : '' }}">
            <a href="{{URL::route('materials.categories')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"> </i> Add Material
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.list') ? 'active' : '' }}">
            <a href="{{URL::route('materials.list')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Search Materials
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.brands.create') ? 'active' : '' }}">
            <a href="{{URL::route('materials.brands.create')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Brands
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.subbrands.create') ? 'active' : '' }}">
            <a href="{{URL::route('materials.subbrands.create')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Sub Brands
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.mapping') ? 'active' : '' }}">
            <a href="{{URL::route('materials.mapping')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Materials Mapping
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.types') ? 'active' : '' }}">
            <a href="{{URL::route('materials.types')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Types
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.surfacecategory') ? 'active' : '' }}">
            <a href="{{URL::route('materials.surfacecategory')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Surface Category
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.surfacefinish') ? 'active' : '' }}">
            <a href="{{URL::route('materials.surfacefinish')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Surface Finish
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materials.surfacerange') ? 'active' : '' }}">
            <a href="{{URL::route('materials.surfacerange')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Surface Range
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'pricerange') ? 'active' : '' }}">
            <a href="{{URL::route('pricerange')}}" title="">
                <i class="fa fa-circle-o"></i> Material Price Range
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materialcolors') ? 'active' : '' }}">
            <a href="{{URL::route('materialcolors')}}" title="">
                <i class="fa fa-circle-o"></i> Material Color
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materialpatterns') ? 'active' : '' }}">
            <a href="{{URL::route('materialpatterns')}}" title="">
                <i class="fa fa-circle-o"></i> Material Pattern
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'materialenduses') ? 'active' : '' }}">
            <a href="{{URL::route('materialenduses')}}" title="">
                <i class="fa fa-circle-o"></i> Material End Use
            </a>
        </li>
    </ul>
</li>