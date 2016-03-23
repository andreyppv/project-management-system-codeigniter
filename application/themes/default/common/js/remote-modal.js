$(document).ready(function(){
	$('body').append('<div id="modal" class="modal fade"></div>');

		$('[data-load-remote]').on('click',function(e) {
		    e.preventDefault();
		    var $this = $(this);
		    var remote = $this.data('load-remote');
		    if(remote) {
		        $('#modal').load(remote, function(){
		        	jsElements();
		        });
		    }
		});

});


