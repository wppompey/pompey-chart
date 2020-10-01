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
	wp_enqueue_script('leaflet-script', 'https://unpkg.com/leaflet@1.0.3/dist/leaflet.js' );
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
	//$html = pompey_chart();
	return $html;
}


function pompey_chart()
{
	$lines = file('https://www.andrew-leonard.co.uk/Chart/meetup-stats.csv', FILE_IGNORE_NEW_LINES);
	$Date="";
	$null="";
	$tick="'";
	$comma=",";
	$series1="";
	$series2="";
	$series3="";
	$bit1="{meta: ";
	$bit2=",value: ";
	$bit3="}";
	$newline="<br>\n";
	foreach ($lines as $line_num => $line)
	{
		if  (substr($line,0,4)<>"Date")
		{
			if  (substr($line,6,4)<>",,,,")
			{
				$line=str_replace("'","",$line);
				$line=str_replace('"',"",$line);
				$pieces = explode(",", $line);
				if ($Date=="") {$Date= $tick.$pieces[0].$tick;}
				else {$Date.= $comma.$tick.$pieces[0].$tick;}
				if ($series1=="") {$series1= $bit1.$tick.$pieces[4].$tick.$bit2.$pieces[1].$bit3;}
				else {$series1.= $comma.$bit1.$tick.$pieces[4].$tick.$bit2.$pieces[1].$bit3;;}
				if ($series2=="") {$series2= $bit1.$tick.$tick.$bit2.$pieces[2].$bit3;}
				else {$series2.= $comma.$bit1.$tick.$tick.$bit2.$pieces[2].$bit3;;}
				if ($series3=="") {$series3= $bit1.$tick.$tick.$bit2.$pieces[3].$bit3;}
				else {$series3.= $comma.$bit1.$tick.$tick.$bit2.$pieces[3].$bit3;;}
			}
		}
	}
	$Data="var chart=new Chartist.Line('.ct-chart',{labels:[".$Date."],series:[[".$series1."],[".$series2."],[".$series3."]]},{fullWidth:true,width:'100%',height:'700px',chartPadding:{right:50,left:50},plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});";
	echo "<h1 style=\"text-align:center;\">Portsmouth MeetUp Statistics</h1>";
	echo "<div class=\"ct-chart\"></div>";
	echo "<script>";
	echo $Data;
	echo "</script>";
}