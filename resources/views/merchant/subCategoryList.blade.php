
@foreach($subcategories as $subcategory)
<ul>
    <li class="category">
        {{$subcategory->category_name}}
        @if(Auth::user()->access_type == 'superadmin')
        &nbsp;&nbsp;<a href="{{ url('add-edit-categories/'. $subcategory->id) }}" class="edit-category"><i class="fa fa-edit"></i></a>
        &nbsp;&nbsp;<a href="javascript:void(0)" class="delete-category" data-id="{{ $subcategory->id }}"><i class="fa fa-trash text-danger"></i></a>
        @endif
    </li> 
    @if(count($subcategory->subcategory))
    @include('merchant.subCategoryList',['subcategories' => $subcategory->subcategory])
    @endif
</ul> 
@endforeach