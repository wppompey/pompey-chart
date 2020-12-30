<?php
/**
 * Plugin Name:     Pompey Chart
 * Plugin URI: 		https://github.com/wppompey/pompey-chart.git
 * Description:     Displays a chart of WordPress Portsmouth Meetup attendees over time
 * Version:         0.2.0
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
	add_shortcode( 'chart', 'pompey_chart_chart_shortcode');
	add_action( 'wp_enqueue_scripts', 'pompey_chart_enqueue_scripts' );
}

/**
 * Chartist and its plugins:
 * - https://github.com/gionkunz/chartist-js/
 * - https://github.com/tmmdata/chartist-plugin-tooltip
 * - https://github.com/CodeYellowBV/chartist-plugin-legend
 *
 * https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.js
 * <script src="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.js" integrity="sha512-9rxMbTkN9JcgG5euudGbdIbhFZ7KGyAuVomdQDI9qXfPply9BJh0iqA7E/moLCatH2JD4xBGHwV6ezBkCpnjRQ==" crossorigin="anonymous"></script>
 * @TODO Tooltips don't work when enqueued locally. Probably a versioning problem.
 * Need to find out what source Andrew used that pointed him to cloudflare and unpkg.
 */

function pompey_chart_enqueue_local() {
	pompey_chart_enqueue_local_styles();
	pompey_chart_enqueue_local_scripts();
}

function pompey_chart_enqueue_local_styles() {
	$path=plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'chartist-style', $path . '/dist/chartist.min.css' );
	wp_enqueue_style( 'chart-style', $path . '/Chart/Tooltip.css' );
	wp_enqueue_style( 'chart-extra-style', $path . '/Chart/Extra.css' );
}

function pompey_chart_enqueue_local_scripts() {
	$path = plugin_dir_url( __FILE__ );
	wp_enqueue_script('chartist-script', $path . '/dist/chartist.min.js' );
	wp_enqueue_script('chartist-tooltip-script', $path . '/dist/chartist-plugin-tooltip.js' );
	wp_enqueue_script('chartist-legend-script', $path . '/dist/chartist-plugin-legend.js' );
}

function pompey_chart_enqueue_scripts() {
	pompey_chart_enqueue_local_styles();

	/*
	$path = plugin_dir_url( __FILE__ );

	wp_enqueue_style( 'chartist-style', 'https://cdnjs.cloudflare.com/ajax/libs/chartist/0.10.1/chartist.min.css' );
	// __DIR__ /
	wp_enqueue_style( 'chart-style', plugin_dir_url( __FILE__ ) . '/Chart/Tooltip.css' );
	wp_enqueue_style( 'chart-extra-style', 'https://www.andrew-leonard.co.uk/Chart/Extra.css' );
	*/

	//wp_enqueue_script('leaflet-script', 'https://unpkg.com/leaflet@1.0.3/dist/leaflet.js' );
	wp_enqueue_script('chartist-script', 'https://cdnjs.cloudflare.com/ajax/libs/chartist/0.10.1/chartist.min.js' );
	wp_enqueue_script('chartist-tooltip-script', 'https://unpkg.com/chartist-plugin-tooltips@0.0.17/dist/chartist-plugin-tooltip.js' );
	wp_enqueue_script('chartist-legend-script', 'https://cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.1/chartist-plugin-legend.min.js' );
}

/**
 * Implements the generic [chart] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content The chart's raw data. CSV format
 * @param string $tag The shortcode.
 */
function pompey_chart_chart_shortcode( $atts, $content, $tag ) {

	bw_trace2();
	$atts = pompey_chart_default_atts( $atts );


	if ( $content ) {
		$content=trim( $content );
		$content=str_replace( '<br />', '', $content );
		$lines  =explode( "\n", $content );
	}
	//$legend = $lines[0];

	$legend=array_shift( $lines );

	//GetSeries( $lines );
	$series=pompey_chart_transpose( $lines );
	bw_trace2( $series, "series" );
	//print_r( $series );
	$id  =pompey_chart_id();
	$html= "<div class=\"ct-chart\" id=\"$id\"></div>";
	// Data: Labels and Series
	if ( $atts['type'] !== 'Pie') {
		$script = pompey_chart_data_label_series( $series, $atts );
		$jsonlegend=pompey_chart_json_legend( $legend );

		$script.=pompey_chart_options( $atts, $jsonlegend );
		$script.=pompey_chart_type( $atts['type'] );
		$script.="'#$id',data,options );";
	} else {
		//$script = 'var data = { series: [' . implode( ',', $series[0] ) . ']};';
		$script = pompey_chart_data_label_series( $series, $atts );
		$script.=pompey_chart_options( $atts, null );
		$script.=pompey_chart_type( $atts['type'] );
		$script .= "'#$id',data, options );";
	}
	$html .=  pompey_chart_inline_script( $script );

	// Parameters
	// Plugins

	//$script = pompey_chart_process( $id, $lines, $height );
	return $html;
}

