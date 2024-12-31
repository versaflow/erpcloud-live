
@extends('__app.layouts.auth')

@section('content')
	
	 	{!! Form::open(array('url' => 'user/doreset/'.$verCode, 'class'=>'form-horizontal form-material sky-form boxed')) !!}

<div class="col-12 p-5 text-center" >
        <div class="form-group m-t-20">
          <div class="col-xs-12">
            {!! Form::password('password',  array('class'=>'form-control', 'placeholder'=>'New Password')) !!}	
          </div>
        </div>
        <div class="form-group ">
          <div class="col-xs-12">
           {!! Form::password('password_confirmation', array('class'=>'form-control', 'placeholder'=>'Confirm Password')) !!}
          </div>
        </div>
<br><br>
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button class="btn btn-success btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Reset Password</button>
          </div>
        </div> 

        <div class="form-group m-b-0">
          <div class="col-sm-12 text-center">
            <p>  <a href="{{ url('user/login') }}" class="text-success m-l-5"><b>{{ Lang::get('core.signin') }} </b></a></p>
          </div>
        </div>               
    </form> 
        </div>   

@endsection