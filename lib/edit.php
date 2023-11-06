<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2020-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoEdit' ) ) {

	class WpssoEdit {

		private $p;		// Wpsso class object.
		private $general;	// WpssoEditGeneral class object.
		private $media;		// WpssoEditMedia class object.
		private $prev;		// WpssoEditPrev class object.
		private $schema;	// WpssoEditSchema class object.
		private $validators;	// WpssoEditValidators class object.
		private $visibility;	// WpssoEditVisibility class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			require_once WPSSO_PLUGINDIR . 'lib/edit-general.php';

			$this->general = new WpssoEditGeneral( $this->p );

			require_once WPSSO_PLUGINDIR . 'lib/edit-media.php';

			$this->media = new WpssoEditMedia( $this->p );

			require_once WPSSO_PLUGINDIR . 'lib/edit-prev.php';

			$this->prev = new WpssoEditPrev( $this->p );

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/edit-schema.php';

				$this->schema = new WpssoEditSchema( $this->p );
			}

			require_once WPSSO_PLUGINDIR . 'lib/edit-validators.php';

			$this->validators = new WpssoEditValidators( $this->p );

			require_once WPSSO_PLUGINDIR . 'lib/edit-visibility.php';

			$this->visibility = new WpssoEditVisibility( $this->p );
		}
	}
}
