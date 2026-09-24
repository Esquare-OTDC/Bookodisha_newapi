@extends('layouts.app')

@section('title','All Flight')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">All Flights</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{ route('add-new-flight') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Add New Flight
            </a>
        </div>
    </div>

    @if(Session::has('success'))
        <p class="flashMessage" style="color:#3bbc2e;text-align:center;">
            {{ Session::get('success') }}
        </p>
    @endif

    @if(Session::has('failure'))
        <p class="flashMessage" style="color:red;text-align:center;">
            {{ Session::get('failure') }}
        </p>
    @endif

    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Flight Name</th>
                                <th>Flight Number</th>
                                <th>Air Line Code</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($FlightList as $flight)
                                <tr>
                                    <td>{{ $flight->operator_name ?? '-' }}</td>
                                    <td>{{ $flight->flight_number ?? '-' }}</td>
                                    <td>{{ $flight->airline_code ?? '-' }}</td>
                                    <td>
                                        @if($flight->is_active === 1)
                                            <span class="label label-success">Active</span>
                                        @else
                                            <span class="label label-warning">
                                                {{ $flight->is_active ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button"
                                                    class="btn btn-info btn-xs btn-outline dropdown-toggle"
                                                    data-toggle="dropdown">
                                                Action <span class="caret"></span>
                                            </button>

                                            <ul role="menu" class="dropdown-menu">
                                                <li>
                                                    <a href="{{ route('edit-new-flight', $flight->id) }}">
                                                        Edit
                                                    </a>
                                                </li>

                                                <li>
                                                    <a href="{{ route('manage-flight', $flight->id) }}">
                                                        Manage
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        No Flight Found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){
    $('#datatable-responsive').DataTable({
        order:[]
    });
});
</script>

@endsection