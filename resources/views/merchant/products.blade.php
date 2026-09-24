@extends('layouts.app')

@section('title','Merchant Products')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Merchandise</li>
                <li class="breadcrumb-item active">Products</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('add-merchant-product')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Product</a>
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
                <div class="col-md-4">
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Move to draft</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form>
                </div>
                <div class="col-md-8">
                    <form class="form-inline" onsubmit="event.preventDefault();" style="float: right;">
                        @if(Auth::user()->role == 1)
                        <select class="form-control select2" id="venderId">
                            <option value="">Select Vendor</option>
                            @foreach ($Vendors as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @endif
                        <select class="form-control" id="customColumn">
                            <option value="">Select</option>
                            <option value="name">Name</option>
                            <option value="sku">SKU</option>
                            <option value="price">Price</option>
                        </select>
                        <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control"></span>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                    </form>
                </div><br><br><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>
                                <div class="checkbox-fade">
                                    <label><input type="checkbox" value="checkAll" id="checkAll"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label>
                                </div>
                            </th>
                            @if (Auth::user()->access_type == 'superadmin')
                            <th>Vendor</th>
                            @endif
                            <th>Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="8" class="dataTables_empty">Loading data from server...</td>
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
        var aTarget = Number("{{ (Auth::user()->access_type == 'superadmin') ? 7 : 6 }}");
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText1 = $("#venderId").val();
            let searchText2 = $("#customColumn").val();
            let searchText3 = $("#searchValue").val();
            console.log(searchText1, searchText2, searchText3);
            if (typeof searchText1 != "undefined" || (searchText2 != '' && searchText3 != '')) {
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
        
        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));
            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            }
            else {
                $("#changeStatus").hide();
            }
        });
        
        $(document).on('change', '.itemcheck', function () {
            if ($('.itemcheck:checked').length == $('.itemcheck').length) {
                $("#checkAll").prop('checked', true);
            } else {
                $("#checkAll").prop('checked', false);
            }
        });
        
        $(document).on('click', '#submitBulkOperation', function () {
            let idArray = [];
            $("input:checkbox[class=itemcheck]:checked").each(function(){
                idArray.push($(this).val());
            });
            if(idArray.length == 0) {
                alert('No items selected!');
                return false;
            }
            let action = $("#bulkOperation").val();
            if(action != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('merchant-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {IdArray: JSON.stringify(idArray), request_type: action},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            } else {
                alert('please select an action!');
            }
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
                    "url": "{{ url('get-merchant-products') }}",
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
                    'aTargets': [0, aTarget]
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
    });
        
</script>

@endsection