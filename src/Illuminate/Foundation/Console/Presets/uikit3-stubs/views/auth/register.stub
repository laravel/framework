@extends('layouts.app')

@section('content')
    <div class="uk-section">
        <div class="uk-container uk-container-center">

            <div class="uk-width-1-2@m uk-align-center">

                <div class="uk-padding uk-box-shadow-large">

                    <h2>Register</h2>

                    <form class="uk-form-stacked" role="form" method="POST" = action="{{ route('register') }}">

                        {{ csrf_field() }}

                        <div>
                            <label class="uk-form-label">Name</label>
                            <input id="name" type="text" class="uk-input{{ $errors->has('name') ? ' uk-form-danger' : '' }}" name="name" value="{{ old('name') }}" required autofocus>

                            @if ($errors->has('name'))
                                <div class="uk-alert-danger" uk-alert>
                                    {{ $errors->first('name') }}
                                </div>
                            @endif
                        </div>

                        <div class="uk-margin">
                            <label class="uk-form-label">Email Address</label>
                            <input id="email" type="email" class="uk-input{{ $errors->has('email') ? ' uk-form-danger' : '' }}" name="email" value="{{ old('email') }}" required>

                            @if ($errors->has('email'))
                                <div class="uk-alert-danger" uk-alert>
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>

                        <div class="uk-margin">
                            <label class="uk-form-label">Password</label>
                            <input id="password" type="password" class="uk-input{{ $errors->has('password') ? ' uk-form-danger' : '' }}" name="password" value="{{ old('password') }}" required>

                            @if ($errors->has('password'))
                                <div class="uk-alert-danger" uk-alert>
                                    {{ $errors->first('password') }}
                                </div>
                            @endif
                        </div>

                        <div class="uk-margin">
                            <label class="uk-form-label">Confirm Password</label>
                            <input id="password_confirmation" type="password" class="uk-input{{ $errors->has('password_confirmation') ? ' uk-form-danger' : '' }}" name="password_confirmation" value="{{ old('password_confirmation') }}" required>

                            @if ($errors->has('password_confirmation'))
                                <div class="uk-alert-danger" uk-alert>
                                    {{ $errors->first('password_confirmation') }}
                                </div>
                            @endif
                        </div>

                        <div class="uk-margin">
                            <button class="uk-button uk-button-primary" type="submit" name="button">Register</button>
                            <a class="uk-float-right" href="{{ route('login') }}">
                                Already have an account?
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection
