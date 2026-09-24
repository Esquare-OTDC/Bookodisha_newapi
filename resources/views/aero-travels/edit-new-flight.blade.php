@extends('layouts.app')

@section('title', 'Edit Flight')

@section('content')

<link rel="stylesheet"  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Air Travels</li>
                <li class="breadcrumb-item active">Edit Flight</li>
            </ol>
        </div>
    </div>
    @if(session('success'))
        <p class="flashMessage" style="color:#3bbc2e;text-align:center;">
            {{ session('success') }}
        </p>
    @endif

    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Edit Flight</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" method="POST" action="{{ route('edit-new-flight-request', $Flight->id) }}" enctype="multipart/form-data" id="flightForm">
                    @csrf
                    <div class="col-md-9">
                        <div class="white-box">
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="name"> Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $Flight->operator_name) }}" placeholder="Name of the Flight">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="flight_number"> Flight Number <span class="text-danger">*</span>
                                    </label>
                                    <input  type="text"  class="form-control"  id="flight_number"  name="flight_number"  value="{{ old('flight_number', $Flight->flight_number) }}"  placeholder="Flight Number">
                                    @error('flight_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="airline_code">
                                   Air Line Code
                                </label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="airline_code"  id="airline_code"  value="{{ old('airline_code', $Flight->airline_code) }}" placeholder="Enter Airline Code">
                                </div>
                            </div>
                            {{-- content_data --}}
                            <div class="form-group">

                                <label class="col-md-12" for="content_data">
                                    Content
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="col-md-12">

                                    <textarea
                                        name="content_data"
                                        id="content_data"
                                        cols="10"
                                        rows="5"
                                    >{{ old('content_data', $Flight->content) }}</textarea>

                                    @error('content_data')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                            </div>
                        </div>

                        {{-- ======================================================
                             TERMS AND CONDITIONS
                        ======================================================= --}}
                        <div class="white-box">

                            <h3 class="box-title">
                                Terms & Conditions
                                <span class="text-danger">*</span>
                            </h3>

                            <hr>

                            <div class="row">

                                <div class="form-group col-md-12">

                                    <textarea
                                        name="terms_conditions"
                                        id="terms_conditions"
                                        cols="10"
                                        rows="5"
                                    >{{ old('terms_conditions', $Flight->terms_conditions) }}</textarea>

                                    @error('terms_conditions')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                            </div>

                        </div>

                        <div class="white-box">

                            <h3 class="box-title">
                                FAQs
                            </h3>

                            <hr>

                            <div class="row">

                                <div class="form-group col-md-12">

                                    <table class="display nowrap table table-bordered">

                                        <thead>
                                            <tr>
                                                <th class="text-center">
                                                    Title
                                                </th>

                                                <th class="text-center">
                                                    content_data
                                                </th>

                                                <th class="text-center">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody id="faq-container">
                                            {{-- Dynamic FAQs --}}
                                        </tbody>

                                    </table>

                                    <span
                                        class="btn btn-info btn-sm"
                                        id="addNewFaq"
                                        style="float:right;"
                                    >
                                        <i class="icon-plus"></i>
                                        Add Item
                                    </span>

                                    <div class="clearfix"></div>

                                </div>

                            </div>

                        </div>


                        {{-- ======================================================
                             CONTACT INFORMATION
                        ======================================================= --}}
                        <div class="white-box">

                            <h3 class="box-title">
                                Contact Information
                            </h3>

                            <hr>

                            <div class="row">

                                {{-- Contact Email --}}
                                <div class="form-group col-md-6">

                                    <label for="contact_email">
                                        Contact Email
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="contact_email"
                                        name="contact_email"
                                        value="{{ old('contact_email', $Flight->contact_email) }}"
                                        placeholder="Contact email"
                                    >

                                    @error('contact_email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                                {{-- Contact Number --}}
                                <div class="form-group col-md-6">

                                    <label for="contact_number">
                                        Contact Number
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control numvalidate"
                                        id="contact_number"
                                        name="contact_number"
                                        value="{{ old('contact_number', $Flight->contact_number) }}"
                                        placeholder="Contact Number"
                                        maxlength="10"
                                        inputmode="numeric"
                                    >

                                    @error('contact_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                                {{-- Additional Email --}}
                                <div class="form-group col-md-12">

                                    <label for="additional_email">
                                        Additional Email
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="additional_email"
                                        name="additional_email"
                                        value="{{ old('additional_email', $Flight->additional_email) }}"
                                        placeholder="Additional Contact Email"
                                        autocomplete="off"
                                    >

                                    @error('additional_email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                                {{-- Additional Phone --}}
                                <div class="form-group col-md-12">

                                    <label for="additional_phone">
                                        Additional Contact Number
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="additional_phone"
                                        name="additional_phone"
                                        value="{{ old('additional_phone', $Flight->additional_contact) }}"
                                        placeholder="Additional Contact Number"
                                        autocomplete="off"
                                        maxlength="10"
                                        inputmode="numeric"
                                    >

                                    @error('additional_phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-3">
                        <div class="white-box">
                            <h3 class="box-title">Publish</h3>
                            <hr>
                            <div class="form-group">
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio1" value="publish" {{ old('status', $Flight->is_active == 1 ? 'publish' : 'draft') == 'publish' ? 'checked' : '' }} >

                                    <label for="radio1">
                                        Publish
                                    </label>
                                </div>

                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio2" value="draft" {{ old('status', $Flight->is_active == 1 ? 'publish' : 'draft') == 'draft' ? 'checked' : '' }}>

                                    <label for="radio2">
                                        Draft
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="float:right;margin-top:-20px;">
                                Update
                            </button>
                        </div>

                        <div class="white-box">
                            <h3 class="box-title">Vendor<span class="text-danger">*</span></h3><hr>
                            <select class="form-control" id="vendor_id" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                @foreach($Vendors as $key => $value)
                                    <option value="{{ $key }}" {{ old('vendor_id', $SelectedVendorId) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vendor_id'))
                                <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                            @endif
                        </div>
                    </div>
                </form>
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
        width: 100% !important;
    }

    .multiselect-container.dropdown-menu {
        width: 100% !important;
        max-height: 250px !important;
        overflow-y: auto;
    }

    .multiselect-container .input-group {
        margin: 4px 8px;
    }

    .input-group {
        width: 100% !important;
    }

    .dropdown-menu > .active > a,
    .dropdown-menu > .active > a:focus,
    .dropdown-menu > .active > a:hover {
        background-color: #fff;
    }

    label.checkbox {
        margin-left: 20px !important;
    }

    .checkbox input[type=checkbox] {
        opacity: 1;
    }

    .icheck-list li label {
        display: inline;
        color: black;
    }

    .icheck-list {
        padding-right: 0;
    }

    .icheck-list li {
        padding-bottom: 8px;
    }

    .bootstrap-tagsinput {
        width: 100%;
        text-align: left;
    }

    .mt2 {
        margin-top: 5px;
    }

    .image-uploader {
        min-height: 20rem;
    }

    .dynamic-error {
        display: block;
        margin-top: 5px;
    }


    #slot-container td,
    #faq-container td {
        vertical-align: middle;
    }

</style>


<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script>

$(document).ready(function () {
    const oldFaqs = @json(old('faqs'));
    const initialFaqs = oldFaqs !== null ? Object.values(oldFaqs) : @json($FaqList);

    $('#available_days').multiselect({
        includeSelectAllOption: true,
        enableFiltering: false,
        nonSelectedText: 'Select Days',
        buttonWidth: '100%'
    });

    $('#datepicker-autoclose').datepicker({
        autoclose: true,
        todayHighlight: true,
        format: 'dd-mm-yyyy',
        startDate: '0d'
    });

    $('.clockpicker').clockpicker({
        donetext: 'Done',
        autoclose: true
    });


    CKEDITOR.replace('content_data');
    CKEDITOR.replace('terms_conditions');

    let faqIndex = 0;
    
    function addFaq(faqData = {}) {
        faqIndex++;
        const title = faqData.title || '';
        const contentData = faqData.content_data || '';
        const row = `
            <tr id="faq-${faqIndex}">

                <td>

                    <input type="text"
                 class="form-control"
                        name="faqs[${faqIndex}][title]"
                        value="${escapeHtml(title)}"
                        placeholder="FAQ Title"
                    >

                </td>

                <td>

                    <textarea
                        rows="2"
                        class="form-control"
                        name="faqs[${faqIndex}][content_data]"
                        placeholder="FAQ content_data"
                        style="overflow:auto;resize:vertical;"
                    >${escapeHtml(contentData)}</textarea>

                </td>

                <td style="width:7%;text-align:center;">

                    <button
                        type="button"
                        class="btn btn-danger btn-sm deleteFaq"
                        title="Delete FAQ"
                    >
                        <i class="fa fa-trash"></i>
                    </button>

                </td>

            </tr>
        `;

        $('#faq-container').append(row);

    }

    if (initialFaqs.length > 0) {
        $.each(initialFaqs, function (index, faq) {
            addFaq(faq);
        });
    }


    $(document).on('click', '#addNewFaq', function (e) {
        e.preventDefault();
        addFaq();
    });


    $(document).on('click', '.deleteFaq', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });



    $(document).on('input', '#contact_number, #additional_phone', function () {
        this.value = this.value.replace(/\D/g, '');
    });


    $('#flightForm').on('submit', function (e) {

        let hasError = false;
        $('.dynamic-error').remove();
        $('.has-error').removeClass('has-error');

        function showError(selector, message) {
            const field = $(selector);
            field.after('<span class="text-danger dynamic-error">' +message +'</span>'
            );
            hasError = true;
        }

        const name = $('input[name="name"]').val().trim();
        if (name === '') {
            showError('input[name="name"]','Flight Name is required');
        }

        const flightNumber = $('input[name="flight_number"]').val().trim();
        if (flightNumber === '') {
            showError('input[name="flight_number"]','Flight Number is required');
        } else if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9-]+$/.test(flightNumber)) {
            showError('input[name="flight_number"]','Flight Number must contain both letters and numbers and should only contain letters, numbers, or hyphens');
        }


        let content_dataValue = '';
        if (CKEDITOR.instances.content_data) {
            content_dataValue = CKEDITOR.instances.content_data
                .getData()
                .replace(/<[^>]*>/g, '')
                .trim();
        }

        if (content_dataValue === '') {
            $('#content_data').next('.cke').after(
                '<span class="text-danger dynamic-error">' +
                'content_data is required' +
                '</span>'
            );
            hasError = true;
        }


        let termsValue = '';
        if (CKEDITOR.instances.terms_conditions) {
            termsValue = CKEDITOR.instances.terms_conditions .getData().replace(/<[^>]*>/g, '').trim();
        }
        if (termsValue === '') {
            $('#terms_conditions').next('.cke').after( '<span class="text-danger dynamic-error">' + 'Terms & Conditions are required' + '</span>');
            hasError = true;
        }

        const contactEmail = $('input[name="contact_email"]').val().trim();
        if (contactEmail === '') {
            showError('input[name="contact_email"]','Contact Email is required');
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(contactEmail)) {
                showError( 'input[name="contact_email"]','Please enter a valid email address');
            }
        }
        const additionalEmail = $('input[name="additional_email"]').val().trim();
        if (additionalEmail !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(additionalEmail)) {
                showError('input[name="additional_email"]','Please enter a valid additional email address');
            }
        }

        const contactNumber = $('input[name="contact_number"]').val().trim();
        if (contactNumber === ''){
            showError('input[name="contact_number"]','Contact Number is required');
        } else if (!/^\d{10}$/.test(contactNumber)) {
            showError('input[name="contact_number"]','Contact Number must contain exactly 10 digits');
        }


        const additionalPhone = $('input[name="additional_phone"]').val().trim();
        if (additionalPhone !== '' && !/^\d{10}$/.test(additionalPhone)){
            showError('input[name="additional_phone"]','Additional Contact Number must contain exactly 10 digits');
        }

        const vendorId = $('select[name="vendor_id"]').val();
        if (vendorId === '') {
            showError('select[name="vendor_id"]','Vendor is required' );
        }

        if (hasError) {
            e.preventDefault();
            const firstError = $('.dynamic-error:first');
            if (firstError.length) {
                $('html, body').animate(
                    { scrollTop : firstError.offset().top - 120
                    },500
                );
            }
            return false;
        }

        for (const instanceName in CKEDITOR.instances) {
            CKEDITOR.instances[instanceName].updateElement();

        }
    });

    function escapeHtml(value) {
        return $('<div>')
            .text(value)
            .html();
    }

});

</script>

@endsection
