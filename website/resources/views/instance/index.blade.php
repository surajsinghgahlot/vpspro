@extends('layouts.loggedIn')

@section('title') {{__('VPS List')}} @endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">{{__('VPS')}} ({{$data['instances']->total()}})</h3>
        @if(auth()->user()->role_id == '2')
          @if(strtotime(date('Y-m-d')) < strtotime(date('Y-m-d',strtotime(auth::user()->instance_expiry))))
           @php
              $date1 = date_create(auth()->user()->instance_expiry);
              $date2 = date_create(date('Y-m-d'));
              $diff = date_diff($date2,$date1);
              $diff->format("%a");
              $days = $diff->format("%a");
           @endphp
           @if($days < 10)
             <p>Appka VPS expire hone wala hai. Please Contact admin</p>
           @endif  
        @endif
        @endif
    </div>
</div>

@include('components.alert')

@if(auth()->user()->role_id == '1')
<div class="row">
    <div class="col-md-8">
         <form class="form-inline">
              <input type="search" class="form-control" placeholder="{{__('Search by name')}}" value="{{Request::get('search')}}" name="search" />
              <select class="form-control" name="status"><option value="">{{__('All')}}</option><option @if(Request::get('status') == '1') selected @endif value="1">{{__('Running')}}</option><option @if(Request::get('status') == '0') selected @endif value="0">{{__('STOPPED')}}</option></select>
              <a href="{{route('instances')}}" class="btn btn-default">{{__('Clear')}}</a>
              <input type="submit" class="btn yellow-btn" value="{{__('Submit')}}"/>
         </form>
    </div>
    <div class="col-md-4 text-right">
      <button class="btn btn-success btn-all-start-instance">Start All</button>
      <button class="btn btn-danger btn-all-stop-instance">Stop All</button>
    </div>
@endif
@if(auth()->user()->role_id == '2')
<div class="row">
    <div class="col-md-8">
         <form class="form-inline">
         </form>
    </div>
@endif

