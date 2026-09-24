@extends('layouts.app')

@section('title', 'Send Mail')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Send E-mail</li>
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
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Send Mail</h2>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="white-box">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <select class="form-control" id="usertype">
                                        <option value="">Select User Type</option>
                                        <option value="all">All User</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-8" id="customSelectedUser">
                                <select id="SelectUser" multiple="multiple">
                                    @foreach ($AllUser as $users)
                                    <option value="{{ $users->id }}">{{ $users->first_name .' '. $users->last_name .' ('. $users->email .')' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div><br/>

                        <div class="row">
                            <div class="col-md-12">
                                <h6 style="margin-bottom:5px; font-weight: bold;">Subject</h6>
                                <input type="text" id="subject" class="form-control" />
                            </div>
                        </div><br/>

                        <div class="row">
                            <div class="col-md-12">
                                <h6 style="margin-bottom:5px; font-weight: bold;">Message</h6>
                                <textarea name="message" cols="10" rows="5"></textarea>
                            </div>
                        </div><br/>

                        <div class="row">
                            <div class="col-md-12" style="text-align: center;">
                                <button type="button" class="btn btn-warning" id="send_email" style="width:165px; margin-top:10px;">Send Email</button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>                
        </div>
    </div>
</div>

<style>
    .multiselect-container > li > a > label.checkbox {
        color: #000 !important;
    }
    .multiselect-container > li > a > label {
        padding: 3px 3px 3px 10px;
    }
    .multiselect-clear-filter {
        background-color: #fff;
        margin-right: 5px;
        color: #b0b0b0;
    }
    .multiselect.dropdown-toggle.btn.btn-default {
        width: 500px !important;
        overflow :hidden;
    }
    .multiselect-container.dropdown-menu {
        width: 500px !important;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .multiselect-container .input-group {
        margin: 4px 8px;
    }
    .input-group {
        width: 95% !important;
    }
    .dropdown-menu>.active>a, .dropdown-menu>.active>a:focus, .dropdown-menu>.active>a:hover {
        background-color: #fff;
    }
    label.checkbox {
        margin-left: 20px !important;
    }
    .checkbox input[type=checkbox] {
        opacity: 1;
    }
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // custom selected user
        $('#SelectUser').multiselect({
            includeSelectAllOption: true,
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true
        });
        $(".input-group-addon").remove();
        $(".input-group-btn").remove();
        
        $(".multiselect-selected-text").text('Select User');

        $('#customSelectedUser').hide();
        $('#usertype').change(function () {
            if ($(this).val() == 'custom') {
                $('#customSelectedUser').show();
            } else {
                $('#customSelectedUser').hide();
            }
        });
        
        
        // Send Email
        $('#send_email').on('click', function () {
            var message = CKEDITOR.instances['message'].getData();
            var subject = $("#subject").val();

            if ($('#usertype').val() == "") {
                alert('Please select the user');
                $('#usertype').focus();
            } 
            else if(subject == "") {
                alert('Please enter the subject');
                $('#subject').focus();
            } 
            else if(message == "") {
                alert('Please enter the message');
                $("#message").focus();
            } else {
                if($('#usertype').val() == "all") {
                    $.ajax({
                        type: "POST",
                        url: "{{url('sendmail-oprsn')}}",
                        headers: {'X-CSRF-Token': '{{ csrf_token() }}'},
                        data: {message: message, subject: subject, request_type: 'send_to_all_user'},
                        success: function (result) {
                            alert(result);
                            window.location = '';
                        }
                    });
                } else {
                    var all_userId = [];
                    $("input:checkbox:checked").each(function () {
                        var checkedValue = $(this).val();
                        if (checkedValue != 'multiselect-all') {
                            all_userId.push(checkedValue);
                        }
                    });
                    
                    if (all_userId.length == '') {
                        alert('Please select the custom user for send email');
                    } else {
                        $.ajax({
                            type: "POST",
                            url: "{{url('sendmail-oprsn')}}",
                            headers: {'X-CSRF-Token': '{{ csrf_token() }}'},
                            data: {message: message, subject: subject, userIds: all_userId, request_type: 'send_to_custom_user'},
                            success: function (result) {
                                alert(result);
                                window.location = '';
                            }
                        });
                    }
                }
            }
        });
    });
    CKEDITOR.replace( 'message' );
</script>

@endsection