<div class="tui">
<form action="/support_form_post" id="support_form" >
  <input type="hidden" name="id" id="id" value="{{$row->id}}" />
  <input type="hidden" name="account_id" id="account_id" value="{{$row->account_id}}" />
<nav aria-label="Progress">
  <ol role="list" class="divide-y divide-gray-300 rounded-md border border-gray-300 md:flex md:divide-y-0">
    <li class="relative md:flex md:flex-1">
      <!-- Completed Step -->
      <a href="javascript:void(0);" onClick="showStep(1)" class="group flex w-full items-center">
          <!-- <span class="flex items-center px-6 py-4 text-sm font-medium">
          <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 group-hover:bg-indigo-800">
            <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd" />
            </svg>
          </span>
          <span class="ml-4 text-sm font-medium text-gray-900">Checklist</span>
        </span>-->
        <span class="flex items-center px-6 py-4 text-sm font-medium">
          <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 border-indigo-600">
          <span class="text-indigo-600">01</span>
        </span>
          <span class="ml-4 text-sm font-medium text-gray-500 group-hover:text-gray-900">Checklist</span>
        </span>
      </a>
      <!-- Arrow separator for lg screens and up -->
      <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
        <svg class="h-full w-full text-gray-300" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
          <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor" stroke-linejoin="round" />
        </svg>
      </div>
    </li>
    <li class="relative md:flex md:flex-1">
      <!-- Upcoming Step -->
      <a href="javascript:void(0);" onClick="showStep(2)" class="group flex items-center">
        <span class="flex items-center px-6 py-4 text-sm font-medium">
          <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 border-indigo-600">
          <span class="text-indigo-600">02</span>
        </span>
          <span class="ml-4 text-sm font-medium text-gray-500 group-hover:text-gray-900">Email</span>
        </span>
      </a>
    </li>
  </ol>
</nav>
<div class="d-none stepper" id="stepper_1">
<div class="bg-gray-100">
    <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-4xl">
        
  <div class="bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:p-6">
   <fieldset>
  <legend class="sr-only">Checklist</legend>
  <div class="space-y-5">
    @foreach($checklist_items as $ck)
    <div class="relative flex items-start">
      <div class="flex h-6 items-center">
        <input id="comments" aria-describedby="comments-description" name="checklist_items[]" value="{{$ck['name']}}" @if($ck['checked']) checked="checked" @endif type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
      </div>
      <div class="ml-3 text-sm leading-6">
        <label for="comments" class="font-medium text-gray-900">{{$ck['name']}}</label>
      </div>
    </div>
    @endforeach
  </div>
</fieldset>
    </div>
  </div>

      </div>
    </div>
  </div>
</div>

<div class="d-none stepper" id="stepper_2">
<div class="bg-gray-100">
    <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-4xl">
        
  <div class="bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:p-6">
{!! $email_form !!}
    </div>
  </div>

      </div>
    </div>
  </div>
</div>
</div>
</form>
<script>
    function showStep(id){
        $('.stepper').addClass('d-none');
        $('#stepper_'+id).removeClass('d-none');
    }
    
    $(function() {
        @if($row->checklist_completed)
        showStep(2);
        @else
        showStep(1);
        @endif
    });
    
$(document).off('submit','#support_form').on('submit','#support_form', function(e) {
	$(".btn-toolbar").hide();
	e.preventDefault();
	 
   
        
	try{
		tinyMCE.triggerSave();	
	}catch(e){
	}
	
		
	
		
		formSubmit('support_form');
		if($("#messagebox").length > 0){
			$("#messagebox").hide();
		}
		for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
		var ed_id = tinymce.editors[i].id;
		tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
		}
	return false;
});
</script>