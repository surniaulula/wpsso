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

		protected function add_plugin_hooks() {

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows'  => 1,
			) );
		}

		protected function show_form_content() {

			if ( ! current_user_can( 'create_users' ) ) {	// Just in case.
				return;
			}

			/**
			 * Add a form to support side metabox open / close functions.
			 */
			$menu_hookname = SucomUtil::sanitize_hookname( $this->menu_id );

			echo '<form name="' . $this->p->lca . '" ' .
				'id="' . $this->p->lca . '_setting_form_' . $menu_hookname . '" ' .
				'action="options.php" method="post">' . "\n";

			settings_fields( $this->p->lca . '_setting' );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			echo '</form>', "\n";

			echo '<div id="add-person-content">' . "\n";

			$contact_methods = wp_get_user_contact_methods();

			$editable_roles = array( 'none' => array( 'name' => _x( '[None]', 'option value', 'wpsso' ) ) );
			
			$editable_roles += array_reverse( get_editable_roles() );

			unset( $editable_roles[ 'person' ] );

			$creating = isset( $_POST[ 'createuser' ] );

			$attr = array();

			foreach ( array_merge( array(
				'user_login',
				'first_name',
				'last_name',
				'email',
				'url',
				'role',
			), array_keys( $contact_methods ) ) as $input ) {

				$attr[ $input ] = $creating && isset( $_POST[ $input ] ) ? esc_attr( wp_unslash( $_POST[ $input ] ) ) : '';
			}

			if ( empty( $attr[ 'role' ] ) ) {

				$attr[ 'role' ] = get_option( 'default_role' );
			}

			?>

			<form method="post" name="createuser" id="createuser" class="validate" novalidate="novalidate">

			<input type="hidden" name="action" value="createuser" />
			<?php wp_nonce_field( 'create-user', '_wpnonce_create-user' ); ?>

			<input type="hidden" name="send_user_notification" value="0" />
			<input type="hidden" name="pass1" value="" />
			<input type="hidden" name="pass2" value="" />
			<input type="hidden" name="send_user_notification" value="0" />

			<table class="form-table" role="presentation">
			
				<tr class="form-field form-required">

					<th scope="row"><label for="user_login"><?php _e( 'Username' ); ?>
						<span class="description"><?php _e( '(required)' ); ?></span></label></th>

					<td><input name="user_login" type="text" id="user_login" value="<?php echo $attr[ 'user_login' ]; ?>"
						aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" /></td>
					
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
			
			<?php submit_button( __( 'Add Person', 'wpsso' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?> 

			</form><?php

			echo '</div><!-- #add-person-content -->' . "\n";
		}
	}
}
