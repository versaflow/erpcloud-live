@extends('__app.layouts.auth') 

@section('content')

    <div class="container">
      <div class="row mt-lg-n10 mt-md-n11 mt-n10 justify-content-center">
        <div class="col-xl-4 col-lg-5 col-md-7 mx-auto">
        	
          <div class="card z-index-0" id="login-area">
            <div class="card-header text-center pt-4 pb-0">
            	
              <h5>Sign in</h5>
            </div>
            <div class="card-body">
				@if(Session::has('message'))
				@php
				$msg = str_replace(PHP_EOL,'<br>',Session::get("message"));
				@endphp
				<div class="alert alert-primary  alert-dismissible fade show" role="alert">
				{{ $msg }}
				</div>
				@endif
              <form role="form" class="text-start" action="/user/signin" id="loginform" method="POST">
                <div class="mb-3">
                  
                  <input type="text" name="username" class="form-control" placeholder="Email or mobile number" aria-label="Email or mobile number" value="{{ old('username') }}" required="required">
                </div>
                <div class="mb-3">
                  <input type="password" name="password" class="form-control" placeholder="Password" aria-label="Password" required="required">
                </div>
                @if(!empty($requires_otp))
                <div class="mb-3">
                  <input type="text" name="otp_code" class="form-control" placeholder="OTP Code" aria-label="OTP Code" required="required">
                </div>
                @endif
                <!--
                <div class="mb-3">
                  <select name="account_type" class="form-control" placeholder="Account Type" aria-label="Account Type" required="required">
                    <option value="customer" selected>Customer</option>
                    <option value="reseller">Reseller</option>
                    <option value="reseller_user">Reseller User</option>
                  </select>
                </div>
                -->
                
                
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember">
                  <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                <div class="text-center">
                  <button type="submit" class="btn bg-gradient-info w-100 my-4 mb-2">Sign in</button>
                </div>
                <div class="mb-2 position-relative text-center">
                  <p class="text-sm font-weight-bold mb-2 text-secondary text-border d-inline z-index-2 bg-white px-3">
                    or
                  </p>
                </div>
                <div class="text-center">
                  <a href="/user/register"  class="btn bg-gradient-dark w-100 mt-2 mb-4">Sign up</a>
                </div>
                
                   
      				<div class="form-group m-b-0">
      				  <div class="col-sm-12 text-center">
      				  	
      				    <p> <a href="javascript:void(0)" class="text-sm text-primary m-l-5 forgot-button"><b> Forgot password? </b></a></p>
      				  </div>
      				</div>
               
              </form>
            </div>
          </div>
          
          
          <div class="card z-index-0" id="forgot-area" style="display:none;">
            <div class="card-header text-center pt-4">
              <h5>Reset Password</h5>
            </div>
            <div class="card-body">
				@if(Session::has('message'))
				@php
				$msg = str_replace(PHP_EOL,'<br>',Session::get("message"));
				@endphp
				<div class="alert alert-primary  alert-dismissible fade show" role="alert">
				{{ $msg }}
				</div>
				@endif
              <form role="form" class="text-start" action="/user/reset" id="recoverform" method="POST">
                <div class="mb-3">
                  <input type="text" name="reset_username" class="form-control" placeholder="Email or mobile number" aria-label="Username"  required="required">
                </div>
               
        		<input name="_token" value="{!! csrf_token() !!}" type="hidden">
               
                <div class="text-center">
                  <button type="submit" class="btn bg-gradient-info w-100 my-4 mb-2">Reset</button>
                </div>
                <div class="mb-2 position-relative text-center">
                  <p class="text-sm font-weight-bold mb-2 text-secondary text-border d-inline z-index-2 bg-white px-3">
                    or
                  </p>
                </div>
               
                <div class="text-center">
                  <a href="javascript:void(0)"  class="btn bg-gradient-dark w-100 mt-2 mb-4 forgot-button">Login</a>
                </div>
              </form>
            </div>
          </div>
          
        </div>
      </div>
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