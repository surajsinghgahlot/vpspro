@extends('layouts.loggedIn')

@section('title') Profile | {{ auth::user()->name}} @endsection

@section('content')

<section class="gr-user-details">
    @include('components.alert')
    <div >
        <div class="custom-img-txt clearfix">
            <div class="row">
                 <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6 ">
                     <div class="shadow-wrapper">
                        <h3 class="mb-5">User Profile</h3>
                     <form class="form" action="{{route('update.profile')}}" method="POST">
                        @csrf
                        {{ method_field('PUT') }}
                          <div class="form-group">
                              <label>Name</label>
                              <input type="name" value="{{old('name') ?? auth::user()->name}}" name="name" placeholder="Name" class="form-control"/>
                              @if ($errors->has('name'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('name') }}</strong>
                              </span>
                               @endif
                            </div>
                          @if(auth::user()->role_id == '1')
                          <div class="form-group">
                              <label>Email</label>
                              <input type="email" value="{{old('email') ?? auth::user()->email}}" name="email" placeholder="Email" class="form-control"/>
                                @if ($errors->has('email'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('email') }}</strong>
                              </span>
                               @endif
                            </div>
                          @else
                            <div class="form-group">
                              <label>Email</label>
                              <input type="email" value="{{old('email') ?? auth::user()->email}}" name="email" placeholder="Email" class="form-control" readonly/>
                                @if ($errors->has('email'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('email') }}</strong>
                              </span>
                               @endif
                            </div>
                          @endif
{{--                           <div class="form-group">
                              <label>Phone</label>
                              <input type="phone" value="{{old('phone') ?? auth::user()->phone}}" name="phone" placeholder="Phone" class="form-control"/>
                              @if ($errors->has('phone'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('phone') }}</strong>
                              </span>
                              @endif
                            </div> --}}
                          <input type="submit" class="btn yellow-btn" value="Update"/>
                     </form>
                 </div>
                 </div>
                 <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6 ">
                    <div class="shadow-wrapper">
                        <h3 class="mb-5">Change Password</h3>
                     <form class="form" action="{{route('change.password')}}" method="POST">
                        @csrf
                        {{ method_field('PUT') }}
                          <div class="form-group">
                              <label>Old Password</label>
                              <input type="password" value="" name="old_password" placeholder="Old Password" class="form-control"/>
                              @if ($errors->has('old_password'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('old_password') }}</strong>
                              </span>
                               @endif
                            </div>
                          <div class="form-group">
                              <label>New Password</label>
                              <input type="password" value="" name="new_password" placeholder="New Password" class="form-control"/>
                                @if ($errors->has('new_password'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('new_password') }}</strong>
                              </span>
                               @endif
                            </div>
                          <div class="form-group">
                              <label>Confirm Password</label>
                              <input type="password" value="" placeholder="Confirm password" name="confirm_password" class="form-control"/>
                              @if ($errors->has('confirm_password'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('confirm_password') }}</strong>
                              </span>
                              @endif
                            </div>
                          <input type="submit" class="btn yellow-btn" value="Update"/>
                     </form>
                     </div>
                 </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('js')
   <script>
       $('.btn-dlt').on('click',function(e){
          $('#deleteModal').modal('show');
       });
       $('.btn-deactive').on('click',function(e){
          $('#deactiveModal').modal('show');
       });
       $('.btn-active').on('click',function(e){
          $('#activeModal').modal('show');
       });
   </script>
@endpush
