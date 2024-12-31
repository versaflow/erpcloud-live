@extends('__app.layouts.auth')

@section('content')


    <div class="container">
      <div class="row mt-lg-n10 mt-md-n11 mt-n10 justify-content-center">
        <div class="col-xl-4 col-lg-5 col-md-7 mx-auto">
        	
          <div class="card z-index-0" id="login-area">
            <div class="card-header text-center pt-4 pb-0">
              <h5>Register</h5>
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
              <form role="form" class="text-start" action="/user/create" id="loginform" method="POST">
              
            	@if(!empty($referral_code))
					<input type="hidden" name="referral_code" value="{{$referral_code}}" />
				@endif
				
				<div class="form-group ">
				<div class="col-xs-12">
				{!! Form::text('contact', null, array('class'=>'form-control', 'placeholder'=>Lang::get('Full Name') ,'required'=>'required' )) !!}
				
				</div>
				</div>
			
				<div class="form-group ">
				  <div class="col-xs-12">
				    {!! Form::text('company', null, array('class'=>'form-control', 'placeholder'=>Lang::get('Company'))) !!}
				  </div>
				</div>
				<div class="form-group ">
				  <div class="col-xs-12">
				    {!! Form::text('mobile', null, array('class'=>'form-control', 'placeholder'=>Lang::get('Mobile Number'))) !!}
				  </div>
				</div>
				<div class="form-group ">
				  <div class="col-xs-12">
				    {!! Form::email('username', null, array('class'=>'form-control', 'placeholder'=>Lang::get('Email'))) !!}
				  </div>
				</div>
				
				<div class="form-group ">
				  <div class="col-xs-12">
				    {!! Form::checkbox('newsletter', 1) !!} Subscribe to Newsletter
				  </div>
				</div>
				<div class="form-group text-center m-t-20">
				  <div class="col-xs-12">
				  	
					{!! NoCaptcha::display() !!}
				   
				  </div>
				</div>
				
                <div class="text-center">
                  <button type="submit" class="btn bg-gradient-info w-100 my-4 mb-2">Register</button>
                </div>
                
				<div class="form-group m-b-0">
				  <div class="col-sm-12 text-center">
				  	<p>Free 087 number, free R10 airtime, free sip trunk, free pbx extension with all signups</p>
				    <p>Already have an account? <a href="\user\login" class="text-primary m-l-5"><b> {{ Lang::get('core.signin') }} </b></a></p>
				  </div>
				</div>
               
              </form>
            </div>
          </div>
          
          
          
        </div>
      </div>
    </div>

	
@endsection

@push('scripts')


 {!! NoCaptcha::renderJs() !!}
@endpush
