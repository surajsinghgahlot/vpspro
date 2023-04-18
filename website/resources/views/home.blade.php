@extends('layouts.loggedIn')

@section('title') Dashboard @endsection

@section('content')

<div class="row mt-4">
    <div class="col-12 col-lg-12">
        <h4 class="page-header">Hello, {{ auth::user()->name }}</h4>
    </div>
    <!-- /.col-lg-12 -->
</div>

@include('components.alert')

<!-- /.row -->
<div class="row mt-4">
        @if(auth::user()->role_id == '1')
          @foreach($instances as $ins)
               @php
                  $date2 = $ins->user->instance_expiry;
                  $date1 = date('Y-m-d');
                  $diff = abs(strtotime($date2) - strtotime($date1));
                  $years = floor($diff / (365*60*60*24));
                  $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                  $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

                  if($days > 5)
                    $color = 'panel-success';
                  elseif($days < 2 && $days >= 1 )
                    $color = 'panel-warning';
                  else
                    $color = 'panel-danger';
                  @endphp
              <div class="col-12 col-lg-3 col-md-6 col-sm-6 instances">
                  <a href="{{route('instances',['instance_id'=>$ins->id])}}">
                      <div class="card {{$color}}">
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-3">
                                      <i class="fa fa-server fa-4x"></i>
                                  </div>
                                  <div class="col-9 text-right">
                                      <div><p>{{ strtolower($ins->user->email) }}</p></div>
                                      <div>RAM {{ $ins->instancetype->memory }} GB </div>
                                      <div>{{ $ins->amititle->title ?? '' }}</div>
                                      <div>@if($days>0) Expiry on @else Expiry at @endif : {{ date('M,d Y',strtotime($ins->expiry_date)) }}</div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </a>
              </div>
          @endforeach
        @endif
        @if(auth::user()->is_change_password == '1' && auth()->user()->role_id == '2')
            @php 
              $mTime = date('H:i:s',strtotime(env('START_TIME')));
              $aTime = date('H:i:s',strtotime(env('END_TIME')));
              $bonus = false;
              $bonusDate = auth::user()->bonus_date;
              if($bonusDate && strtotime(date('Y-m-d',strtotime($bonusDate))) == strtotime(date('Y-m-d'))){
                $startTime   =  date('Y-m-d H:i:s',strtotime($bonusDate));
                $endTime     =  date('Y-m-d H:i:s',strtotime('+'. env('BONUS_TIME') .' minutes', strtotime($startTime)));
                $currentTime =  date('Y-m-d H:i:s');
                if((strtotime($startTime) < strtotime($currentTime)) && (strtotime($endTime) > strtotime($currentTime))){
                   $bonus = true;
                }
              }
            @endphp
            @if($bonus || (time() >= strtotime($mTime) && time() <= strtotime($aTime) ))
              @foreach($instances as $ins)
                 @php
                    $date1 = date_create($ins->user->instance_expiry);
                    $date2 = date_create(date('Y-m-d'));
                    $diff  = date_diff($date1,$date2);
                    $days  = $diff->format("%a");
                    if($days > 5)
                      $color = 'panel-success';
                    elseif($days < 2 && $days >= 1 )
                      $color = 'panel-warning';
                    else
                      $color = 'panel-danger';
                 @endphp
                <div class="col-12 col-lg-3 col-md-6 col-sm-6 instances">
                    <a href="{{route('instances',['instance_id'=>$ins->id])}}">
                        <div class="card {{$color}}">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-3">
                                        <i class="fa fa-server fa-4x"></i>
                                    </div>
                                    <div class="col-9 text-right">
                                        <div><p>{{ strtolower($ins->user->email) }}</p></div>
                                        <div>RAM {{ $ins->instancetype->memory }} GB </div>
                                        <div>{{ $ins->amititle->title ?? '' }}</div>
                                        <div>@if($days>0) Expiry on @else Expiry at @endif : {{ date('M,d Y',strtotime($ins->expiry_date)) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
            @endif

        @endif
    @if(auth::user()->role_id == '2' && auth::user()->is_change_password != '1')
</div>
      <section class="gr-user-details" style="margin-top: -65px">
    <div class="shadow-wrapper">
        <div class="custom-img-txt clearfix">
            <div class="row">
                 <div class="col-md-6 ">
                     <h4 class="text-danger">To get all services, Please change your default password</h4>
                     <br>
                     <form class="form" action="{{route('change.default.password')}}" method="POST">
                        @csrf
                        {{ method_field('PUT') }}
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
</section>
    @endif
</div>
@endsection
