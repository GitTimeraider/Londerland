<?php
$GLOBALS['londerlandPages'][] = 'homepage';
function get_page_homepage($Londerland = null)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	return '
<script>
(function () {
    "use strict";
    var calendarElement = document.getElementById("calendar");
    if (!calendarElement || typeof FullCalendar === "undefined") {
        return;
    }
    // FullCalendar 7 view names for the views stored in Londerland\'s settings
    var calendarViews = { month: "dayGridMonth", basicWeek: "dayGridWeek", basicDay: "dayGridDay", list: "listUpcoming" };
    var dayMaxEvents = ' . (int)$Londerland->config['calendarLimit'] . ';
    window.londerlandCalendar = new FullCalendar.Calendar(calendarElement, {
        locale: "' . $Londerland->config['calendarLocale'] . '",
        buttons: {
            filterCalendar: {
                text: window.lang.translate("Filter"),
                click: function () {
                    toggleCalendarFilter();
                }
            },
            refreshCalendar: {
                text: window.lang.translate("Refresh"),
                click: function () {
                    homepageCalendar();
                }
            }
        },
        initialView: (activeInfo.mobile) ? "listUpcoming" : (calendarViews["' . $Londerland->config['calendarDefault'] . '"] || "dayGridMonth"),
        firstDay: ' . (int)$Londerland->config['calendarFirstDay'] . ',
        eventTimeFormat: calendarTimeFormat("' . $Londerland->config['calendarTimeFormat'] . '"),
        headerToolbar: {
            left: "prev,next,today",
            center: "title",
            right: (activeInfo.mobile) ? "refreshCalendar,filterCalendar" : "refreshCalendar,filterCalendar,dayGridMonth,dayGridWeek,dayGridDay,listUpcoming"
        },
        views: {
            dayGridDay: { buttonText: window.lang.translate("Day"), dayMaxEvents: dayMaxEvents },
            dayGridWeek: { buttonText: window.lang.translate("Week"), dayMaxEvents: dayMaxEvents },
            dayGridMonth: { buttonText: window.lang.translate("Month"), dayMaxEvents: dayMaxEvents },
            listUpcoming: { type: "list", duration: { days: 15 }, buttonText: window.lang.translate("List") }
        },
        timeZone: "local",
        editable: false,
        navLinks: true,
        selectable: false,
        height: "auto"
    });
    londerlandCalendar.render();
})();
$(".homepage-loading-box").fadeOut(5000);
</script>
<div class="container-fluid p-t-30" id="homepage-items">
    ' . $Londerland->buildHomepage() . '
</div>
<div id="open-youtube" class="white-popup mfp-with-anim mfp-hide">
    <div class="col-lg-8 offset-lg-2 youtube-div">  </div>
</div>
<!-- /.container-fluid -->

';
}