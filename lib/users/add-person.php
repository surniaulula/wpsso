<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoUsersAddPerson' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoUsersAddPerson extends WpssoAdmin {

		private $errors     = array();
		private $messages   = array();
		private $add_errors = array();

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

		/**
		 * Called by WpssoAdmin->load_setting_page() after the 'wpsso-action' query is handled.
		 *
		 * Add settings page filter and action hooks.
		 */
		protected function add_plugin_hooks() {

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.
	
				wp_die( 
					'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>',
					403
				);
			}

			if ( isset( $_REQUEST[ 'action' ] ) && 'createuser' === $_REQUEST[ 'action' ] ) {

				check_admin_referer( 'create-user', '_wpnonce_create-user' );
	
				$user_id = $this->add_person();

				if ( is_wp_error( $user_id ) ) {

					$this->add_errors = $user_id;

				} else {

					$redirect = add_query_arg( 'update', 'add' );

					wp_redirect( $redirect );

					die();
				}
			}

			if ( ! empty( $_GET[ 'update' ] ) ) {

				if ( 'add' === $_GET[ 'update' ] ) {

					$this->messages[] = __( 'Person added.', 'wpsso' );
				}
			}

			wp_enqueue_script( 'user-profile' );
		}

		protected function show_post_body_setting_form() {

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.
	
				wp_die( 
					'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>',
					403
				);
			}

			$this->show_notices();

			$contact_methods = wp_get_user_contact_methods();
			$editable_roles  = array( 'none' => array( 'name' => _x( '[None]', 'option value', 'wpsso' ) ) );
			$editable_roles  += array_reverse( get_editable_roles() );

			unset( $editable_roles[ 'person' ] );

			$attr = array();

			$have_submit = isset( $_POST[ 'createuser' ] );

			foreach ( array_merge( array(
				'user_login',	// Username.
				'first_name',	// First name.
				'last_name',	// Last name.
				'role',		// Additional role.
				'email',	// Email.
				'url',		// Website.
				'description',	// Biographical info.
			), array_keys( $contact_methods ) ) as $input ) {

				$attr[ $input ] = $have_submit && isset( $_POST[ $input ] ) ? wp_unslash( $_POST[ $input ] ) : '';

				$attr[ $input ] = 'description' === $input ? esc_textarea( $attr[ $input ] ) : esc_attr( $attr[ $input ] );
			}

			if ( empty( $attr[ 'role' ] ) ) {

				$attr[ 'role' ] = get_option( 'default_role' );
			}

			?>

			<div id="add-person-content">

			<form method="post" name="createuser" id="createuser" class="validate" novalidate="novalidate">

			<?php wp_nonce_field( 'create-user', '_wpnonce_create-user' ); ?>

			<input type="hidden" name="action" value="createuser" />
			<input type="hidden" name="send_user_notification" value="0" />
			<input type="hidden" name="pass1" value="" />
			<input type="hidden" name="pass2" value="" />
			<input type="hidden" name="send_user_notification" value="0" />

			<table class="form-table" role="presentation">
			
				<tr class="form-field form-required">

					<th scope="row"><label for="user_login"><?php _e( 'Username' ); ?>
						<span class="description"><?php _e( '(required)' ); ?></span></label></th>

					<td>
						<input name="user_login" type="text" id="user_login" value="<?php echo $attr[ 'user_login' ]; ?>"
							aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" />
						<p><span class="description"><?php _e( 'Usernames cannot be changed (without a plugin).',
							'wpsso' ); ?></span></p>
					</td>
					
				</tr>

				<tr class="form-field form-required">
				
					<th scope="row"><label for="first_name"><?php _e( 'First Name' ); ?>
						<span class="description"><?php _e( '(required)' ); ?></span></label></th>
					
					<td><input name="first_name" type="text" id="first_name" value="<?php echo $attr[ 'first_name' ]; ?>" /></td>

				</tr>

				<tr class="form-field form-required">
				
					<th scope="row"><label for="last_name"><?php _e( 'Last Name' ); ?>
						<span class="description"><?php _e( '(required)' ); ?></span></label></th>
					
					<td><input name="last_name" type="text" id="last_name" value="<?php echo $attr[ 'last_name' ]; ?>" /></td>

				</tr>
				
				<tr class="form-field">
				
					<th scope="row"><label for="role"><?php _e( 'Additional Role' ); ?></label></th>
					
					<td><select name="role" id="role"><?php 

						foreach ( $editable_roles as $role => $details ) {

							$role = esc_attr( $role );

							$name = translate_user_role( $details[ 'name' ] );

							echo '<option';

							if ( $role === $attr[ 'role' ] ) {
								echo ' selected="selected"';
							}

							echo ' value="' . $role . '">' . $name . '</option>"';
						}

					?></select></td>

				</tr>

			</table>

			<h2><?php _e( 'Contact Info', 'wpsso' ); ?></h2>

			<table class="form-table" role="presentation">

				<tr class="form-field">

					<th scope="row"><label for="email"><?php _e( 'Email' ); ?></label></th>
					
					<td><input name="email" type="email" id="email" value="<?php echo $attr[ 'email' ]; ?>" /></td>
					
				</tr>
				
				<tr class="form-field">
				
					<th scope="row"><label for="url"><?php _e( 'Website' ); ?></label></th>

					<td><input name="url" type="url" id="url" class="code" value="<?php echo $attr[ 'url' ]; ?>" /></td>

				</tr>

				<?php foreach ( wp_get_user_contact_methods() as $name => $desc ) {
				
					echo '<tr class="user-' . $name . '-wrap">';

					echo '<th><label for="' . $name . '">';
			
					echo apply_filters( 'user_' . $name . '_label', $desc );

					echo '</label></th>';

					echo '<td><input type="text" name="' . $name . '" id="' . $name . '" value="" class="regular-text" /></td></tr>';

				} ?>

			</table>
			
			<h2><?php _e( 'About the person', 'wpsso' ); ?></h2>

			<table class="form-table" role="presentation">

				<tr class="user-description-wrap">
				
					<th><label for="description"><?php _e( 'Biographical Info' ); ?></label></th> 
					
					<td><textarea name="description" id="description" rows="5" cols="30"><?php echo $attr[ 'description' ] ?></textarea></td>
				</tr>

			</table>

			<?php submit_button( __( 'Add Person', 'wpsso' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?> 

			</form>

			</div><!-- #add-person-content --><?php
		}

		private function add_person() {

			$user_id = 0;

			$password = wp_generate_password( 24 );

			return edit_user( $user_id );
		}

		private function show_notices() {

			if ( ! empty( $this->errors ) && is_wp_error( $this->errors ) ) {

				echo '<div class="error">';
				echo '<ul>';

				foreach ( $this->errors->get_error_messages() as $err ) {
				
					echo '<li>' . $err . '</li>';
				}
		
				echo '</ul>';
				echo '</div>';
			}

			if ( ! empty( $this->messages ) ) {

				foreach ( $this->messages as $msg ) {

					echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
				}
			}

			if ( ! empty( $this->add_errors ) && is_wp_error( $this->add_errors ) ) {
			
				foreach ( $this->add_errors->get_error_messages() as $message ) {

					echo '<div class="error">';
					echo '<p>' . $message . '</p>';
					echo '</div>';
				}
			}
		}
	}
}
