<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2025 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/*
		 * Add settings page filters and actions hooks.
		 *
		 * Called by WpssoAdmin->load_settings_page() after the 'wpsso-action' query is handled.
		 */
		protected function add_settings_page_callbacks() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.

				// translators: Please ignore - translation uses a different text domain.
				wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					// translators: Please ignore - translation uses a different text domain.
					'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>', 403 );
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

		protected function show_post_body_settings_form() {

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.

				// translators: Please ignore - translation uses a different text domain.
				wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					// translators: Please ignore - translation uses a different text domain.
					'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>', 403 );
			}

			$this->show_notices();

			$contact_methods = wp_get_user_contact_methods();

			$editable_roles = array( 'none' => array( 'name' => _x( '[None]', 'option value', 'wpsso' ) ) ) + array_reverse( get_editable_roles() );

			unset( $editable_roles[ 'person' ] );

			$attr = array();

			$have_submit = isset( $_POST[ 'createuser' ] );

			foreach ( array_merge( array(
				'user_login',	// Username.
				'first_name',	// First name.
				'last_name',	// Last name.
				'email',	// Email.
				'url',		// Website.
				'description',	// Biographical info.
			), array_keys( $contact_methods ) ) as $input ) {

				$attr[ $input ] = $have_submit && isset( $_POST[ $input ] ) ? wp_unslash( $_POST[ $input ] ) : '';

				$attr[ $input ] = 'description' === $input ? esc_textarea( $attr[ $input ] ) : esc_attr( $attr[ $input ] );
			}

			?>

			<div id="add-person-content">

			<p><?php _e( 'Create a new person for use in Open Graph meta tags and Schema markup.', 'wpsso' ); ?></p>

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
						<p class="description"><?php _e( 'Usernames cannot be changed (without a plugin).', 'wpsso' ); ?></p>
					</td>

				</tr>

				<tr class="form-field form-required">

					<th scope="row"><label for="first_name"><?php _e( 'First Name' ); ?>
						<span class="description"><?php _e( '(required)' ); ?></span></label></th>

					<td><input name="first_name" type="text" id="first_name" value="<?php echo $attr[ 'first_name' ]; ?>" /></td>

				</tr>

				<tr class="form-field form-required">

					<th scope="row">
						<label for="last_name"><?php _e( 'Last Name' ); ?>
							<span class="description"><?php _e( '(required)' ); ?></span>
						</label>
					</th>

					<td><input name="last_name" type="text" id="last_name" value="<?php echo $attr[ 'last_name' ]; ?>" /></td>

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

				<?php foreach ( $contact_methods as $name => $desc ) {

					echo '<tr class="user-' . $name . '-wrap">';

					echo '<th><label for="' . $name . '">';

					echo apply_filters( 'user_' . $name . '_label', $desc );

					echo '</label></th>';

					echo '<td><input type="text" name="' . $name . '" id="' . $name . '" value="' . $attr[ $name ] . '" class="regular-text" /></td></tr>';

				} ?>

			</table>

			<h2><?php _e( 'About the person', 'wpsso' ); ?></h2>

			<table class="form-table" role="presentation">

				<tr class="user-description-wrap">

					<th><label for="description"><?php _e( 'Biographical Info' ); ?></label></th>

					<td><textarea name="description" id="description" rows="5" cols="30"><?php echo $attr[ 'description' ] ?></textarea></td>
				</tr>

				<?php $this->p->user->show_about_section(); ?>

			</table>

			<?php submit_button( __( 'Add the Person', 'wpsso' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?>

			</form>

			</div><!-- #add-person-content --><?php
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

		private function add_person() {

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.

				// translators: Please ignore - translation uses a different text domain.
				wp_die( '<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					// translators: Please ignore - translation uses a different text domain.
					'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>', 403 );
			}

			$contact_methods = wp_get_user_contact_methods();

			$illegal_logins = apply_filters( 'illegal_user_logins', array() );

			/*
			 * Create a user object.
			 */
			$user = new stdClass;

			$user->user_login = isset( $_POST[ 'user_login' ] ) ?
				$user->user_login = sanitize_user( wp_unslash( $_POST[ 'user_login' ] ), $strict = true ) : '';

			$user->user_pass = wp_generate_password( $length = 24, $special_chars = true, $extra_special_chars = false );

			$user->first_name = isset( $_POST[ 'first_name' ] ) ?
				$user->first_name = sanitize_text_field( $_POST[ 'first_name' ] ) : '';

			$user->last_name = isset( $_POST[ 'last_name' ] ) ?
				$user->last_name = sanitize_text_field( $_POST[ 'last_name' ] ) : '';

			$user->role = 'person';

			$user->user_email = isset( $_POST[ 'email' ] ) ?
				$user->user_email = sanitize_text_field( wp_unslash( $_POST[ 'email' ] ) ) : '';

			if ( empty( $_POST[ 'url' ] ) || 'http://' === $_POST[ 'url' ] ) {

				$user->user_url = '';

			} else {

				$protocols = implode( '|', array_map( 'preg_quote', wp_allowed_protocols() ) );

				$user->user_url = esc_url_raw( $_POST[ 'url' ] );

				$user->user_url = preg_match( '/^(' . $protocols . '):/is', $user->user_url ) ?
					$user->user_url : 'http://' . $user->user_url;
			}

			$user->description = isset( $_POST[ 'description' ] ) ?
				$user->description = trim( $_POST[ 'description' ] ) : '';

			foreach ( $contact_methods as $method => $name ) {

				if ( isset( $_POST[ $method ] ) ) {

					$user->$method = sanitize_text_field( $_POST[ $method ] );
				}
			}

			$errors = new WP_Error();

			/*
			 * Check the user login.
			 */
			if ( empty( $user->user_login ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$errors->add( 'user_login', __( '<strong>Error</strong>: Please enter a username.' ),
					array( 'form-field' => 'user_login' ) );

			} elseif ( isset( $_POST[ 'user_login' ] ) && ! validate_username( $_POST[ 'user_login' ] ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$errors->add( 'user_login', __( '<strong>Error</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ),
					array( 'form-field' => 'user_login' ) );

			} elseif ( username_exists( $user->user_login ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$errors->add( 'user_login', __( '<strong>Error</strong>: This username is already registered. Please choose another one.' ),
					array( 'form-field' => 'user_login' ) );

			} elseif ( in_array( strtolower( $user->user_login ), array_map( 'strtolower', $illegal_logins ), $strict = true ) ) {

				// translators: Please ignore - translation uses a different text domain.
				$errors->add( 'invalid_username', __( '<strong>Error</strong>: Sorry, that username is not allowed.' ),
					array( 'form-field' => 'user_login' ) );
			}

			/*
			 * Check the email address.
			 */
			if ( ! empty( $user->user_email ) ) {

				if ( ! is_email( $user->user_email ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$errors->add( 'invalid_email', __( '<strong>Error</strong>: The email address isn&#8217;t correct.' ),
						array( 'form-field' => 'email' ) );

				} elseif ( email_exists( $user->user_email ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$errors->add( 'email_exists', __( '<strong>Error</strong>: This email is already registered, please choose another one.' ),
						array( 'form-field' => 'email' ) );
				}
			}

			if ( $errors->has_errors() ) {

				return $errors;
			}

			$user_id = wp_insert_user( $user );

			$this->p->user->save_about_section( $user_id );

			return $user_id;
		}
	}
}
