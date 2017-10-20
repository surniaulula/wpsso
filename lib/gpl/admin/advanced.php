<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
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
				'plugin_content_rows' => 2,	// $table_rows, $form
				'plugin_integration_rows' => 2,	// $table_rows, $form
				'plugin_custom_meta_rows' => 2,	// $table_rows, $form
				'plugin_cache_rows' => 3,	// $table_rows, $form, $network
				'plugin_apikeys_rows' => 2,	// $table_rows, $form
				'cm_custom_rows' => 2,		// $table_rows, $form
				'cm_builtin_rows' => 2,		// $table_rows, $form
				'taglist_og_rows' => 3,		// $table_rows, $form, $network
				'taglist_fb_rows' => 3,		// $table_rows, $form, $network
				'taglist_twitter_rows' => 3,	// $table_rows, $form, $network
				'taglist_schema_rows' => 3,	// $table_rows, $form, $network
				'taglist_other_rows' => 3,	// $table_rows, $form, $network
			), 20 );
		}

		public function filter_plugin_content_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows[] = $form->get_th_html( _x( 'Use Filtered (SEO) Title',
				'option label', 'wpsso' ), '', 'plugin_filter_title' ).
			$form->get_nocb_td( 'plugin_filter_title' );

			$table_rows[] = $form->get_th_html( _x( 'Apply WordPress Content Filters',
				'option label', 'wpsso' ), '', 'plugin_filter_content' ).
			$form->get_nocb_td( 'plugin_filter_content' );

			$table_rows[] = $form->get_th_html( _x( 'Apply WordPress Excerpt Filters',
				'option label', 'wpsso' ), '', 'plugin_filter_excerpt' ).
			$form->get_nocb_td( 'plugin_filter_excerpt' );

			$table_rows[] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Content Starts at 1st Paragraph',
				'option label', 'wpsso' ), '', 'plugin_p_strip' ).
			$form->get_nocb_td( 'plugin_p_strip' );

			$table_rows[] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Use Image Alt if No Content',
				'option label', 'wpsso' ), '', 'plugin_use_img_alt' ).
			$form->get_nocb_td( 'plugin_use_img_alt' );

			$table_rows['plugin_img_alt_prefix'] = $form->get_th_html( _x( 'Image Alt Text Prefix',
				'option label', 'wpsso' ), '', 'plugin_img_alt_prefix', array( 'is_locale' => true ) ).
			'<td class="blank">'.SucomUtil::get_key_value( 'plugin_img_alt_prefix', $this->p->options ).'</td>';

			$table_rows['plugin_p_cap_prefix'] = $form->get_th_html( _x( 'WP Caption Prefix',
				'option label', 'wpsso' ), '', 'plugin_p_cap_prefix', array( 'is_locale' => true ) ).
			'<td class="blank">'.SucomUtil::get_key_value( 'plugin_p_cap_prefix', $this->p->options ).'</td>';

			$table_rows['plugin_embedded_media'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Check for Embedded Media',
				'option label', 'wpsso' ), '', 'plugin_embedded_media' ).
			'<td class="blank">'.
			'<p>'.$form->get_nocb_cmt( 'plugin_facebook_api' ).' '.
				_x( 'Facebook Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_slideshare_api' ).' '.
				_x( 'Slideshare Presentations', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_soundcloud_api' ).' '.
				_x( 'Soundcloud Tracks', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_vimeo_api' ).' '.
				_x( 'Vimeo Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_wistia_api' ).' '.
				_x( 'Wistia Videos', 'option value', 'wpsso' ).'</p>'.
			'<p>'.$form->get_nocb_cmt( 'plugin_youtube_api' ).' '.
				_x( 'YouTube Videos and Playlists', 'option value', 'wpsso' ).'</p>'.
			'</td>';

			return $table_rows;
		}

		public function filter_plugin_integration_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="3" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows['plugin_honor_force_ssl'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Honor the FORCE_SSL Constant',
				'option label', 'wpsso' ), '', 'plugin_honor_force_ssl' ).
			$form->get_nocb_td( 'plugin_honor_force_ssl' );

			$table_rows['plugin_html_attr_filter'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( '&lt;html&gt; Attributes Filter Hook',
				'option label', 'wpsso' ), '', 'plugin_html_attr_filter' ).
			'<td class="blank field_name">Name:&nbsp;'.$this->p->options['plugin_html_attr_filter_name'].'</td>'.
			'<td class="blank">Priority:&nbsp;'.$this->p->options['plugin_html_attr_filter_prio'].'</td>';

			$table_rows['plugin_head_attr_filter'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( '&lt;head&gt; Attributes Filter Hook',
				'option label', 'wpsso' ), '', 'plugin_head_attr_filter' ).
			'<td class="blank field_name">Name:&nbsp;'.$this->p->options['plugin_head_attr_filter_name'].'</td>'.
			'<td class="blank">Priority:&nbsp;'.$this->p->options['plugin_head_attr_filter_prio'].'</td>';

			$table_rows['plugin_check_head'] = $form->get_th_html( _x( 'Check for Duplicate Meta Tags',
				'option label', 'wpsso' ), '', 'plugin_check_head' ).
			$form->get_nocb_td( 'plugin_check_head' );

			$table_rows['plugin_filter_lang'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Use WP Locale for Language',
				'option label', 'wpsso' ), '', 'plugin_filter_lang' ).
			$form->get_nocb_td( 'plugin_filter_lang' );

			$table_rows['plugin_create_wp_sizes'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Create Missing WP Media Sizes',
				'option label', 'wpsso' ), '', 'plugin_create_wp_sizes' ).
			$form->get_nocb_td( 'plugin_create_wp_sizes' );

			$table_rows['plugin_check_img_dims'] = $form->get_th_html( _x( 'Enforce Image Dimensions Check',
				'option label', 'wpsso' ), '', 'plugin_check_img_dims' ).
			$form->get_nocb_td( 'plugin_check_img_dims',
				'<em>'._x( 'recommended', 'option comment', 'wpsso' ).'</em>' );

			$table_rows['plugin_upscale_images'] = $form->get_th_html( _x( 'Allow Upscale of WP Media Images',
				'option label', 'wpsso' ), '', 'plugin_upscale_images' ).
			$form->get_nocb_td( 'plugin_upscale_images' );

			$table_rows['plugin_upscale_img_max'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Maximum Image Upscale Percent',
				'option label', 'wpsso' ), '', 'plugin_upscale_img_max' ).
			'<td class="blank">'.$this->p->options['plugin_upscale_img_max'].' %</td>';

			$table_rows[] = $form->get_th_html( _x( 'Enable WP Excerpt for Pages',
				'option label', 'wpsso' ), '', 'plugin_page_excerpt' ).
			$form->get_nocb_td( 'plugin_page_excerpt' );

			$table_rows[] = $form->get_th_html( _x( 'Enable WP Tags for Pages',
				'option label', 'wpsso' ), '', 'plugin_page_tags' ).
			$form->get_nocb_td( 'plugin_page_tags' );

			return $table_rows;
		}

		public function filter_plugin_custom_meta_rows( $table_rows, $form, $network = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			/*
			 * Include Columns in Admin Lists
			 */
			$cols = '<table class="plugin-list-columns">'."\n".'<tr>';

			foreach ( WpssoMeta::get_column_headers() as $col_idx => $col_header ) {
				$cols .= '<th>'.$col_header.'</th>';
			}

			$cols .= '<td class="underline"></td></tr>'."\n";

			foreach ( array(
				'post' => __( 'Posts, Pages, and Custom Post Types List', 'wpsso' ),
				'media' => __( 'Media Library Item List', 'wpsso' ),
				'term' => __( 'Terms (Categories and Tags) List', 'wpsso' ),
				'user' => __( 'Users List' ),
			) as $mod_name => $mod_label ) {
				$cols .= '<tr>';
				foreach ( WpssoMeta::get_column_headers() as $col_idx => $col_header ) {
					$cols .= $form->get_nocb_td( 'plugin_'.$col_idx.'_col_'.$mod_name, '', true );
				}
				$cols .= '<td><p>'.$mod_label.'</p></td></tr>'."\n";
			}

			$cols .= '</table>'."\n";

			$table_rows['plugin_show_columns'] = $form->get_th_html( _x( 'Show Columns in Admin Lists',
				'option label', 'wpsso' ), '', 'plugin_show_columns' ).
					'<td>'.$cols.'</td>';

			/*
			 * Include Custom Meta Metabox
			 */
			$add_to_checkboxes = $form->get_post_type_checkboxes( 'plugin_add_to', '', '', true );
			$add_to_checkboxes .= '<p>'.$form->get_nocb_cmt( 'plugin_add_to_term' ).	// add term checbox
				' '.__( 'Terms (Categories and Tags)', 'wpsso' ).'</p>';
			$add_to_checkboxes .= '<p>'.$form->get_nocb_cmt( 'plugin_add_to_user' ).	// add user checkbox
				' '.__( 'User Profile', 'wpsso' ).'</p>';

			$menu_title = _x( $this->p->cf['menu']['title'], 'menu title', 'wpsso' );

			$table_rows['plugin_add_to'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( sprintf( _x( 'Add %s Metabox to',
				'option label', 'wpsso' ), $menu_title ), '', 'plugin_add_to' ).
			'<td class="blank">'.$add_to_checkboxes.'</td>';

			$table_rows['plugin_wpseo_social_meta'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Read Yoast SEO Social Meta',
				'option label', 'wpsso' ), '', 'plugin_wpseo_social_meta' ).
			$form->get_nocb_td( 'plugin_wpseo_social_meta' );

			$table_rows['plugin_def_currency'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Default Currency',
				'option label', 'wpsso' ), '', 'plugin_def_currency' ).
			'<td class="blank">'.$form->get_no_select( 'plugin_def_currency',
				SucomUtil::get_currencies() ).'</td>';

			foreach ( (array) apply_filters( $this->p->cf['lca'].'_get_cf_md_idx',
				$this->p->cf['opt']['cf_md_idx'] ) as $cf_idx => $md_idx ) {

				if ( isset( $this->p->cf['form']['cf_labels'][$cf_idx] ) &&	// just in case
					$opt_label = $this->p->cf['form']['cf_labels'][$cf_idx] ) {

					if ( empty( $md_idx ) ) {	// custom fields can be disabled by filters
						$this->p->options[$cf_idx] = '';
					}

					$table_rows[$cf_idx] = '<tr class="hide_in_basic">'.
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

			$table_rows[] = '<td colspan="'.( $network ? 4 : 2 ).'" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpsso' ) ).'</td>';

			$table_rows['plugin_head_cache_exp'] = $form->get_th_html( _x( 'Head Markup Array Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_head_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_head_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_head_cache_exp', $form, $network );

			$table_rows['plugin_short_url_cache_exp'] = $form->get_th_html( _x( 'Shortened URL Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_short_url_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_short_url_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_short_url_cache_exp', $form, $network );

			$table_rows['plugin_content_cache_exp'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Filtered Content Text Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_content_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_content_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_content_cache_exp', $form, $network );

			$table_rows['plugin_imgsize_cache_exp'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Get Image (URL) Size Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_imgsize_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_imgsize_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_imgsize_cache_exp', $form, $network );

			$table_rows['plugin_topics_cache_exp'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Article Topics Array Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_topics_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_topics_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_topics_cache_exp', $form, $network );

			$table_rows['plugin_types_cache_exp'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Schema Types Array Cache Expiry',
				'option label', 'wpsso' ), '', 'plugin_types_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_types_cache_exp'].' '.
			_x( 'seconds (0 to disable)', 'option comment', 'wpsso' ).'</td>'.
			WpssoAdmin::get_option_site_use( 'plugin_types_cache_exp', $form, $network );

			$table_rows['plugin_show_purge_count'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Show Cache Purge Count on Update',
				'option label', 'wpsso' ), '', 'plugin_show_purge_count' ).
			$form->get_nocb_td( 'plugin_show_purge_count' ).
			WpssoAdmin::get_option_site_use( 'plugin_show_purge_count', $form, $network );

			$table_rows['plugin_clear_on_save'] = $form->get_th_html( _x( 'Clear All Cache on Save Settings',
				'option label', 'wpsso' ), '', 'plugin_clear_on_save' ).
			$form->get_nocb_td( 'plugin_clear_on_save' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_on_save', $form, $network );

			$table_rows['plugin_clear_short_urls'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Clear Short URLs on Clear All Cache',
				'option label', 'wpsso' ), '', 'plugin_clear_short_urls' ).
			$form->get_nocb_td( 'plugin_clear_short_urls' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_short_urls', $form, $network );

			$table_rows['plugin_clear_for_comment'] = $form->get_th_html( _x( 'Clear Post Cache for New Comment',
				'option label', 'wpsso' ), '', 'plugin_clear_for_comment' ).
			$form->get_nocb_td( 'plugin_clear_for_comment' ).
			WpssoAdmin::get_option_site_use( 'plugin_clear_for_comment', $form, $network );

			return $table_rows;
		}

		public function filter_plugin_apikeys_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpsso' ) ).'</td>';

			$table_rows['plugin_shortener'] = $form->get_th_html( _x( 'Preferred URL Shortening Service',
				'option label', 'wpsso' ), '', 'plugin_shortener' ).
			'<td class="blank">[None]</td>';

			$table_rows['plugin_min_shorten'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Minimum URL Length to Shorten',
				'option label', 'wpsso' ), '', 'plugin_min_shorten' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_min_shorten'].' '.
				_x( 'characters', 'option comment', 'wpsso' ).'</td>';

			$table_rows['plugin_shortlink'] = $form->get_th_html( _x( 'Use Shortened URL for WP Shortlink',
				'option label', 'wpsso' ), '', 'plugin_shortlink' ).
			$form->get_nocb_td( 'plugin_shortlink' );

			$table_rows['subsection_plugin_bitly'] = '<tr class="hide_in_basic">'.
				'<td></td><td class="subsection"><h4>'.
				_x( 'Bitly URL Shortener', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_bitly_login'] = $form->get_th_html( _x( 'Bitly Username',
				'option label', 'wpsso' ), '', 'plugin_bitly_login' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_login'].'</td>';

			$table_rows['plugin_bitly_access_token'] = $form->get_th_html( '<a href="https://bitly.com/a/oauth_apps">'.
				_x( 'Bitly Generic Access Token', 'option label', 'wpsso' ).'</a>', '', 'plugin_bitly_access_token' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_access_token'].'</td>';

			$table_rows['plugin_bitly_api_key'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( '<a href="http://bitly.com/a/your_api_key">'.
				_x( 'or Bitly API Key (deprecated)', 'option label', 'wpsso' ).'</a>', '', 'plugin_bitly_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_api_key'].' <em>'.
				_x( 'api key authentication is deprecated', 'option comment', 'wpsso' ).'</em></td>';

			$table_rows['plugin_bitly_domain'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Bitly Custom Short Domain',
				'option label', 'wpsso' ), '', 'plugin_bitly_domain' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_domain'].'</td>';

			$table_rows['subsection_plugin_google'] = '<tr class="hide_in_basic">'.
				'<td></td><td class="subsection"><h4>'.
				_x( 'Google APIs', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_google_api_key'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Google Project App BrowserKey',
				'option label', 'wpsso' ), '', 'plugin_google_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_google_api_key'].'</td>';

			$table_rows['plugin_google_shorten'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Google URL Shortener API is ON',
				'option label', 'wpsso' ), '', 'plugin_google_shorten' ).
			'<td class="blank">'._x( $this->p->cf['form']['yes_no'][$this->p->options['plugin_google_shorten']],
				'option value', 'wpsso' ).'</td>';

			$table_rows['subsection_plugin_owly'] = '<tr class="hide_in_basic">'.
				'<td></td><td class="subsection"><h4>'.
				_x( 'Ow.ly URL Shortener', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_owly_api_key'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'Ow.ly API Key',
				'option label', 'wpsso' ), '', 'plugin_owly_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_owly_api_key'].'</td>';

			$table_rows['subsection_plugin_yourls'] = '<tr class="hide_in_basic">'.
				'<td></td><td class="subsection"><h4>'.
				_x( 'Your Own URL Shortener (YOURLS)', 'metabox title', 'wpsso' ).'</h4></td>';

			$table_rows['plugin_yourls_api_url'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'YOURLS API URL',
				'option label', 'wpsso' ), '', 'plugin_yourls_api_url' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_api_url'].'</td>';

			$table_rows['plugin_yourls_username'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'YOURLS Username',
				'option label', 'wpsso' ), '', 'plugin_yourls_username' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_username'].'</td>';

			$table_rows['plugin_yourls_password'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'YOURLS Password',
				'option label', 'wpsso' ), '', 'plugin_yourls_password' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_password'].'</td>';

			$table_rows['plugin_yourls_token'] = '<tr class="hide_in_basic">'.
			$form->get_th_html( _x( 'YOURLS Token',
				'option label', 'wpsso' ), '', 'plugin_yourls_token' ).
			'<td class="blank mono">'.$this->p->options['plugin_yourls_token'].'</td>';

			return $table_rows;
		}

		public function filter_cm_custom_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="4" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows[] = '<td></td>'.
			$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), 'checkbox left', 'custom-cm-show-checkbox' ).
			$form->get_th_html( _x( 'Contact Field Name', 'column title', 'wpsso' ), 'medium left', 'custom-cm-field-name' ).
			$form->get_th_html( _x( 'Profile Contact Label', 'column title', 'wpsso' ), 'wide left', 'custom-cm-contact-label',
				array( 'is_locale' => true ) );

			$sorted_opt_pre = $this->p->cf['opt']['cm_prefix'];
			ksort( $sorted_opt_pre );

			foreach ( $sorted_opt_pre as $id => $opt_pre ) {

				$cm_enabled_key = 'plugin_cm_'.$opt_pre.'_enabled';
				$cm_name_key = 'plugin_cm_'.$opt_pre.'_name';
				$cm_label_value = SucomUtil::get_key_value( 'plugin_cm_'.$opt_pre.'_label', $this->p->options );

				// not all social websites have a contact method field
				if ( isset( $this->p->options[$cm_enabled_key] ) ) {

					switch ( $id ) {
						case 'facebook':
						case 'gplus':
						case 'twitter':
							$tr = '';
							break;
						default:
							$tr = '<tr class="hide_in_basic">';
							break;
					}

					$opt_label = empty( $this->p->cf['*']['lib']['website'][$id] ) ?
						ucfirst( $id ) : $this->p->cf['*']['lib']['website'][$id];

					$opt_label_lc = strtolower( $opt_label );

					if  ( $opt_label_lc === 'googleplus' || $opt_label_lc === 'gplus' ) {
						$opt_label = 'Google+';
					}

					$table_rows[] = $tr.$form->get_th_html( $opt_label, 'medium' ).
					$form->get_nocb_td( $cm_enabled_key, '', true ).
					'<td class="blank medium">'.$form->get_no_input( $cm_name_key, 'medium' ).'</td>'.
					'<td class="blank wide">'.$form->get_no_input_value( $cm_label_value ).'</td>';
				}
			}

			return $table_rows;
		}

		public function filter_cm_builtin_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="4" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$table_rows[] = '<td></td>'.
			$form->get_th_html( _x( 'Show', 'column title', 'wpsso' ), 'checkbox left', 'custom-cm-show-checkbox' ).
			$form->get_th_html( _x( 'Contact Field Name', 'column title', 'wpsso' ), 'medium left', 'custom-cm-field-name' ).
			$form->get_th_html( _x( 'Profile Contact Label', 'column title', 'wpsso' ), 'wide left', 'custom-cm-contact-label', 
				array( 'is_locale' => true ) );

			$sorted_cm_names = $this->p->cf['wp']['cm_names'];
			ksort( $sorted_cm_names );

			foreach ( $sorted_cm_names as $id => $opt_label ) {

				$cm_enabled_key = 'wp_cm_'.$id.'_enabled';
				$cm_name_key = 'wp_cm_'.$id.'_name';
				$cm_label_value = SucomUtil::get_key_value( 'wp_cm_'.$id.'_label', $this->p->options );

				// not all social websites have a contact method field
				if ( isset( $this->p->options[$cm_enabled_key] ) ) {
					$table_rows[] = $form->get_th_html( $opt_label, 'medium' ).
					'<td class="checkbox blank">'.$form->get_nocb_cmt( $cm_enabled_key ).'</td>'.
					'<td class="medium">'.$form->get_no_input( $cm_name_key, 'medium' ).'</td>'.
					'<td class="blank wide">'.$form->get_no_input( $cm_label_value ).'</td>';
				}
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

					if ( strpos( $opt_key, 'add_' ) !== 0 ) {	// optimize
						continue;
					} elseif ( isset( $this->taglist_opts[$opt_key] ) ) {	// check cache for tags already shown
						continue;
					} elseif ( ! preg_match( $preg, $opt_key, $match ) ) {	// check option name for a match
						continue;
					}

					$highlight = '';
					$this->taglist_opts[$opt_key] = $opt_val;
					switch ( $opt_key ) {
						// disabled with a constant instead
						case 'add_meta_name_generator':
							continue 2;
					}
					$table_cells[] = '<!-- '.( implode( ' ', $match ) ).' -->'.	// required for sorting
						'<td class="checkbox blank">'.$form->get_nocb_cmt( $opt_key ).'</td>'.
						'<td class="xshort'.$highlight.'">'.$match[1].'</td>'.
						'<td class="taglist'.$highlight.'">'.$match[2].'</td>'.
						'<th class="taglist'.$highlight.'">'.$match[3].'</th>';
				}
			}
			return array_merge( $table_rows, SucomUtil::get_column_rows( $table_cells, 2 ) );
		}
	}
}

?>
