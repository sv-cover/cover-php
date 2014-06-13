<?php
	/* 
		This page (optimized for full screen display on a 1080p screen) provides an overview of the current weather in Groningen.
		The weather data is provided by buienradar.nl (documentation http://gratisweerdata.buienradar.nl/ and http://gps.buienradar.nl/).
		The icons used are a (slightly) modified version of the ones created by Spovv (http://sm-artists.com/?page_id=925).
		
		Author: Martijn Luinstra (martijnluinstra@gmail.com)
		06-06-2014
	*/

	$xml_data = simplexml_load_file('http://xml.buienradar.nl/');

	// Find the data of Groningen
	foreach($xml_data->weergegevens->actueel_weer->weerstations->weerstation as $data){
		if($data['id'] == '6280') break;
	}

	// Get icon, default to unknown.svg
	// Missing icons for h, hh, i, ii, l, ll, n, nn. Used the same icon for x (and) xx as for t (and) tt, they should actually be slightly different. 
	$icon = 'img/'.$data->icoonactueel['ID'].'.svg';
	if(!is_file($icon)) $icon = $this->link_resource('img/unknown.svg');

	// Get rain forecast from the coordinates of the data
	$rain_data = file_get_contents('http://gps.buienradar.nl/getrr.php?lat=53.24041&lon=6.536724');
	// Test data in case of a boring forecast
	// $rain_data = "60|00:05\n50|00:10\n128|00:15\n115|00:20\n100|00:25\n255|00:30\n128|00:35\n100|00:40\n060|00:45\n050|00:50\n090|00:55\n130|01:00\n080|01:05\n015|01:10\n020|01:15\n025|01:20\n000|01:25\n000|01:30\n000|01:35\n000|01:40\n050|01:45\n130|01:50\n180|01:55\n200|02:00\n150|02:05\n";
	
	// Make array from rain forecast string
	foreach(explode("\n", $rain_data) as $point){
		if(empty($point)) continue;
		list($rain, $time) = explode('|', $point);
		$rain_forecast[]=array($rain, $time);
	}
?>
<div class="weather-slide">
	<div class="main">
		<!-- Include weather icon -->
		<?php include $icon; ?>
	</div>
	<div class="sidebar">
		<div id="temp">
			<!-- Temperature in degrees Celcius (rounded to 0 decimals) -->
			<?php echo htmlspecialchars(round(intval($data->temperatuurGC))); ?>&deg;C
		</div>
		<div id="wind">
			<!-- Wind direction -->
			<svg id="icon-arrow" width="200px" height="200px" style="shape-rendering:geometricPrecision;">
				<g>
					<polygon points="100,25 67,175 133,175" transform="rotate(<?php echo htmlspecialchars(intval($data->windrichtingGR)); ?>, 100, 100)"/>
				</g>
			</svg>
			<!-- Wind speed in Beaufort -->
			<?php echo htmlspecialchars($data->windsnelheidBF); ?>
		</div>
		<div id="rain">
			<svg id="rain-graph" width="400px" height="400px" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision;">
				<defs>
					<style type="text/css">
						<![CDATA[
						.grid-thin {stroke:#B3B3B3; stroke-width:4px; stroke-linecap:round}
						.grid-thick {stroke:#B3B3B3; stroke-width:6px; stroke-linecap:round}
						.graph {stroke:#333333; stroke-width:6px; fill:none; stroke-linecap:round;stroke-linejoin:round;}
						.label {fill:#333333;font-weight:normal;font-size:40px;font-family:'Source Sans Pro';stroke-width:0;}
						]]>
					</style>
				</defs>
				<g>
					<!-- Labels of the X-axis -->
					<text x="0" y="400" class="label"><?php echo htmlspecialchars(reset($rain_forecast)[1]); ?></text>
					<text x="400" y="400" text-anchor="end" class="label"><?php echo htmlspecialchars(end($rain_forecast)[1]); ?></text>
					<!-- Draw a 394*347 grid (leave space for linewidth) -->
					<g id="Grid">
						<line class="grid-thick" x1="3"   y1="3" x2="3"   y2= "350" />
						<line class="grid-thin" x1="50"  y1="3" x2="50"  y2= "350" />
						<line class="grid-thin" x1="100" y1="3" x2="100" y2= "350" />
						<line class="grid-thin" x1="150" y1="3" x2="150" y2= "350" />
						<line class="grid-thick" x1="200" y1="3" x2="200" y2= "350" />
						<line class="grid-thin" x1="250" y1="3" x2="250" y2= "350" />
						<line class="grid-thin" x1="300" y1="3" x2="300" y2= "350" />
						<line class="grid-thin" x1="350" y1="3" x2="350" y2= "350" />
						<line class="grid-thick" x1="397" y1="3" x2="397" y2= "350" />
					</g>
					<!-- Plot the rain forecast data -->
					<path class="graph" d="
					<?php 
					foreach($rain_forecast as $index=>$point){
						/* 
						 * First, move (M) to the starting postition, then draw lines (L) between the points.
						 * Calculate horizontal position: divide the width of the canvas in even bits and leave some space for rounded line ends.
						 * Calculate vertical position: 0 means no rain, 255 means rain from hell. Expand this scale to the height of the canvas.
						 */
						echo ($index==0?'M':'L').strval($index*(394/(sizeof($rain_forecast)-1))+3).' '.strval(350-(347*$point[0]/255));
					}
					?>"/>
				</g>
			</svg>
		</div>
	</div>
	<div class="credits">
		Source: Buienradar.nl
	</div>
</div>
