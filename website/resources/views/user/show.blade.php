@extends('layouts.loggedIn')

@section('title') {{__('User')}} | {{$data['user']->name}} @endsection

@section('content')
<section class="gr-user-details">
     @include('components.alert')
    <div class="shadow-wrapper">
        <div class="custom-description">
            <h2>{{__('User Details')}}</h2>
            <p><span>{{__('Name')}}:</span> {{$data['user']->name}}</p>
            <p><span>{{__('Email')}}:</span> {{$data['user']->email}}</p>
{{--             <p><span>{{__('Phone')}}:</span> {{$data['user']->phone}}</p> --}}
            <p><span>{{__('Created Date')}}:</span> {{date('d-M-Y',strtotime($data['user']->created_at))}}</p>
        </div>
        <br/>
        <div class="btn-wrapper">
            <button class="btn btn-dlt btn-danger">{{__('Delete')}}</button>
            @if($data['user']->is_active == '1')
            <button class="btn btn-deactive yellow-btn">{{__('Deactive')}}</button>
            @else
            <button class="btn btn-active yellow-btn">{{__('Active')}}</button>
            @endif
        </div>
    </div>
</section>
<section class="gr-user-details" style="margin-top: -65px">
    <div class="shadow-wrapper">
        <div class="custom-img-txt clearfix">
            <div class="row">
                 <div class="col-md-6 ">
                     <form class="form" action="{{route('reset.password',$data['user']->id)}}" method="POST">
                        @csrf
                        {{ method_field('PUT') }}
                           <div class="row">
                             <div class="col-md-8">
                                <div class="form-group">
                                  <label>Reset Password</label>
                                  <input type="text" value="" name="password" placeholder="Password" class="form-control rest-password"/>
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
                           </div>
                          <input type="submit" class="btn yellow-btn" value="Reset"/>
                     </form>
                 </div>
            </div>
        </div>
    </div>
</section>

 <!-- Modal -->
 <div class="modal fade" id="deleteModal" role="dialog">
    <div class="modal-dialog modal-md">
        <form class="form" action="{{route('delete.user')}}" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">{{__('Delete user')}}</h4>
        </div>
        <div class="modal-body">
                @csrf
                {{ method_field('DELETE') }}
                 <input type="hidden" name="id" value="{{$data['user']->id}}">
{{--                  <div class="form-group">
                      <textarea class="form-control" name="reason" placeholder="Why are you delete this user account?(Optional)"></textarea>
                 </div>
                 <label><input type="checkbox" name="is_notify" checked value="1"/>&nbsp;&nbsp;&nbsp;{{__('Notify to user')}}</label> --}}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{__('Cancel')}}</button>
          <input type="submit" class="btn btn-danger" value="{{__('Delete')}}" />
        </div>
      </div>
    </form>
    </div>
  </div>
</div>

 <!-- Modal -->
 <div class="modal fade" id="deactiveModal" role="dialog">
    <div class="modal-dialog modal-md">
        <form class="form" action="{{route('deactive.user')}}" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">{{__('Deactive user')}}</h4>
        </div>
        <div class="modal-body">
                @csrf
                {{ method_field('PUT') }}
                 <input type="hidden" name="id" value="{{$data['user']->id}}">
                 <div class="form-group">
                      <textarea class="form-control" name="reason" placeholder="{{__('Why are you deactive this user account?(Optional)')}}"></textarea>
                 </div>
{{--                  <label><input type="checkbox" name="is_notify" checked value="1"/>&nbsp;&nbsp;&nbsp;{{__('Notify to user')}}</label> --}}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{__('Cancel')}}</button>
          <input type="submit" class="btn yellow-btn" value="{{__('Deactive')}}" />
        </div>
      </div>
    </form>
    </div>
  </div>

   <!-- Modal -->
 <div class="modal fade" id="activeModal" role="dialog">
    <div class="modal-dialog modal-md">
        <form class="form" action="{{route('active.user')}}" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">{{__('Active user')}}</h4>
        </div>
        <div class="modal-body">
                @csrf
                {{ method_field('PUT') }}
                 <input type="hidden" name="id" value="{{$data['user']->id}}">
{{--                  <label><input type="checkbox" name="is_notify" checked value="1"/>&nbsp;&nbsp;&nbsp;{{__('Notify to user')}}</label> --}}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{__('Cancel')}}</button>
          <input type="submit" class="btn yellow-btn" value="{{__('Active')}}" />
        </div>
      </div>
    </form>
    </div>
  </div>

@include('components.backBtn')
@endsection
@push('js')
   <script>
       $('document').ready(function(){
          $('.btn-dlt').on('click',function(e){
              $('#deleteModal').modal('show');
          });
          
          $('.btn-deactive').on('click',function(e){
              $('#deactiveModal').modal('show');
          });

          $('.btn-active').on('click',function(e){
              $('#activeModal').modal('show');
          });

          $('.btn-generate').on('click',function(e){
              e.preventDefault();
              $('.rest-password').val(Math.random().toString(36).slice(-8));
          });

       });
   </script>
@endpush
