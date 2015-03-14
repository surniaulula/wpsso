<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPostmeta' ) ) {

	/*
	 * This class is extended by gpl/util/postmeta.php or pro/util/postmeta.php
	 * and the class object is created as $this->p->mods['util']['postmeta']
	 */
	class WpssoPostmeta {

		protected $p;
		protected $form;
		protected $header_tags = array();
		protected $post_info = array();

		protected function add_actions() {
			// everything bellow is for the admin interface
			if ( is_admin() ) {
				add_action( 'admin_head', array( &$this, 'set_header_tags' ) );
				add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes' ) );
				add_action( 'save_post', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'save_post', array( &$this, 'flush_cache' ), WPSSO_META_CACHE_PRIORITY );
				add_action( 'save_post', array( &$this, 'check_head' ), 1000 );
				add_action( 'edit_attachment', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_attachment', array( &$this, 'flush_cache' ), WPSSO_META_CACHE_PRIORITY );
			}
		}

		public function add_metaboxes() {
			if ( ( $obj = $this->p->util->get_post_object() ) === false ||
				empty( $obj->post_type ) )
					return;
			$post_id = empty( $obj->ID ) ? 0 : $obj->ID;
			$post_type = get_post_type_object( $obj->post_type );
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_type->name ] ) ? false : true;
			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_postmeta', $add_metabox, $post_id ) === true )
				add_meta_box( WPSSO_META_NAME, 'Social Settings', array( &$this, 'show_metabox_postmeta' ), $post_type->name, 'advanced', 'high' );
		}

		// hooked into the admin_head action
		public function set_header_tags() {
			if ( ! empty( $this->header_tags ) )	// only set header tags once
				return;

			if ( ( $obj = $this->p->util->get_post_object() ) === false ||
				empty( $obj->post_type ) )
					return;

			$screen = get_current_screen();
			$this->p->debug->log( 'screen id = '.$screen->id );
			// check for post/page/media edititing lists
			if ( strpos( $screen->id, 'edit-' ) !== false ||
				$screen->id === 'upload' )
					return;

			$post_id = empty( $obj->ID ) ? 0 : $obj->ID;
			if ( isset( $obj->post_status ) && $obj->post_status !== 'auto-draft' ) {

				$post_type = get_post_type_object( $obj->post_type );
				$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_type->name ] ) ? false : true;
				if ( apply_filters( $this->p->cf['lca'].'_add_metabox_postmeta', $add_metabox, $post_id ) === true ) {

					$this->p->util->add_plugin_image_sizes( $post_id );
					do_action( $this->p->cf['lca'].'_admin_postmeta_header', $post_type->name, $post_id );
					$this->header_tags = $this->p->head->get_header_array( $post_id );
					$this->post_info = $this->p->head->extract_post_info( $this->header_tags );

					if ( $obj->post_status == 'publish' &&
						! empty( $this->p->options['plugin_check_head'] ) &&
						empty( $this->post_info['og_image']['og:image'] ) )
							$this->p->notice->err( 'A Facebook / Open Graph image meta tag for this webpage could not be generated. Facebook and other social websites require at least one image meta tag to render their shared content correctly.', true );
				}
			}
		}

		public function show_metabox_postmeta( $post ) {
			$opts = $this->get_options( $post->ID );	// sanitize when saving, not reading
			$def_opts = $this->get_defaults();
			$post_type = get_post_type_object( $post->post_type );	// since 3.0
			$this->post_info['ptn'] = ucfirst( $post_type->name );
			$this->post_info['id'] = $post->ID;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );

			$metabox = 'meta';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', 
				array( 
					'header' => 'Title and Descriptions', 
					'media' => 'Priority Media', 
					'preview' => 'Social Preview',
					'tags' => 'Header Preview',
					'tools' => 'Validation Tools'
				)
			);

			if ( empty( $this->p->is_avail['metatags'] ) )
				unset( $tabs['tags'] );

			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key, $this->post_info ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form, $this->post_info ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		protected function get_rows( $metabox, $key, &$post_info ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'meta-preview':
					if ( get_post_status( $post_info['id'] ) !== 'auto-draft' ) {
						$rows = $this->get_rows_social_preview( $this->form, $post_info );
					} else $rows[] = '<td><p class="centered">Save a draft version or publish the '.
						$post_info['ptn'].' to display the Social Preview.</p></td>';
					break;

				case 'meta-tags':	
					if ( get_post_status( $post_info['id'] ) !== 'auto-draft' ) {
						foreach ( $this->header_tags as $m ) {
							if ( ! empty( $m[1] ) )
								$rows[] = '<th class="xshort">'.$m[1].'</th>'.
								'<th class="xshort">'.$m[2].'</th>'.
								'<td class="short">'.( isset( $m[6] ) ? '<!-- '.$m[6].' -->' : '' ).$m[3].'</td>'.
								'<th class="xshort">'.$m[4].'</th>'.
								'<td class="wide">'.( strpos( $m[5], 'http' ) === 0 ? 
									'<a href="'.$m[5].'">'.$m[5].'</a>' : $m[5] ).'</td>';
						}
						sort( $rows );
					} else $rows[] = '<td><p class="centered">Save a draft version or publish the '.
						$post_info['ptn'].' to display the Header Preview.</p></td>';
					break; 

				case 'meta-tools':
					if ( get_post_status( $post_info['id'] ) === 'publish' ||
						get_post_type( $post_info['id'] ) === 'attachment' ) {

						$rows = $this->get_rows_validation_tools( $this->form, $post_info );
					} else $rows[] = '<td><p class="centered">The Validation Tools will be available when the '
						.$post_info['ptn'].' is published with public visibility.</p></td>';
					break; 
			}
			return $rows;
		}

		public function get_rows_social_preview( &$form, &$post_info ) {
			$rows = array();
			$max_width = 600;
			$max_height = 315;
			$og_image = $post_info['og_image'];
			$div_style = 'width:'.$max_width.'px; height:'.$max_height.'px;';
			$have_sizes = ( ! empty( $og_image['og:image:width'] ) && 
				! empty( $og_image['og:image:height'] ) ) ? true : false;
			$is_sufficient = ( $have_sizes === true && 
				$og_image['og:image:width'] >= $max_width && 
				$og_image['og:image:height'] >= $max_height ) ? true : false;
			$msgs = array(
				'not_found' => '<p>No Open Graph Image Found</p>',
				'too_small' => '<p>Image Dimensions Smaller<br/>than Suggested Minimum<br/>of '.$max_width.' x '.$max_height.'px</p>',
				'no_size' => '<p>Image Dimensions Unknown<br/>or Not Available</p>',
			);

			foreach ( array( 'og:image:secure_url', 'og:image' ) as $key ) {
				if ( ! empty( $og_image[$key] ) ) {
					if ( $have_sizes === true ) {
						$image_preview_html = '<div class="preview_img" style="'.$div_style.' 
						background-size:'.( $is_sufficient === true ? 'cover' : $og_image['og:image:width'].' '.$og_image['og:image:height'] ).'; 
						background-image:url('.$og_image[$key].');" />'.( $is_sufficient === true ? '' : $msgs['too_small'] ).'</div>';
					} else {
						$image_preview_html = '<div class="preview_img" style="'.$div_style.' 
						background-image:url('.$og_image[$key].');" />'.$msgs['no_size'].'</div>';
					}
					break;	// stop after first image
				}
			}

			if ( empty( $image_preview_html ) )
				$image_preview_html = '<div class="preview_img" style="'.$div_style.'">'.$msgs['not_found'].'</div>';

			$rows[] = $this->p->util->th( 'Open Graph Social Preview Example', 'medium', 'postmeta-social-preview' ).
			'<td style="background-color:#e9eaed;">
			<div class="preview_box" style="width:'.( $max_width + 40 ).'px;">
				<div class="preview_box" style="width:'.$max_width.'px;">
					'.$image_preview_html.'
					<div class="preview_txt">
						<div class="preview_title">'.( empty( $post_info['og:title'] ) ? 'No Title' : $post_info['og:title'] ).'</div>
						<div class="preview_desc">'.( empty( $post_info['og:description'] ) ? 'No Description' : $post_info['og:description'] ).'</div>
						<div class="preview_by">'.( $_SERVER['SERVER_NAME'].( empty( $post_info['author'] ) ? '' : ' | By '.$post_info['author'] ) ).'</div>
					</div>
				</div>
			</div></td>';
	
			return $rows;
		}

		public function get_rows_validation_tools( &$form, &$post_info ) {
			$rows = array();

			$rows[] = $this->p->util->th( 'Facebook Debugger' ).'<td class="validate"><p>Refresh the Facebook cache and validate the Open Graph / Rich Pin meta tags for this '.$post_info['ptn'].'. Facebook, Pinterest, LinkedIn, Google+, and most social websites use Open Graph meta tags. The Facebook Debugger remains the most stable and reliable method to verify Open Graph meta tags.</p>
			
			<p><strong>Please note that you may have to click the "Fetch new scrape Information" button several times to refresh Facebook\'s cache</strong>.</p></td>

			<td class="validate">'.$form->get_button( 'Validate Open Graph', 'button-secondary', null, 
			'https://developers.facebook.com/tools/debug/og/object?q='.urlencode( $this->p->util->get_sharing_url( $post_info['id'] ) ), true ).'</td>';

			$rows[] = $this->p->util->th( 'Google Structured Data Testing Tool' ).'<td class="validate"><p>Verify that Google can correctly parse your structured data markup (meta tags, Schema, Microdata, and social JSON-LD markup) for Google Search and Google+.</p></td>

			<td class="validate">'.$form->get_button( 'Validate Data Markup', 'button-secondary', null, 
			'https://developers.google.com/structured-data/testing-tool/?url='.urlencode( $this->p->util->get_sharing_url( $post_info['id'] ) ), true ).'</td>';

			$rows[] = $this->p->util->th( 'Pinterest Rich Pin Validator' ).'<td class="validate"><p>Validate the Open Graph / Rich Pin meta tags, and apply to have them displayed on Pinterest.</p></td>

			<td class="validate">'.$form->get_button( 'Validate Rich Pins', 'button-secondary', null, 
			'http://developers.pinterest.com/rich_pins/validator/?link='.urlencode( $this->p->util->get_sharing_url( $post_info['id'] ) ), true ).'</td>';

			$rows[] = $this->p->util->th( 'Twitter Card Validator' ).'<td class="validate"><p>The Twitter Card Validator does not accept query arguments &ndash; copy-paste the following sharing URL into the validation input field. To enable the display of Twitter Card information in tweets, you must submit a URL for each type of card you provide (Summary, Summary with Large Image, Photo, Gallery, Player, and/or Product card).</p>
			
			<p>'.$form->get_input_for_copy( $this->p->util->get_sharing_url( $post_info['id'] ), 'wide' ).'</p></td>

			<td class="validate">'.$form->get_button( 'Validate Twitter Card', 'button-secondary', null, 
			'https://dev.twitter.com/docs/cards/validation/validator', true ).'</td>';

			return $rows;
		}

		public function get_og_video( $num = 0, $post_id, $check_dupes = true, $meta_pre = 'og' ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
			return array();
		}

		public function get_og_image( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true, $force_regen = false, $meta_pre = 'og' ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
			return array();
		}

                public function reset_options( $post_id ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
		}

                public function get_options( $post_id, $idx = false, $attr = array() ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
			if ( $idx !== false )
				return false;
			else return array();
		}

		public function get_defaults( $idx = false ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
			if ( $idx !== false )
				return false;
			else return array();
		}

		public function save_options( $post_id ) {
			$this->p->debug->log( __METHOD__.' not implemented in free version' );
			return $post_id;
		}

		public function flush_cache( $post_id ) {
			$this->p->util->flush_post_cache( $post_id );
			return $post_id;
		}

		public function check_head( $post_id ) {
			if ( empty( $this->p->options['plugin_check_head'] ) )
				return $post_id;

			if ( ( $obj = $this->p->util->get_post_object( $post_id ) ) === false )
				return $post_id;

			// only check registered front-end post types (to avoid menu items, product variations, etc.)
			$post_types = $this->p->util->get_post_types( 'frontend', 'names' );
			if ( empty( $obj->post_type ) || 
				! in_array( $obj->post_type, $post_types ) )
					return $post_id;

			// only check published posts
			if ( ! isset( $obj->post_status ) || 
				$obj->post_status !== 'publish' )
					return $post_id;

			$permalink_no_meta = add_query_arg( array( 'WPSSO_META_TAGS_DISABLE' => 1 ), get_permalink( $post_id ) );
			if ( ( $metas = $this->p->util->get_head_meta( $permalink_no_meta, '/html/head/link|/html/head/meta', false ) ) !== false ) {
				foreach( array(
					'link' => array( 'rel' ),
					'meta' => array( 'name', 'itemprop', 'property' ),
				) as $tag => $types ) {
					if ( isset( $metas[$tag] ) ) {
						foreach( $metas[$tag] as $m ) {
							foreach( $types as $t ) {
								if ( isset( $m[$t] ) && $m[$t] !== 'generator' && 
									! empty( $this->p->options['add_'.$tag.'_'.$t.'_'.$m[$t]] ) )
										$this->p->notice->err( 'Possible conflict detected - your theme or another plugin is adding a <code>'.$tag.' '.$t.'="'.$m[$t].'"</code> HTML tag to the head section of this webpage.', true );
							}
						}
					}
				}
			}
			return $post_id;
		}

		protected function get_nonce() {
			return ( defined( 'NONCE_KEY' ) ? NONCE_KEY : '' ).plugin_basename( __FILE__ );
		}
	}
}

?>
