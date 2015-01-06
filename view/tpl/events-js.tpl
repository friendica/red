{{$tabs}}
<div class="generic-content-wrapper-styled">
<h2>{{$title}}</h2>


<div id="export-event-link"><button class="btn btn-default btn-sm" onclick="exportDate(); return false;" >{{$export.1}}</button></div>
<div id="new-event-link"><button class="btn btn-default btn-sm" onclick="window.location.href='{{$new_event.0}}'; return false;" >{{$new_event.1}}</button></div>

<script>
function exportDate() {
    var moment = $('#events-calendar').fullCalendar('getDate');
	var sT = 'events/' + moment.getFullYear() + '/' + (moment.getMonth() + 1) + '/export';
    window.location.href=sT;
}
</script>

<div id="events-calendar"></div>
</div>
