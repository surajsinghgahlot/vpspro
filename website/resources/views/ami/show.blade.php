@extends('layouts.loggedIn')

@section('title') {{__('Update AMI')}} @endsection

@section('content')
<section class="gr-user-details">
     @include('components.alert')
     <div class="row">
        <div class="col-12">
            <div class="shadow-wrapper">
                <div class="form-wrapper">
                <form class="form" action="{{route('update.ami',$ami->id)}}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6">
                            <div class="form-group">
                            <label>Name*</label>
                            <input type="text" name="name" placeholder="Name" value="{{old('title') ?? $ami->title }}" class="form-control"/>
                              @if ($errors->has('name'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('name') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div>
                         <div class="col-12 col-lg-6 col-md-6 col-sm-12 col-xl-6">
                            <div class="form-group">
                            <label>AMI ID*</label>
                            <input type="text" name="ami_id" placeholder="AMI ID" value="{{old('value') ?? $ami->value }}" class="form-control"/>
                              @if ($errors->has('ami_id'))
                              <span class="invalid-feedback text-error" role="alert">
                                 <strong>{{ $errors->first('ami_id') }}</strong>
                              </span>
                               @endif
                               </div>
                        </div>
                        <div class="col-md-12">
                            <br>
                            <input type="submit" value="Update" class="btn yellow-btn"/>          
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
@push('css')
  <style type="text/css">
    .invalid-feedback{
      display: block;
    }
  </style>
@endpush