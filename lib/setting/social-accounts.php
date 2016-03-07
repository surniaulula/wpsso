<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSettingSocialAccounts' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSettingSocialAccounts extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_social_accounts',
				_x( 'Website / Business Social Accounts', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_social_accounts' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_social_accounts() {
			$metabox = $this->menu_id;
			echo '<table class="sucom-setting '.$this->p->cf['lca'].'">';
			echo '<tr><td colspan="2">'.$this->p->msgs->get( 'info-'.$metabox ).'</td></tr>';

			foreach ( array_merge( $this->get_rows( $metabox, 'general' ), 
				apply_filters( $this->p->cf['lca'].'_'.$metabox.'_general_rows', 
					array(), $this->form ) ) as $num => $row ) 
						echo '<tr>'.$row.'</tr>';
			echo '</table>';
		}

		protected function get_rows( $metabox, $key ) {
			$rows = array();

			switch ( $metabox.'-'.$key ) {

				case 'social-accounts-general':

					foreach ( array(
						'fb_publisher_url' => array(
							'label' => _x( 'Facebook Business Page URL', 'option label', 'wpsso' ),
							'tooltip' => 'fb_publisher_url',
							'css_class' => 'wide',
						),
						'seo_publisher_url' => array(
							'label' => _x( 'Google+ Business Page URL', 'option label', 'wpsso' ),
							'tooltip' => 'google_publisher_url',
							'css_class' => 'wide',
						),
						'rp_publisher_url' => array(
							'label' => _x( 'Pinterest Company Page URL', 'option label', 'wpsso' ),
							'tooltip' => 'rp_publisher_url',
							'css_class' => 'wide',
						),
						'tc_site' => array(
							'label' => _x( 'Twitter Business @username', 'option label', 'wpsso' ),
							'tooltip' => 'tc_site',
							'css_class' => null,
						),
						'instgram_publisher_url' => array(
							'label' => _x( 'Instagram Business URL', 'option label', 'wpsso' ),
							'tooltip' => 'instgram_publisher_url',
							'css_class' => 'wide',
						),
						'linkedin_publisher_url' => array(
							'label' => _x( 'LinkedIn Company Page URL', 'option label', 'wpsso' ),
							'tooltip' => 'linkedin_publisher_url',
							'css_class' => 'wide',
						),
						'myspace_publisher_url' => array(
							'label' => _x( 'MySpace Business Page URL', 'option label', 'wpsso' ),
							'tooltip' => 'myspace_publisher_url',
							'css_class' => 'wide',
						),
					) as $key => $pub ) {
						$rows[$key] = $this->p->util->get_th( $pub['label'], null, $pub['tooltip'], array( 'is_locale' => true ) ).
						'<td>'.$this->form->get_input( SucomUtil::get_key_locale( $key, $this->p->options ), $pub['css_class'] ).'</td>';
					}

					break;
			}
			return $rows;
		}
	}
}

?>
