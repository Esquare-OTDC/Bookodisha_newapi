@extends('layouts.app')

@section('title','Manage Privilege')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">Sub-user</li>
                <li class="breadcrumb-item active">Manage Privilege</li>
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
            <div class="white-box">
                <div class="row">
                    <div class="col-md-6" style="padding-left:70px;"><strong><u>Pages</u></strong></div>
                    <div class="col-md-2 text-center"><strong><u>None</u></strong></div>
                    <div class="col-md-2 text-center"><strong><u>View</u></strong></div>
                    <div class="col-md-2 text-center"><strong><u>Add/Edit/Delete/View</u></strong></div>
                </div>
                <form action="{{ route('save-privilege') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $UserDetails->id }}">
                    @foreach ($MenuList as $MainMenu)
                    @php
                    $checked = (array_key_exists($MainMenu['id'], $Previlege)) ? $Previlege[$MainMenu['id']] : '';
                    @endphp
                    <div class="row">
                        <div class="row">
                            <div class="col-md-6" style="margin-top: 10px;padding-left: 70px !important;">{{ $MainMenu['menu_name'] }}</div>
                            @if (!empty($MainMenu['child_menu']))
                            <div class="col-md-2"></div>
                            <div class="col-md-2"></div>
                            <div class="col-md-2"></div>
                            </div>
                                @foreach ($MainMenu['child_menu'] as $ChildMenu)
                                @php
                                $child_checked = (array_key_exists($ChildMenu['id'], $Previlege)) ? $Previlege[$ChildMenu['id']] : '';
                                @endphp
                                <div class="row">
                                    <div class="col-md-6" style="margin-top: 10px;padding-left: 120px !important;"><i class="fa fa-angle-double-right m-r-2" aria-hidden="true"></i>{{ $ChildMenu['menu_name'] }}</div>
                                    <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $ChildMenu['id'] }}" {{ ($child_checked == '0') ? 'checked' : '' }} value="0" class="childMenu{{ $ChildMenu['id'] }}" data-id="{{ $ChildMenu['id'] }}"></div>
                                    <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $ChildMenu['id'] }}" {{ ($child_checked == '1') ? 'checked' : '' }} value="1" class="childMenu{{ $ChildMenu['id'] }}" data-id="{{ $ChildMenu['id'] }}"></div>
                                    <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $ChildMenu['id'] }}" {{ ($child_checked == '2') ? 'checked' : '' }} value="2" class="childMenu" data-id="{{ $ChildMenu['id'] }}"></div>
                                </div>
                                @endforeach
                            @else
                            <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $MainMenu['id'] }}" {{ ($checked == '0') ? 'checked' : '' }} value="0" class="mainMenu" data-id="{{ $MainMenu['id'] }}"></div>
                            <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $MainMenu['id'] }}" {{ ($checked == '1') ? 'checked' : '' }} value="1" class="mainMenu" data-id="{{ $MainMenu['id'] }}"></div>
                            <div class="col-md-2 text-center" style="margin-top: 10px;"><input type="radio" name="{{ $MainMenu['id'] }}" {{ ($checked == '2') ? 'checked' : '' }} value="2" class="mainMenu" data-id="{{ $MainMenu['id'] }}"></div>
                            </div>
                            @endif
                    </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary m-t-10">Save Privilege</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        
    });
</script>

@endsection