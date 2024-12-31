
<style> 
.sw-btn-prev{
display:none !important;    
}
</style>
<script>

   
    
    function showSpinnerWindow(){
        try {
            $("html").busyLoad("show", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function hideSpinnerWindow(){
        try {
            $("html").busyLoad("hide", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function validate_session(){
        $.get('validate_session', function(data) {
            if(data == 'logout'){
                window.location.href = '{{ url("/") }}';
            }
        });
    }
    function isMobile() {
try{ document.createEvent("TouchEvent"); return true; }
catch(e){ return false; }
}
</script>
    <div class="container">
        <!-- SmartWizard html -->
        <div id="smartwizard">
            <ul>
  			    @for($i=1;$i<=$num_steps;$i++)
  			    <li><a href="#step-{{$i}}" >Step {{$i}}</a></li>
                @endfor
            </ul>

            <div>
                @for($i=1;$i<=$num_steps;$i++)
                <div id="step-{{$i}}" >	
                </div>
                @endfor
            </div>
        </div>
    </div>


    <script type="text/javascript">
    $(function(){

        // Toolbar extra buttons
        @if($current_step != $num_steps)
        var btnFinish = $('<button id="activate_submit_btn"></button>').text('Next')
        .addClass('btn btn-info')
        .on('click', function(){  
        $("#provision_form").submit();
        return false;
        });
        @else
        var btnFinish = $('<button id="activate_submit_btn"></button>').text('Submit')
        .addClass('btn btn-info')
        .on('click', function(){  
        $("#provision_form").submit();
        return false;
        });
        @endif
           

        // Smart Wizard
        $('#smartwizard').smartWizard({
            selected: {{$selected_step-1}},
            theme: 'arrows',
            transitionEffect:'fade',
            contentCache: false,
            useURLhash: false,
            showStepURLhash: false,
            backButtonSupport: false,
            keyNavigation: false,
            cycleSteps: false,
            contentURL: "{{ url('/provision_service/'.$service_table.'/'.$provision->id) }}",
            ajaxSettings: {method: 'GET'},
            toolbarSettings: {
                toolbarPosition: 'bottom',
                @if($current_step != 1)
                showPreviousButton: true,
                @endif
                showNextButton: false,
                toolbarExtraButtons: [btnFinish]
                                }
        });
             
          
    });   
        // Initialize the leaveStep event
        $("#smartwizard").on("leaveStep", function(e, anchorObject, stepNumber, stepDirection) {
               
        });
        
        // Initialize the showStep event
        $("#smartwizard").on("showStep", function(e, anchorObject, stepNumber, stepDirection) {
            var response = $(anchorObject[0].hash).html();
            if(testJSON(response)){
                dialog.hide();
                var json = JSON.parse(response); 
                processAjaxSuccess(json);
            }
        });
        
        function testJSON(text) { 
            if (typeof text !== "string") { 
                return false; 
            } 
            try { 
                JSON.parse(text); 
                return true; 
            } catch (error) { 
                return false; 
            } 
        } 
        
        $(document).off('click', '#activate_submit_btn').on('click', '#activate_submit_btn', function() {
            $("#activate_submit_btn").attr('disabled','disabled');
        });

    </script>