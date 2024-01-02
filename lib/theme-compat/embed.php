<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 *
 * Original source from wordpress/wp-includes/theme-compat/embed.php.
 */

get_header( 'embed' );

if ( have_posts() ) {

	while ( have_posts() ) {

		the_post();

		get_template_part( 'wpsso/embed', 'content' );
	}

} else {

	get_template_part( 'embed', '404' );
}

get_footer( 'embed' );
