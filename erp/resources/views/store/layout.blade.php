<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="softui/assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="softui/assets/img/favicon.png">
  <title>
   {{ ($page_title) ? $page_title : 'Store' }}
  </title>
  <!--     Fonts and icons     -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="softui/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="softui/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link href="softui/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- CSS Files -->
  <link id="pagestyle" href="softui/assets/css/soft-ui-dashboard.css?v=1.1.1" rel="stylesheet" />

  @yield('scripts')
  @yield('styles')
  @yield('page-styles')
</head>

<body class="g-sidenav-show  bg-gray-100">
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    @include('store.partials.navbar')
    <div class="container py-4">
    @yield('content')
    @include('store.partials.footer')
    </div>
  </main>

  <!--   Core JS Files   -->
  <script src="softui/assets/js/core/popper.min.js"></script>
  <script src="softui/assets/js/core/bootstrap.min.js"></script>
  <script src="softui/assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="softui/assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="softui/assets/js/plugins/datatables.js"></script>
  <script>
    if (document.getElementById('products-list')) {
      const dataTableSearch = new simpleDatatables.DataTable("#products-list", {
        searchable: true,
        fixedHeight: false,
        perPage: 7
      });

      document.querySelectorAll(".export").forEach(function(el) {
        el.addEventListener("click", function(e) {
          var type = el.dataset.type;

          var data = {
            type: type,
            filename: "soft-ui-" + type,
          };

          if (type === "csv") {
            data.columnDelimiter = "|";
          }

          dataTableSearch.export(data);
        });
      });
    };
  </script>
  <!-- Kanban scripts -->
  <script src="softui/assets/js/plugins/dragula/dragula.min.js"></script>
  <script src="softui/assets/js/plugins/jkanban/jkanban.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="softui/assets/js/soft-ui-dashboard.min.js?v=1.1.1"></script>
  @yield('page-scripts')
</body>

</html>