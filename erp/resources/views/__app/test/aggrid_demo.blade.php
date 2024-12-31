<!DOCTYPE html>
<html lang="en">
	<head>
		<title>JavaScript example</title>
		<meta charSet="UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<style media="only screen">
            html, body {
                height: 100%;
                width: 100%;
                margin: 0;
                box-sizing: border-box;
            }

            html {
                position: absolute;
                top: 0;
                left: 0;
                padding: 0;
                overflow: auto;
            }

            body {
                padding: 1rem;
                overflow: auto;
            }
            
.ag-unselectable {
     transition: top 100s, left 100s;
}
        </style>
	</head>
	<body class="ag-theme-alpine ag-row-animation">
		<div id="myGrid" style="height: 100%;" class="ag-theme-alpine ag-row-animation ">
		</div>
		<script>var __basePath = './';</script>
    <script src="https://unpkg.com/@ag-grid-enterprise/all-modules@26.0.1/dist/ag-grid-enterprise.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>

		</script>
		</script>
	</body>
</html>


<script>
$(document).ready(function () {
  $(".ag-row").addClass(".ag-row-animation");
})

var gridOptions = {
  columnDefs: [
    { field: 'athlete', rowDrag: true },
    { field: 'country' },
    { field: 'year', width: 100 },
    { field: 'date' },
    { field: 'sport' },
    { field: 'gold' },
    { field: 'silver' },
    { field: 'bronze' },
  ],
  defaultColDef: {
    width: 170,
    sortable: true,
    filter: true,
  },
  // this tells the grid we are doing updates when setting new data
  suppressContextMenu:true,
  onRowDragMove: onRowDragMove,
  defaultColDef:"defaultColDef",
      
  rowModelType: 'serverSide',
  serverSideStoreType: 'full',
  getRowNodeId: getRowNodeId,
  onSortChanged: onSortChanged,
  onFilterChanged: onFilterChanged,
  immutableData: false,
  animateRows: true,
  onGridReady: function onGridReady() {
      var datasource = {
          getRows(params) {
              fetch('aggrid_demo_data', {
                  method: 'post',
                  body: JSON.stringify(params.request),
                  headers: {"Content-Type": "application/json; charset=utf-8"}
              })
              .then(httpResponse => httpResponse.json())
              .then(response => {
                  
                  params.successCallback(response.rows, response.lastRow);
              
              })
              .catch(error => {
                  params.failCallback();
              })
          }
      };
      gridOptions.api.setServerSideDatasource(datasource);
  },
};

document.addEventListener('DOMContentLoaded', function () {
  var gridDiv = document.querySelector('#myGrid');
  new agGrid.Grid(gridDiv, gridOptions);
});


var sortActive = false;
var filterActive = false;

// listen for change on sort changed
function onSortChanged() {
  var sortModel = gridOptions.api.getSortModel();
  sortActive = sortModel && sortModel.length > 0;
  // suppress row drag if either sort or filter is active
  var suppressRowDrag = sortActive || filterActive;
  //console.log(
    'sortActive = ' +
      sortActive +
      ', filterActive = ' +
      filterActive +
      ', allowRowDrag = ' +
      suppressRowDrag
  );
  gridOptions.api.setSuppressRowDrag(suppressRowDrag);
}

// listen for changes on filter changed
function onFilterChanged() {
  filterActive = gridOptions.api.isAnyFilterPresent();
  // suppress row drag if either sort or filter is active
  var suppressRowDrag = sortActive || filterActive;
  //console.log(
    'sortActive = ' +
      sortActive +
      ', filterActive = ' +
      filterActive +
      ', allowRowDrag = ' +
      suppressRowDrag
  );
  gridOptions.api.setSuppressRowDrag(suppressRowDrag);
}

function getRowNodeId(data) {
  return data.id;
}

  
function onRowDragMove(event) {
  // //console.log(event);
  // var movingNode = event.node;
  // var overNode = event.overNode;

  // var rowNeedsToMove = movingNode !== overNode;
  // var rowStore = event.api.getRenderedNodes();
  
  // if (rowNeedsToMove) {
  //   // the list of rows we have is data, not row nodes, so extract the data
  //   var movingData = movingNode.data;
  //   var overData = overNode.data;

  //   var fromIndex = rowStore.indexOf(movingData);
  //   var toIndex = rowStore.indexOf(overData);

  //   var newStore = rowStore.slice();
  //   moveInArray(newStore, fromIndex, toIndex);

  //   rowStore = newStore;
  //   gridOptions.api.setRowData(rowStore);

  //   gridOptions.api.clearFocusedCell();
  // }

  // function moveInArray(arr, fromIndex, toIndex) {
  //   var element = arr[fromIndex];
  //   arr.splice(fromIndex, 1);
  //   arr.splice(toIndex, 0, element);
  // }
  

}

/*
function onRowDragMove(event) {
  var movingNode = event.node;
  var overNode = event.overNode;

  var rowNeedsToMove = movingNode !== overNode;

  if (rowNeedsToMove) {
    // the list of rows we have is data, not row nodes, so extract the data
    var movingData = movingNode.data;
    var overData = overNode.data;

    var fromIndex = immutableStore.indexOf(movingData);
    var toIndex = immutableStore.indexOf(overData);

    var newStore = immutableStore.slice();
    moveInArray(newStore, fromIndex, toIndex);

    immutableStore = newStore;
    gridOptions.api.setRowData(newStore);

    gridOptions.api.clearFocusedCell();
  }

  function moveInArray(arr, fromIndex, toIndex) {
    var element = arr[fromIndex];
    arr.splice(fromIndex, 1);
    arr.splice(toIndex, 0, element);
  }
}
*/

</script>