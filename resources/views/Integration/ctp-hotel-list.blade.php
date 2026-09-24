@extends('layouts.app')

@section('title','Cleartrip Hotels List')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Cleartrip Integration</li>
                <li class="breadcrumb-item active">Hotel List</li>
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
<!--                <div class="col-md-4">
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Move to draft</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form>
                </div>-->
<!--                <div class="col-md-12">
                    <form class="form-inline" onsubmit="event.preventDefault();" style="float: right;">
                        <select class="form-control" id="customColumn">
                            <option value="">Select</option>
                            <option value="name">Name</option>
                            <option value="city">Location</option>
                        </select>
                        <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control" placeholder="Search by name"></span>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                    </form>
                </div><br><br><br>-->
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Hotel Code</th>
                            <th>Rooms</th>
                            <th>Mapped Hotel</th>
                            <th>Mapped Rooms</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="5" class="dataTables_empty">Loading data from server...</td>
                            </tr>
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText1 = $("#venderId").val();
            let searchText2 = $("#customColumn").val();
            let searchText3 = $("#searchValue").val();
            if (searchText1 != '' || (searchText2 != '' && searchText3 != '')) {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                getHotels(oTable, searchValue1, searchValue2, searchValue3);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            getHotels(oTable);
        });
        
        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();
            if(column == 'city') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {request_type: 'get_city'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            $("#searchInput").html('<select class="form-control select2" id="searchValue"><option value="">Select</option></select>');
                        } else {
                            let cityArray = Object.keys(responce.data);
                            $html = '<option value="">Select</option>';
                            for (let i = 0; i < cityArray.length; i++) {
                                $html += '<option value="'+ cityArray[i] +'">'+ cityArray[i] +'</option>';
                            }
                            $("#searchInput").html('<select class="form-control select2" id="searchValue">'+ $html +'</select>');
                            $("#searchValue").select2();
                        }
                    }
                });
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            }
        });
    });
    function getHotels(oTable, searchValue1 = '', searchValue2 = '', searchValue3 = '') {
        if ($.fn.DataTable.isDataTable('#datatable-responsive')) {
             $('#datatable-responsive').DataTable().destroy();
        }
        oTable = $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "fixedHeader": {
                header: true
            },
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-ctp-hotel-list') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2,
                    "searchValue3": searchValue3
                },
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [1,2,3,4]
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [],
            "iDisplayLength": 10,
            "drawCallback": function () {
                var totalrecords = oTable.fnSettings().fnRecordsTotal();
                $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                $("#datatable-responsive_info").hide();
            }
        });
    }
</script>

@endsection