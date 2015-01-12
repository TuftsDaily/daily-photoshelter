jQuery(function($){
    $("img").error(function() {
        $(this).attr("src", PS_WP_ROOT + "/wp-content/plugins/photoshelter-official-plugin/img/uploader-badge.png");
    });

    var url = $('#gal-sorter').attr('data-url');
    
    function resort(){
        var sort_by = $('#gal-sorter').find(":selected").val();
        var sort_dir = $('#sort-dir').attr('data-dir');
        document.location.href = url + "&sort_by=" + sort_by + "&sort_dir=" + sort_dir;
    }

    $('#gal-sorter').bind('change', function(e){
        resort();
    });

    $('#sort-dir').bind('click', function(e){
        var dir = $(this).attr('data-dir');
        $('#sort-dir').attr('data-dir', dir == 'asc' ? 'desc' : 'asc');
        resort();
    });
});
