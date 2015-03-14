<?php
/* 
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomUpdate' ) ) {

	class SucomUpdate {
	
		private $p;
		private $cron_hook = '';
		private $sched_hours = 0;
		private $sched_name = '';
		private static $c = array();

		public function __construct( &$plugin, &$ext, $hours = 24 ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$lca = $this->p->cf['lca'];						// ngfb
			$slug = $this->p->cf['plugin'][$lca]['slug'];				// nextgen-facebook
			$this->cron_hook = 'plugin_updates-'.$slug;				// plugin_updates-nextgen-facebook
			$this->sched_hours = ( empty( $hours ) ? 0 : $hours );			// 24
			$this->sched_name = ( empty( $hours ) ? '' : 'every'.$hours.'hours' );	// every24hours
			$this->set_config( $ext );
			$this->install_hooks();
		}

		public static function get_umsg( $lca ) {
			if ( ! isset( self::$c[$lca] ) )
				self::$c[$lca] = array();
			if ( ! array_key_exists( 'umsg', self::$c[$lca] ) ) {
				self::$c[$lca]['umsg'] = get_option( $lca.'_umsg' );
				if ( self::$c[$lca]['umsg'] !== false && self::$c[$lca]['umsg'] !== true )
					self::$c[$lca]['umsg'] = base64_decode( get_option( $lca.'_umsg' ) );
				if ( empty( self::$c[$lca]['umsg'] ) )
					self::$c[$lca]['umsg'] = false;
			}
			return self::$c[$lca]['umsg'];
		}

		public static function get_option( $lca, $idx = false ) {
			if ( ! empty( self::$c[$lca]['opt_name'] ) ) {
				$option_data = get_site_option( self::$c[$lca]['opt_name'], false, true );	// use_cache = true
				if ( $idx !== false ) {
					if ( is_object( $option_data->update ) &&
						isset( $option_data->update->$idx ) )
							return $option_data->update->$idx;
					else return false;
				} else return $option_data;
			}
			return false;
		}

		public function set_config( &$ext ) {
			foreach ( $ext as $lca => $info ) {
				$tid_name = 'plugin_'.$lca.'_tid';					// plugin_ngfb_tid
				if ( empty( $this->p->options[$tid_name] ) )
					$this->p->debug->log( 'empty '.$tid_name.' option - no update config for '.$lca );
				elseif ( empty( $info['slug'] ) ||
					empty( $info['base'] ) || 
					empty( $info['url']['update'] ) )
						$this->p->debug->log( 'incomplete config array - no update config for '.$lca );
				else self::$c[$lca] = array(
					'slug' => $info['slug'],					// nextgen-facebook
					'base' => $info['base'],					// nextgen-facebook/nextgen-facebook.php
					'opt_name' => 'external_updates-'.$info['slug'],		// external_updates-nextgen-facebook
					'json_url' => $info['url']['update'].'?tid='.$this->p->options[$tid_name],
					'expire' => 3600,
					'utime' => '',
				);
			}
		}

		public function install_hooks() {
			$this->p->debug->mark();
			add_filter( 'plugins_api', array( &$this, 'inject_data' ), 100, 3 );
			add_filter( 'transient_update_plugins', array( &$this, 'inject_update' ), 1000, 1 );
			add_filter( 'site_transient_update_plugins', array( &$this, 'inject_update' ), 1000, 1 );
			add_filter( 'pre_site_transient_update_plugins', array( &$this, 'enable_update' ), 1000, 1 );
			add_filter( 'http_headers_useragent', array( &$this, 'check_wpua' ), 9000, 1 );
			add_filter( 'http_request_host_is_external', array( &$this, 'allow_host' ), 1000, 3 );

			if ( $this->sched_hours > 0 && ! empty( $this->sched_name ) ) {
				add_filter( 'cron_schedules', array( &$this, 'custom_schedule' ) );
				add_action( $this->cron_hook, array( &$this, 'check_for_updates' ) );

				$schedule = wp_get_schedule( $this->cron_hook );
				if ( ! empty( $schedule ) && $schedule !== $this->sched_name ) {
					$this->p->debug->log( 'changing '.$this->cron_hook.' schedule from '.$schedule.' to '.$this->sched_name );
					wp_clear_scheduled_hook( $this->cron_hook );
				}
				if ( ! defined('WP_INSTALLING') && ! wp_next_scheduled( $this->cron_hook ) )
					wp_schedule_event( time(), $this->sched_name, $this->cron_hook );	// since wp 2.1.0
			} else wp_clear_scheduled_hook( $this->cron_hook );
		}

		public function check_wpua( $current_wpua ) {
			global $wp_version;
			$default_wpua = 'WordPress/'.$wp_version.'; '.get_bloginfo( 'url' );
			if ( $default_wpua !== $current_wpua ) {
				$this->p->debug->log( 'incorrect wpua found: '.$current_wpua );
				return $default_wpua;
			} else return $current_wpua;
		}
	
		public function allow_host( $ret, $host, $url ) {
			if ( strpos( $url, '?tid=' ) !== false ) {
				foreach ( self::$c as $lca => $info ) {
					$plugin_data = $this->get_json( $lca );
					if ( $url == $plugin_data->download_url )
						return true;
				}
			}
			return $ret;
		}

		public function inject_data( $result, $action = null, $args = null ) {
		    	if ( $action == 'plugin_information' && isset( $args->slug ) ) {
				foreach ( self::$c as $lca => $info ) {
					if ( ! empty( $info['slug'] ) && 
						$args->slug === $info['slug'] ) {
						$plugin_data = $this->get_json( $lca );
						if ( ! empty( $plugin_data ) ) 
							return $plugin_data->json_to_wp();
					}
				}
			}
			return $result;
		}

		// if updates have been disabled and/or manipulated (ie. $updates is not false), 
		// then re-enable by including our update data (if a new version is present)
		public function enable_update( $updates = false ) {
			if ( $updates !== false )
				$updates = $this->inject_update( $updates );
			return $updates;
		}

		public function inject_update( $updates = false ) {
			foreach ( self::$c as $lca => $info ) {
				if ( empty( $info['slug'] ) )
					continue;
				
				// remove existing plugin information to make sure it is correct
				if ( isset( $updates->response[$info['base']] ) ) {
					$this->p->debug->log( 'previous update information found (and removed)' );
					$this->p->debug->log( $updates->response[$info['base']] );
					unset( $updates->response[$info['base']] );	// nextgen-facebook/nextgen-facebook.php
				}

				$option_data = get_site_option( $info['opt_name'], false, true );	// use_cache = true
				if ( empty( $option_data ) )
					$this->p->debug->log( 'update option is empty' );
				elseif ( empty( $option_data->update ) )
					$this->p->debug->log( 'no update information' );
				elseif ( ! is_object( $option_data->update ) )
					$this->p->debug->log( 'update property is not an object' );
				elseif ( version_compare( $option_data->update->version, $this->get_installed_version( $lca ), '>' ) ) {
					$updates->response[$info['base']] = $option_data->update->json_to_wp();
					$this->p->debug->log( 'update version ('.$option_data->update->version.') is newer than installed version ('.$this->get_installed_version( $lca ).')' );
					$this->p->debug->log( $updates->response[$info['base']], 5 );
				} else {
					$this->p->debug->log( 'installed version is current - no update required' );
					$this->p->debug->log( $option_data->update->json_to_wp(), 5 );
				}
			}
			return $updates;
		}
	
		public function custom_schedule( $schedule ) {
			if ( $this->sched_hours > 0 ) {
				$schedule[$this->sched_name] = array(
					'interval' => $this->sched_hours * 3600,
					'display' => sprintf( 'Every %d hours', $this->sched_hours )
				);
			}
			return $schedule;
		}
	
		public function check_for_updates( $lca = null, $notice = false, $use_cache = true ) {
			if ( empty( $lca ) )
				$plugins = self::$c;				// check all plugins defined
			elseif ( isset( self::$c[$lca] ) )
				$plugins = array( $lca => self::$c[$lca] );	// check only one specific plugin
			else $plugins = array();

			foreach ( $plugins as $lca => $info ) {
				if ( empty( $info['slug'] ) )
					continue;

				$option_data = get_site_option( $info['opt_name'], false, true );	// use_cache = true
				if ( empty( $option_data ) ) {
					$option_data = new StdClass;
					$option_data->lastCheck = 0;
					$option_data->checkedVersion = 0;
					$option_data->update = null;
				}
				$option_data->lastCheck = time();
				$option_data->checkedVersion = $this->get_installed_version( $lca );
				$option_data->update = $this->get_update_data( $lca, $use_cache );
				$saved = update_site_option( $info['opt_name'], $option_data );
				if ( $notice === true || $this->p->debug->is_on() ) {
					if ( $saved === true ) {
						$this->p->debug->log( 'update information saved to the '.$info['opt_name'].' option' );
						$this->p->notice->inf( 'Plugin update information ('.
							$info['opt_name'].') has been retrieved and saved.', true );
					} else {
						$this->p->debug->log( 'failed saving the update information to the '.$info['opt_name'].' option' );
						$this->p->notice->err( 'WordPress returned an error saving the plugin update information ('.
							$info['opt_name'].') to the options table.', true );
					}
					$this->p->debug->log( $option_data );
				}
			}
		}
	
		public function get_update_data( $lca, $use_cache = true ) {
			$plugin_data = $this->get_json( $lca, $use_cache );
			if ( empty( $plugin_data ) ) 
				return null;
			return SucomPluginUpdate::from_plugin_data( $plugin_data );
		}
	
		public function get_json( $lca, $use_cache = true ) {
			if ( empty( self::$c[$lca]['slug'] ) )
				return null;

			global $wp_version;
			$site_url = get_bloginfo( 'url' );
			$json_url = empty( self::$c[$lca]['json_url'] ) ? '' : self::$c[$lca]['json_url'];
			$query = array( 'installed_version' => $this->get_installed_version( $lca ) );

			if ( empty( $json_url ) ) {
				$this->p->debug->log( 'exiting early: empty json_url' );
				return null;
			}
			
			if ( ! empty( $query ) ) 
				$json_url = add_query_arg( $query, $json_url );

			if ( ! empty( $this->p->is_avail['cache']['transient'] ) ) {
				$cache_salt = __METHOD__.'(json_url:'.$json_url.'_site_url:'.$site_url.')';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$this->p->debug->log( $cache_type.': transient salt '.$cache_salt );
				$last_update = get_option( $lca.'_utime' );
				if ( $use_cache && $last_update !== false ) {
					$plugin_data = get_transient( $cache_id );
					if ( $plugin_data !== false ) {
						$this->p->debug->log( $cache_type.': plugin data retrieved from transient '.$cache_id );
						return $plugin_data;
					}
				}
			}

			$uaplus = 'WordPress/'.$wp_version.' ('.( apply_filters( $lca.'_ua_plugin', 
				self::$c[$lca]['slug'].'/'.$query['installed_version'] ) ).'); '.$site_url;

			$options = array(
				'timeout' => 10, 
				'user-agent' => $uaplus,
				'headers' => array( 
					'Accept' => 'application/json',
					'X-WordPress-Id' => $uaplus,
				),
			);

			$plugin_data = null;
			$result = wp_remote_get( $json_url, $options );
			if ( is_wp_error( $result ) ) {
				if ( isset( $this->p->notice ) && is_object( $this->p->notice ) &&
					isset( $this->p->debug ) && is_object( $this->p->debug ) &&
					$this->p->debug->is_on() === true ) {

					$this->p->debug->log( 'update error: '.$result->get_error_message().'.' );
					$this->p->notice->err( 'Update error &ndash; '.$result->get_error_message().'.' );
				}
			} elseif ( isset( $result['response']['code'] ) && 
				( $result['response']['code'] == 200 ) && 
				! empty( $result['body'] ) ) {
	
				if ( ! empty( $result['headers']['x-smp-error'] ) ) {
					self::$c[$lca]['umsg'] = json_decode( $result['body'] );
					update_option( $lca.'_umsg', base64_encode( self::$c[$lca]['umsg'] ) );
				} else {
					self::$c[$lca]['umsg'] = false;
					delete_option( $lca.'_umsg' );
					$plugin_data = SucomPluginData::from_json( $result['body'] );
				}
			}

			self::$c[$lca]['utime'] = time();
			update_option( $lca.'_utime', self::$c[$lca]['utime'] );

			if ( ! empty( $this->p->is_avail['cache']['transient'] ) ) {
				set_transient( $cache_id, ( $plugin_data === null ? '' : $plugin_data ), self::$c[$lca]['expire'] );
				$this->p->debug->log( $cache_type.': plugin data saved to transient '.$cache_id.' ('.self::$c[$lca]['expire'].' seconds)');
			}
			return $plugin_data;
		}
	
		public function get_installed_version( $lca ) {
			$version = 0;
			if ( isset( self::$c[$lca]['base'] ) ) {
				$base = self::$c[$lca]['base'];
				if ( ! function_exists( 'get_plugins' ) ) 
					require_once( ABSPATH.'/wp-admin/includes/plugin.php' );
				$plugins = get_plugins();
				if ( array_key_exists( $base, $plugins ) && 
					array_key_exists( 'Version', $plugins[$base] ) )
						$version = $plugins[$base]['Version'];
			}
			return apply_filters( $lca.'_installed_version', $version );
		}
	}
}
	
if ( ! class_exists( 'SucomPluginData' ) ) {

	class SucomPluginData {
	
		public $id = 0;
		public $name;
		public $slug;
		public $version;
		public $homepage;
		public $sections;
		public $download_url;
		public $author;
		public $author_homepage;
		public $requires;
		public $tested;
		public $upgrade_notice;
		public $rating;
		public $num_ratings;
		public $downloaded;
		public $last_updated;
	
		public static function from_json( $json ) {
			$json_data = json_decode( $json );
			if ( empty( $json_data ) || 
				! is_object( $json_data ) ) 
					return null;
			if ( isset( $json_data->name ) && 
				! empty( $json_data->name ) && 
				isset( $json_data->version ) && 
				! empty( $json_data->version ) ) {

				$plugin_data = new SucomPluginData();
				foreach( get_object_vars( $json_data ) as $key => $value) {
					$plugin_data->$key = $value;
				}
				return $plugin_data;
			} else return null;
		}
	
		public function json_to_wp(){
			$fields = array(
				'name', 
				'slug', 
				'version', 
				'requires', 
				'tested', 
				'rating', 
				'upgrade_notice',
				'num_ratings', 
				'downloaded', 
				'homepage', 
				'last_updated',
				'download_url',
				'author_homepage');
			$data = new StdClass;
			foreach ( $fields as $field ) {
				if ( isset( $this->$field ) ) {
					if ($field == 'download_url') {
						$data->download_link = $this->download_url; }
					elseif ($field == 'author_homepage') {
						$data->author = sprintf('<a href="%s">%s</a>', $this->author_homepage, $this->author); }
					else { $data->$field = $this->$field; }
				} elseif ( $field == 'author_homepage' )
					$data->author = $this->author;
			}
			if ( is_array( $this->sections ) ) 
				$data->sections = $this->sections;
			elseif ( is_object( $this->sections ) ) 
				$data->sections = get_object_vars( $this->sections );
			else $data->sections = array( 'description' => '' );
			return $data;
		}
	}
}
	
if ( ! class_exists( 'SucomPluginUpdate' ) ) {

	class SucomPluginUpdate {
	
		public $id = 0;
		public $slug;
		public $version = 0;
		public $homepage;
		public $download_url;
		public $upgrade_notice;
	
		public function from_json( $json ) {
			$plugin_data = SucomPluginData::from_json( $json );
			if ( $plugin_data !== null ) 
				return self::from_plugin_data( $plugin_data );
			else return null;
		}
	
		public static function from_plugin_data( $data ){
			$plugin_update = new SucomPluginUpdate();
			$fields = array(
				'id', 
				'slug', 
				'qty_used', 
				'version', 
				'homepage', 
				'download_url', 
				'upgrade_notice'
			);
			foreach( $fields as $field )
				$plugin_update->$field = $data->$field;
			return $plugin_update;
		}
	
		public function json_to_wp() {
			$data = new StdClass;
			$fields = array(
				'id' => 'id',
				'slug' => 'slug',
				'qty_used' => 'qty_used',
				'new_version' => 'version',
				'url' => 'homepage',
				'package' => 'download_url',
				'upgrade_notice' => 'upgrade_notice'
			);
			foreach ( $fields as $new_field => $old_field ) {
				if ( isset( $this->$old_field ) )
					$data->$new_field = $this->$old_field;
			}
			return $data;
		}
	}
}

?>