</div>
<br/>
<div class="row">
    <div class="col-lg-12">
         <div class="table-responsive">
             <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                 <thead>
                    <tr>
                        <th>Sr</th>
                        @if(auth()->user()->role_id == '1')
                          <th>User</th>
                        @endif
                        <th>Download VPS</th>
                        <th>GET IP</th>
                        <th>Password</th>
                        <th>RAM</th>
                        <th>Expiry</th>
                        @if(auth::user()->role_id ==1)
                            <th>Renew</th>
                        @endif
                        <th nowrap>Add Bonus</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = $data['instances']->perPage() * ($data['instances']->currentPage() - 1); @endphp
                    @foreach ($data['instances'] as $key => $value)
                        <tr class="odd gradeX">
                            <td>{{++$i}}</td>
                            @if(auth()->user()->role_id == '1')
                            <td><a href="{{route('show.user',['id'=>$value->user_id])}}">{{$value->user->name ?? '-'}}</a></td>
                            @endif
                             <td>
                                @if($value->status == '1')
                                 <a href="{{route('instance.rdp',[ 'email' => $value->user->email ])}}" download class="btn yellow-btn">Get RDP</a>
                                @endif
                             </td>
                             <td>
                                @if($value->status == '1')
                                <p class="btn btn-primary yellow-btn btn-ip" data-name="{{$value->user->email}}">Get IP</p>
                                @endif
                             </td>
                               <td>
                              @if($value->status == '1')
                                 <p class="btn yellow-btn btn-password-modal">Get Password</p>
                              @endif
                              </td>
                            <td>{{$value->instancetype->memory ?? '-'}} GB</td>
                            <td>
                              @php
                                  $date1 = date_create($value->user->instance_expiry);
                                  $date2 = date_create(date('Y-m-d'));
                                  $diff = date_diff($date1,$date2);
                              @endphp
                                  @if(strtotime(date('Y-m-d')) < strtotime(date('Y-m-d',strtotime($value->user->instance_expiry))))
                                    @if($diff->format("%a") >= 0 && $diff->format("%a") < 10)
                                       <p style="color:red">{{ $diff->format("%a") }} Day's</p>
                                    @elseif($diff->format("%a") >= 10 && $diff->format("%a") < 20 )
                                       <p style="color:orange">{{ $diff->format("%a") }} Day's</p>
                                    @else
                                       <p style="color:green">{{ $diff->format("%a") }} Day's</p>
                                    @endif
                                  @elseif(strtotime(date('Y-m-d')) == strtotime(date('Y-m-d',strtotime($value->user->instance_expiry))))
                                    <p style="color:red">0 Day</p>
                                  @else
                                    <p style="color:red"> - {{ $diff->format("%a") }} day's </p>
                                  @endif
                             </td>
                            @if(auth::user()->role_id ==1)
                                <td>
                                   <button class="btn btn-default add-instance-modal-btn" data-id="{{$value->id}}">Renew</button>
                                </td>
                            @endif
                            <td nowrap>
                              @if($value->user->bonus_date && date('Y-m-d',strtotime($value->user->bonus_date)) == date('Y-m-d'))
                                 
                                @php
                                     $bonusDate = $value->user->bonus_date;
                                     $startTime   =  date('Y-m-d H:i:s',strtotime($bonusDate));
                                     $endTime     =  date('Y-m-d H:i:s',strtotime('+' . env('BONUS_TIME') .' minutes', strtotime($startTime)));
                                     $currentTime =  date('Y-m-d H:i:s');
                                     if((strtotime($startTime) < strtotime($currentTime)) && (strtotime($endTime) > strtotime($currentTime))){
                                        $time =  round(abs(strtotime($currentTime) - strtotime($endTime)) / 60);
                                         echo '<p> Time Left : <span class="timer-count-'.$value->id.'" data-time="'.$time.'" style="color:red"> ' . $time . ' mins</span></p>';
                                         echo  '<script>
                                                     let el   = $(".timer-count-'.$value->id.'");
                                                     let time = el.attr("data-time");
                                                  setInterval(function(){
                                                       console.log(time);
                                                         time--;
                                                         if(time >= 0)
                                                           el.text(time + " mins");
                                                    },1000*60)
                                               </script>';
                                     }else{
                                       echo 'BONUS TIME OVER';
                                     }
                                  @endphp
                              @else
                               @if(auth()->user()->role_id == '1')
                               <button class="btn yellow-btn btn-add-bonus"  data-id="{{$value->user_id}}">Bonus</button>
                               @endif
                              @endif
                             </td>
                            <td class="status">
                                @if($value->status == '1')
                                  <p class="text-success">RUNNING</p>
                                @else
                                  <p class="text-danger">STOPPED</p>
                                @endif
                            </td>
                            <td>
                                  <button @if($value->status == '0') style="display:none" @endif class="btn btn-danger btn-stop" data-name="{{$value->user->email}}">STOP</button>
                                  <button @if($value->status == '1') style="display:none" @endif class="btn btn-success btn-start" data-name="{{$value->user->email}}">START</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
            <!-- Modal -->
        <div class="modal fade" id="renewInstanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="exampleModalLabel">Confirm </h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('renew.instance')}}" method="POST" id="add-instance-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value=""/>
                    <div class="modal-body p-5">
                        <p>You are sure to want to renew this VPS</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn yellow-btn">Confirm</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

                    <!-- Modal -->
        <div class="modal fade" id="addBonusModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="addBonusModalLabel">Confirm </h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('add.bonus')}}" method="POST" id="add-bonus-time-form">
                    @csrf
                    <input type="hidden" name="user_id" value="" />
                    <div class="modal-body p-5">
                        <p>You are sure to want to add {{ env('BONUS_TIME') }} mins bonus time</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn yellow-btn">Confirm</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="startAllInstanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Confirm </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('start.all.instance')}}" method="POST" id="start-all-instance-form">
                    @csrf
                     @method('PUT')
                    <div class="modal-body p-5">
                        <p>You are sure to want  to start all VPS</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Confirm</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="stopAllInstanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Confirm </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('stop.all.instance')}}" method="POST" id="stop-all-instance-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-5">
                        <p>You are sure to want to  stop all VPS</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Confirm</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

                <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Password </h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('stop.all.instance')}}" method="POST" id="add-instance-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <p>{{env('INSTACE_PASSWORD')}}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

    <div class="col-md-12 text-right">
        {{ $data['instances']->links() }}
    </div>
</div>

@endsection
@push('css')
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
   <style type="text/css">
    .preloader-wrapper #text{
 /*     display: flex;
      justify-content: center;*/
      /*background: rgba(22, 22, 22, 0.3);*/
/*      width: 100%;
      height: 100%;
      position: fixed;
      top: 51% !important;
      left: 45% !important;
      z-index: 10;
      color: #ffffff;
      align-items: center;
      margin-left: -2%;*/
    }
    .preloader-wrapper .text{
       display: none;
     }
     .loader-text{
         top: 51% !important;
         left: 45% !important;
     }
  </style>
