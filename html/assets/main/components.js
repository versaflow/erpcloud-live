class CustomLoadingOverlay {
  init(params) {
    this.eGui = document.createElement('div');
    this.eGui.innerHTML = `
            <div class="ag-custom-loading-cell" style="background-color: #fff; padding-left: 10px; line-height: 25px; font-size: 15px;">  
                <i class="fas fa-spinner fa-pulse"></i> 
                <span><b>${params.loadingMessage} </b></span>
            </div>
        `;
  }

  getGui() {
    return this.eGui;
  }
}

class CustomTooltip  {
    
    init(params) {
        const eGui = this.eGui = document.createElement('div');
        const color = '#fff';
        console.log('tooltip params',params);
        eGui.classList.add('custom-tooltip');
        //@ts-ignore
        eGui.style['background-color'] = color;
        eGui.innerHTML = `
            <div>${params.value}</div>
        `;
    }

    getGui() {
        return this.eGui;
    }
}

function booleanCellRenderer(params){
  //  console.log(params);
    if(params.value === "1" || params.value === 1 || params.value === "true" ){
    return "Yes";
    }
    if(params.value === "0" || params.value === 0 || params.value === "false" ){
    return "No";
    }
  
   // console.log(params.value);
    return params.value;
}