<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
		 <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> 
	</head>
	<body>
		<div class="d-flex justify-content-center align-items-center container">
		<div class="card bg-lighter" style="width:600px">
		<div class="card-header text-center">
			@if(!empty($partner_logo))
	            <img src="{{ $partner_logo }}" alt="logo" style="max-height:50px" data-auto-embed>
	        @else
	            <h2>{{ $parent_company }}</h2>
	        @endif
		</div>
		<div class="card-body">
			Hi {{ $customer->contact }}, <br><br>
			{!! $msg !!}
		</div>
		<div class="card-footer text-muted text-sm">
			Regards,<br>
			{{ $parent_company }}
		</div>
		</div>
		</div>
	</body>
</html>