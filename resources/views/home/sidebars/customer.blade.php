<li class="{{ in_array($currentRouteName, $ideasRoutes) ? "active" : "" }}">
    <a href="{{ route('ideas.create') }}">
        <i class="fa fa-lightbulb-o pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Ideas</span>
    </a>
</li>
<li class="{{ (Route::currentRouteName() == 'designs.list') || (Route::currentRouteName() == 'designs.show') ? 'active' : '' }}">
    <a href="{{URL::route('designs.list')}}" title="">
        <i class="ion ion-images"></i> <span>Design</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $enquiryRoutes) ? 'active' : '' }}">
    <a href="{{ route('enquiries.index') }}">
        <i class="fa fa-list-ul"></i> <span>Enquiry</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $quickEstimateRoutes) ? 'active' : '' }}">
    <a href="{{URL::route('quick-estimates.index')}}" title="">
        <i class="fa fa-list-alt"></i> <span>Quick Estimate</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $checklistRoutes) ? 'active':'' }}">
    <a href="{{ route('work.checklists') }}">
        <i class="fa fa-fw fa-list-ol" aria-hidden="true"></i>
        <span class="pd-rt-3">Checklists</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $myCataloguesRoutes) ? 'active':'' }}" >
    <a href="#">
        <i class="fa fa-shopping-cart"></i>
        <span>Selections</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'catalogue.laminates.project.select')  ||  (Route::currentRouteName() == 'catalogues.laminates.list') || (Route::currentRouteName() == 'catalogues.laminates.shortlist') || (Route::currentRouteName() == 'catalogues.laminates.combination.finalize') || (Route::currentRouteName() == 'catalogues.laminates.compare') || (Route::currentRouteName() == 'catalogues.laminates.combination.edit') ? 'active' : '' }}">
            <a href="{{URL::route('catalogue.laminates.project.select')}}" title="" class="no-text-transform">
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
<li class="{{ (Route::currentRouteName() == 'faqs.view') ? 'active' : '' }}">
    <a href="{{URL::route('faqs.view')}}" title="">
        <i class="fa fa-question"></i> 
        <span>FAQs</span>
    </a>
</li>
<li class="{{ (Route::currentRouteName() == '') ? 'active' : '' }}">
    <a href="http://www.hechpe.com/#portfolio" title="" target="_blank">
        <i class="ion ion-filing"></i> 
        <span>HECHPE Portfolio</span>
    </a>
</li>
