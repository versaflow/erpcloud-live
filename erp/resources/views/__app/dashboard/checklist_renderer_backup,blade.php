<script>
    
     init(params) {
   
    //console.log('init',params);
    this.eGui = document.createElement('div');
    this.type = params.data.type;
    this.eGui.innerHTML = '';
    if(params.data.type == 'Process' && params.data.module_id > 0 && params.data.layout_id > 0){
      
      var iframe_url = module_urls[params.data.module_id]+'?layout_id='+params.data.layout_id+'&from_iframe=1';
      this.eGui.innerHTML = '<iframe src="'+iframe_url+'" width="100%" frameborder="0px" height="300px"  style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> ';
      
    }
    if(params.data.type == 'Project'){
       // trick to convert string of HTML into DOM object
   // var iframe_url = module_urls[1955]+'?task_id='+params.data.id+'&from_iframe=1';
     // this.eGui.innerHTML = '<iframe src="'+iframe_url+'" width="100%" frameborder="0px" height="300px"  style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> ';
     
      this.eGui.innerHTML = '<div id="taskchecklist'+params.data.id+'" class="workboard_checklist"></div>';
      
      this.eGui.task_id = params.data.id;
      
      this.setupTaskChecklist();
      
    }
    
  }
  
  setupTaskChecklist(){
    if (this.type == 'Project' && this.eGui.task_id) {
    // Define a function to check if the element is in the DOM.
    const checkElementInDOM = () => {
      const element = document.getElementById('taskchecklist' + this.eGui.task_id);
      if (element) {
        //console.log('render');
        // The element is in the DOM, so render the ListView.
        render_task_listview(this.eGui.task_id, 'taskchecklist' + this.eGui.task_id);
      } else {
        //console.log('timeout');
        // The element is not in the DOM, so recheck after a delay.
        setTimeout(checkElementInDOM, 100); // You can adjust the delay as needed.
      }
    };

    // Start checking for the element in the DOM.
    checkElementInDOM();
  }
  }
  
  getGui() {
   
    return this.eGui;
  }
  
  

  refresh(params) {
    //console.log('refresh',params);
    return false;
  }
</script>