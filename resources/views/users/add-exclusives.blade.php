@extends('layouts.app')

@section('title', 'Add Exclusives')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">CMS</li>
                <li class="breadcrumb-item active">Add Exclusives</li>
            </ol>
        </div>
    </div>
    
    @if(Session::has('success'))
        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
            {{ Session::get('success') }}
            @php
                Session::forget('success');
            @endphp
        </p>
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Add Exclusives</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('add-exclusives-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="form-group">
                                <label for="name">Menu Name</label>
                                <input type="text" class="form-control" name="title" placeholder="menu Name" required>
                                @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="name">Url</label>
                                <input type="text" class="form-control" name="url" placeholder="Url" required>
                                @if ($errors->has('url'))
                                <span class="text-danger">{{ $errors->first('url') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="name">Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                @if ($errors->has('status'))
                                <span class="text-danger">{{ $errors->first('status') }}</span>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary ">Submit</button>
                            <a href="{{url('manage-exclusives')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                        </div>
                    </div>
                    
                </form>
            </div>                
        </div>
    </div>
</div>
@endsection