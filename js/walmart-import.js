jQuery(document).ready(function($){
    let interval = null;

    function fetchProgress(){
        $.get(gokulWalmartAjax.ajaxurl, { action: 'gokul_walmart_import_progress' }, function(data){
            if(!data || typeof data !== 'object') return;
            var imported = data.imported || 0;
            var total = data.total || 0;
            var error = data.error || '';
            var done = !data.in_progress && total > 0;
            var percent = total ? Math.floor(imported * 100 / total) : 0;

            $("#walmart-progress-bar")
                .css('width', percent + '%')
                .text(percent + '%');

            $("#walmart-progress-imported").text(imported);
            $("#walmart-progress-total").text(total);
            $("#walmart-progress-pending").text(total - imported);
            $("#walmart-progress-message").html(error ? '<span style="color:red">Error: '+error+'</span>' : (done ? '<b>Import Complete!</b>' : 'Importing...'));

            if(done && interval) clearInterval(interval);
        });
    }

    $("#walmart-import-btn").on('click', function(){
        $(this).prop('disabled',true).text('Importing...');
        $.post(gokulWalmartAjax.ajaxurl, { action: 'gokul_walmart_start_import', _ajax_nonce: gokulWalmartAjax.startImportNonce }, function(resp){
            if(resp.success){
                fetchProgress();
                interval = setInterval(fetchProgress, 2000);
            } else {
                alert(resp.data || "Failed to start import.");
                $("#walmart-import-btn").prop('disabled',false).text('Start Import');
            }
        });
    });

    // Auto-refresh if import already in progress
    fetchProgress();
    interval = setInterval(fetchProgress, 2000);
});