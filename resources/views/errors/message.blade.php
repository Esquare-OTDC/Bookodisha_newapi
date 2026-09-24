@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(Session::has('success'))
    <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
        {{ Session::get('success') }}
        @php Session::forget('success'); @endphp
    </p>
@endif
@if(Session::has('error'))
    <p class="flashMessage" style="color: #c04621; text-align: center;">
        {{ Session::get('error') }}
        @php Session::forget('error'); @endphp
    </p>
@endif
