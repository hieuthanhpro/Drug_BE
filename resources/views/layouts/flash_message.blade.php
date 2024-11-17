@if ($message = Session::get('success'))
<div class="alert alert-success alert-block">
	<button type="button" class="close" data-dismiss="alert">×</button>
	{{ $message }}
</div>
@endif

@if ($message = Session::get('errors'))
<div class="alert alert-danger alert-block">
	<button type="button" class="close" data-dismiss="alert">×</button>
	@if (!is_string($errors))
	<ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
	@else
	{{ $message }}
	@endif
</div>
@endif

@if ($message = Session::get('warning'))
<div class="alert alert-warning alert-block">
	<button type="button" class="close" data-dismiss="alert">×</button>	
	<strong>{{ $message }}</strong>
</div>
@endif

@if ($message = Session::get('info'))
<div class="alert alert-info alert-block">
	<button type="button" class="close" data-dismiss="alert">×</button>	
	<strong>{{ $message }}</strong>
</div>
@endif