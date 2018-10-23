@extends('layouts/master_template')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title no-text-transform" style="vertical-align:middle">My Quick Estimates</h3>
                    <a href="{{route('estimate.create')}}" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;New Estimate
                    </a>
                </div>                
                <div class="box-body">
                    @if($QuickEstimates->isEmpty())
                    <div class="callout callout-info">
                        <h4>Information!</h4>
                        <p>No Estimation found. Would you like to <a href="{{route('estimate.create')}}" title="Add an Item">create one</a>.</p>
                    </div>
                    @else
                    <div class="table-responsive" id="SearchResultsBody">
                        <table id="QuickEstList" class="table table-striped table-bordered dataTable">
                            <thead>                                
                                <tr>
                                    <th colspan="5" class="text-left bg-white"></th>
                                    <th colspan="3" class="text-center bg-white">Total</th>                                   
                                    <th colspan="2" class="text-left bg-white"></th>
                                </tr>                                
                                <tr>
                                    <th width="1%" class="text-vertical-align text-center">S.No</th>
                                    <th width="10%" class="text-vertical-align text-center">QE No</th>
                                    <th width="10%" class="text-vertical-align text-center">Enquiry</th>
                                    <th width="15%" class="text-vertical-align text-center">Site Address</th>
                                    <th width="8%" class="text-vertical-align text-center">Unit</th>
                                    <th width="14%" class="text-center bg-orange amount-text">                                        
                                       {{$PricePackages[0]['Name']}}
                                    </th>
                                    <th width="14%" class="text-center bg-aqua amount-text">
                                        {{$PricePackages[1]['Name']}}
                                    </th>
                                    <th width="14%" class="text-center bg-green amount-text">
                                        {{$PricePackages[2]['Name']}}
                                    </th>
                                    <th width="10%" class="text-vertical-align text-center">Work Type</th>
                                    <th width="4%" class="text-vertical-align text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; ?>
                                @foreach($QEList as $QuickEst)
                                <tr>
                                    <td class="text-vertical-align text-center">{{$i}}</td>
                                    <td class="text-vertical-align text-center">{{$QuickEst['ReferenceNumber']}}</td>
                                    <td class="text-vertical-align text-center">{!! $QuickEst['Enquiry'] !!}</td>
                                    <td class="text-vertical-align text-center">{!! $QuickEst['SiteAddress'] !!}</td>                                    
                                    <td class="text-vertical-align text-center">{{$QuickEst['UnitType']}}</td>
                                    <td class="text-vertical-align text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount1']) }}</span></td>
                                    <td class="text-vertical-align text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount2']) }}</span></td>
                                    <td class="text-vertical-align text-center"><span class="SumAmount1">&#8377;{{ money_format('%!.0n', $QuickEst['SumAmount3']) }}</span></td>
                                    <td class="text-vertical-align text-center">{{ $QuickEst['WorkType'] }}</td>
                                    <td class="text-center">
                                        <span class="dropdown">
                                            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                                <li>
                                                    <a href="{{route('quickestimate.show', ['quickestrefno' => $QuickEst['ReferenceNumber']])}}">
                                                        <i class="fa fa-eye" aria-hidden="true"></i> View Estimation
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{route('quickestimate.pdf', ['quickestrefno' => $QuickEst['ReferenceNumber']])}}">
                                                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Download PDF
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('quickestimate.duplicate', ['refno' => $QuickEst['ReferenceNumber'], 'cityid' => $QuickEst['CityId']]) }}">
                                                        <i class="fa fa-clone" aria-hidden="true"></i> Copy as New
                                                    </a>
                                                </li>
                                            </ul>
                                        </span>
                                    </td>
                                </tr>
                                <?php $i++; ?>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.css")}}">
<link rel="stylesheet" href="{{ URL::assetUrl('/css/quickestimate/list.css')}}">
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/jquery.dataTables.min.js")}}"></script>
<script src="{{ URL::assetUrl("/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js")}}"></script>
<script src="{{ URL::assetUrl('/js/QuickEstimation/QuickEstList.js') }}"></script>
@endsection
