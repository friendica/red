<a href='{{$embedurl}}' onclick='this.innerHTML=Base64.decode("{{$escapedhtml}}"); return false;' style='float:left; margin: 1em; position: relative;'>
	<img width='{{$tw}}' height='{{$th}}' src='{{$turl}}' >
	<div style='position: absolute; top: 0px; left: 0px; width: {{$twpx}}; height: {{$thpx}}; background: url({{$baseurl}}/images/icons/48/play.png) no-repeat center center;'></div>
</a>
