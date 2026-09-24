@extends('layouts.app')

@section('title','Merchandise Categories')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Merchandise</li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <a href="{{ url('add-edit-categories') }}" class="btn btn-info"><i class="fa fa-plus"></i> Add Category</a>
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
            <div class="white-box">
                @foreach($ParentCategory as $category)
                <ul>
                    <li class="category">
                        {{ $category->category_name }}
                        @if(Auth::user()->access_type == 'superadmin')
                        &nbsp;&nbsp;<a href="{{ url('add-edit-categories/'. $category->id) }}" class="edit-category" title="Edit"><i class="fa fa-edit"></i></a>
                        &nbsp;&nbsp;<a href="javascript:void(0)" class="delete-category" title="Delete" data-id="{{ $category->id }}"><i class="fa fa-trash text-danger"></i></a>
                        @endif
                    </li>
                    @if(count($category->subcategory))
                    @include('merchant.subCategoryList',['subcategories' => $category->subcategory])
                    @endif 
                </ul>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .category {
        font-size: x-large;
        line-height: 40px;
        color: #000000;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', '.delete-category', function () {
            let id = $(this).data('id');
            if(id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('merchant-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: id,request_type: 'delete_category'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            location.reload();
                        }
                    }
                });
            }
        });
    });
</script>

@endsection