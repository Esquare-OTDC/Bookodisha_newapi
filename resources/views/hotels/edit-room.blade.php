@extends('layouts.app')

@section('title', 'Edit Room')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Hotels</li>
                <li class="breadcrumb-item active">Room Management</li>
                <li class="breadcrumb-item active">Edit room</li>
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
                <h2 id="PageHeading">Edit Room: {{ $RoomDetails->title }}</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('room-edit-request') }}" method="POST" enctype="multipart/form-data">
                @csrf
                    <div class="col-md-9">
                        <div class="white-box">
                            <h3 class="box-title">Room information</h3><hr>                        
                                <input type="hidden" name="id" value="{{ $RoomDetails->id }}">
                                <div class="row">
                                    <div class="form-group">
                                        <label class="col-md-12" for="name">Title <span class="text-danger">*</span></label>
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" name="title" value="{{ $RoomDetails->title }}" placeholder="Room name" required>
                                            @if ($errors->has('name'))
                                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12" for="name">Feature Image</label>
                                        <div class="col-md-12">
                                            <input type="file" id="input-file-now" class="dropify" name="image" data-default-file="{{ $RoomDetails->image }}">
                                            @if ($errors->has('image'))
                                                <span class="text-danger">{{ $errors->first('image') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12">Gallery</label>
                                        <div class="col-md-12">
                                            <div id="gallery-image" style="padding-top: .5rem;"></div>
                                            @if ($errors->has('images'))
                                            <span class="text-danger">{{ $errors->first('images') }}</span>
                                            @endif
                                        </div>
                                    </div><hr>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Price <span class="text-danger">*</span></label>
                                            <div class="">
                                                <input type="number" class="form-control" min="0" name="price" value="{{ $RoomDetails->price }}" placeholder="Price" required>
                                                @if ($errors->has('price'))
                                                    <span class="text-danger">{{ $errors->first('price') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Number of room <span class="text-danger">*</span></label>
                                            <div class="">
                                                <input type="number" class="form-control" min="1" name="quantity"  value="{{ $RoomDetails->quantity }}" placeholder="Quantity" required>
                                                @if ($errors->has('quantity'))
                                                    <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Number of rooms for MMT <span class="text-danger">*</span></label>
                                            <div class="">
                                                <input type="number" class="form-control" min="0" name="mmt_quantity"  value="{{ $RoomDetails->mmt_quantity }}" placeholder="Quantity for MMT" required>
                                                @if ($errors->has('mmt_quantity'))
                                                    <span class="text-danger">{{ $errors->first('mmt_quantity') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div> -->
                                </div><hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Number of beds</label>
                                            <div class="">
                                                <input type="number" class="form-control" min="1" name="beds" value="{{ $RoomDetails->beds }}" required>
                                                @if ($errors->has('beds'))
                                                    <span class="text-danger">{{ $errors->first('beds') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Room Size</label>
                                            <div class="input-group">
                                                <input type="number" id="example-input2-group1" min="0" name="size" value="{{ $RoomDetails->size }}" class="form-control"> <span class="input-group-addon">sqft</span>
                                                @if ($errors->has('size'))
                                                    <span class="text-danger">{{ $errors->first('size') }}</span>
                                                @endif
                                            </div>                                        
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Max Adults</label>
                                            <div class="">
                                                <input type="number" class="form-control" min="1" name="adults" value="{{ $RoomDetails->adults }}" required>
                                                @if ($errors->has('adults'))
                                                    <span class="text-danger">{{ $errors->first('adults') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Max Children</label>
                                            <div class="">
                                                <input type="number" class="form-control" min="0" name="children" value="{{ $RoomDetails->children }}">
                                                @if ($errors->has('children'))
                                                    <span class="text-danger">{{ $errors->first('children') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Extra Mattress Allowed</label>
                                            <div class="">
                                                <input type="number" class="form-control" min="0" name="extra_bed_allowed" value="{{ $RoomDetails->extra_bed_allowed }}" required>
                                                @if ($errors->has('extra_bed_allowed'))
                                                    <span class="text-danger">{{ $errors->first('extra_bed_allowed') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="" for="name">Extra Mattress Price</label>
                                            <div class="">
                                                <input type="number" class="form-control" min="0" name="extra_bed_price" value="{{ $RoomDetails->extra_bed_price }}">
                                                @if ($errors->has('extra_bed_price'))
                                                    <span class="text-danger">{{ $errors->first('extra_bed_price') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        @php
                        $attr_array = array_column($RoomDetails->property, 'name');
                        @endphp
                        @foreach ($RoomAttributes as $attrs => $terms)
                        <div class="white-box">
                            <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div><hr>
                            <div class="input-group">
                                <ul class="icheck-list">
                                    <?php
                                    foreach ($terms as $key => $values) {
                                        $checked = '';
                                        if (in_array($attrs, $attr_array) && in_array($values, $RoomDetails->property[array_search($attrs, $attr_array)]['props'], true)) {
                                            $checked = 'checked';
                                        }?>
                                        <li>
                                            <input type="checkbox" class="check" name="property[{{ $attrs }}][]" {{ $checked }} value="{{ $key .'~'. $values }}" data-checkbox="icheckbox_flat-blue">
                                            <label>{{ $values }}</label>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                        @endforeach
                        <div class="white-box">
                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Bed Type</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="bed_type" value="{{ $RoomDetails->bed_type }}" placeholder="e.g. : King Bed" required maxlength="50">
                                        @if ($errors->has('bed_type'))
                                            <span class="text-danger">{{ $errors->first('bed_type') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Status</label>
                                    <div class="col-md-12">
                                        <select name="status" class="form-control">
                                            <option {{ ($RoomDetails->status == 'publish') ? 'selected' : '' }} value="publish">Publish</option>
                                            <option {{ ($RoomDetails->status == 'draft') ? 'selected' : '' }} value="draft">Draft</option>
                                        </select>
                                        @if ($errors->has('status'))
                                        <span class="text-danger">{{ $errors->first('status') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-success pull-right m-t-20"><i class="fa fa-save"></i> Save Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>                
        </div>
    </div>
</div>

<style>
    .icheck-list li label {
        display: inline;
        color: black;
    }
    .icheck-list {
        padding-right: 0px;
    }
    .icheck-list li {
        padding-bottom: 8px;
    }
    .image-uploader {
        min-height: 20rem;
    }
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();

        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            // maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });
    });
</script>

@endsection