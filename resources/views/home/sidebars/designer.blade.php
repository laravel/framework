<li class="{{ in_array($currentRouteName, $ideasRoutes) ? "active" : "" }}">
    <a href="{{ route('ideas.create') }}">
        <i class="fa fa-lightbulb-o pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Ideas</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $MyDesignRoutes) ? 'active' : '' }}">
    <a href="{{URL::route('designs.editlist')}}">
        <i class="ion ion-images"></i> <span>Designs</span>
    </a>
</li>
<li class="{{ @in_array($currentRouteName,$myCataloguesRoutes) ? 'active':'' }}" >
    <a href="#">
        <i class="fa fa-shopping-cart"></i> <span>Selections</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'catalogue.laminate.select') || (Route::currentRouteName() == 'catalogue.laminate.list') || (Route::currentRouteName() == 'catalogue.laminate.compare') || (Route::currentRouteName() == 'catalogue.laminate.newsuggestion') || (Route::currentRouteName() == 'catalogue.combination.edit')? 'active' : '' }}">
            <a href="{{URL::route('catalogue.laminate.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i>Laminates
            </a>
        </li>
    </ul>
</li>
<li class="{{ @in_array($currentRouteName,$recommendationsRoutes) ? 'active':'' }}" >
    <a href="#">
        <i class="fa fa-fw fa-gift"></i> 
        <span>Recommendations</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'category.recommendations.select') ? 'active' : '' }}">
            <a href="{{URL::route('category.recommendations.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i>Create
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'category.recommendations.reports') ? 'active' : '' }}">
            <a href="{{URL::route('category.recommendations.reports')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i>Finalized Materials
            </a>
        </li>
    </ul>
</li>
<li class="{{ (Route::currentRouteName() == 'myprojects.list') ? 'active' : '' }}">
    <a href="{{URL::route('myprojects.list')}}" title="" class="no-text-transform">
        <i class="fa fa-clipboard"></i><span>Projects</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $checklistRoutes) ? 'active' : '' }}">
    <a href="{{URL::route('work.checklist.report')}}">
        <i class="fa fa-fw fa-check"></i><span>Checklists</span>
    </a>
</li>
