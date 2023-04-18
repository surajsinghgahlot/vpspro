@extends('layouts.loggedIn')

@section('title') {{__('User List')}} @endsection

@section('content')

<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header">{{__('AMIS')}} ({{ $amis->total() }})</h3>
    </div>
</div>

@include('components.alert')


<div class="row">
    <div class="col-md-8">
    </div>
    <div class="col-md-4" style="text-align:right">
       <a href="{{route('create.ami')}}" class="btn yellow-btn">Add AMI</a>
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
                        <th>{{__('AMI ID')}}</th>
                        <th>{{__('Action')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = $amis->perPage() * ($amis->currentPage() - 1); @endphp
                    @foreach ($amis as $key => $value)
                        <tr class="odd gradeX">
                            <td>{{++$i}}</td>
                            <td>{{$value->title ?? '-'}}</td>
                            <td>{{$value->value ?? '-'}}</td>
                            <td>
                                <a href="{{route('edit.ami',$value->id)}}" class="btn btn-sm yellow-btn">{{__('Edit')}}</a>
                                <button class="btn btn-sm btn-danger btn-dlt" data-id="{{$value->id}}">{{__('Delete')}}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

            <!-- Modal -->
        <div class="modal fade" id="addAMIModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label>AMI Title</label>
                                <input type="text" name="title" class="form-control" required>
                                @if ($errors->has('title'))
                                <span class="invalid-feedback text-error" role="alert">
                                    <strong>{{ $errors->first('title') }}</strong>
                                </span>
                                @endif
                        </div>
                    </div>
                      <div class="col-12">
                        <div class="form-group">
                            <label>AMI Value</label>
                                <input type="text" name="value" class="form-control" required>
                                @if ($errors->has('value'))
                                <span class="invalid-feedback text-error" role="alert">
                                    <strong>{{ $errors->first('value') }}</strong>
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
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="exampleModalLabel">Delete AMI </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
             <form action="{{route('destroy.ami')}}" method="POST" id="remove-instance-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="id" value=""/>
                <div class="modal-body">
                    <div class="row">
                      <div class="col-md-12">
                      	  <h3>Are you sure want to delete this AMI?</h5>
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
        {{ $amis->links() }}
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
       });
   </script>
@endpush
