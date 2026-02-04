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

/**
 * Column shortcodes.	
 */

///
function theme_shortcode_column( $atts, $content = null, $tag = '' ) {

    $map = [
        'one_third'     => 'col-12 col-md-4 col-sm-6',
        'two_third'     => 'col-12 col-md-8 col-sm-6',
        'one_half'      => 'col-12 col-md-6 col-sm-6',
        'one_fourth'    => 'col-12 col-md-3 col-sm-6',
        'three_fourth'  => 'col-12 col-md-9 col-sm-6',
    ];

    if ( ! isset( $map[ $tag ] ) ) {
        return '';
    }

    return sprintf(
        '<div class="%s">%s</div>',
        esc_attr( $map[ $tag ] ),
        do_shortcode( $content )
    );
}
foreach ( [
    'one_third',
    'two_third',
    'one_half',
    'one_fourth',
    'three_fourth',
] as $shortcode ) {
    add_shortcode( $shortcode, 'theme_shortcode_column' );
}
///
add_shortcode( 'row', 'dukeyin_shortcode_row' );
function dukeyin_shortcode_row( $atts, $content = null ) {
	$content = remove_tags($content, array('p'));
    return '<div class="row">' . do_shortcode( $content ) . '</div>';
}

add_shortcode('hr', 'dukeyin_delimiter');
function dukeyin_delimiter($atts, $content=null, $shortcodename ="")
{
	$return = '<hr class="clearfix">';
	return $return;
}

// [slideshow]
// [slide src='图片URL地址']
// [slide src='图片URL地址']
// [slide src='图片URL地址']
// [/slideshow]

// 定义全局变量用于传递 ID 和计数
global $dukeyin_carousel_id;
global $dukeyin_carousel_count;

// 1. 父容器 Shortcode: [slideshow]
function dukeyin_sc_slideshow($atts, $content = null) {
    global $dukeyin_carousel_id, $dukeyin_carousel_count;

    // 生成唯一的 Carousel ID
    $dukeyin_carousel_id = 'carousel-' . uniqid();
    
    // 初始化计数器
    $dukeyin_carousel_count = 0;

    // 关键步骤：先执行 do_shortcode 解析内部的 [slide]
    // 这样子元素会执行并增加 $dukeyin_carousel_count 计数，同时生成图片 HTML
    $slides_html = do_shortcode($content);

    // 此时 $dukeyin_carousel_count 已经是图片的总数了
    $total_slides = $dukeyin_carousel_count;

    // --- 开始构建 HTML ---
    
    $output = '<div id="' . esc_attr($dukeyin_carousel_id) . '" class="carousel slide" data-bs-ride="carousel">';

    // A. 生成 Indicators (底部的指示器/小圆点)
    if ($total_slides > 1) {
        $output .= '<div class="carousel-indicators">';
        for ($i = 0; $i < $total_slides; $i++) {
            $active_class = ($i === 0) ? 'active' : '';
            $aria_current = ($i === 0) ? 'true' : 'false';
            $output .= '<button type="button" data-bs-target="#' . esc_attr($dukeyin_carousel_id) . '" data-bs-slide-to="' . $i . '" class="' . $active_class . '" aria-current="' . $aria_current . '" aria-label="Slide ' . ($i + 1) . '"></button>';
        }
        $output .= '</div>';
    }

    // B. 输出 Slides (由子短代码生成的 HTML)
    $output .= '<div class="carousel-inner">';
    $output .= $slides_html;
    $output .= '</div>';

    // C. 生成 Controls (左右切换箭头) - 只有多于1张图时才显示
    if ($total_slides > 1) {
        $output .= '<button class="carousel-control-prev" type="button" data-bs-target="#' . esc_attr($dukeyin_carousel_id) . '" data-bs-slide="prev">';
        $output .= '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
        $output .= '<span class="visually-hidden">Previous</span>';
        $output .= '</button>';
        
        $output .= '<button class="carousel-control-next" type="button" data-bs-target="#' . esc_attr($dukeyin_carousel_id) . '" data-bs-slide="next">';
        $output .= '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
        $output .= '<span class="visually-hidden">Next</span>';
        $output .= '</button>';
    }

    $output .= '</div>'; // End carousel

    return $output;
}
add_shortcode('slideshow', 'dukeyin_sc_slideshow');


