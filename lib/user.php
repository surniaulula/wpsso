<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoUser' ) ) {

	/*
	 * This class is extended by gpl/util/user.php or pro/util/user.php
	 * and the class object is created as $this->p->mods['util']['user']
	 */
	class WpssoUser {

		protected $p;
		protected $form;
		protected $header_tags = array();
		protected $post_info = array();

		protected $opts = array();
		protected $defs = array();
		protected static $pref = array();

		protected function add_actions() {
			add_filter( 'user_contactmethods', array( &$this, 'add_contact_methods' ), 20, 2 );

			if ( is_admin() ) {
				add_action( 'admin_head', array( &$this, 'set_header_tags' ) );
				add_action( 'admin_init', array( &$this, 'add_metaboxes' ) );
				add_action( 'show_user_profile', array( &$this, 'show_metaboxes' ), 20 );
				add_action( 'edit_user_profile', array( &$this, 'show_metaboxes' ), 20 );
				add_action( 'edit_user_profile_update', array( &$this, 'sanitize_contact_methods' ), 5 );
				add_action( 'edit_user_profile_update', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_user_profile_update', array( &$this, 'flush_cache' ), WPSSO_META_CACHE_PRIORITY );
				add_action( 'personal_options_update', array( &$this, 'sanitize_contact_methods' ), 5 ); 
				add_action( 'personal_options_update', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY ); 
				add_action( 'personal_options_update', array( &$this, 'flush_cache' ), WPSSO_META_CACHE_PRIORITY ); 
			}
		}

		public function add_metaboxes() {
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;
			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_usermeta', $add_metabox ) === true )
				add_meta_box( WPSSO_META_NAME, 'Social Settings', array( &$this, 'show_metabox_usermeta' ), 
					'user', 'normal', 'high' );
		}

		// hooked into the admin_head action
		public function set_header_tags() {
			if ( ! empty( $this->header_tags ) )	// only set header tags once
				return;

			$screen = get_current_screen();
			$this->p->debug->log( 'screen id = '.$screen->id );
			switch ( $screen->id ) {
				case 'user-edit':
				case 'profile':
					$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;
					if ( apply_filters( $this->p->cf['lca'].'_add_metabox_usermeta', $add_metabox, $screen->id ) === true ) {

						$this->p->util->add_plugin_image_sizes();
						do_action( $this->p->cf['lca'].'_admin_usermeta_header', $screen->id );
						$this->header_tags = $this->p->head->get_header_array( false );
						$this->post_info = $this->p->head->extract_post_info( $this->header_tags );

						if ( ! empty( $this->p->options['plugin_check_head'] ) &&
							empty( $this->post_info['og_image']['og:image'] ) )
								$this->p->notice->err( 'A Facebook / Open Graph image meta tag for this webpage could not be generated. Facebook and other social websites require at least one image meta tag to render their shared content correctly.', true );
					}
					$this->p->debug->show_html( null, 'debug log' );
					break;
			}
		}

		public function show_metaboxes( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) )
				return;
			echo '<div id="poststuff">';
			do_meta_boxes( 'user', 'normal', $user );
			echo '</div>';
		}

		public function show_metabox_usermeta( $user ) {
			$opts = $this->get_options( $user->ID );
			$def_opts = $this->get_defaults();
			$screen = get_current_screen();
			$this->post_info['ptn'] = ucfirst( $screen->id );
			$this->post_info['id'] = false;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );

			$metabox = 'user';
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
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, $this->post_info ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		protected function get_rows( $metabox, $key, &$post_info ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'user-preview':
					$rows = $this->p->mods['util']['postmeta']->get_rows_social_preview( $this->form, $post_info );
					break;

				case 'user-tools':
					$rows = $this->p->mods['util']['postmeta']->get_rows_validation_tools( $this->form, $post_info );
					break; 

				case 'user-tags':	
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
					break; 
			}
			return $rows;
		}

		public function get_contact_fields( $fields = array() ) { 
			return array_merge( 
				array( 'none' => '[none]' ), 	// make sure none is first
				$this->add_contact_methods( 
					array( 
						'author' => 'Author Index', 
						'url' => 'Website'
					)
				)
			);
		}

		public function add_contact_methods( $fields = array(), $user = null ) { 
			// loop through each social website option prefix
			if ( ! empty( $this->p->cf['opt']['pre'] ) && 
				is_array( $this->p->cf['opt']['pre'] ) ) {

				foreach ( $this->p->cf['opt']['pre'] as $id => $pre ) {
					$cm_opt = 'plugin_cm_'.$pre.'_';
					// not all social websites have a contact fields, so check
					if ( array_key_exists( $cm_opt.'name', $this->p->options ) ) {
						$enabled = $this->p->options[$cm_opt.'enabled'];
						$name = $this->p->options[$cm_opt.'name'];
						$label = $this->p->options[$cm_opt.'label'];
						if ( ! empty( $enabled ) && 
							! empty( $name ) && 
							! empty( $label ) )
								$fields[$name] = $label;
					}
				}
			}
			if ( $this->p->check->aop() && 
				! empty( $this->p->cf['wp']['cm'] ) && 
				is_array( $this->p->cf['wp']['cm'] ) ) {

				foreach ( $this->p->cf['wp']['cm'] as $id => $name ) {
					$cm_opt = 'wp_cm_'.$id.'_';
					if ( array_key_exists( $cm_opt.'enabled', $this->p->options ) ) {
						$enabled = $this->p->options[$cm_opt.'enabled'];
						$label = $this->p->options[$cm_opt.'label'];
						if ( ! empty( $enabled ) ) {
							if ( ! empty( $label ) )
								$fields[$id] = $label;
						} else unset( $fields[$id] );
					}
				}
			}
			ksort( $fields, SORT_STRING );
			return $fields;
		}

		public function sanitize_contact_methods( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) )
				return;

			foreach ( $this->p->cf['opt']['pre'] as $id => $pre ) {
				$cm_opt = 'plugin_cm_'.$pre.'_';
				// not all social websites have a contact fields, so check
				if ( array_key_exists( $cm_opt.'name', $this->p->options ) ) {
					$enabled = $this->p->options[$cm_opt.'enabled'];
					$name = $this->p->options[$cm_opt.'name'];
					$label = $this->p->options[$cm_opt.'label'];
					if ( ! empty( $enabled ) && ! empty( $name ) && ! empty( $label ) ) {
						// sanitize values only for those enabled contact methods
						$val = wp_filter_nohtml_kses( $_POST[$name] );
						if ( ! empty( $val ) ) {
							switch ( $name ) {
								case $this->p->options['plugin_cm_skype_name']:
									// no change
									break;
								case $this->p->options['plugin_cm_twitter_name']:
									$val = substr( preg_replace( '/[^a-zA-Z0-9_]/', '', $val ), 0, 15 );
									if ( ! empty( $val ) ) 
										$val = '@'.$val;
									break;
								default:
									// all other contact methods are assumed to be URLs
									if ( strpos( $val, '://' ) === false )
										$val = '';
									break;
							}
						}
						$_POST[$name] = $val;
					}
				}
			}
			return $user_id;
		}

		// provides backwards compatibility for wp 3.0
		public static function get_user_id_contact_methods( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( function_exists( 'wp_get_user_contact_methods' ) )	// since wp 3.7
				return wp_get_user_contact_methods( $user );
			else {
				$methods = array();
				if ( get_site_option( 'initial_db_version' ) < 23588 ) {
					$methods = array(
						'aim'    => __( 'AIM' ),
						'yim'    => __( 'Yahoo IM' ),
						'jabber' => __( 'Jabber / Google Talk' )
					); 
				}
				return apply_filters( 'user_contactmethods', $methods, $user );
			}
		}

		public function get_person_json_script( $author_id, $size_name = 'thumbnail' ) {
			if ( empty( $author_id ) )
				return false;

			$website_url = get_the_author_meta( 'url', $author_id );
			if ( strpos( $website_url, '://' ) === false )
				return false;

			$cm = self::get_user_id_contact_methods( $author_id );
			$og_image = $this->p->media->get_author_image( 1, $size_name, $author_id, false );
			if ( count( $og_image ) > 0 ) {
				$image = reset( $og_image );
				$image_url = $image['og:image'];
			} else $image_url = '';

			$json_script = '<script type="application/ld+json">{
	"@context" : "http://schema.org",
	"@type" : "Person",
	"name" : "'.$this->get_author_name( $author_id, 'fullname' ).'",
	"url" : "'.$website_url.'",
	"image" : "'.$image_url.'",
	"sameAs" : ['."\n";
			foreach ( $cm as $id => $label ) {
				$sameAs = trim( get_the_author_meta( $id, $author_id ) );
				if ( empty( $sameAs ) )
					continue;

				if ( $id === $this->p->options['plugin_cm_twitter_name'] )
					$sameAs = 'https://twitter.com/'.preg_replace( '/^@/', '', $sameAs );

				if ( strpos( $sameAs, '://' ) !== false )
					$json_script .= "\t\t\"".$sameAs."\",\n";
			}
			$json_script = rtrim( $json_script, ",\n" )."\n\t]\n}</script>\n";

			return $json_script;
		}

		public function get_article_author( $author_id, $url_field = 'og_author_field' ) {
			$ret = array();
			if ( ! empty( $author_id ) ) {
				$ret[] = $this->get_author_website_url( $author_id, $this->p->options[$url_field] );

				// add the author's name if this is the Pinterest crawler
				if ( SucomUtil::crawler_name( 'pinterest' ) === true )
					$ret[] = $this->get_author_name( $author_id, $this->p->options['rp_author_name'] );

			} else $this->p->debug->log( 'author_id provided is empty' );
			return $ret;
		}

		public function get_display_names() {
			$user_ids = array();
			foreach ( get_users() as $user ) 
				$user_ids[$user->ID] = $user->display_name;
			$user_ids[0] = 'none';
			return $user_ids;
		}

		// called from head and opengraph classes
		public function get_author_name( $author_id, $field_id = 'display_name' ) {
			$name = '';
			switch ( $field_id ) {
				case 'none':
					break;
				case 'fullname':
					$name = trim( get_the_author_meta( 'first_name', $author_id ) ).' '.
						trim( get_the_author_meta( 'last_name', $author_id ) );
					break;
				// sanitation controls, just in case ;-)
				case 'user_login':
				case 'user_nicename':
				case 'display_name':
				case 'nickname':
				case 'first_name':
				case 'last_name':
					$name = get_the_author_meta( $field_id, $author_id );	// since wp 2.8.0 
					break;
			}
			return $name;
		}

		// called from head and opengraph classes
		public function get_author_website_url( $author_id, $field_id = 'url' ) {
			$url = '';
			switch ( $field_id ) {
				case 'none':
					break;
				case 'index':
					$url = get_author_posts_url( $author_id );
					break;
				default:
					$url = get_the_author_meta( $field_id, $author_id );	// since wp 2.8.0 

					// if empty or not a url, then fallback to the author index page,
					// if the requested field is the opengraph or link author field
					if ( empty( $url ) || ! preg_match( '/:\/\//', $url ) ) {
						if ( $this->p->options['og_author_fallback'] && (
							$field_id === $this->p->options['og_author_field'] || 
							$field_id === $this->p->options['seo_author_field'] ) ) {

							$this->p->debug->log( 'fetching the author index page url as fallback' );
							$url = get_author_posts_url( $author_id );
						}
					}
					break;
			}
			return $url;
		}

		public function reset_metabox_prefs( $pagehook, $box_ids = array(), $meta_name = '', $section = '', $force = false ) {
			$user_id = get_current_user_id();	// since wp 3.0
			// define a new state to set for the box_ids given
			switch ( $meta_name ) {
				case 'order':	$meta_states = array( 'meta-box-order' ); break ;
				case 'hidden':	$meta_states = array( 'metaboxhidden' ); break ;
				case 'closed':	$meta_states = array( 'closedpostboxes' ); break ;
				default: $meta_states = array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ); break;
			}
			foreach ( $meta_states as $state ) {
				// define the meta_key for that option
				$meta_key = $state.'_'.$pagehook; 
				// an empty box_ids array means reset the whole page
				if ( $force && empty( $box_ids ) )
					delete_user_option( $user_id, $meta_key, true );
				$is_changed = false;
				$is_default = false;
				$opts = get_user_option( $meta_key, $user_id );
				if ( ! is_array( $opts ) ) {
					$is_changed = true;
					$is_default = true;
					$opts = array();
				}
				if ( $is_default || $force ) {
					foreach ( $box_ids as $id ) {
						// change the order only if forced (default is controlled by add_meta_box() order)
						if ( $force && $state == 'meta-box-order' && ! empty( $opts[$section] ) ) {
							// don't proceed if the metabox is already first
							if ( strpos( $opts[$section], $pagehook.'_'.$id ) !== 0 ) {
								$boxes = explode( ',', $opts[$section] );
								// remove the box, no matter its position in the array
								if ( $key = array_search( $pagehook.'_'.$id, $boxes ) !== false )
									unset( $boxes[$key] );
								// assume we want to be top-most
								array_unshift( $boxes, $pagehook.'_'.$id );
								$opts[$section] = implode( ',', $boxes );
								$is_changed = true;
							}
						} else {
							// check to see if the metabox is present for that state
							$key = array_search( $pagehook.'_'.$id, $opts );

							// if we're not targetting , then clear it
							if ( empty( $meta_name ) && $key !== false ) {
								unset( $opts[$key] );
								$is_changed = true;
							// otherwise if we want a state, add if it's missing
							} elseif ( ! empty( $meta_name ) && $key === false ) {
								$opts[] = $pagehook.'_'.$id;
								$is_changed = true;
							}
						}
					}
				}
				if ( $is_default || $is_changed )
					update_user_option( $user_id, $meta_key, array_unique( $opts ), true );
			}
		}

		static function delete_metabox_prefs( $user_id = false ) {
			$cf = WpssoConfig::get_config( false, true );

			$parent_slug = 'options-general.php';
			foreach ( array_keys( $cf['*']['lib']['setting'] ) as $id ) {
				$menu_slug = $cf['lca'].'-'.$id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}

			$parent_slug = $cf['lca'].'-'.key( $cf['*']['lib']['submenu'] );
			foreach ( array_keys( $cf['*']['lib']['submenu'] ) as $id ) {
				$menu_slug = $cf['lca'].'-'.$id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}
		}

		static function delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug ) {
			$pagehook = get_plugin_page_hookname( $menu_slug, $parent_slug);
			foreach ( array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ) as $state ) {
				$meta_key = $state.'_'.$pagehook;
				if ( $user_id !== false )
					delete_user_option( $user_id, $meta_key, true );
				else foreach ( get_users( array( 'meta_key' => $meta_key ) ) as $user )
					delete_user_option( $user->ID, $meta_key, true );
			}
		}

		public function get_options( $user_id = false, $idx = false ) {
			if ( $idx !== false ) 
				return false;
			else return array();
		}

		public function get_defaults( $idx = false ) {
			if ( $idx !== false ) 
				return false;
			else return array();
		}

		public function save_options( $user_id = false ) {
			return $user_id;
		}

		public static function save_pref( $prefs, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;
			if ( ! current_user_can( 'edit_user', $user_id ) )
				return false;
			if ( ! is_array( $prefs ) || empty( $prefs ) )
				return false;

			$old_prefs = self::get_pref( false, $user_id );	// get all prefs for user
			$new_prefs = array_merge( $old_prefs, $prefs );

			// don't bother saving unless we have to
			if ( $old_prefs !== $new_prefs ) {
				self::$pref[$user_id] = $new_prefs;	// update the pref cache
				unset( $new_prefs['options_filtered'] );
				update_user_meta( $user_id, WPSSO_PREF_NAME, $new_prefs );
				return true;
			} else return false;
		}

		public static function show_opts( $test = false, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;
			$value = self::get_pref( 'show_opts' );
			if ( $test !== false )
				return $test === $value ? true : false;
			else return $value;
		}

		public static function get_pref( $idx = false, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;
			if ( ! isset( self::$pref[$user_id]['options_filtered'] ) || 
				self::$pref[$user_id]['options_filtered'] !== true ) {

				self::$pref[$user_id] = get_user_meta( $user_id, WPSSO_PREF_NAME, true );
				if ( ! is_array( self::$pref[$user_id] ) )
					self::$pref[$user_id] = array();

				$wpsso = Wpsso::get_instance();
				if ( ! isset( self::$pref[$user_id]['show_opts'] ) )
					self::$pref[$user_id]['show_opts'] = $wpsso->options['plugin_show_opts'];

				self::$pref[$user_id]['options_filtered'] = true;
			}
			if ( $idx !== false ) {
				if ( isset( self::$pref[$user_id][$idx] ) ) 
					return self::$pref[$user_id][$idx];
				else return false;
			} else return self::$pref[$user_id];
			return false;	// just in case
		}

		public function flush_cache( $user_id ) {
			$lang = SucomUtil::get_locale();
			$post_id = 0;
			$sharing_url = $this->p->util->get_sharing_url( false );
			$transients = array(
				'WpssoHead::get_header_array' => array( 
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url,
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url.'_crawler:pinterest',
				),
			);
			$transients = apply_filters( $this->p->cf['lca'].'_user_cache_transients', 
				$transients, $post_id, $lang, $sharing_url );
			$deleted = $this->p->util->flush_cache_objects( $transients );
			if ( ! empty( $this->p->options['plugin_cache_info'] ) && $deleted > 0 )
				$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', true );
			return $user_id;
		}

		protected function get_nonce() {
			return ( defined( 'NONCE_KEY' ) ? NONCE_KEY : '' ).plugin_basename( __FILE__ );
		}
	}
}

?>
