<li class="{{ (Route::currentRouteName() == 'sitemeasurement.add') || (Route::currentRouteName() == 'user.measurements') || (Route::currentRouteName() == 'active.projects') || (Route::currentRouteName() == 'sitemeasurement.list') || (Route::currentRouteName() == 'sitemeasurement.show') || (Route::currentRouteName() == 'sitemeasurement.edit') || (Route::currentRouteName() == 'sitemeasurement.rooms.calculations') || (Route::currentRouteName() == 'sitemeasurement.room.ac.get') || (Route::currentRouteName() == 'sitemeasurement.room.firesprinkler.get') ? 'active' : '' }}">
    <a href="{{URL::route('sitemeasurement.list')}}">
        <i class="ion ion-clipboard pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Site Measurements</span>
    </a>
</li>
<li class="{{ (Route::currentRouteName() == 'myprojects.list') ? 'active' : '' }}">
    <a href="{{URL::route('myprojects.list')}}">
        <i class="fa fa-clipboard pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Projects</span>
    </a>
</li>
<li class="{{ (Route::currentRouteName() == 'work.checklist.report') || (Route::currentRouteName() == 'work.checklist.view') ? 'active' : '' }}">
    <a href="{{URL::route('work.checklist.report')}}">
        <i class="fa fa-fw fa-check pd-lt-3" aria-hidden="true"></i>
        <span class="pd-rt-3">Checklists</span>
    </a>
</li>