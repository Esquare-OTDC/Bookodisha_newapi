@extends('layouts.app')

@section('title','Manage Hall Block')

@section('content')
<style>
    label.text-danger,
    span.text-danger {
        color: red !important;
        font-weight: normal;
    }
    input.error,
    select.error,
    textarea.error {
        border-color: #dc3545 !important;
        color: #495057 !important;
    }

    input.error::placeholder,
    textarea.error::placeholder {
        color: #6c757d !important;
        opacity: 1;
    }
    .error {
        color: inherit !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>

        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Manage Hall Block</div>

                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">

                        @include('errors.message')

                        <form action="{{ route('block-hall-room-request') }}" method="POST" id="availabilityForm">
                            @csrf

                            <input type="hidden" name="property_name" id="property_name">
                            <input type="hidden" name="hall_name" id="hall_name">

                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Property</label>
                                            <span class="required_field">*</span>

                                            <select class="form-control" id="property" name="property_id" required>
                                                <option value="">Select Property</option>
                                                @foreach ($MasterProperty as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>

                                            @if ($errors->has('property_id'))
                                                <span class="text-danger">{{ $errors->first('property_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="hallTypeSection">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Type of Hall</label>
                                            <span class="required_field">*</span>

                                            <select class="form-control" id="hcategory" name="hcategory_id" required>
                                                <option value="">Select Type of Hall</option>
                                            </select>

                                            @if ($errors->has('hcategory_id'))
                                                <span class="text-danger">{{ $errors->first('hcategory_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="hallNameSection">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Name of Hall</label>
                                            <span class="required_field">*</span>

                                            <select class="form-control" id="hall" name="hall_id" required>
                                                <option value="">Select Name of Hall</option>
                                            </select>

                                            @if ($errors->has('hall_id'))
                                                <span class="text-danger">{{ $errors->first('hall_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Slot Type</label>
                                            <span class="required_field">*</span>

                                            <select class="form-control" id="slot_type" name="slot_type" required>
                                                <option value="">Select Slot</option>
                                                <option value="1">Full Day</option>
                                                <option value="2">First Half</option>
                                                <option value="3">Second Half</option>
                                            </select>

                                            @if ($errors->has('slot_type'))
                                                <span class="text-danger">{{ $errors->first('slot_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Date</label>
                                            <span class="required_field">*</span>

                                            <div class="input-group" id="blockDateDiv">
                                                <input class="form-control input-daterange-datepicker"
                                                       id="check_date"
                                                       type="text"
                                                       name="block_date"
                                                       required>
                                            </div>

                                            @if ($errors->has('block_date'))
                                                <span class="text-danger">{{ $errors->first('block_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Block Reason</label>
                                            <span class="required_field">*</span>

                                            <input type="text"
                                                   class="form-control"
                                                   name="block_reason"
                                                   id="block_reason"
                                                   value="{{ old('block_reason') }}"
                                                   required>

                                            @if ($errors->has('block_reason'))
                                                <span class="text-danger">{{ $errors->first('block_reason') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <hr>
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData">
                                    <i class="fa fa-check"></i> Save
                                </button>

                                <a href="{{ route('blocked-halls') }}">
                                    <button type="button" class="btn btn-default">Cancel</button>
                                </a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-1"></div>
    </div>
</div>

<style>
    .input-group {
        width: 100% !important;
    }
</style>

<script type="text/javascript">
    var hallList = @json($HallList);
    var categoryList = @json($CategoryList);

    $(document).ready(function () {
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment(),
            minDate: moment(),
            locale: {
                format: 'DD-MM-YYYY'
            }
        });

        $(document).on('change', '#property', function () {
            let propertyId = $(this).val();
            let usedCategoryIds = [];

            $('#property_name').val($("#property option:selected").text());
            $('#hcategory').html('<option value="">Select Type of Hall</option>');
            $('#hall').html('<option value="">Select Name of Hall</option>');
            $('#hall_name').val('');

            $.each(hallList, function (index, hall) {
                if (propertyId == hall.property_id && $.inArray(hall.hcategory_id, usedCategoryIds) === -1) {
                    usedCategoryIds.push(hall.hcategory_id);

                    $.each(categoryList, function (catIndex, category) {
                        if (hall.hcategory_id == category.id) {
                            $('#hcategory').append(
                                '<option value="' + category.id + '">' + category.hcategory_name + '</option>'
                            );
                        }
                    });
                }
            });
        });

        $(document).on('change', '#hcategory', function () {
            let propertyId = $('#property').val();
            let hcategoryId = $(this).val();

            $('#hall').html('<option value="">Select Name of Hall</option>');
            $('#hall_name').val('');

            $.each(hallList, function (index, hall) {
                if (propertyId == hall.property_id && hcategoryId == hall.hcategory_id) {
                    $('#hall').append(
                        '<option value="' + hall.id + '">' + hall.hall_name + '</option>'
                    );
                }
            });
        });

        $(document).on('change', '#hall', function () {
            $('#hall_name').val($("#hall option:selected").text());
        });

       $('.addData').on('click', function () {
            return validator.form();
        });

        var validator = $('#availabilityForm').validate({
            errorClass: 'text-danger',
            errorElement: 'span',

            rules: {
                property_id: {
                    required: true
                },
                hcategory_id: {
                    required: true
                },
                hall_id: {
                    required: true
                },
                slot_type: {
                    required: true
                },
                block_date: {
                    required: true
                },
                block_reason: {
                    required: true
                }
            },

            messages: {
                property_id: {
                    required: "Please choose property"
                },
                hcategory_id: {
                    required: "Please choose type of hall"
                },
                hall_id: {
                    required: "Please choose name of hall"
                },
                slot_type: {
                    required: "Please choose slot type"
                },
                block_date: {
                    required: "Please choose date"
                },
                block_reason: {
                    required: "Block reason is required"
                }
            },

            highlight: function(element, errorClass) {
                $(element).removeClass(errorClass);
            },

            unhighlight: function(element, errorClass) {
                $(element).removeClass(errorClass);
            }
        });
    });
</script>

@endsection
