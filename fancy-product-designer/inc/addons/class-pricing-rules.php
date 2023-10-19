<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Pricing_Rules') ) {

	class FPD_Pricing_Rules {

		public static function display_names_numbers_items( $views ) {

			echo '<div class="fpd-table-item-names-numbers">';

			/*print_r("<pre>");
			print_r($views);
			print_r("</pre>");*/

			if( is_array($views) ) {

				foreach($views as $view) {

					if( isset($view['names_numbers']) ) {

						$names_numbers = $view['names_numbers'];
						foreach($names_numbers as $name_number) {

							$nn_line = array();
							echo '<p>';

							if( isset($name_number['name']) ){
								echo "<strong>Name:</strong> ".$name_number['name']." - ";
							}
								
								/*$nn_line[] = $name_number['name'];*/
							if( isset($name_number['number']) ){
								echo "<strong>Number:</strong> ".$name_number['number']." <br>";
							}

							echo '</p>';
								
								/*$nn_line[] = $name_number['number'];*/
							/*if( isset($name_number['select']) )
								$nn_line[] = $name_number['select'];*/
							/*echo '<div>'. implode(' / ', $nn_line) .'</div>';*/
			    		}
			    	}
			    }

		    }

		    echo '</div>';
		    ?>
		    <style type="text/css">
			    .fpd-table-item-names-numbers { font-size: 12px; display: inline-block; width: 100%; position: relative; padding: 20px 0; }
				.fpd-table-item-names-numbers p { font-size: 15px; margin-bottom: 3px; }
				.fpd-table-item-names-numbers p strong {    font-weight: 700 !important;   color: #000;}
		    </style>
		    <?php

		}

	}

}

?>