function pompey_chart_default_atts( $atts ) {
	if ( empty( $atts ) ) {
		$atts = [];

	}
	//echo "before";	print_r( $atts );

	$atts['type'] = isset( $atts['type'] ) ? $atts['type'] : 'Line';
	$atts['title']=isset( $atts['title'] ) ? $atts['title'] : 'Chart';
	$atts['height'] = isset( $atts['height'] ) ? $atts['height'] : '450px';

	$atts['type'] = pompey_chart_validate_type( $atts['type'] );
	$atts['tooltips'] = isset( $atts['tooltips']) ? true : false;
	$atts['stackBars'] = isset( $atts['stackbars']) ? true : false;
	//echo "after";
	//print_r( $atts );
	return $atts;

}

function pompey_chart_validate_type( $type ) {
	//echo $type;
	switch ( $type ) {
		case 'Line':
		case 'Bar':
		case 'Pie':
			break;
		case 'line':
			$type='Line';
			break;
		case 'bar':
			$type='Bar';
			break;
		case 'pie':
			$type='Pie';
			break;
		default:
			$type='Line';
	}
	//echo $type;
	return $type;
}

/**
 * Returns the data parameter for Chartist.
 *
 * If we need tooltips for a series then we have to use arrays of
 * [ { meta: 'tooltip 1', value: value1 }, [meta: 'tooltip 2', value: value2 }, etc ]
 * to replace the simple arrays:
 * [ value1, value2 ]
 *
 *
 * @param $series
 * @param $atts
 *
 * @return false|string
 */

function pompey_chart_data_label_series(  $series, $atts ) {
	// If tooltips are required these are the last column.
	// Otherwise we use the same as the legend.
	if ( $atts['tooltips'] ) {
		$tooltips=array_pop( $series );
	} else {
		$tooltips = null;
	}
	$data = new stdClass();
	// Line and Bar
	if ( 'Pie' !== $atts['type'] ) {
		$data->labels = array_shift( $series );
		$data->series = [];
		foreach ( $series as $index=>$seriesn ) {
			if ( $tooltips ) {
				$data->series[]=pompey_chart_data_seriesn( $seriesn, $tooltips );
			} else {
				$data->series[] = $seriesn;
			}
		}
	} else {
		$data->labels = $series[0];
		$data->series = $series[1];
	}
	$json = json_encode( $data );

	$script = "var data = $json;\n";
	//echo $script;
	return $script;

}

function pompey_chart_data_seriesn( $seriesn, $tooltips ) {
	$metavalues=[];
	foreach ( $seriesn as $key=>$value ) {
		$metavalue=new StdClass();
		$metavalue->meta =$tooltips[ $key ];
		$metavalue->value=$value;
		$metavalues[]    =$metavalue;
	}
	return $metavalues;
}

/**
 * Returns Chartist options parameter.
 *
 * ```
$Data .= "{fullWidth:true,width:'100%',height:'";
$Data .= $height
$Data .= "',chartPadding:{right:0,left:0},";
$Data .= "plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});";
 * ```
 * @param array $atts array of attributes
 * @param $legend string Heading line - to use for Legends
 * @return string
 */

function pompey_chart_options( $atts, $jsonlegend=null ) {
	$options                     =new StdClass();

	//echo print_r( $atts );
	if ( $atts['type'] !== 'Pie') {
		$options->fullWidth          =true;
		$options->width              ='100%';
		$options->height             =$atts['height'];
		$options->chartPadding = new StdClass();
		$options->chartPadding->right = 80;
		$options->chartPadding->left = 40;
		$options->plugins='repl_plugins';
		$options->stackBars = $atts['stackBars'];
	} else {
		//$options->donut = true;
		// $options->donutWidth = 60;
		$options->startAngle = 270;
		$options->showLabel = true;
		//$options->chartPadding = 300;
		$options->labelDirection = 'explode';
	}
	$json                        =json_encode( $options, JSON_UNESCAPED_SLASHES );
	if ( $atts['type'] !== 'Pie') {
		$plugins='Chartist.plugins.tooltip(),';
		$plugins.='Chartist.plugins.legend(' . $jsonlegend . ')';
		$json   =str_replace( '"repl_plugins"', "[$plugins]", $json );
	}
	$script                      ="var options = $json;\n";
	//echo $script;

	return $script;
}

