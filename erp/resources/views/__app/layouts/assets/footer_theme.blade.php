<!--   Core JS Files   -->
<script src="/softui/assets/js/core/popper.min.js"></script>
<script src="/softui/assets/js/core/bootstrap.min.js"></script>
<script src="/softui/assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="/softui/assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="/softui/assets/js/plugins/datatables.js"></script>
<!-- jQuery -->
<script>
// Save the original jQuery ajax method
/*
var originalAjax = $.ajax;

$.ajax = function(settings) {
    // Save any custom beforeSend function defined in the AJAX call
    var customBeforeSend = settings.beforeSend;

    // Define the global beforeSend function
    var globalBeforeSend = function(xhr, settings) {
        // Extract the current URL parameters
        var params = new URLSearchParams(settings.url.split('?')[1]);
        // Check if 'cidb' is present in the current page URL
        var cidb = new URLSearchParams(window.location.search).get('cidb');
        // Append 'cidb' to the AJAX request URL if present
        if (cidb) {
            params.set('cidb', cidb);
        }
        // Reconstruct the URL with updated parameters
        settings.url = settings.url.split('?')[0] + '?' + params.toString();
        //console.log('Global beforeSend');
       // console.log('Final URL:', settings.url);
    };

    // Directly modify the settings.url before the AJAX call
    // Call the global beforeSend function immediately to modify the URL
    globalBeforeSend(null, settings);

    // Override the beforeSend function to include both the global and custom logic
    settings.beforeSend = function(xhr) {
        // If a custom beforeSend function is defined, call it as well
        if (customBeforeSend && typeof customBeforeSend === 'function') {
            return customBeforeSend(xhr, settings);
        }
    };

    // Log the settings before calling the original ajax method
   // console.log('AJAX settings before request:', settings);

    // Call the original jQuery ajax method with the modified settings
    return originalAjax(settings);
};
*/
</script>


<!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
<!--
<script src="/softui/assets/js/soft-ui-dashboard.js?v=1.1.1"></script>
-->