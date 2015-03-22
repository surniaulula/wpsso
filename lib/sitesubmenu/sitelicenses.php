<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoSitesubmenuSitelicenses' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSitesubmenuSitelicenses extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function set_form() {
			$def_site_opts = $this->p->opt->get_site_defaults();
			$this->form = new SucomForm( $this->p, WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $def_site_opts );
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook.'_licenses', 'Network Pro Version Licenses', 
				array( &$this, 'show_metabox_licenses' ), $this->pagehook, 'normal' );

			// add a class to set a minimum width for the network postboxes
			add_filter( 'postbox_classes_'.$this->pagehook.'_'.$this->pagehook.'_licenses', 
				array( &$this, 'add_class_postbox_network' ) );
		}

		public function add_class_postbox_network( $classes ) {
			array_push( $classes, 'admin_postbox_network' );
			return $classes;
		}

		public function show_metabox_licenses() {
			echo '<table class="sucom-setting licenses-metabox" style="padding-bottom:10px">'."\n";
			echo '<tr><td colspan="5">'.$this->p->msgs->get( 'info-plugin-tid' ).'</td></tr>'."\n";

			$num = 0;
			$total = count( $this->p->cf['plugin'] );
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				$num++;
				$links = '';
				$img_href = '';

				if ( ! empty( $info['slug'] ) ) {
					$img_href = add_query_arg( array(
						'tab' => 'plugin-information',
						'plugin' => $info['slug'],
						'TB_iframe' => 'true',
						'width' => 600,
						'height' => 550
					), get_admin_url( null, 'plugin-install.php' ) );
					$status = 'View Plugin Details';
					if ( is_dir( WP_PLUGIN_DIR.'/'.$info['slug'] ) ) {
						$update_plugins = get_site_transient('update_plugins');
						if ( isset( $update_plugins->response ) ) {
							foreach ( (array) $update_plugins->response as $file => $plugin ) {
								if ( $plugin->slug === $info['slug'] ) {
									$status = '<strong>View Plugin Details and Update</strong>';
									break;
								}
							}
						}
					} else $status = '<em>View Plugin Details and Install</em>';
					$links .= ' | <a href="'.$img_href.'" class="thickbox">'.$status.'</a>';
				}

				if ( ! empty( $info['url']['download'] ) ) {
					$img_href = $info['url']['download'];
					$links .= ' | <a href="'.$img_href.'" target="_blank">Download Free Version</a>';
				}

				if ( ! empty( $info['url']['purchase'] ) ) {
					$img_href = $info['url']['purchase'];
					if ( $this->p->cf['lca'] === $lca || $this->p->check->aop() )
						$links .= ' | <a href="'.$img_href.'" target="_blank">Purchase Pro License(s)</a>';
					else $links .= ' | <em>Purchase Pro License(s)</em>';
				}

				if ( ! empty( $info['img']['icon-small'] ) )
					$img_icon = $info['img']['icon-small'];
				else $img_icon = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

				// logo image
				echo '<tr><td style="width:148px; padding:10px;" rowspan="3" valign="top" align="left">';
				if ( ! empty( $img_href ) ) 
					echo '<a href="'.$img_href.'"'.( strpos( $img_href, 'TB_iframe' ) ?
						' class="thickbox"' : ' target="_blank"' ).'>';
				echo '<img src="'.$img_icon.'" width="128" height="128">';
				if ( ! empty( $img_href ) ) 
					echo '</a>';
				echo '</td>';

				// plugin name
				echo '<td colspan="4" style="padding:10px 0 0 0;">
					<p><strong>'.$info['name'].'</strong></p>';

				if ( ! empty( $info['desc'] ) )
					echo '<p>'.$info['desc'].'</p>';

				if ( ! empty( $links ) )
					echo '<p>'.trim( $links, ' |' ).'</p>';

				echo '</td></tr>'."\n";

				if ( ! empty( $info['url']['purchase'] ) || 
					! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
					if ( $this->p->cf['lca'] === $lca || $this->p->check->aop() ) {
						echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium' ).'<td class="tid">'.
							$this->form->get_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td>'.
							$this->p->util->th( 'Site Use', 'narrow' ).'<td>'.
							$this->form->get_select( 'plugin_'.$lca.'_tid:use', 
								$this->p->cf['form']['site_option_use'], 'site_use' ).'</td></tr>'."\n";
					} else {
						echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium' ).'<td class="blank">'.
							$this->form->get_no_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td><td>'.
							$this->p->msgs->get( 'pro-option-msg' ).'</td>
								<td>&nbsp;</td><td>&nbsp;</td></tr>'."\n";
					}
				} else echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>'."\n";
				if ( $num < $total )
					echo '<tr><td style="border-bottom:1px dotted #ddd;" colspan="4">&nbsp;</td></tr>'."\n";
				else echo '<tr><td colspan="4">&nbsp;</td></tr>'."\n";
			}
			echo '</table>'."\n";
		}
	}
}

?>
