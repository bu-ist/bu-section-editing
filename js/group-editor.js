jQuery(document).ready(function($){

	// Active tab switching
	var $tabs = $('a.nav-tab');
	var $panels = $('div.edit_group_panel')

	$tabs.click(function(e){
		e.preventDefault();
		var $active = $(this);
		var $panel = $($active.attr('href'));

		$active.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$panel.addClass('active').siblings().removeClass('active');

		// make it fancy ?

	});

});