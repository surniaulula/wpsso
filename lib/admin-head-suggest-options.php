<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/*
		 * Instantiated by WpssoAdminHeadSuggest->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;
		}

		public function suggest( $suggest_max = 2 ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'suggest_max' => $suggest_max,
				) );
			}

			$suggested = 0;

			if ( ! current_user_can( 'manage_options' ) ) return $suggested;

			/*
			 * Suggest integration and seo options, in that order.
			 */
			foreach ( array( 'integration', 'seo' ) as $suffix ) {

				$methodname = 'suggest_options_' . $suffix;
				$suggested  = $suggested + $this->$methodname( $suggest_max - $suggested );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $methodname . ' suggested = ' . $suggested );
				}

				if ( $suggested >= $suggest_max ) break;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'return suggested = ' . $suggested );
			}

			return $suggested;
		}

		private function suggest_options_integration( $suggest_max ) {

			$suggested = 0;

			/*
			 * Image Dimension Checks.
			 */
			if ( empty( $this->p->options[ 'plugin_check_img_dims' ] ) ) {

				$notice_key = 'notice-check-img-dims-disabled';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

					if ( $notice_msg = $this->p->msgs->get( $notice_key ) ) {

						$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

						if ( ++$suggested >= $suggest_max ) return $suggested;
					}
				}
			}

			/*
			 * Inherit Featured Image for WooCommerce.
			 */
			if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {	// WooCommerce plugin is active.

				if ( empty( $this->p->options[ 'plugin_inherit_featured' ] ) ) {

					$notice_key = 'notice-wc-inherit-featured-disabled';

					if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

						if ( $notice_msg = $this->p->msgs->get( $notice_key ) ) {

							$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

							if ( ++$suggested >= $suggest_max ) return $suggested;
						}
					}
				}
			}

			return $suggested;
		}

		private function suggest_options_seo( $suggest_max ) {

			$suggested = 0;

			if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {	// An SEO plugin is not active.

				/*
				 * If the option is not checked and not disabled, then show a notice and return.
				 */
				foreach ( array(
					'add_link_rel_canonical'    => 'link rel canonical',
					'add_link_rel_shortlink'    => 'link rel shortlink',
					'add_meta_name_description' => 'meta name description',
					'add_meta_name_robots'      => 'meta name robots',
				) as $opt_key => $tag_name ) {

					$notice_key = 'suggest-options-seo-' . $opt_key;

					if ( ! empty( $this->p->options[ $opt_key ] ) ) continue;

					if ( ! empty( $this->p->options[ $opt_key . ':disabled' ] ) ) continue;

					if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) continue;

					$seo_other_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
						_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
						_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
						_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
						_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

					$notice_msg = sprintf( __( 'Please note that the %s HTML tag is currently disabled and a known SEO plugin has not been detected.',
						'wpsso' ), '<code>' . $tag_name . '</code>' ) . ' ';

					$notice_msg .= sprintf( __( 'If another SEO plugin or your theme templates are not adding the %1$s HTML tag to your webpages, you should enable this option under the %2$s tab.', 'wpsso' ), '<code>' . $tag_name . '</code>', $seo_other_tab_link ) . ' ';

					$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );

					if ( ++$suggested >= $suggest_max ) return $suggested;
				}
			}

			return $suggested;
		}
	}
}
