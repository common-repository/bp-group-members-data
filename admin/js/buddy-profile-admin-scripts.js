jQuery(document).ready(function($) { 
	$("#pp-profile-data-fields ul").sortable({ 
		cursor: 'move' 
	}); 

	$('#pp-profile-data-fields ul').disableSelection(); 
	$('#pp-profile-data-fields li').disableSelection(); 
});
