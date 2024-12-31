{!! Form::open(array("url"=> "github_issue", "class"=>"form-horizontal","id"=> "github_issue")) !!}	

<div class="row mt-3">
    <div class="col">
        <div class="card">
          
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="title">  Title </label>
                    </div>
                    <div class="col-md-9">
                    <input id="title" name="title" class='e-input' />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="description"> Description </label>
                    </div>
                    <div class="col-md-9">
                    <textarea id="description" name="description" rows="5" class='e-input'></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">
    title = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    title.appendTo("#title");
    
    description = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    description.appendTo("#description");

$('#github_issue').on('submit', function(e) {
	e.preventDefault();
   formSubmit("github_issue");
   
});
</script>