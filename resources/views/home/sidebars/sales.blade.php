<li class="{{ in_array($currentRouteName, $enquiryRoutes) ? 'active' : '' }}">
    <a href="#">
        <i class="fa fa-list-ul"></i> <span>Enquiries</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ in_array($currentRouteName, ["enquiries.index", "enquiries.show", "viewenquiry", "enquiry","search.enquiries"]) ? 'active' : '' }}">
            <a href="{{ route('search.enquiries') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Search
            </a>
        </li>
        <li class="{{ in_array($currentRouteName, ['enquiries.actions', 'enquiries.action.delete']) ? 'active' : '' }}">
            <a href="{{ route('enquiries.actions') }}">
                <i class="fa fa-fw fa-cog"></i> <span>Actions</span>
            </a>
        </li>
        <li class="{{ in_array($currentRouteName, ['enquiry.reports', 'enquiry.reports.by.status']) ? 'active' : '' }}">
            <a href="{{ route('enquiry.reports') }}">
                <i class="fa fa-fw fa-file"></i> <span>Reports</span>
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $quickEstimateRoutes) ? 'active' : '' }}">
    <a href="{{ route('search.quickestimates') }}">
        <i class="fa fa-list-alt"></i> <span>Quick Estimate</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $customerRoutes) ? 'active' : '' }}">
    <a href="#" title="">
        <i class="fa fa-users"></i> <span>Customers</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ ($currentRouteName == 'customers.search') ? 'active' : '' }}">
            <a href="{{ route('customers.search') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Search
            </a>
        </li>
        <li class="{{ ($currentRouteName == 'customers.register') ? 'active' : '' }}">
            <a href="{{ route('customers.register') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Register
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $MapProjectRoutes) ? 'active' : '' }}">
    <a href="#">
        <i class="fa fa-fw fa-suitcase"></i> <span>Projects</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'pnintegration.add.project') ? 'active' : '' }}">
            <a href="{{URL::route('pnintegration.add.project')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Create Project
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'projectmanagement.project.list') ? 'active' : '' }}">
            <a href="{{URL::route('projectmanagement.project.list')}}" title="">
                <i class="fa fa-circle-o"></i> Search Projects
            </a>
        </li>
    </ul>
</li>  
<li class="{{ in_array($currentRouteName, $checklistRoutes) ? 'active' : '' }}">
    <a href="#">
        <i class="fa fa-fw fa-list-ol"></i> <span>Checklists</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{(Route::currentRouteName() == 'work.checklist.project.select') || (Route::currentRouteName() == 'work.checklist') ? 'active' : '' }}">
            <a href="{{route('work.checklist.project.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Create Checklist
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'work.checklist.report') || (Route::currentRouteName() == 'work.checklist.view') ? 'active' : '' }}">
            <a href="{{URL::route('work.checklist.report')}}" title="">
                <i class="fa fa-circle-o"></i> Search Checklists
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $builderProjectRoutes) ? 'active' : '' }}">
   <a href="#">
        <i class="fa fa-building"></i> <span>Builders</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'builder') || (Route::currentRouteName() == 'builder.edit')? 'active' : '' }}">
            <a href="{{URL::route('builder')}}" title="">
                <i class="fa fa-circle-o"></i> Builder Management
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'project.new') ? 'active' : '' }}">
            <a href="{{URL::route('project.new')}}" title="">
                <i class="fa fa-circle-o"></i> Add Builder Project
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'project.searchproject')|| (Route::currentRouteName() == 'project.edit') ? 'active' : '' }}">
            <a href="{{URL::route('project.searchproject')}}" title="">
                <i class="fa fa-circle-o"></i> Update Builder Project
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'project.report') ? 'active' : '' }}">
            <a href="{{URL::route('project.report')}}" title="">
                <i class="fa fa-circle-o"></i> <span>Search Builder Projects</span>
            </a>
        </li>   
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $MyDesignRoutes) ? 'active' : '' }}" >
    <a href="{{ route('designs.editlist') }}">
        <i class="ion ion-images"></i>
        <span>Designs</span>
    </a>
</li>
<li class="{{ in_array($currentRouteName, $ideasRoutes) ? "active" : "" }}">
    <a href="{{ route('ideas.create') }}">
        <i class="fa fa-lightbulb-o pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Ideas</span>
    </a>
</li>
<li class="{{ @in_array($currentRouteName, $siteMeasurementRoutes) ? 'active':'' }}" >
    <a href="#">
        <i class="ion ion-clipboard pd-lt-3"></i> <span>Site Measurement</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'sitemeasurement.list') || (Route::currentRouteName() == 'sitemeasurement.edit') || (Route::currentRouteName() == 'sitemeasurement.show') || (Route::currentRouteName() == 'sitemeasurement.room.ac.get') || (Route::currentRouteName() == 'sitemeasurement.room.firesprinkler.get') ? 'active' : '' }}">
            <a href="{{URL::route('sitemeasurement.list')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Search Site Measurement
            </a>
        </li>
    </ul>
<li class="{{ @(in_array($currentRouteName, $myCataloguesRoutes) || in_array($currentRouteName,$recommendationsRoutes)) ? 'active':'' }}" >
    <a href="#">
        <i class="fa fa-shopping-cart"></i>
        <span>Selections</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'catalogue.laminate.select') || (Route::currentRouteName() == 'catalogue.laminate.list') || (Route::currentRouteName() == 'catalogue.laminate.compare') || (Route::currentRouteName() == 'catalogue.laminate.newsuggestion') || (Route::currentRouteName() == 'catalogue.combination.edit')? 'active' : '' }}">
            <a href="{{URL::route('catalogue.laminate.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i>Laminates
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'category.recommendations.reports') ? 'active' : '' }}">
            <a href="{{URL::route('category.recommendations.reports')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i>Other Materials
            </a>
        </li>
    </ul>
</li>