@endpush
@push('js')
   <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js" integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
   <script>
       $('document').ready(function(){

            var myVar;

            function myFunction() {
               document.getElementById("myDiv").style.opacity = "0.5";
               myVar = setTimeout(showPage, 1000);
            }

            function showPage() {
              document.getElementById("loader").style.display = "none";
              document.getElementById("myDiv").style.display = "block";
              document.getElementById("myDiv").style.opacity = "1";
              document.getElementById("text").style.display   = "none";
            }
            
            $('.btn-add-bonus').on('click',function(e){
                 let userId = $(this).attr('data-id');
                     $('#addBonusModal input[name="user_id"]').val(userId);
                     $('#addBonusModal').modal('show');
            });

            $('.btn-password-modal').on('click',function(){
                 $(this).parents('td').text("{{ env('INSTACE_PASSWORD')}}");
            });

            $('.btn-all-start-instance').on('click',function(){
                $('#startAllInstanceModal').modal('show');
            });

             $('.btn-all-stop-instance').on('click',function(){
                $('#stopAllInstanceModal').modal('show');
            });

            $('.btn-dlt').on('click',function(e){
                $('#deleteModal').modal('show');
                $('#deleteModal input[name="id"]').val($(this).attr('data-id'));
            });

            $('.add-instance-modal-btn').on('click',function(e){
                    $('#renewInstanceModal input[name="id"]').val($(this).attr('data-id'));
                    $('#renewInstanceModal').modal('show');
            });

            $('#start-all-instance-form').on('submit',function(e){
            });

            $('#stop-all-instance-form').on('submit',function(e){
            });

             $('.btn-ip').on('click',function(e){
                    e.preventDefault();
                     let click = $(this);
                     let name = $(this).attr('data-name');
           $.ajax({
            'type':'GET',
            'url' : "{{route('get.ip')}}" + '?name=' + name,
           beforeSend: function() {
           },
           'success' : function(response){
                        if(response.status){
                             click.parents('td').text( response.message.replace(/"|'/g,''));
                         }else{
                             click.parents('td').text( response.message.replace(/"|'/g,''));
                         }
           },
           'error' : function(error){
            },
           complete: function() {
           },
           });
             });

            $('.btn-stop').on('click',function(e){
                  e.preventDefault();
                     $('#text').addClass('loader-text');
                    let click = $(this);
                    let name = $(this).attr('data-name');
          $.ajax({
                        "headers":{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
               },
            'type':'GET',
            'url' : "{{route('stop.instance')}}" + '?name=' + name,
          beforeSend: function() {
                            document.getElementById("loader").style.display = "block";
                            document.getElementById("myDiv").style.display = "none";
                            document.getElementById("text").style.display   = "block";
          },
          'success' : function(response){
                        if(response.status){
                            click.parents('td').find('.btn-stop').hide();
                            click.parents('td').find('.btn-start').show();
                            click.parents('tr').find('.status').html('<p class="text-danger">STOPPED</p>');
                            click.parents('tr').find('.btn-ip').hide();
                            toastr.success(response.message);
                            location.reload();
                        }else{
                            toastr.error(response.message);
                        }
           },
          'error' : function(error){
           },
          complete: function() {
                        document.getElementById("loader").style.display = "none";
                        document.getElementById("myDiv").style.display = "block";
                        document.getElementById("text").style.display  = "none";
          },
          });
            });

                 $('.btn-start').on('click',function(e){
                  e.preventDefault();
                    $('#text').addClass('loader-text');
                    let click = $(this);
                    let name = $(this).attr('data-name');
          $.ajax({
                        "headers":{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
               },
            'type':'GET',
            'url' : "{{route('start.instance')}}"  + '?name=' + name,
          beforeSend: function() {
              document.getElementById("loader").style.display = "block";
              document.getElementById("myDiv").style.display = "none";
              document.getElementById("text").style.display   = "block";
          },
          'success' : function(response){
                        if(response.status){
                            click.parents('td').find('.btn-stop').show();
                            click.parents('td').find('.btn-start').hide();
                            click.parents('tr').find('.status').html('<p class="text-success">RUNNING</p>');
                            click.parents('tr').find('.btn-ip').show();
                            toastr.success(response.message);
                            location.reload();
                        }else{
                            toastr.error(response.message);
                        }
           },
          'error' : function(error){
           },
          complete: function() {
              document.getElementById("loader").style.display = "none";
              document.getElementById("myDiv").style.display = "block";
              document.getElementById("text").style.display   = "none";
          },
          });
            });

       });
    
   </script>
@endpush
