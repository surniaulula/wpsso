<?php

if ( ! defined( 'ABSPATH' ) ) {
        die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! defined( 'SUEXT_README_MARKDOWN' ) ) {
	define( 'SUEXT_README_MARKDOWN', dirname(__FILE__).'/markdown.php' );
}

if ( ! class_exists( 'SuextParseReadme' ) ) {

class SuextParseReadme {

	function __construct( &$debug = false ) {

		if ( ! empty( $this->debug->enabled ) )
			$this->debug->mark();
	}

	function parse_readme( $file ) {
		$file_contents = @implode('', @file($file));
		return $this->parse_readme_contents( $file_contents );
	}

	function parse_readme_contents( $file_contents ) {

		$file_contents = str_replace( array("\r\n", "\r"), "\n", $file_contents );
		$file_contents = trim( $file_contents );

		if ( 0 === strpos( $file_contents, "\xEF\xBB\xBF" ) ) {
			$file_contents = substr( $file_contents, 3 );
		}

		if ( ! preg_match('|^===(.*)===|', $file_contents, $_title ) ) {
			return array(); // require a title
		}

		$title = trim( $_title[1], '=' );
		$title = $this->sanitize_text( $title );
		$file_contents = $this->chop_string( $file_contents, $_title[0] );

		if ( preg_match( '|Plugin Name: *(.*)|i', $file_contents, $_plugin_name ) )
			$plugin_name = $this->sanitize_text( $_plugin_name[1] );
		else $plugin_name = null;

		if ( preg_match( '|Plugin Slug: *(.*)|i', $file_contents, $_plugin_slug ) )
			$plugin_slug = $this->sanitize_text( $_plugin_slug[1] );
		else $plugin_slug = null;

		if ( preg_match( '|License: *(.*)|i', $file_contents, $_license ) )
			$license = $this->sanitize_text( $_license[1] );
		else $license = null;

		if ( preg_match( '|License URI: *(.*)|i', $file_contents, $_license_uri ) )
			$license_uri = esc_url( $_license_uri[1] );
		else $license_uri = null;

		if ( preg_match( '|Donate Link: *(.*)|i', $file_contents, $_donate_link ) )
			$donate_link = esc_url( $_donate_link[1] );
		else $donate_link = null;

		if ( preg_match( '|Assets URI: *(.*)|i', $file_contents, $_assets_uri ) )
			$assets_uri = esc_url( $_assets_uri[1] );
		else $assets_uri = null;

		if ( preg_match( '|Tags: *(.*)|i', $file_contents, $_tags ) ) {
			$tags = preg_split( '|,[\s]*?|', trim( $_tags[1] ) );
			foreach ( array_keys($tags) as $t ) {
				$tags[$t] = $this->sanitize_text( $tags[$t] );
			}
		} else $tags = array();

		$contributors = array();
		if ( preg_match( '|Contributors: *(.*)|i', $file_contents, $_contributors ) ) {
			$all_contributors = preg_split( '|,[\s]*|', trim( $_contributors[1] ) );
			foreach ( array_keys( $all_contributors ) as $c ) {
				$c_sanitized = trim( $this->user_sanitize( $all_contributors[$c] ) );
				if ( strlen( $c_sanitized ) > 0 ) {
					$contributors[$c] = $c_sanitized;
				}
				unset( $c_sanitized );
			}
		}

		if ( preg_match( '|Requires At Least: *(.*)|i', $file_contents, $_requires_at_least ) )
			$requires_at_least = $this->sanitize_text( $_requires_at_least[1] );
		else $requires_at_least = null;

		if ( preg_match( '|Tested Up To: *(.*)|i', $file_contents, $_tested_up_to ) )
			$tested_up_to = $this->sanitize_text( $_tested_up_to[1] );
		else $tested_up_to = null;

		if ( preg_match( '|Stable Tag: *(.*)|i', $file_contents, $_stable_tag ) )
			$stable_tag = $this->sanitize_text( $_stable_tag[1] );
		else $stable_tag = null;

		foreach ( array(
			'plugin_name',
			'plugin_slug',
			'license',
			'license_uri',
			'donate_link',
			'assets_uri',
			'tags',
			'contributors',
			'requires_at_least',
			'tested_up_to',
			'stable_tag',
		) as $chop ) {
			if ( $$chop ) {
				$_chop = '_'.$chop;
				$file_contents = $this->chop_string( $file_contents, ${$_chop}[0] );
			}
		}

		$file_contents = trim( $file_contents );

		if ( ! preg_match( '/(^(.*?))^[\s]*=+?[\s]*.+?[\s]*=+?/ms', $file_contents, $_short_description ) ) {
			$_short_description = array( 1 => &$file_contents, 2 => &$file_contents );
		}

		$short_desc_filtered = $this->sanitize_text( $_short_description[2] );
		$short_desc_length = strlen( $short_desc_filtered );
		$short_description = substr( $short_desc_filtered, 0, 150 );

		if ( $short_desc_length > strlen( $short_description ) )
			$truncated = true;
		else $truncated = false;

		if ( $_short_description[1] ) {
			$file_contents = $this->chop_string( $file_contents, $_short_description[1] );
		}

		$_sections = preg_split('/^[\s]*==[\s]*(.+?)[\s]*==/m', $file_contents, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$sections = array();
		for ( $i = 1; $i <= count( $_sections ); $i += 2 ) {
			if ( isset( $_sections[$i] ) ) {
				$_sections[$i] = preg_replace('/^[\s]*=[\s]+(.+?)[\s]+=/m', '<h4>$1</h4>', $_sections[$i]);
				$_sections[$i] = $this->filter_text( $_sections[$i], true );
				$_sections[$i] = preg_replace( '/\[youtube https:\/\/www\.youtube\.com\/watch\?v=([^\]]+)\]/', '<div class="video"><object width="532" height="325"><param name="movie" value="http://www.youtube.com/v/$1?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="never"></param><embed src="http://www.youtube.com/v/$1?fs=1" type="application/x-shockwave-flash" allowscriptaccess="never" allowfullscreen="true" width="532" height="325"></embed></object></div>', $_sections[$i] );
				$section_title = $this->sanitize_text( $_sections[$i-1] );
				$sections[str_replace( ' ', '_', strtolower( $section_title ) )] = array(
					'section_title' => $section_title,
					'section_content' => $_sections[$i]
				);
			}
		}

		$final_sections = array();
		foreach ( array(
			'description' => 'description',
			'installation' => 'installation',
			'frequently_asked_questions' => 'faq',
			'screenshots' => 'screenshots',
			'changelog' => 'changelog',
			'change_log' => 'changelog',
			'upgrade_notice' => 'upgrade_notice',
		) as $section_key => $final_key ) {
			if ( isset( $sections[$section_key] ) ) {
				if ( empty( $final_sections[$final_key] ) ) {
					$final_sections[$final_key] = $sections[$section_key]['section_content'];
				}
				unset( $sections[$section_key] );
			}
		}

		$final_screenshots = array();
		if ( isset( $final_sections['screenshots'] ) ) {
			preg_match_all('|<li>(.*?)</li>|s', $final_sections['screenshots'], $screenshots, PREG_SET_ORDER);
			if ( $screenshots ) {
				foreach ( (array) $screenshots as $ss ) {
					$final_screenshots[] = $ss[1];
				}
			}
		}

		if ( isset( $final_sections['upgrade_notice'] ) ) {
			$upgrade_notice = array();
			$split = preg_split( '#<h4>(.*?)</h4>#', $final_sections['upgrade_notice'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
			for ( $i = 0; $i < count( $split ); $i += 2 ) {
				if ( isset( $split[$i + 1] ) ) {
					$upgrade_notice[$this->sanitize_text( $split[$i] )] = substr( $this->sanitize_text( $split[$i + 1] ), 0, 300 );
				}
			}
			unset( $final_sections['upgrade_notice'] );
		} else {
			$upgrade_notice = '';
		}

		$excerpt = false;
		if ( ! isset ( $final_sections['description'] ) ) {
			$final_sections = array_merge( array( 'description' => $this->filter_text( $_short_description[2], true ) ), $final_sections );
			$excerpt = true;
		}

		$remaining_content = '';
		foreach ( $sections as $s_name => $s_data ) {
			$remaining_content .= "\n<h3>{$s_data['section_title']}</h3>\n{$s_data['section_content']}";
		}
		$remaining_content = trim( $remaining_content );

		$r = array(
			'title' => $title,
			'plugin_name' => $plugin_name,
			'plugin_slug' => $plugin_slug,
			'license' => $license,
			'license_uri' => $license_uri,
			'donate_link' => $donate_link,
			'assets_uri' => $assets_uri,
			'tags' => $tags,
			'contributors' => $contributors,
			'requires_at_least' => $requires_at_least,
			'tested_up_to' => $tested_up_to,
			'stable_tag' => $stable_tag,
			'short_description' => $short_description,
			'screenshots' => $final_screenshots,
			'sections' => $final_sections,
			'remaining_content' => $remaining_content,
			'upgrade_notice' => $upgrade_notice,
			'is_excerpt' => $excerpt,
			'is_truncated' => $truncated,
		);

		return $r;
	}

	function chop_string( $string, $chop ) {
		if ( $_string = strstr( $string, $chop ) ) {
			$_string = substr( $_string, strlen( $chop ) );
			return trim( $_string );
		} else {
			return trim( $string );
		}
	}

	function user_sanitize( $text, $strict = false ) {
		if ( function_exists('user_sanitize') ) // bbPress native
			return user_sanitize( $text, $strict );

		if ( $strict ) {
			$text = preg_replace('/[^a-z0-9-]/i', '', $text);
			$text = preg_replace('|-+|', '-', $text);
		} else {
			$text = preg_replace('/[^a-z0-9_-]/i', '', $text);
		}
		return $text;
	}

	function sanitize_text( $text ) { // not fancy
		$text = strip_tags($text);
		$text = esc_html($text);
		$text = trim($text);
		return $text;
	}

	function filter_text( $text, $markdown = false ) {
		$text = trim( $text );
	        $text = call_user_func( array( __CLASS__, 'code_trick' ), $text, $markdown );
		if ( $markdown ) {
			if ( ! function_exists( 'suext_markdown' ) ) {
				require_once SUEXT_README_MARKDOWN;
			}
			$text = suext_markdown( $text, $this->debug );
		}
		$allowed = array(
			'a' => array(
				'name' => array(),
				'href' => array(),
				'title' => array(),
				'rel' => array(),
			),
			'blockquote' => array(
				'cite' => array(),
			),
			'br' => array(),
			'code' => array(),
			'div' => array(),
			'em' => array(),
			'h3' => array(),
			'h4' => array(),
			'font' => array(
				'color' => array(),
			),
			'img' => array(
				'src' => array(),
				'alt' => array(),
				'width' => array(),
				'height' => array(),
				'style' => array(),
			),
			'li' => array(),
			'ol' => array(),
			'p' => array(),
			'pre' => array(),
			'small' => array(),
			'strong' => array(),
			'table' => array(),
			'tr' => array(),
			'th' => array(),
			'td' => array(
				'valign' => array(),
			),
			'ul' => array(),
		);
		$text = balanceTags( $text );
		//$text = wp_kses( $text, $allowed );
		return $text;
	}

	function code_trick( $text, $markdown ) {

		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );

		if ( ! $markdown ) {
			// this gets the "inline" code blocks, but can't be used with markdown
			$text = preg_replace_callback( "!(`)(.*?)`!", array( __CLASS__, 'encodeit' ), $text );
			// this gets the "block level" code blocks and converts them to pre code
			$text = preg_replace_callback( "!(^|\n)`(.*?)`!s", array( __CLASS__, 'encodeit'), $text );
		} else {
			// markdown can do inline code, we convert bbPress style block level code to markdown style
			$text = preg_replace_callback( "!(^|\n)([ \t]*?)`(.*?)`!s", array( __CLASS__, 'indent' ), $text );
		}

		return $text;
	}

	function indent( $matches ) {
		$text = $matches[3];
		$text = preg_replace( '|^|m', $matches[2]."\t", $text );
		return $matches[1]."\n`".$text."`\n";
	}

	function encodeit( $matches ) {
		if ( function_exists( 'encodeit' ) ) // bbPress native
			return encodeit( $matches );

		$text = trim( $matches[2] );
		$text = htmlspecialchars( $text, ENT_QUOTES );
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$text = preg_replace( "|\n\n\n+|", "\n\n", $text );
		$text = str_replace( '&amp;lt;', '&lt;', $text );
		$text = str_replace('&amp;gt;', '&gt;', $text);
		$text = "<code>$text</code>";

		if ( "`" != $matches[1] )
			$text = "<pre>$text</pre>";

		return $text;
	}

	function decodeit( $matches ) {
		if ( function_exists('decodeit') ) // bbPress native
			return decodeit( $matches );

		$text = $matches[2];
		$trans_table = array_flip( get_html_translation_table( HTML_ENTITIES ) );
		$text = strtr( $text, $trans_table );
		$text = str_replace( '<br />', '', $text );
		$text = str_replace( '&#38;', '&', $text );
		$text = str_replace( '&#39;', "'", $text );

		if ( '<pre><code>' == $matches[1] )
			$text = "\n$text\n";

		return "`$text`";
	}

} // end class

}

?>
