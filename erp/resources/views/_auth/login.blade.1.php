@extends('__app.layouts.auth') 

@section('content')

	<div class="col-12 p-4" id="login-area">
		@if(Session::has('message'))
		@php
		$msg = str_replace(PHP_EOL,'<br>',Session::get("message"));
		@endphp
		<div class="alert alert-primary  alert-dismissible fade show" role="alert">
		{{ $msg }}
		</div>
		@endif
		{!! Form::open(array('url'=> 'user/signin', 'class'=>'form-horizontal form-material','id' => 'loginform' , 'parsley-validate'=>'','novalidate'=>' ')) !!} 
		
			<div class="form-group m-t-40">
			<div class="col-xs-12">
			<input class="form-control" name="username" type="text" required="true" placeholder="Username"  value="{{ old('username') }}">
			</div>
			</div>
			<div class="form-group">
			<div class="col-xs-12">
			<input class="form-control" name="password" type="password" required="true" placeholder="{{ Lang::get('core.password') }}">
			</div>
			</div>
			<div class="form-group text-center m-t-20">
			<div class="col-xs-12">
			<button class="btn btn-success btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Log In</button>
			</div>
			</div>
			
			
			<div class="form-group">
			<div class="col-md-12 p-0">
			<a href="javascript:void(0)" style="float:right" class="forgot-button text-right"><i class="fa fa-lock m-r-5"></i> Forgot password?</a> </div><br>
			@if( !$disable_signup )
				<div class="col-sm-12 p-0 text-right">
				<p style="color:#000;">Don't have an account? <a href="/user/register" class="text-success m-l-5"><b> {{ Lang::get('core.signup') }} </b></a></p>
				
				    @if(!empty($website_address))
						<a href="{{ url('https://'.$website_address) }}" target="_blank">{{$website_address}}</a>
                    @endif
				</div>
			@endif
				</div>
		</form>
	</div>
	
	<div class="col-12 p-5" id="forgot-area" style="display:none;">
      <form method="post" action="/user/reset" class="form-horizontal" id="recoverform" >
        <div class="form-group ">
          <div class="col-xs-12">
            <h6>Reset Password</h6>
            <p class="text-muted">Enter your username and instructions will be sent to you. </p>
          </div>
        </div>
        <div class="form-group ">
          <div class="col-xs-12">
           <input type="text" name="reset_username" placeholder="Username" class="form-control" required="required" />
          </div>
        </div>
        <input name="_token" value="{!! csrf_token() !!}" type="hidden">
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button class="btn btn-warning btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Reset</button>
          </div>
        </div>
         <div class="form-group">
          <div class="col-md-12">
            <a href="javascript:void(0)" class="forgot-button pull-right"><i class="fa fa-lock m-r-5"></i> Login</a> 
            
            </div>
        </div>
       
      </form>
          </div>
       
@endsection
@push('page-scripts')

<script type="text/javascript">
	$(document).ready(function(){
		$('#forgot-area').hide();
		$('.forgot-button').click(function(){
			$('#login-area').toggle();
			$('#forgot-area').toggle();
		});
	});
</script>
@endpush