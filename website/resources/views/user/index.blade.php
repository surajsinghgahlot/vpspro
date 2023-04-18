@extends('layouts.loggedIn')

@section('title') {{__('User List')}} @endsection

@section('content')

<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">{{__('Users')}} ({{$data['users']->total()}})</h3>
    </div>
</div>

@include('components.alert')


<div class="row">
    <div class="col-md-8">
         <form class="form-inline">
              <input type="search" class="form-control" placeholder="{{__('Search by name,email')}}" value="{{Request::get('search')}}" name="search" />
              <select class="form-control" name="status"><option value="">{{__('All')}}</option><option @if(Request::get('status') == 'active') selected @endif value="active">{{__('Active')}}</option><option @if(Request::get('status') == 'deactive') selected @endif value="deactive">{{__('Deactive')}}</option></select>
              <div class="mt-2">
                 <a href="{{route('index.user')}}" class="btn btn-default">{{__('Clear')}}</a>
              <input type="submit" class="btn yellow-btn" value="{{__('Submit')}}"/> 
              </div>
              
         </form>
    </div>
    <div class="col-md-4" style="text-align:right">
       <a href="{{route('create.user')}}" class="btn yellow-btn">Create User</a>
    </div>
</div>
<br/>
<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                <thead>
                    <tr>
                        <th>{{__('Sr')}}.</th>
                        <th>{{__('Name')}}</th>
{{--                         <th>{{__('Phone')}}</th> --}}
                        <th>{{__('Email')}}</th>
                        <th>{{__('Instance')}}</th>
                        <th>{{__('Status')}}</th>
                        <th>{{__('Action')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = $data['users']->perPage() * ($data['users']->currentPage() - 1); @endphp
                    @foreach ($data['users'] as $key => $value)
                        <tr class="odd gradeX">
                            <td>{{++$i}}</td>
                            <td>{{$value->name ?? '-'}}</td>
{{--                             <td>{{$value->phone ?? '-'}}</td> --}}
                            <td class="center">{{$value->email ?? '-'}}</td>
                            <td class="center">
                            	@if($value->totalInstance($value->email) > 0)
                            	<button class="btn-danger remove-instance-modal-btn" data-id="{{$value->email}}"><i class="fa fa-minus"></i></button>
                            	@else
                            	<button class=" btn-success add-instance-modal-btn" data-id="{{$value->id}}"><i class="fa fa-plus"></i></button>
                            	@endif
                            </td>
                            <td class="center">{{$value->is_active == '1' ? __('Active') : __('Deactive') }}</td>
                            <td>
                                <a href="{{route('show.user',$value->id)}}" class="btn btn-sm yellow-btn">{{__('Info')}}</a>
                                <button class="btn btn-sm btn-danger btn-dlt" data-id="{{$value->id}}">{{__('Delete')}}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


            <!-- Modal -->
        <div class="modal fade" id="addInstanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="exampleModalLabel">Add AMI </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
             <form action="{{route('create.instance')}}" method="POST" id="add-instance-form">
                @csrf
                <input type="hidden" name="id" value=""/>
                <div class="modal-body">
                    <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>AMI</label>
                                <select name="ami" value="" class="form-control">
                                    <option value="">Select AMI</option>
                                    @foreach($amis as $key => $ami)s
                                    <option value="{{$ami->value}}">{{$ami->title}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('ami'))
                                <span class="invalid-feedback text-error" role="alert">
                                    <strong>{{ $errors->first('phone') }}</strong>
                                </span>
                                @endif
                        </div>
                            
                    </div>
                        <div class="col-md-12">
                            <div class="form-group">
                            <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($instanceTypes as $instance)
                                    <option value="{{$instance->type}}">{{$instance->memory}} GB RAM</option>
                                    @endforeach                            
                                </select>
                                @if ($errors->has('type'))
                                <span class="invalid-feedback text-error" role="alert">
                                    <strong>{{ $errors->first('type') }}</strong>
                                </span>
                                @endif
                                </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn yellow-btn">Add</button>
                </div>
             </form>
            </div>
        </div>
        </div>

                    <!-- Modal -->
        <div class="modal fade" id="removeInstanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="exampleModalLabel">Delete Instance </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
             <form action="{{route('remove.instance')}}" method="POST" id="remove-instance-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="id" value=""/>
                <div class="modal-body">
                    <div class="row">
                      <div class="col-md-12">
                      	  <h3>Are you sure want to delete this instance?</h5>
                      </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn yellow-btn">Confirm</button>
                </div>
             </form>
            </div>
        </div>
        </div>

    <div class="col-md-12 text-right">
        {{ $data['users']->links() }}
    </div>
</div>

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
                 <input type="hidden" name="id" value="">
                {{--  <div class="form-group">
                      <textarea class="form-control" name="reason" placeholder="{{__('Why are you delete this user account?(Optional)')}}"></textarea>
                 </div> --}}
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

@endsection
@push('css')
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush
@push('js')
   <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
   <script>
       $('document').ready(function(){
            
            $('.btn-dlt').on('click',function(e){
                $('#deleteModal').modal('show');
                $('#deleteModal input[name="id"]').val($(this).attr('data-id'));
            });
            $('.add-instance-modal-btn').on('click',function(e){
                    $('#addInstanceModal input[name="id"]').val($(this).attr('data-id'));
                    $('#addInstanceModal').modal('show');
            });
            $('.remove-instance-modal-btn').on('click',function(e){
                    $('#removeInstanceModal input[name="id"]').val($(this).attr('data-id'));
                    $('#removeInstanceModal').modal('show');
            });
            $('#add-instance-form').on('submit',function(e){
                 	e.preventDefault();
                    let form  = $(this);
                    let data = form.serialize();
					$.ajax({
						"headers":{
						'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
					},
						'type':'POST',
						'url' : "{{route('create.instance')}}",
                        'data' : data,
					beforeSend: function() {
                        document.getElementById("loader").style.display = "block";
  document.getElementById("myDiv").style.display = "none";
					},
					'success' : function(response){
                          if(response.status)
                              toastr.success(response.message);
                          else
                              toastr.error(response.message);
                          $('#addInstanceModal').modal('hide');
 					 },
					'error' : function(error){
					 },
					complete: function() {
                         document.getElementById("loader").style.display = "none";
  document.getElementById("myDiv").style.display = "block";

					},
					});
            });

       });
    
   </script>
@endpush
