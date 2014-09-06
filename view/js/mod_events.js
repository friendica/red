
$(document).ready( function() { showHideFinishDate(); });

function showHideFinishDate() {
	if( $('#event-nofinish-checkbox').is(':checked'))
		$('#event-finish-wrapper').hide();
	else
		$('#event-finish-wrapper').show();
}



	function eventGetStart() {
		//reply = prompt("{{$expirewhen}}", $('#jot-expire').val());
		$('#startModal').modal();
		$('#start-modal-OKButton').on('click', function() {
    	reply=$('#start-date').val();
    	if(reply && reply.length) {
			$('#start-text').val(reply);
			$('#startModal').modal('hide');
		}
		})
		
		
	}
	function eventGetFinish() {
		//reply = prompt("{{$expirewhen}}", $('#jot-expire').val());
		$('#finishModal').modal();
		$('#finish-modal-OKButton').on('click', function() {
    	reply=$('#finish-date').val();
    	if(reply && reply.length) {
			$('#finish-text').val(reply);
			$('#finishModal').modal('hide');
		}
		})
				
	}
