var countDownDate = $(".countdown-date").html();

/*$(".tab-content").countdown(countDownDate, function(event) {
    var format = '';
    if(event.offset.days > 0) {
        format = format + '%-d day%!d ';
    }   
    if(event.offset.hours > 0 || event.offset.days > 0) {
        format = format + '%-H hour%!H ';
    }
    if(event.offset.minutes > 0 || event.offset.hours > 0) {
        format = format + '%-M minute%!M ';
    }
    format = format + '%-S second%!S ';
    $(this).text(
        event.strftime(format)
    );
})
.on('finish.countdown', function(event) {
    $(this).html('');
    $(".not-ready-message").hide();
    $(".ready-message").show();
    $(".launch-button").show();
});*/

$(".tab-content").countdown(countDownDate).on('update.countdown', function(event){
    var format = '';

    format = '<br /><center><table id="countdown-table" border="0"><tr><td align="center" colspan="6"><div class="numbers" id="count2" style="padding: 5px 0 0 0; "></div></td></tr>';
    
    var numsrow = '<tr id="spacer1"><td align="center" ><div class="countdown-numbers" ></div></td>';
    var textrow = '<tr id="spacer2"><td align="center" ><div class="countdown-title" ></div></td>';

    if(event.offset.days > 0) {
        //format = format + '%-d day%!d ';
        numsrow = numsrow + '<td align="center" ><div class="countdown-numbers" id="dday">%-d</div></td>';
        textrow = textrow + '<td align="center" ><div class="countdown-title" id="days">Day%!d</div></td>';
    }   
    if(event.offset.hours > 0 || event.offset.days > 0) {
        //format = format + '<span id="countdown-hours">%-H</span> hour%!H ';
        numsrow = numsrow + '<td align="center" ><div class="countdown-numbers" id="dhour">%-H</div></td>';
        textrow = textrow + '<td align="center" ><div class="countdown-title" id="hours">Hour%!H</div></td>';
    }
    if(event.offset.minutes > 0 || event.offset.hours > 0) {
        //format = format + '<span id="countdown-minutes">%-M</span> minute%!M ';
        numsrow = numsrow + '<td align="center" ><div class="countdown-numbers" id="dmin">%-M</div></td>';
        textrow = textrow + '<td align="center" ><div class="countdown-title" id="minutes">Minute%!M</div></td>';
    }
    //format = format + '<span id="countdown-seconds">%-S</span> second%!S ';
    numsrow = numsrow + '<td align="center" ><div class="countdown-numbers" id="dsec">%-S</div></td><td align="center" ><div class="countdown-numbers" ></div></td></tr>';
    textrow = textrow + '<td align="center" ><div class="countdown-title" id="seconds">Second%!S</div></td><td align="center" ><div class="countdown-title" ></div></td></tr>';

    format = format + numsrow + textrow + '</tr></table></center>';

    var $this = $(this).html(event.strftime(format));
}).on('finish.countdown', function(event) {
    $(this).html('');
    $(".not-ready-message").hide();
    $(".ready-message").show();
    $(".launch-button").show();
});
