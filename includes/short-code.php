<?php
function remove_tags($str, $tags) 
{
    foreach($tags as $tag)
    {
    	$str = preg_replace('#<'.$tag.'>|<\/'.$tag.'>#', '', $str);
    }

    return $str;
}
function remove_invalid_tags($str, $tags) 
{
    foreach($tags as $tag)
    {	
    	$str = preg_replace('#^<\/'.$tag.'>|<'.$tag.'>$#', '', trim($str));
    }

    return $str;
}
add_shortcode('one_third', 'dukeyin_sc_one_third');
add_shortcode('two_third', 'dukeyin_sc_two_third');
add_shortcode('one_fourth', 'dukeyin_sc_one_fourth');
add_shortcode('three_fourth', 'dukeyin_sc_three_fourth');
add_shortcode('one_half', 'dukeyin_sc_one_half');

function dukeyin_sc_one_third($atts, $content=null, $shortcodename ="")
{
	$return = '<div class="col-md-4 col-sm-6">';
	$return .= do_shortcode($content);
	$return .= '</div>';
	return $return;
}
function dukeyin_sc_two_third($atts, $content=null, $shortcodename ="")
{
	$return = '<div class="col-md-8 col-sm-6">';
	$return .= do_shortcode($content);
	$return .= '</div>';
	return $return;
}
function dukeyin_sc_one_fourth($atts, $content=null, $shortcodename ="")
{
	$return = '<div class="col-md-3 col-sm-6">';
	$return .= do_shortcode($content);
	$return .= '</div>';
	return $return;
}
function dukeyin_sc_three_fourth($atts, $content=null, $shortcodename ="")
{
	$return = '<div class="col-md-9 col-sm-6">';
	$return .= do_shortcode($content);
	$return .= '</div>';
	return $return;
}
function dukeyin_sc_one_half($atts, $content=null, $shortcodename ="")
{
	$return = '<div class="col-sm-6">';
	$return .= do_shortcode($content);
	$return .= '</div>';
	return $return;
}


add_shortcode('hr', 'dukeyin_delimiter');
function dukeyin_delimiter($atts, $content=null, $shortcodename ="")
{
	$return = '<hr class="clearfix">';
	return $return;
}

add_shortcode('slideshow', 'dukeyin_sc_slideshow');
add_shortcode('slide', 'dukeyin_sc_slide');
function dukeyin_sc_slideshow($atts, $content=null, $shortcodename ="")
{	
	$return  = '<div class="row"><div class="slider" data-animation="fade" data-arrows="true" data-paging="true" data-timing="3000">';
	$return .= '<ul class="slides">';
	$return .= do_shortcode(strip_tags($content));
	$return .= '</ul>';
	$return .= '</div></div>';
	return $return;
}
function dukeyin_sc_slide($atts, $content=null, $shortcodename ="")
{	
	$return  = '<li>';
	if(isset($atts['link'])  && $atts['link'] != "") $return .= '<a href="'.$atts['link'].'">';
	$return .= '<img alt="" src="'.$atts['src'].'" />';
	if(isset($atts['link']) && $atts['link'] != "") $return .= '</a>';
	$return .= '</li>';
	return $return;
}
add_shortcode('toggle_container', 'dukeyin_sc_toggles');
add_shortcode('toggle', 'dukeyin_sc_toggle');
add_shortcode('tab_container', 'dukeyin_sc_tabs');
add_shortcode('tab', 'dukeyin_sc_tab_single');

function dukeyin_sc_toggles($atts, $content=null, $shortcodename =""){
	$content = remove_tags($content, array('p'));
	$one='';
	if(isset($atts['one'])) {$one = "accordion--oneopen";} 
	$return  = '<ul class="accordion '.$one.'">'."\n";
 	$return .= do_shortcode($content)."\n";
	$return .= '</ul>'."\n";
	return $return;
}

function dukeyin_sc_toggle($atts, $content=null, $shortcodename ="")
{
	$content = remove_tags($content, array('p'));
	$active[0] = $active[1] = '';
	if(isset($atts[0]) && $atts[0] == 'active') {$active[0] = 'active'; $active[1] = 'activetoggle open';}
	$return  = '<li class="'.$active[0].'"><div class="accordion__title"><span class="h5">'.$atts['title'].'</span></div>'."\n";
	$return .= '<div class="accordion__content">'."\n";
	$return .= do_shortcode(wpautop($content))."\n";
	$return .= '</div></li>'."\n";
	return $return;
}


