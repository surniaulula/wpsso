<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoOptionsUpgrade' ) && class_exists( 'WpssoOptions' ) ) {

	class WpssoOptionsUpgrade extends WpssoOptions {

		private $renamed_site_keys = array(
			'plugin_tid' => 'plugin_wpsso_tid',
		);

		private $renamed_keys = array(
			'og_img_resize' => 'plugin_auto_img_resize',
			'link_def_author_id' => 'seo_def_author_id',
			'link_def_author_on_index' => 'seo_def_author_on_index',
			'link_def_author_on_search' => 'seo_def_author_on_search',
			'plugin_tid' => 'plugin_wpsso_tid',
			'tc_prod_def_l2' => 'tc_prod_def_label2',
			'tc_prod_def_d2' => 'tc_prod_def_data2',
			'og_publisher_url' => 'fb_publisher_url',
		);

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
		}

		// def_opts accepts output from functions, so don't force reference
		public function options( $options_name, &$opts = array(), $def_opts = array() ) {
			$opts = SucomUtil::rename_keys( $opts, $this->renamed_keys );

			// custom value changes for regular options
			if ( $options_name == constant( $this->p->cf['uca'].'_OPTIONS_NAME' ) ) {

				if ( version_compare( $opts['options_version'], 260, '<=' ) ) {
					if ( $opts['og_img_width'] == 1200 &&
						$opts['og_img_height'] == 630 &&
						! empty( $opts['og_img_crop'] ) ) {

						$this->p->notice->inf( 'Open Graph Image Dimentions have been updated from '.
							$opts['og_img_width'].'x'.$opts['og_img_height'].', '.
							( $opts['og_img_crop'] ? '' : 'un' ).'cropped to '.
							$def_opts['og_img_width'].'x'.$def_opts['og_img_height'].', '.
							( $def_opts['og_img_crop'] ? '' : 'un' ).'cropped.', true );
	
						$opts['og_img_width'] = $def_opts['og_img_width'];
						$opts['og_img_height'] = $def_opts['og_img_height'];
						$opts['og_img_crop'] = $def_opts['og_img_crop'];
					}
				}

				if ( version_compare( $opts['options_version'], 270, '<=' ) ) {
					foreach ( $opts as $key => $val ) {
						if ( strpos( $key, 'inc_' ) === 0 ) {
							$new_key = '';
							switch ( $key ) {
								case ( preg_match( '/^inc_(description|twitter:)/', $key ) ? true : false ):
									$new_key = preg_replace( '/^inc_/', 'add_meta_name_', $key );
									break;
								default:
									$new_key = preg_replace( '/^inc_/', 'add_meta_property_', $key );
									break;
							}
							if ( ! empty( $new_key ) )
								$opts[$new_key] = $val;
							unset( $opts[$key] );
						}
					}
				}
			}

			$opts = $this->sanitize( $opts, $def_opts );	// cleanup excess options and sanitize
			return $opts;
		}
	}
}

?>
