<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoGplAdminAdvanced' ) ) {

	class WpssoGplAdminAdvanced {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'plugin_settings_rows' => 3,
				'plugin_content_rows' => 2,
				'plugin_social_rows' => 2,
				'plugin_cache_rows' => 3,
				'plugin_apikeys_rows' => 3,
				'cm_custom_rows' => 2,
				'cm_builtin_rows' => 2,
				'taglist_tags_rows' => 4,
			), 20 );
		}

		public function filter_plugin_settings_rows( $rows, $form, $network = false ) {

			if ( $network === true )
				return $rows;

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->get_th( 'Check Webpage Head for Conflicts', 'highlight', 'plugin_check_head' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_check_head' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Report Cache Purge Count', null, 'plugin_cache_info' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_cache_info' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Use WP Locale for Language', null, 'plugin_filter_lang' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_filter_lang' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Auto-Resize Media Images', null, 'plugin_auto_img_resize' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_auto_img_resize' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Check Image Dimensions', null, 'plugin_ignore_small_img' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_ignore_small_img' ).'</td>';

			if ( ! empty( $this->p->cf['*']['lib']['shortcode'] ) ) {
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( 'Enable Shortcode(s)', null, 'plugin_shortcodes' ).
				'<td class="blank">'.$form->get_no_checkbox( 'plugin_shortcodes' ).'</td>';
			}

			if ( ! empty( $this->p->cf['*']['lib']['widget'] ) ) {
				$rows[] = '<tr class="hide_in_basic">'.
				$this->p->util->get_th( 'Enable Widget(s)', null, 'plugin_widgets' ).
				'<td class="blank">'.$form->get_no_checkbox( 'plugin_widgets' ).'</td>';
			}

			return $rows;
		}

		public function filter_plugin_content_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->get_th( 'Check for Embedded Media', null, 'plugin_embedded_media' ).
			'<td class="blank">'.
			'<p>'.$form->get_no_checkbox( 'plugin_slideshare_api' ).' Slideshare Presentations</p>'.
			'<p>'.$form->get_no_checkbox( 'plugin_vimeo_api' ).' Vimeo Videos</p>'.
			'<p>'.$form->get_no_checkbox( 'plugin_wistia_api' ).' Wistia Videos</p>'.
			'<p>'.$form->get_no_checkbox( 'plugin_youtube_api' ).' YouTube Videos and Playlists</p>'.
			'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Enable Excerpt for Pages', null, 'plugin_page_excerpt' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_page_excerpt' ).'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Enable WordPress Tags for Pages', null, 'plugin_page_tags' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_page_tags' ).'</td>';

			return $rows;
		}

		public function filter_plugin_social_rows( $rows, $form ) {

			$rows[] = '<td colspan="2" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$checkboxes = '';
			foreach ( $this->p->util->get_post_types( 'backend' ) as $post_type )
				$checkboxes .= '<p>'.$form->get_no_checkbox( 'plugin_add_to_'.$post_type->name ).' '.
					$post_type->label.' '.( empty( $post_type->description ) ? '' : '('.$post_type->description.')' ).'</p>';
			$checkboxes .= '<p>'.$form->get_no_checkbox( 'plugin_add_to_taxonomy' ).' Taxonomy (Categories and Tags)</p>';
			$checkboxes .= '<p>'.$form->get_no_checkbox( 'plugin_add_to_user' ).' User Profile</p>';

			$rows[] = $this->p->util->get_th( 'Show Social Settings Metabox on', null, 'plugin_add_to' ).
			'<td class="blank">'.$checkboxes.'</td>';
			
			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Image URL Custom Field', null, 'plugin_cf_img_url' ).
			'<td class="blank">'.$form->get_hidden( 'plugin_cf_img_url' ).
				$this->p->options['plugin_cf_img_url'].'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Video URL Custom Field', null, 'plugin_cf_vid_url' ).
			'<td class="blank">'.$form->get_hidden( 'plugin_cf_vid_url' ).
				$this->p->options['plugin_cf_vid_url'].'</td>';

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Video Embed HTML Custom Field', null, 'plugin_cf_vid_embed' ).
			'<td class="blank">'.$form->get_hidden( 'plugin_cf_vid_embed' ).
				$this->p->options['plugin_cf_vid_embed'].'</td>';
			
			return $rows;
		}

		public function filter_plugin_cache_rows( $rows, $form, $network = false ) {

			$rows[] = '<td colspan="'.( $network === false ? 2 : 4 ).'" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = $this->p->util->get_th( 'Object Cache Expiry', 'highlight', 'plugin_object_cache_exp' ).
			'<td nowrap class="blank">'.$this->p->options['plugin_object_cache_exp'].' seconds</td>'.
			$this->get_site_use( $form, $network, 'plugin_object_cache_exp' );

			$rows[] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Verify SSL Certificates', null, 'plugin_verify_certs' ).
			'<td class="blank">'.$form->get_no_checkbox( 'plugin_verify_certs' ).'</td>'.
			$this->get_site_use( $form, $network, 'plugin_verify_certs' );

			return $rows;
		}

		public function filter_plugin_apikeys_rows( $rows, $form, $network = false ) {

			$rows[] = '<td colspan="'.( $network === false ? 2 : 4 ).'" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpsso' ) ).'</td>';

			$rows['plugin_shortener'] = $this->p->util->get_th( 'Preferred URL Shortening Service', null, 'plugin_shortener' ).
			'<td class="blank">'.$form->get_hidden( 'plugin_shortener' ).
			$this->p->cf['form']['shorteners'][$this->p->options['plugin_shortener']].'</td>'.
			$this->get_site_use( $form, $network, 'plugin_shortener' );

			$rows['plugin_min_shorten'] = '<tr class="hide_in_basic">'.
			$this->p->util->get_th( 'Minimum URL Length to Shorten', null, 'plugin_min_shorten' ). 
			'<td nowrap class="blank">'.$this->p->options['plugin_min_shorten'].' characters</td>'.
			$this->get_site_use( $form, $network, 'plugin_min_shorten' );

			$rows['plugin_bitly_login'] = $this->p->util->get_th( 'Bit.ly Username', null, 'plugin_bitly_login' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_login'].'</td>'.
			$this->get_site_use( $form, $network, 'plugin_bitly_login' );

			$rows['plugin_bitly_api_key'] = $this->p->util->get_th( 'Bit.ly API Key', null, 'plugin_bitly_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_bitly_api_key'].'</td>'.
			$this->get_site_use( $form, $network, 'plugin_bitly_api_key' );

			$rows['plugin_google_api_key'] = $this->p->util->get_th( 'Google Project App BrowserKey', null, 'plugin_google_api_key' ).
			'<td class="blank mono">'.$this->p->options['plugin_google_api_key'].'</td>'.
			$this->get_site_use( $form, $network, 'plugin_google_api_key' );

			$rows['plugin_google_shorten'] = $this->p->util->get_th( 'Google URL Shortener API is ON', null, 'plugin_google_shorten' ).
			'<td class="blank">'.$this->p->cf['form']['yes_no'][$this->p->options['plugin_google_shorten']].'</td>'.
			$this->get_site_use( $form, $network, 'plugin_google_shorten' );

			return $rows;
		}

		protected function get_site_use( &$form, &$network, $opt ) {
			return $network === false ? '' : $this->p->util->get_th( 'Site Use', 'site_use' ).
				'<td class="site_use blank">'.$form->get_select( $opt.':use', 
					$this->p->cf['form']['site_option_use'], 'site_use', null, true, true ).'</td>';
		}

		public function filter_cm_custom_rows( $rows, $form ) {

			$rows[] = '<td colspan="4" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td></td>'.
			$this->p->util->get_th( 'Show', 'left checkbox' ).
			$this->p->util->get_th( 'Contact Field Name', 'left medium', 'custom-cm-field-name' ).
			$this->p->util->get_th( 'Profile Contact Label', 'left wide' );

			$sorted_opt_pre = $this->p->cf['opt']['pre'];
			ksort( $sorted_opt_pre );

			foreach ( $sorted_opt_pre as $id => $pre ) {

				$cm_enabled = 'plugin_cm_'.$pre.'_enabled';
				$cm_name = 'plugin_cm_'.$pre.'_name';
				$cm_label = 'plugin_cm_'.$pre.'_label';

				// check for the lib website classname for a nice 'display name'
				$name = empty( $this->p->cf['*']['lib']['website'][$id] ) ? 
					ucfirst( $id ) : $this->p->cf['*']['lib']['website'][$id];
				$name = $name == 'GooglePlus' ? 'Google+' : $name;

				// not all social websites have a contact method field
				if ( isset( $this->p->options[$cm_enabled] ) ) {
					$rows[] = $this->p->util->get_th( $name, 'medium' ).
					'<td class="blank checkbox">'.$form->get_no_checkbox( $cm_enabled ).'</td>'.
					'<td class="blank">'.$form->get_no_input( $cm_name, 'medium' ).'</td>'.
					'<td class="blank">'.$form->get_no_input( $cm_label ).'</td>';
				}
			}

			return $rows;
		}

		public function filter_cm_builtin_rows( $rows, $form ) {

			$rows[] = '<td colspan="4" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg' ).'</td>';

			$rows[] = '<td></td>'.
			$this->p->util->get_th( 'Show', 'left checkbox' ).
			$this->p->util->get_th( 'Contact Field Name', 'left medium', 'custom-cm-field-name' ).
			$this->p->util->get_th( 'Profile Contact Label', 'left wide' );

			$sorted_wp_cm = $this->p->cf['wp']['cm'];
			ksort( $sorted_wp_cm );

			foreach ( $sorted_wp_cm as $id => $name ) {

				$cm_enabled = 'wp_cm_'.$id.'_enabled';
				$cm_name = 'wp_cm_'.$id.'_name';
				$cm_label = 'wp_cm_'.$id.'_label';

				if ( array_key_exists( $cm_enabled, $this->p->options ) ) {
					$rows[] = $this->p->util->get_th( $name, 'medium' ).
					'<td class="blank checkbox">'.
						$form->get_hidden( $cm_enabled ).
						$form->get_no_checkbox( $cm_enabled ).'</td>'.
					'<td>'.$form->get_no_input( $cm_name, 'medium' ).'</td>'.
					'<td class="blank">'.$form->get_no_input( $cm_label ).'</td>';
				}
			}

			return $rows;
		}

		public function filter_taglist_tags_rows( $rows, $form, $network = false, $tag = '[^_]+' ) {
			$og_cols = 2;
			$cells = array();
			foreach ( $this->p->opt->get_defaults() as $opt => $val ) {
				if ( preg_match( '/^add_('.$tag.')_([^_]+)_(.+)$/', $opt, $match ) && 
					$opt !== 'add_meta_name_generator' ) {
					$highlight = $opt === 'add_meta_name_description' ? ' highlight' : '';
					$cells[] = '<td class="checkbox blank">'.$form->get_no_checkbox( $opt ).'</td>'.
						'<td class="xshort'.$highlight.'">'.$match[1].'</td>'.
						'<td class="taglist'.$highlight.'">'.$match[2].'</td>'.
						'<th class="taglist'.$highlight.'">'.$match[3].'</th>';
				}
			}

			sort( $cells );
			$col_rows = array();
			$per_col = ceil( count( $cells ) / $og_cols );
			foreach ( $cells as $num => $cell ) {
				if ( empty( $col_rows[ $num % $per_col ] ) )
					$col_rows[ $num % $per_col ] = '<tr class="hide_in_basic">';	// initialize the array
				$col_rows[ $num % $per_col ] .= $cell;					// create the html for each row
			}

			return array_merge( $rows, $col_rows );
		}
	}
}

?>
