@extends('layouts.app')

@section('content')
    <div class="uk-section">
        <div class="uk-container uk-container-center">

            <div class="uk-width-1-2@m uk-align-center">

                <div class="uk-padding uk-box-shadow-large">

                    <h2>Reset Password</h2>

                    @if (session('status'))
                        <div class="uk-alert-primary" uk-alert>
                            {{ session('status') }}
                        </div>
                    @endif

                    <form class="uk-form-stacked" role="form" method="POST" = action="{{ route('password.email') }}">

                        {{ csrf_field() }}

                        <div>
                            <label class="uk-form-label">Email Address</label>
                            <input id="email" type="email" class="uk-input{{ $errors->has('email') ? ' uk-form-danger' : '' }}" name="email" value="{{ old('email') }}" required autofocus>

                            @if ($errors->has('email'))
                                <div class="uk-alert-danger" uk-alert>
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>

                        <div class="uk-margin">
                            <button class="uk-button uk-button-primary" type="submit" name="button">Send Password Reset Link</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection
