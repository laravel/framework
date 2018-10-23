<div class="modal fade" id="FutureRatecards" tabindex="-1" role="dialog" aria-labelledby="FutureRatecards">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-history reverse-icon" aria-hidden="true"></i>&nbsp;&nbsp;Future Ratecards
                </h4>
            </div>
            <div class="modal-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        @foreach($pricePackages as $pricePackage)
                            <li>
                                <a href="#{{ $pricePackage->id }}" data-toggle="tab">{{ $pricePackage->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach($pricePackages as $pricePackage)
                            <div class="tab-pane" id="{{ $pricePackage->id }}">
                                @if (isset($futureRatecards[$pricePackage->id]))
                                    @include("ratecards.partials.create.futureratecards.table")
                                @else
                                    <div class="alert alert-info" style="margin-top:0.5em;margin-bottom:0.8em;">No Future Ratecards are available for {{$pricePackage->name}} Specification.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
