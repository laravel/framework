<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu">
            <li class="header"></li>
            {{-- show dashboard sidebars according to user roles --}}
            @if (auth()->user()->isManager())
                @include('home.sidebars.manager')
            @elseif (auth()->user()->isDesigner())
                @include('home.sidebars.designer')
            @elseif (auth()->user()->isCustomer())
                @include('home.sidebars.customer')
            @elseif (auth()->user()->isSupervisor())
                @include('home.sidebars.supervisor')
            @elseif (auth()->user()->isReviewer())
                @include('home.sidebars.reviewer')
            @elseif (auth()->user()->isApprover())
                @include('home.sidebars.approver')
            @elseif (auth()->user()->isSales())
                @include('home.sidebars.sales')
            @elseif (auth()->user()->isDataManager())
                @include('home.sidebars.datamanager')
            @endif
        </ul>
    </section>
</aside>
