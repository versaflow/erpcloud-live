    function dropdowntargetrender(args){
    
        var el = args.element;   
        $(el).find("a").attr("title",args.item.text);
        if(args.item.border_top){
        $(el).addClass("menu_border_top");
        }
        
        if(args.item.new_tab == 1) {
        var el = args.element;
        $(el).find("a").attr("target","_blank");
        }
        
        
        if(args.item.cssClass > '') {
        var el = args.element;
        $(el).addClass(args.item.cssClass);
        }
        
        
        if(args.item.data_target == 'javascript') {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        $(el).find("a").attr("js-target",args.item.url);
        $(el).find("a").attr("id",args.item.url);
        $(el).find("a").attr("href","javascript:void(0)");
        
        }else if(args.item.data_target) {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        }
    }  
    
    function contextmenurender(args){
    
        var el = args.element;   
        $(el).find("a").attr("title",args.item.text);
        if(args.item.border_top){
        $(el).addClass("menu_border_top");
        }
        
        if(args.item.new_tab == 1) {
        var el = args.element;
        $(el).find("a").attr("target","_blank");
        }
        
        
        if(args.item.cssClass > '') {
        var el = args.element;
        $(el).addClass(args.item.cssClass);
        }
        
        
        if(args.item.data_target == 'javascript') {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        $(el).find("a").attr("js-target",args.item.url);
        $(el).find("a").attr("id",args.item.url);
        $(el).find("a").attr("href","javascript:void(0)");
        
        }else if(args.item.data_target) {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        }
    }
    
    
    function showSpinner(reference = false){
        $(".sidebarbtn").attr("disabled","disabled");
      //  if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
      //      reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
       
        if(reference){
            $(reference).busyLoad("show", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
            $(spinnerel).busyLoad("show", {
                animation: "slide"
            });
        }else{
           
            $(".gridcontainer").busyLoad("show", {
                animation: "slide"
            });
        }
    }
    
    function hideSpinner(reference = false){
        
                $(".sidebarbtn").removeAttr("disabled"); 
      // if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
       //     reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
        if(reference){
            $(reference).busyLoad("hide", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
        
            $(spinnerel).busyLoad("hide", {
                animation: "slide"
            });
        }else{
            $(".gridcontainer").busyLoad("hide", {
                animation: "slide"
            });
        }
    }

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
    
    
    
    function parseJsRule(jsRule) {
        console.log('parseJsRule',jsRule);
        let parsedRule = jsRule
            .replace(/{{/g, '')
            .replace(/}}/g, '')
            .replace(/!=/g, '!==')
            .replace(/==/g, '===')
            .replace(/&&/g, 'AND')
            .replace(/\|\|/g, 'OR')
            .replace(/===/g, '==')
            .replace(/!==/g, '!=');

        let conditions = parsedRule.split(/(AND|OR)/).map(condition => condition.trim());

        let rules = conditions.map(condition => {
            let [field, operator, value] = condition.split(/(==|!=)/).map(part => part.trim());
            return {
                field,
                operator: operator === '==' ? 'equal' : 'notEqual',
                value: value.replace(/'/g, '')
            };
        });

        let jsonRules = {
            condition: jsRule.includes('&&') ? 'and' : 'or',
            rules: rules
        };

        console.log('parseJsRule 2',jsonRules);
        return jsonRules;
    }

    function transformSqlToJs(sql) {
        console.log('transformSqlToJs',sql);
        let jsRule = sql
            .replace(/ AND /g, ' && ')
            .replace(/ OR /g, ' || ')
            .replace(/ = /g, ' == ')
            .replace(/ <> /g, ' != ')
            .replace(/\b(\w+)\b(?=\s*[!=]==?)/g, '{{$1}}');

        console.log('transformSqlToJs 2',jsRule);
        return jsRule;
    }