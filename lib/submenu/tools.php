<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuTools' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuTools extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;
		}

		protected function add_form_buttons( &$form_button_rows ) {

			$role_label = _x( 'Person', 'user role', 'wpsso' );

			/*
			 * Row #0.
			 */
			$count_cache_files             = $this->p->util->cache->count_cache_files();
			$count_ignored_urls            = $this->p->util->cache->count_ignored_urls();
			$count_db_transients_no_short  = $this->p->util->cache->count_db_transients( $key_prefix = '', $incl_shortened = false );
			$count_db_transients_expired   = $this->p->util->cache->count_db_transients_expired();
			$count_db_transients_shortened = $this->p->util->cache->count_db_transients( $key_prefix = 'wpsso_s_', $incl_shortened = true );
			$count_cron_jobs               = $this->p->util->count_cron_jobs();

			$refresh_cache_transl = _x( 'Refresh Cache', 'submit button', 'wpsso' ) . ' *';

			$clear_cache_files_transl = sprintf( _nx( 'Clear %s Cached File', 'Clear %s Cached Files',
				$count_cache_files, 'submit button', 'wpsso' ), number_format_i18n( $count_cache_files ) );

			$clear_ignored_urls_transl = sprintf( _nx( 'Clear %s Failed URL Connection', 'Clear %s Failed URL Connections',
				$count_ignored_urls, 'submit button', 'wpsso' ), number_format_i18n( $count_ignored_urls ) );

			$clear_db_transients_no_short_transl = sprintf( _nx( 'Clear %s Database Transient', 'Clear %s Database Transients',
				$count_db_transients_no_short, 'submit button', 'wpsso' ), number_format_i18n( $count_db_transients_no_short ) ) . ' **';

			$clear_db_transients_expired_transl = sprintf( _nx( 'Clear %s Expired DB Transient', 'Clear %s Expired DB Transients',
				$count_db_transients_expired, 'submit button', 'wpsso' ), number_format_i18n( $count_db_transients_expired ) );

			$clear_db_transients_shortened_transl = sprintf( _nx( 'Clear %s Short URL DB Transient', 'Clear %s Short URL DB Transients',
				$count_db_transients_shortened, 'submit button', 'wpsso' ), number_format_i18n( $count_db_transients_shortened ) );

			$clear_cron_jobs_transl = sprintf( _nx( 'Clear %s WordPress Cron Job', 'Clear %s WordPress Cron Jobs',
				$count_cron_jobs, 'submit button', 'wpsso' ), number_format_i18n( $count_cron_jobs ) );

			$flush_rewrite_rules_transl = _x( 'Flush WordPress Rewrite Rules', 'submit button', 'wpsso' );

			/*
			 * Row #1.
			 */
			$export_settings_transl = _x( 'Export Plugin and Add-on Settings', 'submit button', 'wpsso' );
			$import_settings_transl = _x( 'Import Plugin and Add-on Settings', 'submit button', 'wpsso' );

			/*
			 * Row #2.
			 */
			$add_persons_transl        = sprintf( _x( 'Add %s Role to Content Creators', 'submit button', 'wpsso' ), $role_label ) . ' ***';
			$remove_persons_transl     = sprintf( _x( 'Remove %s Role from All Users', 'submit button', 'wpsso' ), $role_label );
			$reload_image_sizes_transl = _x( 'Reload Default Image Sizes', 'submit button', 'wpsso' );

			/*
			 * Row #3.
			 */
			$change_show_next_key    = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf[ 'form' ][ 'show_options' ] );
			$change_show_next_transl = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $change_show_next_key ], 'option value', 'wpsso' );
			$change_show_transl      = sprintf( _x( 'Change to "%s" View', 'submit button', 'wpsso' ), $change_show_next_transl );

			$form_button_rows = array(

				/*
				 * Row #0.
				 */
				array(
					'refresh_cache'                 => $refresh_cache_transl,
					'clear_cache_files'             => $clear_cache_files_transl,			// Clear Cached Files.
					'clear_ignored_urls'            => $clear_ignored_urls_transl,			// Clear Failed URL Connections.
					'clear_db_transients_no_short'  => $clear_db_transients_no_short_transl,	// Clear Database Transients.
					'clear_db_transients_expired'   => $clear_db_transients_expired_transl,		// Clear Expired DB Transients.
					'clear_db_transients_shortened' => $clear_db_transients_shortened_transl,	// Clear Short URL DB Transients.
					'clear_cron_jobs'               => $clear_cron_jobs_transl,			// Clear WordPress Cron Jobs.
					'flush_rewrite_rules'           => $flush_rewrite_rules_transl,			// Flush WordPress Rewrite Rules.
				),

				/*
				 * Row #1.
				 */
				array(
					'export_plugin_settings_json' => $export_settings_transl,
					'import_plugin_settings_json' => array(
						'html' => '
							<form enctype="multipart/form-data" action="' . $this->p->util->get_admin_url() . '" method="post">' .
							wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME ) . '
							<input type="hidden" name="wpsso-action" value="import_plugin_settings_json" />
							<input type="submit" class="button-secondary button-alt"
								value="' . $import_settings_transl . '" style="display:inline-block;" />
							<input type="file" name="file" accept="application/x-gzip" />
							</form>
						',
					),
				),

				/*
				 * Row #2.
				 */
				array(
					'add_persons'                => $add_persons_transl,
					'remove_persons'             => $remove_persons_transl,
					'reload_default_image_sizes' => $reload_image_sizes_transl,
				),

				/*
				 * Row #3.
				 */
				array(
					'change_show_options&show-opts=' . $change_show_next_key => $change_show_transl,
					'reset_user_dismissed_notices' => _x( 'Reset Dismissed Notices', 'submit button', 'wpsso' ),
					'reset_user_metabox_layout'    => _x( 'Reset Metabox Layout', 'submit button', 'wpsso' ),
				),
			);
		}

		/*
		 * Called from WpssoAdmin->show_settings_page().
		 */
		protected function show_post_body_settings_form() {

			$refresh_max_time = human_time_diff( 0, WPSSO_CACHE_REFRESH_MAX_TIME );

			echo '<div id="tools-content">' . "\n";

			echo $this->get_form_buttons();

			echo '<p class="status-msg smaller left">';
			echo '* ';
			echo sprintf( __( 'The maximum execution time for this background task is currently set to %s.', 'wpsso' ), $refresh_max_time ) . ' ';
			echo '</p>' . "\n";

			echo '<p class="status-msg smaller left">';
			echo '** ';
			echo __( 'All database transients are cleared, except for the shortened URL database transients.', 'wpsso' ) . ' ';

			if ( wp_using_ext_object_cache() ) {

				echo sprintf( __( '<a href="%1$s">Using an external object cache</a> for WordPress transients is <code>%2$s</code>.', 'wpsso' ), 
					__( 'https://developer.wordpress.org/reference/functions/wp_using_ext_object_cache/', 'wpsso' ),
						wp_using_ext_object_cache() ? 'true' : 'false' ) . ' ';
				echo __( 'All database transient counts should be 0.', 'wpsso' ) . ' ';
			}

			echo '</p>' . "\n";

			echo '<p class="status-msg smaller left">';
			echo '*** ';
			echo __( 'Members of the role are used for some Schema property selections.', 'wpsso' ) . ' ';
			echo __( 'Content Creators are administrators, editors, authors, and contributors.', 'wpsso' );
			echo '</p>' . "\n";

			if ( $this->p->util->cache->count_ignored_urls() > 0 ) {

				$ignored_urls       = $this->p->util->cache->get_ignored_urls();
				$count_ignored_urls = count( $ignored_urls );

				echo '<h4>' . sprintf( __( '%s Failed URL Connections:', 'wpsso' ),
					number_format_i18n( $count_ignored_urls ) ) . '</h4>';

				echo '<p class="status-msg smaller left">';

				foreach ( $ignored_urls as $url => $ts ) { echo '<a href="' . $url . '">' . $url . '</a></br>'; }

				echo '</p>' . "\n";
			}

			echo '</div><!-- #tools-content -->' . "\n";
		}
	}
}
