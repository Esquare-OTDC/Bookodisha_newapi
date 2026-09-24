@extends('layouts.app')

@section('title','Manage Sliders')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">CMS</li>
                <li class="breadcrumb-item active">Manage Sliders</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <a href="{{url('add-slider')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add New Slider</a>
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
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Section</th>
                            <th>Image</th>
                            <th>Run Time</th>
                            <th>Content</th>
                            <th>Link</th>
                            <th>Status</th>
                            <th>Created By</th>
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
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalHead">Slider Content</h4>
            </div>
            <div class="modal-body">
                <p id="blogContent"></p>
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
        var oTable;
        getHotels(oTable, searchValue1, searchValue2);
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText1 = $("#venderId").val();
            let searchText2 = $("#searchValue").val();
            if (searchText1 != '' || searchText2 != '') {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                getHotels(oTable, searchValue1, searchValue2);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchValue").val('');
            getHotels(oTable);
        });
        
        $(document).on('click', '.deleteSlider', function () {
            let Id = $(this).data('id');
            if(Id != "" && confirm('Are you sure want to delete the slider ?')){
                $.ajax({
                    type: "POST",
                    url: "{{url('slider-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'delete_slider'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable);
                        }
                    }
                });
            } 
        });
        
        $(document).on('click', '.banner-content', function () {
            $("#blogContent").html($(this).data('content'));
        });
        
        $(document).on('click', '.statusModify', function () {
            let Id = $(this).data('id');
            let status = $(this).data('status');
            let stat = (status == 1) ? 'deactivate' : 'activate';
            if(Id != "" && confirm('Are you sure want to '+ stat +' the slider ?')) {
                $.ajax({
                    type: "POST",
                    url: "{{url('slider-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id,  request_type: 'change_slider_status'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        alert(responce.message);
                        getHotels(oTable, searchValue1, searchValue2);
                        
                    }
                });
            } 
        });
    });
    function getHotels(oTable, searchValue1 = '', searchValue2 = '') {
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
                "url": "{{ url('get-slider') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2
                },
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [7]
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