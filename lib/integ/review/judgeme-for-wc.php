<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegReviewJudgemeForWc' ) ) {

	class WpssoIntegReviewJudgemeForWc {

		private $p;	// Wpsso class object.

		private $api_base_url    = null;
		private $api_shop_domain = null;
		private $api_shop_token  = null;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->options[ 'plugin_ratings_reviews_svc' ]          = 'judgeme';
			$this->p->options[ 'plugin_ratings_reviews_svc:disabled' ] = true;
			$this->p->options[ 'plugin_judgeme_shop_domain' ]          = SucomUtil::get_const( 'JGM_SHOP_DOMAIN' );
			$this->p->options[ 'plugin_judgeme_shop_domain:disabled' ] = true;
			$this->p->options[ 'plugin_judgeme_shop_token' ]           = get_option( 'judgeme_shop_token' );
			$this->p->options[ 'plugin_judgeme_shop_token:disabled' ]  = true;
		}
	}
}
