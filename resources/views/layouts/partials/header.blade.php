<style>
    #menubar .menu-hover {
        border:0px;
    }
    #menubar .menu-hover:hover, .menu-hover:focus {
        background: #242424 !important;
        color: #fff !important;
    }
</style>
<header class="main-header">
    <a href="{{ URL::route('dashboard') }}" class="logo">
        <span class="logo-mini">
            <object class="logo-small" data="{{ URL::CDN("$CurrentDomainSettingsView->SiteSmallLogo") }}">
                {{ $CurrentDomainDetailsView->Name }}
            </object>
        </span>
        <span class="logo-lg">
            <object class="logo-large" data="{{ URL::CDN("$CurrentDomainSettingsView->SiteLogo") }}">
                {{ $CurrentDomainDetailsView->Name }}
            </object>
        </span>
    </a>
    <nav class="navbar navbar-static-top" role="navigation">
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <div class="navbar-custom-menu">

            <ul class="nav navbar-nav" id="result-side-menu">               
                <li id="menubar">
                    <a href="http://hechpe.com/" class="menu-hover" target="_blank">
                        <span class="hidden-xs"><i class="fa fa-globe" aria-hidden="true"></i>&nbsp;&nbsp;www.hechpe.com</span>
                    </a>
                </li>
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="{{$profilePictureURL}}" class='user-image profile-picture' alt='User Image'/>
                        <span class="hidden-xs profile-fullname text-capitalize">{{ Auth::User()->Person->FirstName }} {{ Auth::User()->Person->LastName }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="user-header">
                            <img src="{{$profilePictureURL}}" class='img-circle profile-picture' alt='User Image' />
                            <p class="profile-fullname text-capitalize">{{ Auth::User()->Person->FirstName }} {{ Auth::User()->Person->LastName }}</p>
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{{ route('preferences.index') }}" class="btn btn-primary btn-flat">
                                    <i class="fa fa-cog"></i> Settings
                                </a>
                            </div>
                            <div class="pull-right">
                                <form action="{{ route('logout') }}" method="POST">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary btn-flat">
                                        <i class="fa fa-sign-out"></i> Log Out
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>