function dukeyin_sc_tabs($atts, $content=null, $shortcodename ="")
{	
	$content = remove_tags($content, array('p'));
	$return  = '<section class="tabs-container tabs-5 text-center"><ul class="tabs">'."\n";
 	$return .= do_shortcode($content)."\n";
	$return .= '</ul></section>'."\n";
	return $return;
}
function dukeyin_sc_tab_single($atts, $content=null, $shortcodename ="")
{		
	$active = '';
	if(isset($atts[0]) && $atts[0] == 'active') $active = 'active';
	$return  = '<li class="'.$active.'"><div class="tab__title"><h6>'.$atts['title'].'</h6></div>'."\n";
	$return .= '<div class="tab__content"><div class="row"><div class="col-sm-8 col-sm-offset-2">'."\n";
	$return .= do_shortcode(wpautop($content))."\n";
	$return .= '</div></div></li>'."\n";
	return $return;
}
/**
 * dropcap.
 */
add_shortcode('dropcap1', 'dukeyin_sc_dropcaps');
add_shortcode('dropcap2', 'dukeyin_sc_dropcaps');
add_shortcode('dropcap3', 'dukeyin_sc_dropcaps');
function dukeyin_sc_dropcaps($atts, $content=null, $shortcodename ="")
{
	// add divs to the content
	$return = '<span class="'.$shortcodename.'">';
	$return .= do_shortcode($content);
	$return .= '</span>';
return $return;}
/**
 * Links.
 */
function post_inner_link($catid='') {
$post_link = wp_list_bookmarks(
array(
'orderby'=>'name',
'category'=>$catid,
'echo' =>0,
'show_updated' => 1,
'show_name' => 1,
'title_li'=>0,
'class'=> 'linkcat',
'before'=> '<div class="col-md-4 col-sm-6 link">',
'after'=> '</div>',
'link_before'=>'<p>',
'link_after'=>'</p>',
));
return $post_link;
}
add_shortcode('pil', 'post_inner_link'); 


/**
*
* === Alert Boxes ===
*
* Creates an alert box from the wrapped content
*
* 'type'  => '',	Appearance of the alert box - success, warning or none
* 'close' => true,	Whether you want to display close icon or not (to show use 1, to hide use 0)
* 
* @call [alert type='success']...[/alert]
* 
**/

function alert_func($atts, $content){

	extract(shortcode_atts(array(
	   'type' => '',
	   'close' => '1',
	), $atts)); 

	if ($close == '1') {
		$close = '<a href="#" class="close"><i class="iconfont closebt ico-modal-close-cross"></i></a>';
	}

	return do_shortcode("<div data-alert=\"\" class=\"alert-box text-center {$type}\">{$content}{$close}</div>");

}

add_shortcode("alert", "alert_func");


/**
*
* === Progress bar ===
*
* Creates a progress bar with custom options
*
* 'color'      => '',	Appearance of the progress - green, bluegreen, lightblue, lightpurple, blue, yellow, orange, grey, red, alert, success, disabled, #333
* 'background' => '',	Custom background color (#ccc)
* 'shape'      => '',	Shape of the progress - radius, round
* 'title'      => '',	Optional progress title
* 'percentage' => '',	Percentage of the progress fill bar (50 => progress is on 50%)
* 
* @call [progress title="Progress" percentage="50"]
* 
**/

function progress_func($atts, $content = null){

	extract(shortcode_atts(array(
	   'color'      => '',
	   'background' => '',
	   'shape'      => '',
	   'title'      => '',
	   'percentage' => '0',
	), $atts));


	/* Percentage */
	if ($percentage != '0') {
		$percentage = $percentage;
	}


	/* Custom Color */
	if (preg_match("#^\##", $color)) {
		$background = 'background: ' . $background . ';';
		$color = 'background: ' . $color . ';';
		return "<div class=\"barchart barchart-1\" data-value=\"{$percentage}\"><div class=\"barchart__description\"><span class=\"h6\">{$title}</span></div><div class=\"barchart__bar\"><div class=\"barchart__progress\"></div></div></div>";
	} else {
		$background = 'background: ' . $background . ';';
		return "<div class=\"barchart barchart-1\" data-value=\"{$percentage}\"><div class=\"barchart__description\"><span class=\"h6\">{$title}</span></div><div class=\"barchart__bar\"><div class=\"barchart__progress\"></div></div></div>";
	}
}

