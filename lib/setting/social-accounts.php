<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSettingSocialAccounts' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSettingSocialAccounts extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_social_accounts', 'Website / Business Social Accounts', 
				array( &$this, 'show_metabox_social_accounts' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_social_accounts() {
			$metabox = $this->menu_id;
			echo '<table class="sucom-setting" style="padding-bottom:0;"><tr><td>'.
			$this->p->msgs->get( 'info-'.$metabox ).
			'</td></tr></table>';
			echo '<table class="sucom-setting">';
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

					$rows[] = $this->p->util->th( 'Facebook Business Page URL', null, 'fb_publisher_url' ).
					'<td>'.$this->form->get_input( 'fb_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Google+ Business Page URL', null, 'google_publisher_url' ).
					'<td>'.$this->form->get_input( 'seo_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Pinterest Company Page URL', null, 'rp_publisher_url'  ).
					'<td>'.$this->form->get_input( 'rp_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'Twitter Business @username', null, 'tc_site' ).
					'<td>'.$this->form->get_input( 'tc_site' ).'</td>';

					$rows[] = $this->p->util->th( 'Instagram Business URL', null, 'instgram_publisher_url' ).
					'<td>'.$this->form->get_input( 'instgram_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'LinkedIn Company Page URL', null, 'linkedin_publisher_url'  ).
					'<td>'.$this->form->get_input( 'linkedin_publisher_url', 'wide' ).'</td>';

					$rows[] = $this->p->util->th( 'MySpace Business (Brand) URL', null, 'myspace_publisher_url'  ).
					'<td>'.$this->form->get_input( 'myspace_publisher_url', 'wide' ).'</td>';

					break;
			}
			return $rows;
		}
	}
}

?>
