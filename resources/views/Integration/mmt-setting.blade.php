@extends('layouts.app')

@section('title','MMT Settings')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">MMT Settings</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        
                         @if(Session::has('success'))
                            <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                
                        <form action="{{ route('save-mmt-credentials') }}"  method="POST">
                            @csrf
                            <div class="form-body">
                                <div class="row m-b-20" >
                                    <div class="col-md-12">
                                        <label class="control-label">MMT Gateway Type</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="gateway_type" name="gateway_type" value="sandbox" {{ ($gateway == 'sandbox') ? 'checked' : '' }}> Sandbox
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="gateway_type" name="gateway_type" value="live" {{ ($gateway == 'live') ? 'checked' : '' }}> Live
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Bearer Token</label><span class="required_field">*</span>
                                            <input type="text" name="bearer_token" id="bearer_token" class="form-control" required value="{{ $bearer_token }}"> 
                                            @if ($errors->has('bearer_token'))
                                                <span class="text-danger">{{ $errors->first('bearer_token') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Channel Token</label><span class="required_field">*</span>
                                            <input type="text" name="channel_token" id="channel_token" class="form-control" required value="{{ $channel_token }}"> 
                                            @if ($errors->has('channel_token'))
                                                <span class="text-danger">{{ $errors->first('channel_token') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Listing URL</label><span class="required_field">*</span>
                                            <input type="text" name="listing_url" id="listing_url" class="form-control" required value="{{ $listing_url }}">
                                            @if ($errors->has('listing_url'))
                                                <span class="text-danger">{{ $errors->first('listing_url') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">ARI URL</label><span class="required_field">*</span>
                                            <input type="text" name="ari_url" id="ari_url" class="form-control" required value="{{ $ari_url }}">
                                            @if ($errors->has('ari_url'))
                                                <span class="text-danger">{{ $errors->first('ari_url') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success profileEdit"> <i class="fa fa-check"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('.gateway_type').on('change',function() {
            var gateway = $('input[name="gateway_type"]:checked').val();
            if (gateway != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('mapping-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {request_type: "get_mmt_credentials"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        let mmtData = responce.data;
                        if(mmtData) {
                           if (gateway == 'sandbox') {
                               $('#bearer_token').val(mmtData.sandbox_bearer_token);
                               $('#channel_token').val(mmtData.sandbox_channel_token);
                               $('#listing_url').val(mmtData.sandbox_listing_url);
                               $('#ari_url').val(mmtData.sandbox_ari_url);
                            } else {
                                $('#bearer_token').val(mmtData.live_bearer_token);
                                $('#channel_token').val(mmtData.live_channel_token);
                                $('#listing_url').val(mmtData.live_listing_url);
                                $('#ari_url').val(mmtData.live_ari_url);
                            } 
                        }
                    }
                });
            }
        });
    });
</script>

@endsection