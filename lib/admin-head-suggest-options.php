<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAdminHeadSuggestOptions' ) ) {

	class WpssoAdminHeadSuggestOptions {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoAdminHeadSuggest->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( current_user_can( 'manage_options' ) ) {

				add_action( 'admin_head', array( $this, 'suggest_options' ), 110 );
			}
		}

		public function suggest_options() {

			$this->suggest_options_robots();
		}

		public function suggest_options_robots() {

			$notices_shown = 0;

			if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// No SEO plugin is active.

				if ( empty( $this->p->options[ 'add_meta_name_robots' ] ) && empty( $this->p->options[ 'add_meta_name_robots:disabled' ] ) ) {

					$notice_key = 'suggest-options-robots';

					if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

						$seo_other_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
							_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
							_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
							_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
							_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

						$notice_msg = sprintf( __( 'Please note that the <code>%s</code> HTML tag is currently disabled and a known SEO plugin has not been detected.', 'wpsso' ), 'meta name robots' ) . ' ';

						$notice_msg .= sprintf( __( 'If another SEO plugin or your theme templates are not adding the <code>%1$s</code> HTML tag to your webpages, you should enable this option under the %2$s tab.', 'wpsso' ), 'meta name robots', $seo_other_tab_link ) . ' ';

						$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

						$notices_shown++;
					}
				}
			}

			return $notices_shown;
		}
	}
}
