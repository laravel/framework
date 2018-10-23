@extends('layouts/master_template')

@section('content')
<div class="row">
    <div class="col-md-12 text-right custom-info-block top-header-user-info">
        <span class="pd-5 text-capitalize user-info">
            <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerFullName }}
        </span>
        <span class="pd-5 user-info">
            <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerMobile }}
        </span>
        <span class="pd-5 user-info"> 
            <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
            {{ $CustomerEmail }}
        </span>
        <span class="pd-5 user-info">             
            <i class="fa fa-globe text-info" aria-hidden="true"></i>&nbsp;{{ $CustomerCity }}
        </span>
    </div>
    <div class="col-md-12">
        @if(isset($PricePackages))
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                @foreach($PricePackages as $key => $value)
                <li class="{{$loop->first?'active':''}}"><a href="#tab_{{$key}}" data-toggle="tab" aria-expanded="false">{{$value}}</a></li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach($PricePackages as $key => $value)
                <div class="tab-pane {{$loop->first?'active':''}}" id="tab_{{$key}}">
                    <div class="row">
                        <div class="col-md-12 chart-container">
                            <canvas id="Room_{{$key}}" width="800" height="300"></canvas>
                        </div>
                    </div>
                    <div class="row mr-tp-15">
                        <div class="col-md-8 chart-container">
                            <canvas id="Category_{{$key}}"></canvas>
                        </div>
                        <div class="col-md-4 chart-container">
                            <canvas id="PaymentBy_{{$key}}"></canvas>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <!-- /.tab-content -->
        </div>
        @else
        <div class="callout callout-info mr-tp-6 mr-bt-6" >
            <p>No PricePackages Found.</p>
        </div>
        @endif
    </div>
    <div id="NotificationArea"></div>
</div>
@endsection

@section('dynamicStyles')
<style>
    .chart-container {
      position: relative;
      margin: auto;
    }
</style>
@endsection

@section('dynamicScripts')
<!-- ChartJS 2.5.0 -->
<script src="{{ asset('/js/quick-estimates/statistics/Chart.min.js') }}"></script>
<script src="{{ asset('/js/quick-estimates/statistics/statistics.js') }}"></script>
@endsection
