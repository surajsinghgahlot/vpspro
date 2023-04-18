@extends('layouts.loggedIn')

@section('title') {{__('Create User')}} @endsection

@section('content')
<section class="gr-user-details">
     @include('components.alert')
     <div class="row">
        <div class="col-12">
            <div class="shadow-wrapper">
                <div class="form-wrapper">
                <form class="form" action="{{route('create.store')}}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6">
                            <div class="form-group">
                            <label>Name*</label>
                            <input type="text" name="name" placeholder="Full name" value="{{old('name')}}" class="form-control"/>
                              @if ($errors->has('name'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('name') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div>
                        <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6">
                            <div class="form-group">
                            <label>Email*</label>
                            <input type="text" name="email" placeholder="Email" value="{{old('email')}}" class="form-control"/>
                              @if ($errors->has('email'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('email') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div>   
{{--                         <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6">
                            <div class="form-group">
                            <label>Phone*</label>
                            <input type="text" name="phone" placeholder="Phone" value="{{old('phone')}}" class="form-control"/>
                              @if ($errors->has('phone'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('phone') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div> --}}
                        <div class="col-8 col-lg-8 col-md-8 col-sm-12 col-xl-8">
                            <div class="form-group">
                            <label>Password*</label>
                            <input type="text" name="password" placeholder="Password" value="" class="form-control"/>
                              @if ($errors->has('password'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('password') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div>
                         <div class="col-md-4">
                              <button style="margin-left:-14px;margin-top: 25px;" class="btn btn-generate">Generate</button>
                         </div>
                        <div class="col-md-12">
                            <br>
                            <input type="submit" value="Create" class="btn yellow-btn"/>          
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
     </div>
</section>

@include('components.backBtn')
@endsection
@push('js')
   <script>
       $('document').ready(function(){

          $('.btn-generate').on('click',function(e){
              e.preventDefault();
              $('input[name="password"]').val(Math.random().toString(36).slice(-8));
          });

       });
   </script>
@endpush
