<div class="card bg-white">
   
    <div class="card-body p-2">
        
        <button class="btn btn-primary btn-sm my-2" id="call_completed"  data-call-id="{{ $id }}"  onClick="checkCallCompleted( {{ $id }})"> @if($email_id > 0) Email Sent and Called @else Call Completed @endif</button>
    </div>
    
    <div class="card-body p-2 d-none" id="call_form_container">
     
        <textarea id="call_comments" name="call_comments" rows=5 class="form-control" placeholder="Comments"></textarea>
        
        <select id="call_status" name="call_status" class="form-control">
        @foreach($status_options as $opt)
            <option value="{{$opt}}" @if($opp_status == $opt) selected="selected" @endif>{{$opt}}</option>
        @endforeach
        </select>
        <button class="btn btn-primary btn-sm mt-4" id="call_submit"  data-call-id="{{ $id }}" onClick="queueNextCall( {{ $id }})">Submit</button>
       
    </div>
    @if(!empty($call_script))
    <div class="card-body p-2">
        {!! $call_script !!}
    </div>
    @endif
</div>