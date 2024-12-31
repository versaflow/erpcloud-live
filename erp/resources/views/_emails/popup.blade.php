<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		@if(!empty($parent_logo))
            <img height="35px" src="<?php echo  $message->embed($parent_logo); ?>" alt="logo">
        @else
            <h2>{{ $parent_company }}</h2>
        @endif
        <br>
        <br>
			{!! $msg !!}
	</body>
</html>