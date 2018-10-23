@extends ('layouts/master_template')

@section ('content')
<div class="row">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#ProfileSection" data-toggle="tab" aria-expanded="true" title="Update Profile">
                        <i class="fa fa-user"></i>&nbsp;&nbsp;Profile
                    </a>
                </li>
                <li class="">
                    <a href="#PasswordSection" data-toggle="tab" aria-expanded="false" title="Change Password">
                        <i class="fa fa-lock"></i>&nbsp;&nbsp;Password
                    </a>
                </li>
                <li class="">
                    <a href="#EmailNPhoneSection" data-toggle="tab" aria-expanded="false" title="Contact Details Settings">
                        <i class="fa fa-phone"></i>&nbsp;&nbsp;Email & Phone
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="ProfileSection">
                    <form role="form" id="ProfileForm" method="POST" action="{{route('preferences.profile.update')}}">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="FirstName">First Name *</label>
                                <input type="text" class="form-control" name="FirstName" id="FirstName" placeholder="Ex: John" value="{{$Person->FirstName}}"/>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="MiddleName">Middle Name</label>
                                <input type="text" class="form-control" name="MiddleName" id="MiddleName" placeholder="Middle Name" value="{{$Person->MiddleName}}"/>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="LastName">Last Name *</label>
                                <input type="text" class="form-control" name="LastName" id="LastName" placeholder="Ex: Doe" value="{{$Person->LastName}}"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label>Gender</label>
                                <div>
                                    @if($Person->GenderId === 36)
                                    <input type="radio" name="Gender" value="Male" class="input-radio" id="Male" />
                                    <label for="Male" tabindex="0"></label>
                                    <label for="Male" class="text-normal cursor-pointer mr-rt-8">Male</label>
                                    <input type="radio" name="Gender" value="Female" class="input-radio" id="Female" />
                                    <label for="Female" tabindex="-1"></label>
                                    <label for="Female" class="text-normal cursor-pointer">Female</label>
                                    @elseif($Person->GenderId === 37)
                                    <input type="radio" name="Gender" value="Male" class="input-radio" checked="checked" id="Male" />
                                    <label for="Male" tabindex="0"></label>
                                    <label for="Male" class="text-normal cursor-pointer mr-rt-8">Male</label>
                                    <input type="radio" name="Gender" value="Female" class="input-radio" id="Female"/>
                                    <label for="Female" tabindex="-1"></label>
                                    <label for="Female" class="text-normal cursor-pointer">Female</label>
                                    @elseif($Person->GenderId === 38)
                                    <input type="radio" name="Gender" value="Male" class="input-radio" id="Male"/>
                                    <label for="Male" tabindex="0"></label>
                                    <label for="Male" class="text-normal cursor-pointer mr-rt-8">Male</label>
                                    <input type="radio" name="Gender" value="Female" class="input-radio" checked="checked" id="Female" />
                                    <label for="Female" tabindex="-1"></label>
                                    <label for="Female" class="text-normal cursor-pointer">Female</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="DateOfBirth" class="no-text-transform">Date of Birth</label>
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control date-picker" name="DateOfBirth" id="DateOfBirth" placeholder="Ex: 01-Jan-1994" value="{{$Person->DateOfBirth}}" readonly="true"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="ProfilePicture">Profile Picture</label>
                                <div class="input-group">
                                    <label class="input-group-addon" for="ProfilePicture">
                                        <i class="fa fa-picture-o"></i>
                                    </label>
                                    <label class="form-control" for="ProfilePicture">
                                        <span class="placeholder-text text-normal no-text-transform" id="ProfilePictureAlias">Ex: gravatar.png</span>
                                    </label>
                                </div>
                                <input type="file" class="hidden" name="ProfilePicture" id="ProfilePicture"/>
                            </div>
                        </div>
                        <div class="row">
                            <p class="col-md-4">
                                <input type="submit" id="ProfileFormSubmit" class="btn btn-primary button-custom" value="Update"/>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="tab-pane" id="PasswordSection">
                    <form role="form" id="PasswordForm" method="POST" action="{{route('preferences.password.update')}}">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="Password">Current Password</label>
                                <input type="password" class="form-control" name="Password" id="Password" placeholder="Current Password"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="NewPassword">New Password</label>
                                <input type="password" class="form-control" name="NewPassword" id="NewPassword" placeholder="New Password"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="ConfirmPassword">Confirm Password</label>
                                <input type="password" class="form-control" name="ConfirmPassword" id="ConfirmPassword" placeholder="Confirm New Password"/>
                            </div>
                        </div>
                        <div class="row">
                            <p class="col-md-4">
                                <input type="submit" name="PasswordFormSubmit" id="PasswordFormSubmit" class="btn btn-primary button-custom" value="Update"/>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="tab-pane" id="EmailNPhoneSection" >
                    <div id="ContactDetailsSection" class="mr-tp-10" v-cloak>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label >Primary Phone</label>
                                <p>{{auth()->user()->Phone}}</p>
                            </div>
                            <div class="form-group col-md-4">
                                <label >Primary Email</label>
                                <p>{{auth()->user()->Email}}</p>
                            </div>
                        </div>
                        <form role="form" id="EmailNPhoneForm" method="POST" action="{{route('preferences.emailnphone.update')}}">
                            <div class="row mr-bt-14">
                                <div class="col-md-3">
                                    <h4 class="mr-tp-6">Alternative Email 
                                        <span data-toggle="tooltip" title="" data-original-title="Add Email" v-on:click="addEmailField" :class="{ hidden : filterEmailFields.length == EmailFields}">
                                              <i aria-hidden="true" class="fa fa-plus-circle add-icon mr-lt-12 cursor-pointer" ></i>
                                        </span>
                                    </h4>
                                </div>
                            </div>
                            <div class="row" v-for="(Email, key) in filterEmailFields" id="EmailSection">
                                <div class="form-group col-md-3">
                                    <label>Type</label>
                                    <select class="type form-control email-dropdown" :name="'EmailTypeId_'+key" :id="'EmailTypeId_'+key" v-model="Email.EmailTypeId" >
                                        <option value="">Select Type</option>
                                        <option v-for="(Categorie, Key) in Categories" :value="Categorie.Id" :selected="Email.EmailTypeId == Categorie.Id">@{{Categorie.Name}}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Email</label>
                                    <input type="text" class="form-control alt-email" :name="'Email_'+key" :id="'Email_'+key" placeholder="Ex: John" v-model="Email.Email"/>
                                </div>
                                <input type="hidden" :name="'EmailId_'+key" :id="'EmailId_'+key" :value="Email.Id">
                                <div class="col-md-1">
                                    <span data-toggle="tooltip" title="" data-original-title="Delete Email" >
                                        <i aria-hidden="true" class="fa fa-minus-circle cursor-pointer delete-icon" v-on:click="deleteEmailField(Email.Id, key)"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="row mr-bt-14 mr-tp-10">
                                <div class="col-md-6">
                                    <h4 class="mr-tp-4">Alternative Phone Number
                                        <span data-toggle="tooltip" title="" data-original-title="Add Phone" :class="{ hidden : filterPhoneFields.length == PhoneFields}" v-on:click="addPhoneField">
                                              <i aria-hidden="true" class="fa fa-plus-circle add-icon mr-lt-12 cursor-pointer" ></i>
                                        </span>
                                    </h4>
                                </div>
                            </div>
                            <div class="row" v-for="(Phone, key) in filterPhoneFields" id="PhoneSection">
                                <div class="form-group col-md-3">
                                    <label>Type</label>
                                    <select class="type form-control phone-dropdown" :name="'PhoneTypeId_'+key" :id="'PhoneTypeId_'+key" v-model="Phone.PhoneTypeId">
                                        <option value="">Select Type</option>
                                        <option v-for="Categorie in Categories" :value="Categorie.Id" :selected="Phone.PhoneTypeId == Categorie.Id">@{{Categorie.Name}}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="">Phone</label>
                                    <input type="text" class="form-control alt-phone" :name="'Phone_'+key" :id="'Phone_'+key" placeholder="Ex: John" v-model="Phone.Phone"/>
                                </div>
                                <input type="hidden" :name="'PhoneId_'+key" :id="'PhoneId_'+key" :value="Phone.Id">
                                <div class="col-md-1">
                                    <span data-toggle="tooltip" title="" data-original-title="Delete Phone" >
                                          <i aria-hidden="true" class="fa fa-minus-circle cursor-pointer delete-icon " v-on:click="deletePhoneField(Phone.Id, key)"></i>
                                    </span>
                                </div>
                            </div>
                            <input type="hidden" name="TotalPhone" :value="filterPhoneFields.length">
                            <input type="hidden" name="TotalEmail" :value="filterEmailFields.length">
                            <div class="row mr-tp-10">
                                <p class="col-md-4">
                                    <input type="submit" name="EmailNPhoneFormSubmit" id="EmailNPhoneFormSubmit" class="btn btn-primary button-custom" value="Update"/>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="form-overlay hidden" id="PageLoader">
                <div class="large loader"></div>
                <div class="loader-text">Updating data...</div>
            </div>
            <div class="form-loader hidden" id="PreferencesPageLoader"></div>
        </div>
        <div id="NotificationArea"></div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link href="{{ asset('plugins/datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet"/>
<link href="{{ asset('css/preferences/preferences.css') }}" rel="stylesheet">
@endsection

@section('dynamicScripts')
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('plugins/datepicker/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('js/preferences/preferences.js') }}"></script>
<script src="{{ asset('js/preferences/password.js') }}"></script>
<script src="{{ asset('js/preferences/profile.js') }}"></script>
<script src="{{ asset('js/preferences/emailnphone.js') }}"></script>
@endsection
