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
		<h4>Hi {{ $contact }} ({{ $company }}), </h4>
		Your account has been coverted to a reseller account.  Here are your login details, please store them safely:<br><br>
		
			<b>Username:</b> {{ $username }}<br />
			<b>Password:</b> {{ $password }}<br />
		<br />
		Once logged in, click on Profile to change your details, username or password.<br>
		<br />
		Thank You <br />
		<b>{{ $parent_company }}</b><br />
	</body>
</html>