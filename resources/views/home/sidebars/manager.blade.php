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
<li class="{{ in_array($currentRouteName, $deItemRoutes) ? "active" : "" }}">
    <a href="#">
        <i class="fa fa-cart-plus" aria-hidden="true"></i>&nbsp;<span>Detailed Estimate Items</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ ($currentRouteName == "de-items.create") ? "active" : "" }}">
            <a href="{{ route('de-items.create') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i>&nbsp;Add DE Item
            </a>
        </li>
        <li class="{{ in_array($currentRouteName, ["de-items.select.edit", "de-items.edit"]) ? "active" : "" }}">
            <a href="{{ route("de-items.select.edit") }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i>&nbsp;Update DE Item
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $ratecardRoutes) ? "active" : "" }}">
    <a href="#">
        <i class="fa fa-inr"></i>&nbsp;<span>Rate Cards</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ in_array($currentRouteName, ["de-items.ratecards.select.create", "de-items.ratecards.create"]) ? "active" : "" }}">
            <a href="{{ route("de-items.ratecards.select.create", "select") }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i>&nbsp;Add Rate Card
            </a>
        </li>
        <li class="{{ in_array($currentRouteName, ["de-items.ratecards.select.edit", "de-items.ratecards.edit"]) ? "active" : "" }}">
            <a href="{{ route("de-items.ratecards.select.edit", "select") }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i>&nbsp;Update Rate Card
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'ratecards.reports.filter') ? 'active' : '' }}">
            <a href="{{URL::route('ratecards.reports.filter')}}" title="">
                <i class="fa fa-circle-o"></i> View Rate Card
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $estimationItemRoutes) ? "active" : "" }}">
    <a href="#">
        <i class="fa fa-tasks"></i> <span>Estimation Items</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ ($currentRouteName == "qe-items.create") ? "active" : "" }}">
            <a href="{{ route('qe-items.create') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Add Item
            </a>
        </li>
        <li class="{{ in_array($currentRouteName, ["qe-items.select", "qe-items.edit"]) ? "active" : "" }}">
            <a href="{{ route("qe-items.select") }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Update Item
            </a>
        </li>
        <li class="{{ ($currentRouteName == "qe-items.qeitemsearch") ? "active" : "" }}">
            <a href="{{ route("qe-items.qeitemsearch") }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> <span>Search QE Items</span>
            </a>
        </li><li class="{{ ($currentRouteName == "qe-items.deitemsearch") ? "active" : "" }}">
            <a href="{{ route("qe-items.deitemsearch") }}">
                <i class="fa fa-circle-o"></i> <span>Search DE Items</span>
            </a>
        </li>
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $managementRoutes) ? 'active' : '' }}">
    <a href="#" title="">
        <i class="fa fa-cogs"></i> <span>Users and Roles</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ ($currentRouteName == 'management.roles.index') ? 'active' : '' }}">
            <a href="{{ route('management.roles.index') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Roles
            </a>
        </li>
        <li class="{{ ($currentRouteName == 'management.permission.create') ? 'active' : '' }}">
            <a href="{{ route('management.permission.create') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Permissions
            </a>
        </li>
        <li class="{{ ($currentRouteName == 'management.users.index') ? 'active' : '' }}">
            <a href="{{ route('management.users.index') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Users
            </a>
        </li>
        <li class="{{ ($currentRouteName == 'management.rolepermission.index') ? 'active' : '' }}">
            <a href="{{ route('management.rolepermission.index') }}" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Role Permissions
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
<li class="{{ in_array($currentRouteName, $designItemRoutes) || in_array($currentRouteName, $roomRoutes) || in_array($currentRouteName, $referenceDataRoutes)? 'active' : '' }}">
    <a href="#">
        <i class="fa fa-book"></i> <span>Reference Data</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'designitems.create')? 'active' : '' }}">
            <a href="{{URL::route('designitems.create')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Add Design Item
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'designitems.select' || (Route::currentRouteName() == 'designitems.edit'))? 'active' : '' }}">
            <a href="{{URL::route('designitems.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Update Design Item
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'designitems')? 'active' : '' }}">
            <a href="{{URL::route('designitems')}}" title="">
                <i class="fa fa-circle-o"></i> Search Design Items
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'rooms.create')? 'active' : '' }}">
            <a href="{{URL::route('rooms.create')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Add Room
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'rooms.select' || (Route::currentRouteName() == 'rooms.edit'))? 'active' : '' }}">
            <a href="{{URL::route('rooms.select')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Update Room
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'rooms')? 'active' : '' }}">
            <a href="{{URL::route('rooms')}}" title="">
                <i class="fa fa-circle-o"></i> Search Rooms
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'enquirystatus') ? 'active' : '' }}">
            <a href="{{URL::route('enquirystatus')}}" title="">
                <i class="fa fa-circle-o"></i> Enquiry Status
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'enquirystatus.descriptions') ? 'active' : '' }}">
            <a href="{{URL::route('enquirystatus.descriptions')}}" title="">
                <i class="fa fa-circle-o"></i> Enquiry Status Description
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'enquirystatus.reasons') ? 'active' : '' }}">
            <a href="{{URL::route('enquirystatus.reasons')}}" title="">
                <i class="fa fa-circle-o"></i> Enquiry Status Reason
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
        <li class="{{ (Route::currentRouteName() == 'carousel') ? 'active' : '' }}">
            <a href="{{URL::route('carousel')}}" title="">
                <i class="fa fa-circle-o"></i> Carousel
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'faq') ? 'active' : '' }}">
            <a href="{{URL::route('faq')}}" title="">
                <i class="fa fa-circle-o"></i> FAQ
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'faqcategory') ? 'active' : '' }}">
            <a href="{{URL::route('faqcategory')}}" title="">
                <i class="fa fa-circle-o"></i> FAQ Category
            </a>
        </li>
    </ul>
