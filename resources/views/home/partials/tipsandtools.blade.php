<div id="TipsAndToolsModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title no-capitalize">Tips and Tools
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row ">
                    <div class="col-sm-12">
                        @isset ($tipsAndTools)
                            <ul class="products-list product-list-in-box">
                                @foreach($tipsAndTools as $tipAndTool)
                                    <li class="item">
                                        <div class="col-md-2 col-sm-3 col-xs-12">
                                            <div class="product-img ToolTipImg">
                                                <img src="{{ cdn_asset($tipAndTool->Image) }}" alt="{{ $tipAndTool->Title }}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-10 col-sm-9 col-xs-12">
                                            <div class="product-info ToolTipData">
                                                <h4 class="product-title">{{ $tipAndTool->Title }}</h4>
                                                <p>{{ $tipAndTool->Description }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
