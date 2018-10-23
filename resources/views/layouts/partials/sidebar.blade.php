<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu">
            <li class="header"></li>
            <?php $Manager = false ?>
            <?php $Designer = false ?>
            @foreach(Auth::user()->Role()->get()->toArray() as $Key => $Role)
            @if($Role["Slug"] === env("MANAGER_ROLE_SLUG", "manager"))
            <?php $Manager = true ?>
            @endif
            @if($Role["Slug"] === env("DESIGNER_ROLE_SLUG", "autocaddesigner"))
            <?php $Designer = true ?>
            @endif
            @endforeach
            @if($Manager)
            <li class="{{ in_array($currentRouteName, $enquiryRoutes) ? 'active' : '' }}">
                <a href="{{ route('search.enquiries') }}">
                    <i class="fa fa-list-ul"></i> <span>Enquiries</span>
                </a>
            </li>
            <li class="{{ in_array($currentRouteName, $quickEstimateRoutes) ? 'active' : '' }}">
                <a href="{{ route('search.quickestimates') }}">
                    <i class="fa fa-list-alt"></i> <span>Quick Estimates</span>
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
            <li @if (Route::currentRouteName() == 'ratecards.index' || Route::currentRouteName() == 'items.create' || Route::currentRouteName() == 'items.select' || Route::currentRouteName() == 'items.edit' || Route::currentRouteName() == 'ratecards.select.create' || Route::currentRouteName() == 'ratecards.create' || Route::currentRouteName() == 'ratecards.select.modify' || Route::currentRouteName() == 'ratecards.select.list' || Route::currentRouteName() == 'ratecards.reports.filter') class="active" @endif >
                 <a href="#">
                    <i class="fa fa-inr" style="padding-left:3px"></i> <span>RateCard Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'items.create') ? 'active' : '' }}">
                        <a href="{{URL::route('items.create')}}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'items.select' || Route::currentRouteName() == 'items.edit') ? 'active' : '' }}">
                        <a href="{{URL::route('items.select')}}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'ratecards.select.create' || Route::currentRouteName() == 'ratecards.create') ? 'active' : '' }}">
                        <a href="{{URL::route('ratecards.select.create')}}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add RateCards
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'ratecards.select.modify' || Route::currentRouteName() == 'ratecards.modify') ? 'active' : '' }}">
                        <a href="{{URL::route('ratecards.select.modify')}}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update RateCards
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'ratecards.select.list') ? 'active' : '' }}">
                        <a href="{{URL::route('ratecards.select.list')}}">
                            <i class="fa fa-circle-o"></i> List Items' RateCards
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'ratecards.reports.filter') ? 'active' : '' }}">
                        <a href="{{URL::route('ratecards.reports.filter')}}" title="">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $QEItemRoutes) ? 'active' : '' }}">
                 <a href="#">
                    <i class="fa fa-tasks"></i> <span>QE Item Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ ($currentRouteName == 'qeitems.create') ? 'active' : '' }}">
                        <a href="{{ route('qeitems.create') }}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add a QE Item
                        </a>
                    </li>
                    <li class="{{ (($currentRouteName == 'qeitems.select') || ($currentRouteName == 'qeitems.edit')) ? 'active' : '' }}">
                        <a href="{{ route('qeitems.select') }}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update a QE Item
                        </a>
                    </li>
                    <li class="{{ ($currentRouteName == 'qeitems.reports') ? 'active' : '' }}">
                        <a href="{{URL::route('qeitems.reports')}}">
                            <i class="fa fa-circle-o"></i> <span>Master Report</span>
                        </a>
                    </li>   
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $designItemRoutes) ? 'active' : '' }}" >
               <a href="#">
                    <i class="fa fa-paint-brush"></i> <span>Design Item Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'designitems.create')? 'active' : '' }}">
                        <a href="{{URL::route('designitems.create')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designitems.select' || (Route::currentRouteName() == 'designitems.edit'))? 'active' : '' }}">
                        <a href="{{URL::route('designitems.select')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designitems')? 'active' : '' }}">
                        <a href="{{URL::route('designitems')}}" title="">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $roomRoutes) ? 'active' : '' }}" >
               <a href="#">
                    <i class="fa fa-bed"></i> <span>Room Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'rooms.create')? 'active' : '' }}">
                        <a href="{{URL::route('rooms.create')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add a Room
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'rooms.select' || (Route::currentRouteName() == 'rooms.edit'))? 'active' : '' }}">
                        <a href="{{URL::route('rooms.select')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update a Room
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'rooms')? 'active' : '' }}">
                        <a href="{{URL::route('rooms')}}" title="">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $managementRoutes) ? 'active' : '' }}">
                <a href="#" title="">
                    <i class="fa fa-cogs"></i> <span>Management</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ ($currentRouteName == 'management.roles.index') ? 'active' : '' }}">
                        <a href="{{ route('management.roles.index') }}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Roles
                        </a>
                    </li>
                    <li class="{{ ($currentRouteName == 'management.users.index') ? 'active' : '' }}">
                        <a href="{{ route('management.users.index') }}" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Users
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $MapProjectRoutes) ? 'active' : '' }}" >
                <a href="#">
                    <i class="fa fa-link"></i> <span>Map Project</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'pnintegration.map') ? 'active' : '' }}">
                        <a href="{{URL::route('pnintegration.map')}}" title="">
                            <i class="fa fa-circle-o"></i> Map New Project
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'pnintegration.mappedprojectslist') ? 'active' : '' }}">
                        <a href="{{URL::route('pnintegration.mappedprojectslist')}}" title="">
                            <i class="fa fa-circle-o"></i> Mapped Projects
                        </a>
                    </li>
                </ul>
            </li>            
            <li class="{{ in_array($currentRouteName, $builderProjectRoutes) ? 'active' : '' }}" >
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
                            <i class="fa fa-circle-o"></i> Add Project
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'project.searchproject')|| (Route::currentRouteName() == 'project.edit') ? 'active' : '' }}">
                        <a href="{{URL::route('project.searchproject')}}" title="">
                            <i class="fa fa-circle-o"></i> Update Project
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'project.report') ? 'active' : '' }}">
                        <a href="{{URL::route('project.report')}}" title="">
                            <i class="fa fa-circle-o"></i> <span>Master Report</span>
                        </a>
                    </li>   
                </ul>
            </li>
             
            <li class="{{ in_array($currentRouteName, $MyDesignRoutes) ? 'active' : '' }}" >
                 <a href="#">
                    <i class="ion ion-images"></i> <span>My Designs</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                   <li class="{{ (Route::currentRouteName() == 'mydesigns.add') ? 'active' : '' }}">
                        <a href="{{URL::route('mydesigns.add')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add a Design
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designs.editlist') ||(Route::currentRouteName() == 'designs.edit') ? 'active' : '' }}">
                        <a href="{{URL::route('designs.editlist')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            {{--
            <li class="{{ @in_array($currentRouteName,$materialMasterRoutes) ? 'active':'' }}" >
                 <a href="#">
                    <i class="fa fa-shopping-bag"></i> <span>Detailed Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'materials.categories') ? 'active' : '' }}">
                        <a href="{{URL::route('materials.categories')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"> </i> Add Master
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'masters.select.list') ? 'active' : '' }}">
                        <a href="{{URL::route('masters.select.list')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> List Masters
                        </a>
                    </li>        
                </ul>
            </li>
 --}}
            @elseif($Designer)
            <li class="{{ in_array($currentRouteName, $designItemRoutes) ? 'active' : '' }}" >
               <a href="#">
                    <i class="fa fa-paint-brush"></i> <span>Design Item Master</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'designitems.create')? 'active' : '' }}">
                        <a href="{{URL::route('designitems.create')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designitems.select' || (Route::currentRouteName() == 'designitems.edit'))? 'active' : '' }}">
                        <a href="{{URL::route('designitems.select')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Update an Item
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designitems')? 'active' : '' }}">
                        <a href="{{URL::route('designitems')}}" title="">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ in_array($currentRouteName, $MyDesignRoutes) ? 'active' : '' }}" >
                 <a href="#">
                    <i class="ion ion-images"></i> <span>My Designs</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::currentRouteName() == 'mydesigns.add') ? 'active' : '' }}">
                        <a href="{{URL::route('mydesigns.add')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Add a Design
                        </a>
                    </li>
                    <li class="{{ (Route::currentRouteName() == 'designs.editlist') ||(Route::currentRouteName() == 'designs.edit') ? 'active' : '' }}">
                        <a href="{{URL::route('designs.editlist')}}" title="" class="no-text-transform">
                            <i class="fa fa-circle-o"></i> Master Report
                        </a>
                    </li>
                </ul>
            </li>
            @else
            <li class="{{ (Route::currentRouteName() == 'enquiry') ? 'active' : '' }}">
                <a href="{{URL::route('enquiry')}}">
                    <i class="fa fa-plus-square-o"></i> <span>New Enquiry</span>
                </a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'enquiries.index' || Route::currentRouteName() == 'viewenquiry') ? 'active' : '' }}">
                <a href="{{ route('enquiries.index') }}">
                    <i class="fa fa-list-ul"></i> <span>My Enquiries</span>
                </a>
            </li>
            <li class="{{ ((Route::currentRouteName() == 'estimate.create') || (Route::currentRouteName() == 'quickestimate.duplicate')) ? 'active' : '' }}">
                <a href="{{URL::route('estimate.create')}}" title="">
                    <i class="fa fa-calculator"></i> <span>New Quick Estimate</span>
                </a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'quickestimates.list') || (Route::currentRouteName() == 'quickestimate.show') ? 'active' : '' }}">
                <a href="{{URL::route('quickestimates.list')}}" title="">
                    <i class="fa fa-list-alt"></i> <span>My Quick Estimates</span>
                </a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'designs.list') || (Route::currentRouteName() == 'designs.show') ? 'active' : '' }}">
                <a href="{{URL::route('designs.list')}}" title="">
                    <i class="ion ion-images"></i> <span>My Designs</span>
                </a>
            </li>
            @endif
        </ul>
    </section>
</aside>