/**
 * Returns the legend for the Chartist legend plugin.
 * JSON returns:
 * {"legendNames":"Date,A,B,Tooltip"}
 * {legendNames:['Attendees','Joined','Members'],
 * but we want
 *
 * @param $legend
 *
 * @return false|string
 */

function pompey_chart_json_legend( $legend ) {
	$legends = explode( ',', $legend);
	array_shift( $legends);
	$legendstring = "{legendNames:['";
	$legendstring .= implode( "','", $legends);
	$legendstring .= "'],}";
	return $legendstring;
}


function pompey_chart_series( $series ) {
	/*
	foreach $series as $series
	$Data .= 'series:[[';
	$Data .= $series1;
	$Data .= '],[';
	$Data .= $series2;
	$Data .= '],[';
	$Data .= $series3;
	$Data .= ']]},';
	*/
	$html ='';
	return $html;
}

/**
 * Returns the Chartist invocation.
 *
 * @param string $type
 *
 * @return string
 */
function pompey_chart_type( $type="Line") {
		return "var chart=new Chartist.$type( ";
	}
	/*
	function pompey_chart_javascript( $id, $Date, $series1, $series2, $series3, $height = '500px') {
			bw_trace2();
			$Data = "var chart=new Chartist.Line( '#$id'
			,{labels:[";
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
	*/
/*
<p>Pompey Chart</p>
<div class="ct-chart" id="chart1"></div>
<p><script type="text/javascript">
var chart=new Chartist.Line(
'#chart1'
,{labels:['Jul-20','Aug-20','Sep-20'],
series:[
[{meta: 'How to build your WordPress website - Part Two',value: 17},{meta: 'Plugin Kollektiv',value: 15},{meta: 'Whats new in WordPress 5.5. and how to use it',value: 39}],
[{meta: '',value: 8},{meta: '',value: 7},{meta: '',value: 14}],
[{meta: '',value: 187},{meta: '',value: 194},{meta: '',value: 208}]
]}
,{fullWidth:true,width:'100%',height:'400px',chartPadding:{right:0,left:0},
plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});
</script>
*/




/**
 * Transposes the input CSV into the series array.
 *
 * $series[0] will be the labels for the x-axis - along the bottom
 * We assume that there's a value for each row and column.
 *
 * @param $lines
 * @return array
 */

function pompey_chart_transpose( $lines ) {
	$series = [];
	foreach ( $lines as $line ) {
		$values = explode( ',', $line);
		foreach ( $values as $key => $value ) {
			$series[ $key ][] = $value;
		}
	}
	return $series;
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

/**
 * To support multiple charts in the post content
 * we need to set a unique ID for each chart

 * @param $lines
 * @param $height
 *
 * @return string
 */
function pompey_chart( $lines, $height ) {
	$id = pompey_chart_id();
	$html = "<div class=\"ct-chart\" id=\"$id\"></div>";
	$script = pompey_chart_process( $id, $lines, $height );
	$html .= pompey_chart_inline_script(  $script );
	return $html;
}

function pompey_chart_id() {
	static $id = 0;
	$id++;
	return 'chart' . $id;
}

function pompey_chart_inline_script( $script  ) {
	$html = '<script type="text/javascript">';
	$html .= $script;
	$html .= '</script>';
	return $html;
}

function pompey_chart_process( $id, $lines, $height ) {

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


	$data = pompey_chart_javascript( null, $Date, $series1, $series2, $series3, $height );
	return $data;
}

function pompey_chart_javascript( $id, $Date, $series1, $series2, $series3, $height = '500px') {
	bw_trace2();

	if ( null === $id ) {
		$Data="var chart=new Chartist.Line( '.ct-chart',{labels:[";
	} else {
		$Data="var chart=new Chartist.Line( '#$id',{labels:[";
		gob();
	}
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
	$Data .= "plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members']})]});";
	return $Data;
}


/*
 * $Data="var chart=new Chartist.Line('.ct-chart',{labels:[".$Date."],
 * series:[[".$series1."],[".$series2."],[".$series3."]]}
 * ,{fullWidth:true,width:'100%',height:'700px',chartPadding:{right:50,left:50}
 * ,plugins:[Chartist.plugins.tooltip(),Chartist.plugins.legend({legendNames:['Attendees','Joined','Members'],})]});";

 */

