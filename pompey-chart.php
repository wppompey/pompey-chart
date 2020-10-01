<?php
/**
 * Plugin Name:     Pompey Chart
 * Plugin URI: 		https://github.com/wppompey/pompey-chart.git
 * Description:     Displays a chart of WordPress Portsmouth Meetup attendees over time
 * Version:         0.0.0
 * Author:          andrewleonard, bobbingwide
 * Author URI: 		https://wp-pompey.org.uk
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     pompey-chart
 *
 * @package         pompey-chart
 */



add_action( 'init', 'pompey_chart_init' );

function pompey_chart_init() {
	add_shortcode( 'pompey_chart', 'pompey_chart_shortcode');
	add_action( 'wp_enqueue_scripts', 'pompey_chart_enqueue_scripts' );
}


function pompey_chart_enqueue_scripts() {
	wp_enqueue_style( 'chartist-style', 'https://cdnjs.cloudflare.com/ajax/libs/chartist/0.10.1/chartist.min.css' );
	wp_enqueue_style( 'chart-style', 'https://www.andrew-leonard.co.uk/Chart/Tooltip.css' );
	wp_enqueue_style( 'chart-extra-style', 'https://www.andrew-leonard.co.uk/Chart/Extra.css' );


	//wp_enqueue_script('leaflet-script', 'https://unpkg.com/leaflet@1.0.3/dist/leaflet.js' );
	wp_enqueue_script('chartist-script', 'https://cdnjs.cloudflare.com/ajax/libs/chartist/0.10.1/chartist.min.js' );
	wp_enqueue_script('chartist-tooltip-script', 'https://unpkg.com/chartist-plugin-tooltips@0.0.17/dist/chartist-plugin-tooltip.js' );
	wp_enqueue_script('chartist-legend-script', 'https://cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.1/chartist-plugin-legend.min.js' );
}



/**
 *


 * [pompey_chart]Date,Series1,Series2,Series3
 * 01/18,1,1,1
 * 02/18,2,2,2
 * 03/18,3,3,3
 * [/pompey_chart ]

 *
 */
function pompey_chart_shortcode( $atts, $content, $tag ) {
	$html = "Pompey Chart";
	$lines = pompey_chart_data( $atts, $content );
	$height = isset( $atts['height']) ? $atts['height'] : "450px";

	$html .= pompey_chart( $lines, $height );
	return $html;
}

function pompey_chart_data( $atts, $content ) {
	if ( $content ) {
		$content = str_replace( '<br />', '', $content );
		$lines = explode( "\n", $content );
		//print_r( $lines );
		//gob();

	} else {

		$filename = dirname( __FILE__);

		$filename .= '/meetup-stats.csv';
		//echo $filename;
		if ( !file_exists( $filename) ) {
			$filename='https://www.andrew-leonard.co.uk/Chart/meetup-stats.csv';
		}
		$lines=file( $filename,FILE_IGNORE_NEW_LINES );

	}
	return $lines;
}


function pompey_chart( $lines, $height ) {

	//$html =count( $lines );
	$html = "<div class=\"ct-chart\"></div>";
	$script = pompey_chart_process( $lines, $height );
	$html .= pompey_chart_inline_script(  $script );
	return $html;
}

function pompey_chart_inline_script( $script  ) {
	$html = '<script type="text/javascript">';
	$html .= $script;
	$html .= '</script>';
	return $html;
}

function pompey_chart_process( $lines, $height ) {

	$Date   ="";
	$null   ="";
	$tick   ="'";
	$comma  =",";
	$series1="";
	$series2="";
	$series3="";
	$bit1   ="{meta: ";
	$bit2   =",value: ";
	$bit3   ="}";
	$newline="<br>\n";
	foreach ( $lines as $line_num=>$line ) {
		//$line = trim( ',', $line );
		if ( substr( $line, 0, 4 ) <> "Date" ) {
			if ( substr( $line, 6, 4 ) <> ",,,," ) {
				$line  =str_replace( "'", "", $line );
				$line  =str_replace( '"', "", $line );
				$pieces=explode( ",", $line );
				if ( $Date == "" ) {
					$Date=$tick . $pieces[0] . $tick;
				} else {
					$Date.=$comma . $tick . $pieces[0] . $tick;
				}
				if ( $series1 == "" ) {
					$series1=$bit1 . $tick . $pieces[4] . $tick . $bit2 . $pieces[1] . $bit3;
				} else {
					$series1.=$comma . $bit1 . $tick . $pieces[4] . $tick . $bit2 . $pieces[1] . $bit3;;
				}
				if ( $series2 == "" ) {
					$series2=$bit1 . $tick . $tick . $bit2 . $pieces[2] . $bit3;
				} else {
					$series2.=$comma . $bit1 . $tick . $tick . $bit2 . $pieces[2] . $bit3;;
				}
				if ( $series3 == "" ) {
					$series3=$bit1 . $tick . $tick . $bit2 . $pieces[3] . $bit3;
				} else {
					$series3.=$comma . $bit1 . $tick . $tick . $bit2 . $pieces[3] . $bit3;;
				}
			}
		}
	}
	$data = pompey_chart_javascript( $Date, $series1, $series2, $series3, $height );
	return $data;
}

function pompey_chart_javascript( $Date, $series1, $series2, $series3, $height = '500px') {

	$Data = "var chart=new Chartist.Line('.ct-chart',{labels:[";
	$Data .= $Date;
	$Data .=  '],';
	$Data .= 'series:[[';
	$Data .= $series1;
	$Data .= '],[';
	$Data .= $series2;
	$Data .= '],[';
	$Data .= $series3;
	$Data .= ']]},';
	$Data .= "{fullWidth:true,width:'100%',height:'";
	$Data .= $height;
	$Data .= "',chartPadding:{right:0,left:0},";
	$Data .= "plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});";
	return $Data;
}
	/*
	echo "<h1 style=\"text-align:center;\">Portsmouth MeetUp Statistics</h1>";
	echo "<div class=\"ct-chart\"></div>";
	echo "<script>";
	echo $Data;
	echo "</script>";
	*/

/*
 * $Data="var chart=new Chartist.Line('.ct-chart',{labels:[".$Date."],
 * series:[[".$series1."],[".$series2."],[".$series3."]]}
 * ,{fullWidth:true,width:'100%',height:'700px',chartPadding:{right:50,left:50}
 * ,plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});";

 */

