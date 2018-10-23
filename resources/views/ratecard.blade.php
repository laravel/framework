@extends('layouts/master_template')

@section('content')
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">List of RateCards Items</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-hover">
                        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                            <tr>
                                <th class="text-center">S.No</th>
                                <th></th>
                                <th>RATE CARD ITEM DESCRIPTION</th>
                                <th>Unit</th>
                                <th class="text-center">Market Standard</th>
                                <th class="text-center">HECHPE Select</th>
                                <th class="text-center">All Branded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-center">
                                    <button class="btn btn-default btn-xs" title="Modify rate card">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                <td>Sq.ft</td>
                                <td class="text-right">&#8377; 1250.00</td>
                                <td class="text-right">&#8377; 1350.00</td>
                                <td class="text-right">&#8377; 1450.00</td>
                            </tr>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-center">
                                    <button class="btn btn-default btn-xs" title="Modify rate card">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                <td>Sq.ft</td>
                                <td class="text-right">&#8377; 1250.00</td>
                                <td class="text-right">&#8377; 1350.00</td>
                                <td class="text-right">&#8377; 1450.00</td>
                            </tr>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-center">
                                    <button class="btn btn-default btn-xs" title="Modify rate card">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                <td>Sq.ft</td>
                                <td class="text-right">&#8377; 1250.00</td>
                                <td class="text-right">&#8377; 1350.00</td>
                                <td class="text-right">&#8377; 1450.00</td>
                            </tr>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-center">
                                    <button class="btn btn-default btn-xs" title="Modify rate card">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                <td>Sq.ft</td>
                                <td class="text-right">&#8377; 1250.00</td>
                                <td class="text-right">&#8377; 1350.00</td>
                                <td class="text-right">&#8377; 1450.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div>
        <!-- /.box -->
    </div>
    <div class="modal fade" id="AddItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Add a new item</h4>
                </div>
                <div class="modal-body table-responsive" id="AddItemModalBody">Loading...</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/RateCard.js') }}"></script>
@endsection
