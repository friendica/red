<p id="hide-friends-text">
{{$desc}}
</p>

		<div id="hide-friends-yes-wrapper">
		<label id="hide-friends-yes-label" for="hide-friends-yes">{{$yes_str}}</label>
		<input type="radio" name="hide-friends" id="hide-friends-yes" {{$yes_selected}} value="1" />

		<div id="hide-friends-break" ></div>	
		</div>
		<div id="hide-friends-no-wrapper">
		<label id="hide-friends-no-label" for="hide-friends-no">{{$no_str}}</label>
		<input type="radio" name="hide-friends" id="hide-friends-no" {{$no_selected}} value="0"  />

		<div id="hide-friends-end"></div>
		</div>
