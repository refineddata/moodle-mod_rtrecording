$(document).ready(function () {
    get_rtrecording_filter();

    mod_rtrecording_add_tooltip();

    $('body').on("click", "#rtrecording-update-from-adobe", function(event){
        event.preventDefault();
        var block = $(this);
        var rtrecording_id = block.data('rtrecordingid');
        $('#recordingcontent' + rtrecording_id ).html('');
        $('#recordingcontent' + rtrecording_id ).addClass('rt-loading-image');
        $.ajax({
            url: window.wwwroot + "/mod/rtrecording/ajax/rtrecording_callback.php",
            dataType: "html",
            data: {
                rtrecording_id: rtrecording_id,
                update_from_adobe: 1,
            }   
        }).done(function (data) {
            $('#recordingcontent' + rtrecording_id ).removeClass('rt-loading-image');
            $('#recordingcontent' + rtrecording_id ).html(data);
            mod_rtrecording_add_tooltip();
        }); 
    });
});

function mod_rtrecording_add_tooltip(){
    if (typeof($.uitooltip) != 'undefined') {
        $('.rtrecording_tooltip').uitooltip({
            show: null, // show immediately
            items: '.rtrecording_tooltip',
            content: function () {
                return $(this).next('.rtrecording_popup').html();
            },
            position: {my: "left top", at: "right top", collision: "flipfit"},
            hide: {
                effect: "" // fadeOut
            },
            open: function (event, ui) {
                ui.tooltip.animate({left: ui.tooltip.position().left + 10}, "fast");
            },
            close: function (event, ui) {
                ui.tooltip.hover(
                    function () {
                        $(this).stop(true).fadeTo(400, 1);
                    },
                    function () {
                        $(this).fadeOut("400", function () {
                            $(this).remove();
                        })
                    }
                );
            }
        });
    }
}

function get_rtrecording_filter() {
    $('.rtrecording_display_block').each(function (index) {
        var block = $(this);
        var acurl = block.data('acurl');
        var courseid = block.data('courseid');
        var rtrecording_id = block.data('rtrecording_id');
        block.removeClass('rtrecording_display_block').addClass('rtrecording_display_block_done');
        $.ajax({
            url: window.wwwroot + "/mod/rtrecording/ajax/rtrecording_callback.php",
            dataType: "html",
            data: {
                acurl: acurl,
                courseid: courseid,
                rtrecording_id: rtrecording_id,
                options: encodeURIComponent(block.data('options')),
            }
        }).done(function (data) {
            block.html(data);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            add_rtrecording_filter_alert(block, 'danger', jqXHR.status + " " + jqXHR.statusText);
        });
    });
}

function add_rtrecording_filter_alert(block, type, msg) {
    block.html(
        '<div class="fitem" id="fgroup_id_urlgrp_alert">' +
        '<div class="felement fstatic alert alert-' + type + ' alert-dismissible">' +
        '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>' +
        msg +
        '</div>' +
        '</div>'
    );
}