// 2. 子元素 Shortcode: [slide]
function dukeyin_sc_slide($atts) {
    global $dukeyin_carousel_count;

    // 提取属性
    $atts = shortcode_atts(
        array(
            'src' => '', // 图片地址
            'alt' => 'Slide Image', // 图片描述
        ), 
        $atts
    );

    // 如果没有图片地址，直接返回空
    if (empty($atts['src'])) {
        return '';
    }

    // 判断是否是第一张幻灯片（如果是，添加 active 类）
    $active_class = ($dukeyin_carousel_count === 0) ? 'active' : '';

    // 构建 HTML
    // d-block w-100 是 Bootstrap 建议的类，防止图片尺寸塌陷
    $output  = '<div class="carousel-item ' . $active_class . '">';
    $output .= '<img src="' . esc_url($atts['src']) . '" class="d-block w-100" alt="' . esc_attr($atts['alt']) . '">';
    $output .= '</div>';

    // 计数器加 1，为下一张图做准备
    $dukeyin_carousel_count++;

    return $output;
}
add_shortcode('slide', 'dukeyin_sc_slide');


///Accordion
///[toggle_container]
// [toggle title='标题1']内容1[/toggle]
// [toggle title='标题2']内容2[/toggle]
// [toggle title='标题3']内容3[/toggle]
// [/toggle_container]

// 定义全局变量以在父子短代码间传递 ID
global $dukeyin_accordion_id;
global $dukeyin_accordion_count;

// 1. 父容器 Shortcode: [toggle_container]
function dukeyin_sc_toggles($atts, $content = null) {
    global $dukeyin_accordion_id, $dukeyin_accordion_count;

    // 生成当前 Accordion 组的唯一 ID (防止页面有多个 Accordion 时冲突)
    $dukeyin_accordion_id = 'accordion-' . uniqid();
    
    // 重置计数器
    $dukeyin_accordion_count = 0;

    // 解析内部的 [toggle] 短代码
    // 注意：这里先处理 content，子短代码会使用上面的全局变量
	$content = remove_tags($content, array('p'));
    $items = do_shortcode($content);

    // 输出 Bootstrap 5.3 外层容器结构
    return '<div class="accordion" id="' . esc_attr($dukeyin_accordion_id) . '">' . $items . '</div>';
}
add_shortcode('toggle_container', 'dukeyin_sc_toggles');


// 2. 子元素 Shortcode: [toggle]
function dukeyin_sc_toggle($atts, $content = null) {
    global $dukeyin_accordion_id, $dukeyin_accordion_count;

    // 提取属性，默认标题为 'Title'
    $atts = shortcode_atts(
        array(
            'title' => 'Title',
        ), 
        $atts
    );

    // 增加计数，生成当前 Item 的唯一 ID
    $dukeyin_accordion_count++;
    $item_id = $dukeyin_accordion_id . '-item-' . $dukeyin_accordion_count;
    $heading_id = $item_id . '-heading';

    // 逻辑：默认展开第一个选项 (Bootstrap 标准行为)
    // 如果你想全部默认关闭，可以将下面的 $is_first 设置为 false
    $is_first = ($dukeyin_accordion_count === 1);

    $collapse_class = $is_first ? 'accordion-collapse collapse show' : 'accordion-collapse collapse';
    $button_class   = $is_first ? 'accordion-button' : 'accordion-button collapsed';
    $aria_expanded  = $is_first ? 'true' : 'false';

    // 构建 HTML
    $output  = '<div class="accordion-item">';
    
    // Header & Button
    $output .= '<h2 class="accordion-header" id="' . esc_attr($heading_id) . '">';
    $output .= '<button class="' . $button_class . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . esc_attr($item_id) . '" aria-expanded="' . $aria_expanded . '" aria-controls="' . esc_attr($item_id) . '">';
    $output .= esc_html($atts['title']);
    $output .= '</button>';
    $output .= '</h2>';

    // Body (注意 data-bs-parent 指向父级 ID)
    $output .= '<div id="' . esc_attr($item_id) . '" class="' . $collapse_class . '" aria-labelledby="' . esc_attr($heading_id) . '" data-bs-parent="#' . esc_attr($dukeyin_accordion_id) . '">';
    $output .= '<div class="accordion-body">';
    // 支持内容中嵌套其他短代码，并移除多余的 P 标签
    $output .= do_shortcode(wpautop(trim($content))); 
    $output .= '</div>';
    $output .= '</div>'; // End collapse
    
    $output .= '</div>'; // End item

    return $output;
}
add_shortcode('toggle', 'dukeyin_sc_toggle');


add_shortcode('tab_container', 'dukeyin_sc_tabs');
add_shortcode('tab', 'dukeyin_sc_tab_single');

