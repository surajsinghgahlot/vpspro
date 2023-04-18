@extends('layouts.app')

@section('content')
<style type="text/css">
    .navbar
    {
        display: none;
    }
</style>
<div class="container bg-blue">
    <div class="row h-545">
        <div class="col-md-5 d-flex align-items-center br-8-left" style="background-image: url('images/Mask Group 28.png'">
            <div>
                <div class="text-center mb-4">
                  <img src="images/vpssssss-01.png" class="w-100 img-fluid">
            </div>
            <!-- <h1 class="text-white text-center">VPS <span>Pro</span></h1> -->
            </div>
            
              
        </div>
        <div class="col-md-7 bg-white px-5 py-5 br-8-right">
            <h2 class="mb-5">Login</h2>
            @if(Session::get('message'))
               <div class="alert" style="color:red;"><p>{{Session::get('message')}}</p></div>
            @endif
             <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group ">
                            <label for="email" class="col-form-label ">{{ __('E-Mail Address') }}</label>

                            <div class="">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="col-form-label ">{{ __('Password') }}</label>

                            <div class="">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group  mb-0">
                            <div class="">
                                <button type="submit" class="btn btn-yellow btn-block">
                                    {{ __('Login') }}
                                </button>
                            </div>
                        </div>
                    </form>
            <!-- <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                   
                </div>
            </div> -->
        </div>
    </div>
</div>
@endsection
