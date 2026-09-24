@extends('layouts.app')

@section('title','Block Caravan')

@section('content')
<style>
    .multiselect.dropdown-toggle {
        width: 100% !important;
        height: 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #fff;
        text-align: left;
        padding: 6px 12px;
        font-size: 14px;
        color: #555;
    }

    .multiselect-container {
        width: 100% !important;
        border-radius: 4px;
    }

    .multiselect-container > li > a > label {
        padding: 5px 10px;
        font-weight: normal;
    }

    .multiselect-container > li > a > label.checkbox {
        color: #333 !important;
    }

    .multiselect-selected-text {
        float: left;
    }

    .caret {
        float: right;
        margin-top: 8px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Block Caravan</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        
                        @if(Session::has('success'))
                            <p class="flash" style="color: red; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                        @if(Session::has('error'))
                            <p class="flash" style="color: red; text-align: center;">
                                {{ Session::get('error') }}
                                @php
                                    Session::forget('error');
                                @endphp
                            </p>
                        @endif
                   
                        <form action="{{ route('block-rental-caravan-request') }}"  method="POST" id="blockCaravanForm">
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="caravanSection">
                                            <label class="control-label">Select Caravan</label><span class="required_field">*</span>
                                            <select class="form-control check-quantity" id="caravan_id" name="caravan_id" required>
                                                <option value="">Select Caravan</option>
                                                @foreach ($MasterCaravan as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('caravan_id'))
                                                <span class="text-danger">{{ $errors->first('caravan_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>From Date</label><span class="required_field">*</span>
                                            <input type="date" class="form-control" name="from_date" id="from_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>To Date</label><span class="required_field">*</span>
                                            <input type="date" class="form-control" name="to_date" id="to_date" required>
                                            <span id="date-error" class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Number of Days</label>
                                            <input type="number" class="form-control" name="no_of_days" id="no_of_days" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row  single-data" id="qunatityBlock">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Quantity</label><span class="required_field">*</span>
                                            <input type="number" class="form-control" name="quantity" id="quantity" min="0" required value="{{ old('quantity') }}">
                                            <span class="qty-msg text-info"></span>
                                            @if ($errors->has('quantity'))
                                                <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Block Reason</label><span class="required_field">*</span>
                                            <input type="text" class="form-control" name="block_reason" id="block_reason" value="{{ old('block_reason') }}">
                                            @if ($errors->has('block_reason'))
                                                <span class="text-danger">{{ $errors->first('block_reason') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('caravan-block-data')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
    document.addEventListener("DOMContentLoaded", function () {

        const form = document.getElementById("blockCaravanForm");
        const caravan = document.getElementById("caravan_id");
        const fromDate = document.getElementById("from_date");
        const toDate = document.getElementById("to_date");
        const noOfDays = document.getElementById("no_of_days");
        const quantity = document.getElementById("quantity");
        const dateError = document.getElementById("date-error");

        // 📅 Disable past dates
        let today = new Date().toISOString().split('T')[0];
        fromDate.setAttribute("min", today);
        toDate.setAttribute("min", today);

        // 🔢 Auto calculate number of days
        function calculateDays() {
            if (fromDate.value && toDate.value) {
                let start = new Date(fromDate.value);
                let end = new Date(toDate.value);

                if (end >= start) {
                    let diffTime = end - start;
                    let days = (diffTime / (1000 * 60 * 60 * 24)) + 1;
                    noOfDays.value = days;
                    dateError.textContent = "";
                } else {
                    noOfDays.value = "";
                    dateError.textContent = "To Date cannot be less than From Date";
                }
            }
        }

        fromDate.addEventListener("change", calculateDays);
        toDate.addEventListener("change", calculateDays);

        // 🚫 Form validation
        form.addEventListener("submit", function (e) {
            let errors = [];

            // Caravan validation
            if (caravan.selectedOptions.length === 0) {
                errors.push("Please select at least one caravan.");
            }

            // Date validation
            if (!fromDate.value) {
                errors.push("From Date is required.");
            }

            if (!toDate.value) {
                errors.push("To Date is required.");
            }

            if (fromDate.value && toDate.value) {
                let start = new Date(fromDate.value);
                let end = new Date(toDate.value);

                if (end < start) {
                    e.preventDefault();
                    dateError.textContent = "To Date must be greater than or equal to From Date";
                }
            }

            // Quantity validation
            if (!quantity.value || quantity.value <= 0) {
                errors.push("Quantity must be greater than 0.");
            }

            // Block reason validation
            if (!blockReason.value.trim()) {
                errors.push("Block Reason is required.");
            }

            // Show errors
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\n"));
            }
        });

    });

    $('#quantity, #caravan_id, #from_date, #to_date').on('input change', function () {
        let caravan_id = $('#caravan_id').val();
        let from_date = $('#from_date').val();
        let to_date = $('#to_date').val();
        let qty = $('#quantity').val();

        if (!caravan_id || !from_date || !to_date) return;

        $.ajax({
            url: "{{ url('check-caravan-availability') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                caravan_id: caravan_id,
                from_date: from_date,
                to_date: to_date
            },
            success: function (res) {
                if (res.status) {
                    $('.qty-msg').text(
                        'Maximum ' + res.available + ' quantity can be blocked.'
                    );
                    if (qty && parseInt(qty) > parseInt(res.available)) {
                        $('.addData').prop('disabled', true);
                    } else {
                        $('.addData').prop('disabled', false);
                    }
                }
            }
        });
    });
</script>

@endsection