</li>
<li class="{{ @in_array($currentRouteName,$materialMasterRoutes) || @in_array($currentRouteName, $materialSubRoutes) ? 'active':'' }}" >
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
    </ul>
</li>
<li class="{{ in_array($currentRouteName, $MyDesignRoutes) ? 'active' : '' }}" >
    <a href="#">
        <i class="ion ion-images"></i> <span>Designs</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li class="{{ (Route::currentRouteName() == 'mydesigns.add') ? 'active' : '' }}">
            <a href="{{URL::route('mydesigns.add')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Add Design
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'designs.editlist') ||(Route::currentRouteName() == 'designs.edit') ? 'active' : '' }}">
            <a href="{{URL::route('designs.editlist')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Update Design
            </a>
        </li>
    </ul>
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
        <li class="{{ (Route::currentRouteName() == 'sitemeasurement.add') ? 'active' : '' }}">
            <a href="{{URL::route('sitemeasurement.add')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Create Site Measurement
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'sitemeasurement.notecategory') || (Route::currentRouteName() == 'sitemeasurement.editnotecategory')? 'active' : '' }}">
            <a href="{{URL::route('sitemeasurement.notecategory')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Note Category
            </a>
        </li>
        <li class="{{ (Route::currentRouteName() == 'sitemeasurement.list') || (Route::currentRouteName() == 'sitemeasurement.edit') || (Route::currentRouteName() == 'sitemeasurement.show') || (Route::currentRouteName() == 'sitemeasurement.room.ac.get') || (Route::currentRouteName() == 'sitemeasurement.room.firesprinkler.get') ? 'active' : '' }}">
            <a href="{{URL::route('sitemeasurement.list')}}" title="" class="no-text-transform">
                <i class="fa fa-circle-o"></i> Search Site Measurement
            </a>
        </li>
    </ul>
</li>
<li class="{{ @in_array($currentRouteName, $myCataloguesRoutes) ? 'active':'' }}" >
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