add_shortcode("progress", "progress_func");


/**
*
* === Panels ===
*
* Creates an panel with the content wrapped with shortcode
*
* 'color'      => '',	Appearance of the panel - callout, green, bluegreen, lightblue, lightpurple, blue, yellow, orange, grey, red, success, disabled, #333
* 'shape'      => '',	Shape of the panel - radius, round
* 
* @call [panel]...[/panel]
* 
**/

function panel_func($atts, $content){

	extract(shortcode_atts(array(
	   'color'  => '',
	   'shape' => '',
	), $atts)); 

	if (preg_match("#^\##", $color)) {
		return do_shortcode("<div style=\"background: {$color}; box-shadow: none; -webkit-box-shadow: none;\" class=\"panel {$shape}\">{$content}</div>");
	} else {
		return do_shortcode("<div class=\"panel {$color} {$shape}\">{$content}</div>");
	}

}

add_shortcode("panel", "panel_func");

/**
*
* === Highlight Text ===
*
* Highlighs text with custom color or predefined one
*
* 'color' => '', Custom colors - green, bluegreen, lightblue, lightpurple, blue, yellow, orange, grey, red, success, disabled, #333
* 
* @call [highlight color="red"]...[/highlight]
* 
**/

function highlight_func($atts, $content){

	extract(shortcode_atts(array(
	   'color' => '',
	), $atts)); 

	if (preg_match("#^\##", $color)) {
		return "<span style=\"color: {$color}\" class=\"mark-text\">{$content}</span>";
	} else {
		return "<span class=\"mark-text {$color}\">{$content}</span>";
	}

}

add_shortcode("highlight", "highlight_func");

/**
*
* === Mark Text ===
*
* Mark text with custom color or predefined one
*
* 'color' => '', Custom colors - green, bluegreen, lightblue, lightpurple, blue, yellow, orange, grey, red, success, disabled, #333
* 
* @call [marktext color="red"]...[/marktext]
* 
**/

function marktext_func($atts, $content){

	extract(shortcode_atts(array(
	   'color' => '',
	), $atts)); 

	if (preg_match("#^\##", $color)) {
		return "<mark style=\"background: {$color}\">{$content}</mark>";
	} else {
		return "<mark class=\"{$color}\">{$content}</mark>";
	}

}

add_shortcode("marktext", "marktext_func");

/**
*
* === Subheader ===
*
* Adds subheader class to wrapped heading
*
* @call [subheader]...[/subheader]
* 
**/

function subheader_func($atts, $content){
	return do_shortcode("<span class=\"subheader\">{$content}</span>");
}

add_shortcode("subheader", "subheader_func");

/**
*
* === Icons ===
*
* Add's class to selected text with custom icon 
*
* 'type' => '',		Icon class, see the documentation 
* 
* @call [icon type="icon-aim"]...[/icon]
* 
**/

function icon_func($atts, $content){

	extract(shortcode_atts(array(
	   'type' => '',
	), $atts)); 

	return do_shortcode("<i class=\"iconfont {$type}\"></i>{$content}");

}

add_shortcode("icon", "icon_func");

/*button*/
function button_func($atts, $content = null){

	extract(shortcode_atts(array(
	   'title' => '',
	   'url'  => '',
	), $atts)); 
return do_shortcode("<a class=\"btn btn--primary mb--1\" href=\"{$url}\"><span class=\"btn__text\">{$title}</span></a>");
}
add_shortcode("button", "button_func");

/**
*
* === Pie Chart ===
*
* Radial Bar percentage
* 'size'      => '',	How big is this pie chart
* 'title'      => '',	Optional progress title
* 'percentage' => '',	Percentage of the progress fill bar (50 => progress is on 50%)
* 
* @call [piechart title="90%" size="200" percentage="90"]
* 
**/
function piechart_func($atts, $content = null){
	extract(shortcode_atts(array(
	   'title'      => '',
	   'size'      => '200',
	   'percentage' => '0',
	), $atts));
	/* Percentage */
	if ($percentage != '0') {
		$percentage = $percentage;
	}
		if ($size != '200') {
		$size = $size;
	}
		return "<div class=\"row\"><div class=\"piechart piechart-1\" data-size=\"{$size}\" data-value=\"{$percentage}\"><div class=\"piechart__overlay\"><div class=\"piechart__description\"><span class=\"h3\">{$title}</span></div></div></div></div>";
}
add_shortcode("piechart", "piechart_func");