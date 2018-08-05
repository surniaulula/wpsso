<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoGplAdminAdvanced' ) ) {

	class WpssoGplAdminAdvanced {

		private $taglist_opts = array();

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'plugin_content_rows'     => 2,
				'plugin_integration_rows' => 2,
				'plugin_custom_meta_rows' => 2,
				'plugin_cache_rows'       => 3,
				'plugin_apikeys_rows'     => 2,
				'cm_custom_rows'          => 2,
				'cm_builtin_rows'         => 2,
				'taglist_og_rows'         => 3,
				'taglist_fb_rows'         => 3,
				'taglist_twitter_rows'    => 3,
				'taglist_schema_rows'     => 3,
				'taglist_other_rows'      => 3,
			), 20 );
		}

		public function filter_plugin_content_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$atts_locale = array( 'is_locale' => true );

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows['plugin_filter_title'] = ''.
			$form->get_th_html( _x( 'Use Filtered (SEO) Title', 'option label', 'wpsso' ), '', 'plugin_filter_title' ).
			$form->get_nocb_td( 'plugin_filter_title' );

			$table_rows['plugin_filter_content'] = ''.
			$form->get_th_html( _x( 'Apply WordPress Content Filters', 'option label', 'wpsso' ), '', 'plugin_filter_content' ).
			$form->get_nocb_td( 'plugin_filter_content', '<em>'._x( 'recommended', 'option comment', 'wpsso' ).'</em>' );

			$table_rows['plugin_filter_excerpt'] = ''.
			$form->get_th_html( _x( 'Apply WordPress Excerpt Filters', 'option label', 'wpsso' ), '', 'plugin_filter_excerpt' ).
			$form->get_nocb_td( 'plugin_filter_excerpt' );

			$table_rows['plugin_p_strip'] = $form->get_tr_hide( 'basic', 'plugin_p_strip' ).
			$form->get_th_html( _x( 'Content Starts at 1st Paragraph', 'option label', 'wpsso' ), '', 'plugin_p_strip' ).
			$form->get_nocb_td( 'plugin_p_strip' );

			$table_rows['plugin_use_img_alt'] = $form->get_tr_hide( 'basic', 'plugin_use_img_alt' ).
			$form->get_th_html( _x( 'Use Image Alt if No Content', 'option label', 'wpsso' ), '', 'plugin_use_img_alt' ).
			$form->get_nocb_td( 'plugin_use_img_alt' );

			$table_rows['plugin_img_alt_prefix'] = ''.
			$form->get_th_html( _x( 'Image Alt Text Prefix', 'option label', 'wpsso' ), '', 'plugin_img_alt_prefix', $atts_locale ).
			'<td class="blank">'.SucomUtil::get_key_value( 'plugin_img_alt_prefix', $this->p->options ).'</td>';

			$table_rows['plugin_p_cap_prefix'] = ''.
			$form->get_th_html( _x( 'WP Caption Prefix', 'option label', 'wpsso' ), '', 'plugin_p_cap_prefix', $atts_locale ).
			'<td class="blank">'.SucomUtil::get_key_value( 'plugin_p_cap_prefix', $this->p->options ).'</td>';

			$check_embed_html = '';

			foreach ( $this->p->cf['form']['embed_media_apis'] as $opt_key => $opt_label ) {
				$check_embed_html .= '<p>'.$form->get_nocb_cmt( $opt_key ).' '._x( $opt_label, 'option value', 'wpsso' ).'</p>';
			}

			$table_rows['plugin_embed_media_apis'] = $form->get_tr_hide( 'basic', $this->p->cf['form']['embed_media_apis'] ).
			$form->get_th_html( _x( 'Check for Embedded Media', 'option label', 'wpsso' ), '', 'plugin_embed_media_apis' ).
			'<td class="blank">'.$check_embed_html.'</td>';

			return $table_rows;
		}

		public function filter_plugin_integration_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows['plugin_html_attr_filter'] = $form->get_tr_hide( 'basic', 
				array( 'plugin_html_attr_filter_name', 'plugin_html_attr_filter_prio' ) ).
			$form->get_th_html( _x( '&lt;html&gt; Attributes Filter Hook', 'option label', 'wpsso' ), '', 'plugin_html_attr_filter' ).
			'<td class="blank">Name: '.$this->p->options['plugin_html_attr_filter_name'].', '.
			'Priority: '.$this->p->options['plugin_html_attr_filter_prio'].'</td>';

			$table_rows['plugin_head_attr_filter'] = $form->get_tr_hide( 'basic', 
				array( 'plugin_head_attr_filter_name', 'plugin_head_attr_filter_prio' ) ).
			$form->get_th_html( _x( '&lt;head&gt; Attributes Filter Hook', 'option label', 'wpsso' ), '', 'plugin_head_attr_filter' ).
			'<td class="blank">Name: '.$this->p->options['plugin_head_attr_filter_name'].', '.
			'Priority: '.$this->p->options['plugin_head_attr_filter_prio'].'</td>';

			$table_rows['plugin_honor_force_ssl'] = $form->get_tr_hide( 'basic', 'plugin_honor_force_ssl' ).
			$form->get_th_html( _x( 'Honor the FORCE_SSL Constant', 'option label', 'wpsso' ), '', 'plugin_honor_force_ssl' ).
			$form->get_nocb_td( 'plugin_honor_force_ssl' );

			$table_rows['plugin_add_person_role'] = ''.
			$form->get_th_html( _x( 'Add Person Role for New Users', 'option label', 'wpsso' ), '', 'plugin_add_person_role' ).
			$form->get_nocb_td( 'plugin_add_person_role' );

			$table_rows['plugin_filter_lang'] = $form->get_tr_hide( 'basic', 'plugin_filter_lang' ).
			$form->get_th_html( _x( 'Use WP Locale for Language', 'option label', 'wpsso' ), '', 'plugin_filter_lang' ).
			$form->get_nocb_td( 'plugin_filter_lang' );

			$table_rows['plugin_page_excerpt'] = ''.
			$form->get_th_html( _x( 'Enable WP Excerpt for Pages', 'option label', 'wpsso' ), '', 'plugin_page_excerpt' ).
			$form->get_nocb_td( 'plugin_page_excerpt' );

			$table_rows['plugin_page_tags'] = ''.
			$form->get_th_html( _x( 'Enable WP Tags for Pages', 'option label', 'wpsso' ), '', 'plugin_page_tags' ).
			$form->get_nocb_td( 'plugin_page_tags' );

			$table_rows['plugin_check_head'] = ''.
			$form->get_th_html( _x( 'Check for Duplicate Meta Tags', 'option label', 'wpsso' ), '', 'plugin_check_head' ).
			$form->get_nocb_td( 'plugin_check_head' );

			$table_rows['plugin_create_wp_sizes'] = $form->get_tr_hide( 'basic', 'plugin_create_wp_sizes' ).
			$form->get_th_html( _x( 'Create Missing WP Media Sizes', 'option label', 'wpsso' ), '', 'plugin_create_wp_sizes' ).
			$form->get_nocb_td( 'plugin_create_wp_sizes' );

			$table_rows['plugin_check_img_dims'] = ''.
			$form->get_th_html( _x( 'Enforce Image Dimensions Check', 'option label', 'wpsso' ), '', 'plugin_check_img_dims' ).
			$form->get_nocb_td( 'plugin_check_img_dims', '<em>'._x( 'recommended', 'option comment', 'wpsso' ).'</em>' );

			$table_rows['plugin_upscale_images'] = ''.
			$form->get_th_html( _x( 'Allow Upscale of WP Media Images', 'option label', 'wpsso' ), '', 'plugin_upscale_images' ).
			$form->get_nocb_td( 'plugin_upscale_images' );

			$table_rows['plugin_upscale_img_max'] = $form->get_tr_hide( 'basic', 'plugin_upscale_img_max' ).
			$form->get_th_html( _x( 'Maximum Image Upscale Percent', 'option label', 'wpsso' ), '', 'plugin_upscale_img_max' ).
			'<td class="blank">'.$this->p->options['plugin_upscale_img_max'].' %</td>';

			return $table_rows;
		}

		public function filter_plugin_custom_meta_rows( $table_rows, $form, $network = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			/**
			 * Include Columns in Admin Lists
			 */
			$cols = '<table class="plugin-list-columns">' . "\n" . '<tr>';

			foreach ( WpssoMeta::get_column_headers() as $col_idx => $col_header ) {
				$cols .= '<th>'.$col_header.'</th>';
			}

			$cols .= '<td class="underline"></td></tr>' . "\n";

			foreach ( array(
				'post' => __( 'Posts, Pages, and Custom Post Types List', 'wpsso' ),
				'media' => __( 'Media Library Item List', 'wpsso' ),
				'term' => __( 'Terms (Categories and Tags) List', 'wpsso' ),
				'user' => __( 'Users List' ),
			) as $mod_name => $mod_label ) {
				$cols .= '<tr>';
				foreach ( WpssoMeta::get_column_headers() as $col_idx => $col_header ) {
					if ( $form->in_defaults( 'plugin_'.$col_idx.'_col_'.$mod_name ) ) {	// Just in case.
						$cols .= $form->get_nocb_td( 'plugin_'.$col_idx.'_col_'.$mod_name, '', true );	// $narrow = true
					} else {
						$cols .= '<td class="checkbox"></td>';
					}
				}
				$cols .= '<td><p>'.$mod_label.'</p></td></tr>' . "\n";
			}

			$cols .= '</table>' . "\n";

			$table_rows['plugin_show_columns'] = $form->get_th_html( _x( 'Additional List Table Columns',
				'option label', 'wpsso' ), '', 'plugin_show_columns' ).
					'<td>'.$cols.'</td>';

			/**
			 * Include Custom Meta Metabox
			 */
			$add_to_metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );

			$add_to_checklist = $form->get_no_checklist_post_types( 'plugin_add_to', array(
				'term' => 'Terms (Categories and Tags)',
				'user' => 'User Profile',
			) );

			$table_rows['plugin_add_to'] = $form->get_tr_hide( 'basic', SucomUtil::get_opts_begin( 'plugin_add_to_', $form->options ) ).
			$form->get_th_html( sprintf( _x( 'Add %s Metabox to', 'option label', 'wpsso' ), $add_to_metabox_title ), '', 'plugin_add_to' ).
			'<td class="blank">'.$add_to_checklist.'</td>';

			$table_rows['plugin_wpseo_social_meta'] = $form->get_tr_hide( 'basic', 'plugin_wpseo_social_meta' ).
			$form->get_th_html( _x( 'Read Yoast SEO Social Meta', 'option label', 'wpsso' ), '', 'plugin_wpseo_social_meta' ).
			$form->get_nocb_td( 'plugin_wpseo_social_meta' );

			$table_rows['plugin_def_currency'] = $form->get_tr_hide( 'basic', 'plugin_def_currency' ).
			$form->get_th_html( _x( 'Default Currency', 'option label', 'wpsso' ), '', 'plugin_def_currency' ).
			'<td class="blank">'.$form->get_no_select( 'plugin_def_currency', SucomUtil::get_currencies() ).'</td>';

			foreach ( (array) apply_filters( $this->p->lca.'_get_cf_md_idx', $this->p->cf['opt']['cf_md_idx'] ) as $cf_idx => $md_idx ) {
				if ( isset( $this->p->cf['form']['cf_labels'][$cf_idx] ) && $opt_label = $this->p->cf['form']['cf_labels'][$cf_idx] ) {
					if ( empty( $md_idx ) ) {
						$this->p->options[$cf_idx] = '';
					}
					$table_rows[$cf_idx] = $form->get_tr_hide( 'basic', $cf_idx ).
					$form->get_th_html( _x( $opt_label, 'option label', 'wpsso' ), '', $cf_idx ).
					'<td class="blank">'.$form->get_no_input( $cf_idx ).'</td>';
				}
			}

			return $table_rows;
		}

		public function filter_plugin_cache_rows( $table_rows, $form, $network = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="' . ( $network ? 4 : 2 ) . '">' . 
				$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpsso' ) ) . '</td>';

			$table_rows['plugin_head_cache_exp'] = ''.
			$form->get_th_html( _x( 'Head Markup Array Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_head_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_head_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_head_cache_exp', $form, $network );

			$table_rows['plugin_content_cache_exp'] = $form->get_tr_hide( 'basic', 'plugin_content_cache_exp' ).
			$form->get_th_html( _x( 'Filtered Content Text Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_content_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_content_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_content_cache_exp', $form, $network );

			$table_rows['plugin_short_url_cache_exp'] = ''.
			$form->get_th_html( _x( 'Get Shortened URL Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_short_url_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_short_url_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_short_url_cache_exp', $form, $network );

			$table_rows['plugin_imgsize_cache_exp'] = $form->get_tr_hide( 'basic', 'plugin_imgsize_cache_exp' ).
			$form->get_th_html( _x( 'Get Image URL Info Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_imgsize_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_imgsize_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_imgsize_cache_exp', $form, $network );

			$table_rows['plugin_topics_cache_exp'] = $form->get_tr_hide( 'basic', 'plugin_topics_cache_exp' ).
			$form->get_th_html( _x( 'Article Topics Array Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_topics_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_topics_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_topics_cache_exp', $form, $network );

			$table_rows['plugin_json_data_cache_exp'] = $form->get_tr_hide( 'basic', 'plugin_json_data_cache_exp' ).
			$form->get_th_html( _x( 'Schema JSON Data Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_json_data_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_json_data_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_json_data_cache_exp', $form, $network );

			$table_rows['plugin_types_cache_exp'] = $form->get_tr_hide( 'basic', 'plugin_types_cache_exp' ).
			$form->get_th_html( _x( 'Schema Types Array Cache Expiry', 'option label', 'wpsso' ), '', 'plugin_types_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_types_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_types_cache_exp', $form, $network );

			$table_rows['plugin_show_purge_count'] = ''.
			$form->get_th_html( _x( 'Show Cache Purge Count on Update', 'option label', 'wpsso' ), '', 'plugin_show_purge_count' ).
			$form->get_nocb_td( 'plugin_show_purge_count' ).
			WpssoAdmin::get_option_site_use( 'plugin_show_purge_count', $form, $network );

			$table_rows['plugin_clear_on_save'] = ''.
			$form->get_th_html( _x( 'Clear All Caches on Save Settings', 'option label', 'wpsso' ), '', 'plugin_clear_on_save' ).
			$form->get_nocb_td( 'plugin_clear_on_save' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_on_save', $form, $network );

			$table_rows['plugin_clear_all_refresh'] = ''.
			$form->get_th_html( _x( 'Auto-Refresh Cache After Clear All', 'option label', 'wpsso' ), '', 'plugin_clear_all_refresh' ).
			$form->get_nocb_td( 'plugin_clear_all_refresh' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_all_refresh', $form, $network );

			$table_rows['plugin_clear_short_urls'] = $form->get_tr_hide( 'basic', 'plugin_clear_short_urls' ).
			$form->get_th_html( _x( 'Clear Short URLs on Clear All Caches', 'option label', 'wpsso' ), '', 'plugin_clear_short_urls' ).
			$form->get_nocb_td( 'plugin_clear_short_urls' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_short_urls', $form, $network );

			$table_rows['plugin_clear_for_comment'] = ''.
			$form->get_th_html( _x( 'Clear Post Cache for New Comment', 'option label', 'wpsso' ), '', 'plugin_clear_for_comment' ).
			$form->get_nocb_td( 'plugin_clear_for_comment' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_for_comment', $form, $network );

			return $table_rows;
		}

		public function filter_plugin_apikeys_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$tr_html = array();

			foreach ( array(
				'bitly' => 'plugin_bitly_login',	// Bitly Username
				'dlmyapp' => 'plugin_dlmyapp_api_key',	// DLMY.App API Key
				'googl' => 'plugin_google_api_key',	// Google Project API Key
				'owly' => 'plugin_owly_api_key',	// Ow.ly API Key
				'yourls' => 'plugin_yourls_api_url',	// YOURLS API URL
			) as $tr_key => $opt_key ) {
				$tr_html[$tr_key] = empty( $this->p->options[$opt_key] ) &&
					$this->p->options['plugin_shortener'] !== $tr_key ?
						$form->get_tr_hide( 'basic' ) : '';
			}

			/**
			 * Show bitly shortener by default if 'none' has been selected.
			 */
			if ( empty( $this->p->options['plugin_shortener'] ) || 
				$this->p->options['plugin_shortener'] === 'none' || 
				$this->p->options['plugin_shortener'] === 'bitly' ) {

				$tr_html['bitly'] = '';
			}

			$table_rows[] = '<td colspan="2">' . $this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpsso' ) ) . '</td>';

			$table_rows['plugin_shortener'] = ''.
			$form->get_th_html( _x( 'Preferred URL Shortening Service', 'option label', 'wpsso' ), '', 'plugin_shortener' ).
			'<td class="blank">[None]</td>';

			$table_rows['plugin_min_shorten'] = $form->get_tr_hide( 'basic', 'plugin_min_shorten' ).
			$form->get_th_html( _x( 'Minimum URL Length to Shorten', 'option label', 'wpsso' ), '', 'plugin_min_shorten' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_min_shorten'].' '.
			_x( 'characters', 'option comment', 'wpsso' ).'</td>';

			$table_rows['plugin_wp_shortlink'] = ''.
			$form->get_th_html( _x( 'Short Sharing URL for WP Shortlink', 'option label', 'wpsso' ), '', 'plugin_wp_shortlink' ).
			$form->get_nocb_td( 'plugin_wp_shortlink' );

			$table_rows['plugin_add_link_rel_shortlink'] = ''.
			$form->get_th_html( sprintf( _x( 'Add "%s" HTML Tag', 'option label', 'wpsso' ),
				'link&nbsp;rel&nbsp;shortlink' ), '', 'plugin_add_link_rel_shortlink' ).
			'<td class="blank">'.$form->get_no_checkbox( 'add_link_rel_shortlink',
				'', 'add_link_rel_shortlink_html_tag', null, 'add_link_rel_shortlink' ).'</td>';	// Group with option in head tags list

			/**
			 * Bitly URL  shortener.
			 */
			$table_rows['subsection_plugin_bitly'] = $tr_html['bitly'].
			'<td></td><td class="subsection"><h4>'._x( 'Bitly URL Shortener', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_bitly_login'] = $tr_html['bitly'].
			$form->get_th_html( _x( 'Bitly Username', 'option label', 'wpsso' ), '', 'plugin_bitly_login' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_login'].'</td>';

			$table_rows['plugin_bitly_access_token'] = $tr_html['bitly'].
			$form->get_th_html( '<a href="https://bitly.com/a/oauth_apps">'.
			_x( 'Bitly Generic Access Token', 'option label', 'wpsso' ).'</a>', '', 'plugin_bitly_access_token' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_access_token'].'</td>';

			$table_rows['plugin_bitly_api_key'] = empty( $tr_html['bitly'] ) ? 
				$form->get_tr_hide( 'basic', 'plugin_bitly_api_key' ) : $tr_html['bitly'].
			$form->get_th_html( '<a href="http://bitly.com/a/your_api_key">'.
			_x( 'or Bitly API Key (deprecated)', 'option label', 'wpsso' ).'</a>', '', 'plugin_bitly_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_api_key'].' <em>'.
			_x( 'api key authentication is deprecated', 'option comment', 'wpsso' ).'</em></td>';

			$table_rows['plugin_bitly_domain'] = $tr_html['bitly'].
			$form->get_th_html( _x( 'Bitly Custom Short Domain', 'option label', 'wpsso' ), '', 'plugin_bitly_domain' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_domain'].'</td>';

			/**
			 * DLMY.App URL  shortener.
			 */
			$table_rows['subsection_plugin_dlmyapp'] = $tr_html['dlmyapp'].
			'<td></td><td class="subsection"><h4>'._x( 'DLMY.App URL Shortener', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_dlmyapp_api_key'] = $tr_html['dlmyapp'].
			$form->get_th_html( _x( 'DLMY.App API Key', 'option label', 'wpsso' ), '', 'plugin_dlmyapp_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_dlmyapp_api_key'].'</td>';

			/**
			 * Google URL  shortener.
			 */
			$table_rows['subsection_plugin_googl'] = $tr_html['googl'].
			'<td></td><td class="subsection"><h4>'._x( 'Google APIs', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_google_api_key'] = $tr_html['googl'].
			$form->get_th_html( _x( 'Google Project API Key', 'option label', 'wpsso' ), '', 'plugin_google_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_google_api_key'].'</td>';

			$google_shorten = $this->p->options['plugin_google_shorten'];

			$table_rows['plugin_google_shorten'] = $tr_html['googl'].
			$form->get_th_html( _x( 'URL Shortener API is Enabled', 'option label', 'wpsso' ), '', 'plugin_google_shorten' ).
			'<td class="blank">'._x( $this->p->cf['form']['yes_no'][$google_shorten], 'option value', 'wpsso' ).'</td>';

			/**
			 * Owly URL  shortener.
			 */
			$table_rows['subsection_plugin_owly'] = $tr_html['owly'].
			'<td></td><td class="subsection"><h4>'._x( 'Ow.ly URL Shortener', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_owly_api_key'] = $tr_html['owly'].
			$form->get_th_html( _x( 'Ow.ly API Key', 'option label', 'wpsso' ), '', 'plugin_owly_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_owly_api_key'].'</td>';

			/**
			 * YOURLS URL  shortener.
			 */
			$table_rows['subsection_plugin_yourls'] = $tr_html['yourls'].
			'<td></td><td class="subsection"><h4>'._x( 'Your Own URL Shortener (YOURLS)', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_yourls_api_url'] = $tr_html['yourls'].
			$form->get_th_html( _x( 'YOURLS API URL', 'option label', 'wpsso' ), '', 'plugin_yourls_api_url' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_api_url'].'</td>';

			$table_rows['plugin_yourls_username'] = $tr_html['yourls'].
			$form->get_th_html( _x( 'YOURLS Username', 'option label', 'wpsso' ), '', 'plugin_yourls_username' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_username'].'</td>';

			$table_rows['plugin_yourls_password'] = $tr_html['yourls'].
			$form->get_th_html( _x( 'YOURLS Password', 'option label', 'wpsso' ), '', 'plugin_yourls_password' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_password'].'</td>';

			$table_rows['plugin_yourls_token'] = $tr_html['yourls'].
			$form->get_th_html( _x( 'YOURLS Token', 'option label', 'wpsso' ), '', 'plugin_yourls_token' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_token'].'</td>';

			return $table_rows;
		}

		public function filter_cm_custom_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$atts_locale = array( 'is_locale' => true );

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows[] = '<td></td>'.
			$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), 'checkbox left', 'custom-cm-show-checkbox' ).
			$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ), 'medium left', 'custom-cm-field-id' ).
			$form->get_th_html( _x( 'Contact Field Label', 'column title', 'wpsso' ), 'wide left', 'custom-cm-field-label', $atts_locale );

			$sorted_opt_pre = $this->p->cf['opt']['cm_prefix'];

			ksort( $sorted_opt_pre );

			foreach ( $sorted_opt_pre as $cm_id => $opt_pre ) {

				$cm_enabled_key = 'plugin_cm_'.$opt_pre.'_enabled';
				$cm_name_key = 'plugin_cm_'.$opt_pre.'_name';
				$cm_label_value = SucomUtil::get_key_value( 'plugin_cm_'.$opt_pre.'_label', $this->p->options );

				// not all social websites have a contact method field
				if ( ! isset( $this->p->options[$cm_enabled_key] ) ) {
					continue;
				}

				$opt_label = empty( $this->p->cf['*']['lib']['website'][$cm_id] ) ?	// defined by sharing buttons
					ucfirst( $cm_id ) : $this->p->cf['*']['lib']['website'][$cm_id];

				switch ( strtolower( $opt_label ) ) {
					case 'gp':
					case 'gplus':
					case 'googleplus':
						$opt_label = 'Google+';
						break;
				}

				switch ( $cm_id ) {
					case 'facebook':
					case 'gplus':
					case 'twitter':

						$tr_html = '';

						break;

					default:

						/**
						 * Hide all other contact methods if their values have not been customized.
						 */
						$tr_html = $form->get_tr_hide( 'basic', array( $cm_enabled_key, $cm_name_key, $cm_label_value ) );

						break;
				}

				$table_rows[] = $tr_html.$form->get_th_html( $opt_label, 'medium' ).
				$form->get_nocb_td( $cm_enabled_key, '', true ).
				'<td class="blank medium">'.$form->get_no_input( $cm_name_key, 'medium' ).'</td>'.
				'<td class="blank wide">'.$form->get_no_input_value( $cm_label_value ).'</td>';
			}

			return $table_rows;
		}

		public function filter_cm_builtin_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$atts_locale = array( 'is_locale' => true );

			$table_rows[] = '<td colspan="4">' . $this->p->msgs->get( 'pro-feature-msg' ) . '</td>';

			$table_rows[] = '<td></td>'.
			$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), 'checkbox left', 'custom-cm-show-checkbox' ).
			$form->get_th_html( _x( 'Contact Field ID', 'column title', 'wpsso' ), 'medium left', 'wp-cm-field-id' ).
			$form->get_th_html( _x( 'Contact Field Label', 'column title', 'wpsso' ), 'wide left', 'custom-cm-field-label', $atts_locale );

			$sorted_cm_names = $this->p->cf['wp']['cm_names'];

			ksort( $sorted_cm_names );

			foreach ( $sorted_cm_names as $cm_id => $opt_label ) {

				$cm_enabled_key = 'wp_cm_'.$cm_id.'_enabled';
				$cm_name_key = 'wp_cm_'.$cm_id.'_name';
				$cm_label_value = SucomUtil::get_key_value( 'wp_cm_'.$cm_id.'_label', $this->p->options );

				// not all social websites have a contact method field
				if ( ! isset( $this->p->options[$cm_enabled_key] ) ) {
					continue;
				}

				$table_rows[] = $form->get_th_html( $opt_label, 'medium' ).
				'<td class="checkbox blank">'.$form->get_nocb_cmt( $cm_enabled_key ).'</td>'.
				'<td class="medium">'.$form->get_no_input( $cm_name_key, 'medium' ).'</td>'.
				'<td class="blank wide">'.$form->get_no_input( $cm_label_value ).'</td>';
			}

			return $table_rows;
		}

		public function filter_taglist_og_rows( $table_rows, $form, $network = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_taglist_rows( $table_rows, $form, $network,
				array( '/^add_(meta)_(property)_(.+)$/' ) );
		}

		public function filter_taglist_fb_rows( $table_rows, $form, $network = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_taglist_rows( $table_rows, $form, $network,
				array( '/^add_(meta)_(property)_((fb|al):.+)$/' ) );
		}

		public function filter_taglist_twitter_rows( $table_rows, $form, $network = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_taglist_rows( $table_rows, $form, $network,
				array( '/^add_(meta)_(name)_(twitter:.+)$/' ) );
		}

		public function filter_taglist_schema_rows( $table_rows, $form, $network = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_taglist_rows( $table_rows, $form, $network,
				array( '/^add_(meta|link)_(itemprop)_(.+)$/' ) );
		}

		public function filter_taglist_other_rows( $table_rows, $form, $network = false ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
			return $this->get_taglist_rows( $table_rows, $form, $network,
				array( '/^add_(link)_([^_]+)_(.+)$/', '/^add_(meta)_(name)_(.+)$/' ) );
		}

		private function get_taglist_rows( $table_rows, $form, $network, array $opt_preg_include ) {

			$table_cells = array();

			foreach ( $opt_preg_include as $preg ) {

				foreach ( $form->defaults as $opt_key => $opt_val ) {


					if ( strpos( $opt_key, 'add_' ) !== 0 ) {	// Optimize
						continue;
					} elseif ( isset( $this->taglist_opts[$opt_key] ) ) {	// Check cache for tags already shown.
						continue;
					} elseif ( ! preg_match( $preg, $opt_key, $match ) ) {	// Check option name for a match.
						continue;
					}

					$highlight = '';
					$css_class = '';
					$css_id    = '';
					$force     = null;
					$group     = null;

					$this->taglist_opts[$opt_key] = $opt_val;

					switch ( $opt_key ) {
						case 'add_meta_name_generator':	// Disabled with a constant instead.
							continue 2;
						case 'add_link_rel_shortlink':
							$group = 'add_link_rel_shortlink';
							break;
					}

					$table_cells[] = '<!-- '.( implode( ' ', $match ) ).' -->'.	// Required for sorting.
						'<td class="checkbox blank">'.$form->get_nocb_cmt( $opt_key ).'</td>'.
						'<td class="checkbox blank">'.$form->get_no_checkbox( $opt_key, $css_class, $css_id, $force, $group ).'</td>'.
						'<td class="xshort'.$highlight.'">'.$match[1].'</td>'.
						'<td class="taglist'.$highlight.'">'.$match[2].'</td>'.
						'<th class="taglist'.$highlight.'">'.$match[3].'</th>';
				}
			}

			return array_merge( $table_rows, SucomUtil::get_column_rows( $table_cells, 2 ) );
		}
	}
}
