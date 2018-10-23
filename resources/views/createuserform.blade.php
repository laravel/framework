@extends('layouts/master_template')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form id="CreateCustomerForm" method="POST" action="{{route('createuser')}}">
                        <div class="row">
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="FirstName">First Name</label>
                                <input type="text" name="FirstName" id="FirstName" class="form-control" placeholder="Ex: John" />
                            </div>
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="LastName">Last Name</label>
                                <input type="text" name="LastName" id="LastName" class="form-control" placeholder="Ex: Doe" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="MobileNumber">Mobile Number</label>
                                <input type="text" name="MobileNumber" id="MobileNumber" class="form-control" placeholder="Ex: (898) 989-9898" value="{{$Mobile ?? ''}}" />
                            </div>
                            <div class="form-group col-md-4 col-sm-5">
                                <label for="EmailAddress">Email Address</label>
                                <input type="email" name="EmailAddress" id="EmailAddress" class="form-control" placeholder="Ex: user@example.com" value="{{$Email ?? ''}}" />
                            </div>                            
                        </div>
                        <div class="row">
                            <div class="form-group col-md-8 col-sm-8">
                                <button type="submit" class="btn btn-primary" id="FormSubmit">Create</button>
                                <button type="reset" class="btn btn-danger" id="FormReset">Clear</button>
                            </div>
                        </div>
                    </form>
                    <div id="CalloutsArea"></div>
                </div>
                <div class="form-loader hidden" id="CreateCustomerFormLoader">Creating Customer...</div>
            </div>
            <div id="NotificationArea"></div>
        </div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/CreateCustomer.js') }}"></script>
@endsection
