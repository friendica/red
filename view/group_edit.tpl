<h2>Group Editor</h2>


<div id="group-edit-wrapper" >
<form action="group/$gid" id="group-edit-form" method="post" >
<div id="group-editname-wrapper" >
<label id="group-edit-name-label" for="group-edit-name" >Group Name: </label>
<input type="text" name="groupname" value="$name" />
</div>
<div id="group-edit-name-end"></div>
<div id="group-edit-select-wrapper" >
<label id=group_members_select_label"  for="group_members_select" >Members:</label>
$selector

</div>
<div id="group-edit-select-end" ></div>
</form>
</div>
