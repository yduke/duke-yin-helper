(function( $ ) {
	'use strict';
$( document ).ready(function() {
	$("#toplevel_page_edit-post_type-film_review ul.wp-submenu li:nth-child(2) a").addClass('wp-menu-image dashicons-before dashicons-book-alt');
	$("#toplevel_page_edit-post_type-film_review ul.wp-submenu li:nth-child(3) a").addClass('wp-menu-image dashicons-before dashicons-games');
	$("#toplevel_page_edit-post_type-film_review ul.wp-submenu li:nth-child(4) a").addClass('wp-menu-image dashicons-before dashicons-video-alt');
	$("#toplevel_page_edit-post_type-film_review ul.wp-submenu li:nth-child(5) a").addClass('wp-menu-image dashicons-before dashicons-products');
	$("#toplevel_page_edit-post_type-film_review ul.wp-submenu li:nth-child(6) a").addClass('wp-menu-image dashicons-before dashicons-video-alt3');
//Game score calculate
if($('body').hasClass('post-type-game_review')) {
$('input').on('blur',function(){
	var $graphic = $('#graphic-score').val();
		var $graphic_w;
		$graphic == 0?$graphic_w = 0:$graphic_w = 0.2;
	var $audios = $('#audios-score').val();
		var $audios_w;
		$audios == 0?$audios_w = 0:$audios_w = 0.05;
	var $narrative = $('#narrative-score').val();
		var $narrative_w;
		$narrative == 0?$narrative_w = 0:$narrative_w = 0.25;
	var $technical = $('#technical-score').val();
		var $technical_w;
		$technical == 0?$technical_w = 0:$technical_w = 0.1;
	var $gameplay = $('#gameplay-score').val();
		var $gameplay_w;
		$gameplay == 0?$gameplay_w = 0:$gameplay_w = 0.4;
	var score = 0;
	score = (parseInt($graphic)*$graphic_w + parseInt($audios)*$audios_w + parseInt($narrative)*$narrative_w + parseInt($technical)*$technical_w + parseInt($gameplay)*$gameplay_w)/($graphic_w+$audios_w+$narrative_w+$technical_w+$gameplay_w);
	$('#ranking-score').val(score.toFixed(1));
});
};
});
})( jQuery );