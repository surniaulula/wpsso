<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminGeneral' ) ) {

	class WpssoGplAdminGeneral {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'pub_twitter_rows' => 2,
			) );
		}

		public function filter_pub_twitter_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->th( 'Enable Twitter Cards', 'highlight', 'tc_enable' ).
			'<td class="blank"><input type="checkbox" disabled="disabled" /></td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( 'Maximum Description Length', null, 'tc_desc_len' ).
			'<td class="blank">'.$this->p->options['tc_desc_len'].' characters or less</td>';

			$rows[] = $this->p->util->th( '<em>Summary</em> Card Image Dimensions', null, 'tc_sum_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_sum' ).'</td>';

			$rows[] = $this->p->util->th( '<em>Large Image</em> Card Image Dimensions', null, 'tc_lrgimg_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_lrgimg' ).'</td>';

			$rows[] = $this->p->util->th( '<em>Photo</em> Card Image Dimensions', 'highlight', 'tc_photo_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_photo' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->th( '<em>Gallery</em> Card Minimum Images', null, 'tc_gal_minimum' ).
			'<td class="blank">'.$this->p->options['tc_gal_min'].'</td>';
	
			$rows[] = $this->p->util->th( '<em>Gallery</em> Card Image Dimensions', null, 'tc_gal_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_gal' ).'</td>';

			$rows[] = $this->p->util->th( '<em>Product</em> Card Image Dimensions', null, 'tc_prod_dimensions' ).
			'<td class="blank">'.$form->get_image_dimensions_text( 'tc_prod' ).'</td>';

			$rows[] = ( $this->p->is_avail['ecom']['*'] ? '' : '<tr class="hide_in_basic">' ).
			$this->p->util->th( '<em>Product</em> Card Maximum Labels', null, 'tc_prod_labels' ).
			'<td class="blank">'.$this->p->options['tc_prod_labels'].'</td>';

			$rows[] = ( $this->p->is_avail['ecom']['*'] ? '' : '<tr class="hide_in_basic">' ).
			$this->p->util->th( '<em>Product</em> Card Default 2nd Label', null, 'tc_prod_defaults' ).
			'<td class="blank">Label: '.$this->p->options['tc_prod_def_label2'].
			' &nbsp; Value: '.$this->p->options['tc_prod_def_data2'].'</td>';

			return $rows;
		}
	}
}

?>
