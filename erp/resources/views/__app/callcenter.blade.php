<div class="card">
    <div class="card-header">
        @if($email_id > 0)
        <a style="font-size: 16px;font-weight: bold;" data-target="form_modal" href="email_form/default/{{ $account->id }}?email_id={{$email_id}}">Email {{ $account->email }}</a><br>
        @else
        <a style="font-size: 16px;font-weight: bold;" data-target="form_modal" href="email_form/default/{{ $account->id }}">Email {{ $account->email }}</a><br>
        @endif
        <a style="font-size: 16px;font-weight: bold;" href="javascript:void(0);" onclick="gridAjax('/pbx_call/{{ $account->phone }}/{{ $account->id }}')">Call {{ $account->phone }}</a>
       
    </div>
    <div class="card-body">
        <p><b>Company:</b> {{ $account->company }}</p>
        <p><b>Type:</b> {{ $account->form_name }}</p>
        <p><b>Currency:</b> {{ $account->currency }}</p>
        <p><b>Balance:</b> {{ $account->balance }}</p>
        <p><b>Ad Source:</b> {{ $account->form_name }}</p>
        <p><b>Email:</b> {{ $account->email }}</p>
        <p><b>Phone:</b> {{ $account->phone }}</p>
        <button class="btn btn-primary btn-sm my-2" id="call_completed"  data-call-id="{{ $id }}" onClick="checkCallCompleted( {{ $id }})"> @if($email_id > 0) Email Sent and Called @else Call Completed2 @endif</button>
    </div>
    
    <div class="card-body d-none" id="call_form_container">
     
        <textarea id="call_comments" name="call_comments" rows=5 class="form-control" placeholder="Comments"></textarea>
        
        <select id="call_status" name="call_status" class="form-control">
        @foreach($status_options as $opt)
            <option value="{{$opt}}" @if($opp_status == $opt) selected="selected" @endif>{{$opt}}</option>
        @endforeach
        </select>
        <button class="btn btn-primary btn-sm mt-4" id="call_submit" onClick="queueNextCall( {{ $id }})">Submit</button>
       
    </div>
    @if(!empty($call_script))
    <div class="card-body">
        {!! $call_script !!}
    </div>
    @endif
</div>