function dukeyin_sc_tabs($atts, $content=null, $shortcodename ="")
{	
	$content = remove_tags($content, array('p'));
	$return  = '<section class="tabs-container tabs-5 text-center"><ul class="tabs">';
 	$return .= do_shortcode($content);
	$return .= '</ul></section>';
	return $return;
}
function dukeyin_sc_tab_single($atts, $content=null, $shortcodename ="")
{		
	$active = '';
	if(isset($atts[0]) && $atts[0] == 'active') $active = 'active';
	$return  = '<li class="'.$active.'"><div class="tab__title"><h6>'.$atts['title'].'</h6></div>';
	$return .= '<div class="tab__content"><div class="row"><div class="col-sm-8 col-sm-offset-2">';
	$return .= do_shortcode(wpautop($content));
	$return .= '</div></div></li>';
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
function post_inner_link() {
$post_link = wp_list_bookmarks(
array(
'orderby'=>'name',
'category'=>'',
'category_orderby'=>'name',
'category_name'=>'',
'categorize'=>1,
'hide_invisible'=>1,
'show_description'=>0,
'echo' =>0,
'show_updated' =>0,
'show_name' => 1,
'title_li'=>__( 'Bookmarks','duke-yin-helper' ),
'title_before'     => '<h3>',
'title_after'      => '</h3>',
'class'=> 'linkcat',
'before'=> '<div class="col-md-4 col-sm-6 link">',
'after'=> '</div>',
'link_before'=>'<p>',
'link_after'=>'</p>',
// 'echo'=>1,
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
	   'type' => 'danger',
	   'close' => '1',
	), $atts)); 

	if ($close == '1') {
		$close = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
	}

	return do_shortcode("<div data-alert=\"\" class=\"alert alert-dismissible fade show alert-{$type}\">{$content}{$close}</div>");

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
* @call [progress title="Progress" percentage="50"] [progress title="Download" percentage="75" color="success"]  [progress title="Uploading" percentage="40" striped="true" animated="true"]
* 
**/

function dukeyin_shortcode_progress( $atts ) {

    $atts = shortcode_atts( [
        'title'      => '',
        'percentage' => 0,
        'height'     => '',
        'color'      => '',   // primary, success, warning...
        'striped'    => false,
        'animated'   => false,
    ], $atts, 'progress' );

    // 数值安全
    $percentage = intval( $atts['percentage'] );
    $percentage = max( 0, min( 100, $percentage ) );

    // Progress bar class
    $bar_classes = [ 'progress-bar' ];

    if ( $atts['color'] ) {
        $bar_classes[] = 'bg-' . sanitize_html_class( $atts['color'] );
    }

    if ( filter_var( $atts['striped'], FILTER_VALIDATE_BOOLEAN ) ) {
        $bar_classes[] = 'progress-bar-striped';
    }

    if ( filter_var( $atts['animated'], FILTER_VALIDATE_BOOLEAN ) ) {
        $bar_classes[] = 'progress-bar-animated';
    }

    // Inline style
    $style = 'width: ' . $percentage . '%;';
    if ( $atts['height'] ) {
        $style .= ' height: ' . esc_attr( $atts['height'] ) . ';';
    }

    ob_start();
    ?>

    <div class="mb-3">
        <?php if ( $atts['title'] ) : ?>
            <div class="mb-1 small fw-semibold">
                <?php echo esc_html( $atts['title'] ); ?>
            </div>
        <?php endif; ?>

        <div class="progress" role="progressbar"
             aria-valuenow="<?php echo esc_attr( $percentage ); ?>"
             aria-valuemin="0"
             aria-valuemax="100">
            <div
                class="<?php echo esc_attr( implode( ' ', $bar_classes ) ); ?>"
                style="<?php echo esc_attr( $style ); ?>">
                <?php echo esc_html( $percentage ); ?>%
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'progress', 'dukeyin_shortcode_progress' );


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
		return do_shortcode("<div style=\"background: {$color}; box-shadow: none; -webkit-box-shadow: none;\" class=\"card {$shape}\"><div class=\"card-body\">{$content}</div></div>");
	} else {
		return do_shortcode("<div class=\"card {$color} {$shape}\"><div class=\"card-body\">{$content}</div></div>");
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
		return "<mark style=\"color: {$color}\">{$content}</mark>";
	} else {
		return "<mark class=\"{$color}\">{$content}</mark>";
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
	return do_shortcode("<span class=\"text-body-secondary\">{$content}</span>");
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
return do_shortcode("<a class=\"btn btn-primary mb--1\" href=\"{$url}\"><span class=\"btn__text\">{$title}</span></a>");
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