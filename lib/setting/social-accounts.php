<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSettingSocialAccounts' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSettingSocialAccounts extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_social_accounts',
				_x( 'Social Pages and Accounts', 'metabox title', 'wpsso' ), 
					array( &$this, 'show_metabox_social_accounts' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_social_accounts() {
			$metabox = $this->menu_id;
			echo '<table class="sucom-setting '.$this->p->cf['lca'].'">';
			echo '<tr><td colspan="2">'.$this->p->msgs->get( 'info-'.$metabox ).'</td></tr>';

			$table_rows = array_merge( $this->get_table_rows( $metabox, 'general' ), 
				apply_filters( SucomUtil::sanitize_hookname( $this->p->cf['lca'].'_'.$metabox.'_general_rows' ), 
					array(), $this->form ) );
					
			foreach ( $table_rows as $num => $row ) 
				echo '<tr>'.$row.'</tr>';
			echo '</table>';
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();

			switch ( $metabox.'-'.$key ) {

				case 'social-accounts-general':

					$social_accounts = apply_filters( $this->p->cf['lca'].'_social_accounts', 
						$this->p->cf['form']['social_accounts'] );
					asort( $social_accounts );	// sort by label and maintain key association
					foreach ( $social_accounts as $key => $label ) {
						$table_rows[$key] = $this->form->get_th_html( _x( $label, 'option value', 'wpsso' ),
							null, $key, array( 'is_locale' => true ) ).
						'<td>'.$this->form->get_input( SucomUtil::get_key_locale( $key, $this->p->options ),
							( strpos( $key, '_url' ) ? 'wide' : '' ) ).'</td>';
					}

					break;
			}
			return $table_rows;
		}
	}
}

?>
