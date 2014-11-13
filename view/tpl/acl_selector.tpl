<div class="modal" id="aclModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">{{$aclModalTitle}}</h4>
			</div>
			<div class="modal-body">
				<div id="acl-wrapper">
					<button id="acl-showall" class="btn btn-block btn-default"><i class="icon-globe"></i> {{$showall}}</button>
					<input type="text" id="acl-search" placeholder="&#xf002;">
					<div id="acl-list">
						<div id="acl-list-content"></div>
					</div>
					<span id="acl-fields"></span>
				</div>
				<div class="acl-list-item" rel="acl-template" style="display:none">
					<img data-src="{0}"><p>{1}</p>
					<button class="acl-button-hide btn btn-xs btn-default"><i class="icon-remove"></i> {{$hide}}</button>
					<button class="acl-button-show btn btn-xs btn-default"><i class="icon-ok"></i> {{$show}}</button>
				</div>
				{{if $jotnets}}
				{{$jotnets}}
				{{/if}}
			</div>
			<div class="modal-footer clear">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$aclModalDismiss}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
//$(document).ready(function() {
//	setTimeout( function () {
		if(typeof acl=="undefined"){
			acl = new ACL(
				baseurl+"/acl",
				[ {{$allowcid}},{{$allowgid}},{{$denycid}},{{$denygid}} ]
			);
		}
//	}, 5000 );
//});
</script>
