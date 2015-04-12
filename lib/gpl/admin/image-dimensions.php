<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminImagedimensions' ) ) {

	class WpssoGplAdminImagedimensions {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'image-dimensions_general_rows' => 2,
			) );
		}

		public function filter_image_dimensions_general_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Twitter <em>Summary</em> Card', null, 'tc_sum_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_sum' ).'</td>';
	
			$rows[] = $this->p->util->th( 'Twitter <em>Large Image Summary</em> Card', null, 'tc_lrgimg_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_lrgimg' ).'</td>';

			$rows[] = $this->p->util->th( 'Twitter <em>Photo</em> Card', null, 'tc_photo_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_photo' ).'</td>';

			$rows[] = $this->p->util->th( 'Twitter <em>Gallery</em> Card', null, 'tc_gal_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_gal' ).'</td>';

			$rows[] = $this->p->util->th( 'Twitter <em>Product</em> Card', null, 'tc_prod_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_prod' ).'</td>';

			return $rows;
		}
	}
}

?>
