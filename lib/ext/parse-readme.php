<?php

if ( ! defined( 'ABSPATH' ) )
        die( 'These aren\'t the droids you\'re looking for...' );

if ( ! defined( 'SUEXT_README_MARKDOWN' ) )
	define( 'SUEXT_README_MARKDOWN', dirname(__FILE__).'/markdown.php' );

if ( ! class_exists( 'SuextParseReadme' ) ) {

class SuextParseReadme {

	private $debug;

	function __construct( &$debug = '' ) {

		// check for logging object with mark() method
		$this->debug = method_exists( $debug, 'mark' ) ? $debug : $this;
		$this->debug->mark();
	}

	private function mark() { return; }

	function parse_readme( $file ) {
		$file_contents = @implode('', @file($file));
		return $this->parse_readme_contents( $file_contents );
	}

	function parse_readme_contents( $file_contents ) {
		$file_contents = str_replace(array("\r\n", "\r"), "\n", $file_contents);
		$file_contents = trim($file_contents);
		if ( 0 === strpos( $file_contents, "\xEF\xBB\xBF" ) )
			$file_contents = substr( $file_contents, 3 );

		if ( !preg_match('|^===(.*)===|', $file_contents, $_name) )
			return array(); // require a name

		$name = trim($_name[1], '=');
		$name = $this->sanitize_text( $name );
		$file_contents = $this->chop_string( $file_contents, $_name[0] );

		if ( preg_match('|Requires at least:(.*)|i', $file_contents, $_requires_at_least) )
			$requires_at_least = $this->sanitize_text($_requires_at_least[1]);
		else $requires_at_least = NULL;

		if ( preg_match('|Tested up to:(.*)|i', $file_contents, $_tested_up_to) )
			$tested_up_to = $this->sanitize_text( $_tested_up_to[1] );
		else $tested_up_to = NULL;

		if ( preg_match('|Stable tag:(.*)|i', $file_contents, $_stable_tag) )
			$stable_tag = $this->sanitize_text( $_stable_tag[1] );
		else $stable_tag = NULL;

		if ( preg_match('|Tags:(.*)|i', $file_contents, $_tags) ) {
			$tags = preg_split('|,[\s]*?|', trim($_tags[1]));
			foreach ( array_keys($tags) as $t )
				$tags[$t] = $this->sanitize_text( $tags[$t] );
		} else $tags = array();

		$contributors = array();
		if ( preg_match('|Contributors:(.*)|i', $file_contents, $_contributors) ) {
			$temp_contributors = preg_split('|,[\s]*|', trim($_contributors[1]));
			foreach ( array_keys($temp_contributors) as $c ) {
				$tmp_sanitized = $this->user_sanitize( $temp_contributors[$c] );
				if ( strlen(trim($tmp_sanitized)) > 0 )
					$contributors[$c] = $tmp_sanitized;
				unset($tmp_sanitized);
			}
		}

		if ( preg_match('|Donate link:(.*)|i', $file_contents, $_donate_link) )
			$donate_link = esc_url( $_donate_link[1] );
		else $donate_link = NULL;

		if ( preg_match('|License:(.*)|i', $file_contents, $_license) )
			$license = $this->sanitize_text( $_license[1] );
		else $license = NULL;

		if ( preg_match('|License URI:(.*)|i', $file_contents, $_license_uri) )
			$license_uri = esc_url( $_license_uri[1] );
		else $license_uri = NULL;

		foreach ( array('tags', 'contributors', 'requires_at_least', 'tested_up_to', 'stable_tag', 'donate_link', 'license', 'license_uri') as $chop ) {
			if ( $$chop ) {
				$_chop = '_'.$chop;
				$file_contents = $this->chop_string( $file_contents, ${$_chop}[0] );
			}
		}

		$file_contents = trim( $file_contents );

		if ( ! preg_match( '/(^(.*?))^[\s]*=+?[\s]*.+?[\s]*=+?/ms', $file_contents, $_short_description ) )
			$_short_description = array( 1 => &$file_contents, 2 => &$file_contents );
		$short_desc_filtered = $this->sanitize_text( $_short_description[2] );
		$short_desc_length = strlen($short_desc_filtered);
		$short_description = substr($short_desc_filtered, 0, 150);
		if ( $short_desc_length > strlen($short_description) )
			$truncated = true;
		else
			$truncated = false;
		if ( $_short_description[1] )
			$file_contents = $this->chop_string( $file_contents, $_short_description[1] );

		$_sections = preg_split('/^[\s]*==[\s]*(.+?)[\s]*==/m', $file_contents, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$sections = array();
		for ( $i = 1; $i <= count( $_sections ); $i += 2 ) {
			if ( isset( $_sections[$i] ) ) {
				$_sections[$i] = preg_replace('/^[\s]*=[\s]+(.+?)[\s]+=/m', '<h4>$1</h4>', $_sections[$i]);
				$_sections[$i] = $this->filter_text( $_sections[$i], true );
				$_sections[$i] = preg_replace( '/\[youtube https:\/\/www\.youtube\.com\/watch\?v=([^\]]+)\]/', '<div class="video"><object width="532" height="325"><param name="movie" value="http://www.youtube.com/v/$1?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="never"></param><embed src="http://www.youtube.com/v/$1?fs=1" type="application/x-shockwave-flash" allowscriptaccess="never" allowfullscreen="true" width="532" height="325"></embed></object></div>', $_sections[$i] );
				$title = $this->sanitize_text( $_sections[$i-1] );
				$sections[str_replace(' ', '_', strtolower($title))] = array('title' => $title, 'content' => $_sections[$i]);
			}
		}

		$final_sections = array();
		foreach ( array('description', 'installation', 'frequently_asked_questions', 'screenshots', 'changelog', 'change_log', 'upgrade_notice') as $special_section ) {
			if ( isset($sections[$special_section]) ) {
				$final_sections[$special_section] = $sections[$special_section]['content'];
				unset($sections[$special_section]);
			}
		}
		if ( isset($final_sections['change_log']) && empty($final_sections['changelog']) )
			$final_sections['changelog'] = $final_sections['change_log'];

		$final_screenshots = array();
		if ( isset($final_sections['screenshots']) ) {
			preg_match_all('|<li>(.*?)</li>|s', $final_sections['screenshots'], $screenshots, PREG_SET_ORDER);
			if ( $screenshots ) {
				foreach ( (array) $screenshots as $ss )
					$final_screenshots[] = $ss[1];
			}
		}

		if ( isset($final_sections['upgrade_notice']) ) {
			$upgrade_notice = array();
			$split = preg_split( '#<h4>(.*?)</h4>#', $final_sections['upgrade_notice'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
			for ( $i = 0; $i < count( $split ); $i += 2 )
				if ( isset( $split[$i + 1] ) )
					$upgrade_notice[$this->sanitize_text( $split[$i] )] = substr( $this->sanitize_text( $split[$i + 1] ), 0, 300 );
			unset( $final_sections['upgrade_notice'] );
		} else { $upgrade_notice = ''; }

		$excerpt = false;
		if ( !isset($final_sections['description']) ) {
			$final_sections = array_merge(array('description' => $this->filter_text( $_short_description[2], true )), $final_sections);
			$excerpt = true;
		}

		$remaining_content = '';
		foreach ( $sections as $s_name => $s_data ) {
			$remaining_content .= "\n<h3>{$s_data['title']}</h3>\n{$s_data['content']}";
		}
		$remaining_content = trim($remaining_content);

		$r = array(
			'name' => $name,
			'tags' => $tags,
			'requires_at_least' => $requires_at_least,
			'tested_up_to' => $tested_up_to,
			'stable_tag' => $stable_tag,
			'contributors' => $contributors,
			'donate_link' => $donate_link,
			'license' => $license,
			'license_uri' => $license_uri,
			'short_description' => $short_description,
			'screenshots' => $final_screenshots,
			'is_excerpt' => $excerpt,
			'is_truncated' => $truncated,
			'sections' => $final_sections,
			'remaining_content' => $remaining_content,
			'upgrade_notice' => $upgrade_notice
		);

		return $r;
	}

	function chop_string( $string, $chop ) {
		if ( $_string = strstr($string, $chop) ) {
			$_string = substr($_string, strlen($chop));
			return trim($_string);
		} else {
			return trim($string);
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
		$text = trim($text);
	        $text = call_user_func( array( __CLASS__, 'code_trick' ), $text, $markdown );
		if ( $markdown ) {
			if ( ! function_exists( 'suext_markdown' ) )
				require_once( SUEXT_README_MARKDOWN );
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
			'p' => array(),
			'code' => array(),
			'pre' => array(),
			'em' => array(),
			'small' => array(),
			'strong' => array(),
			'ul' => array(),
			'ol' => array(),
			'li' => array(),
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
			'table' => array(),
			'tr' => array(),
			'th' => array(),
			'td' => array(
				'valign' => array(),
			),
		);
		$text = balanceTags( $text );
		//$text = wp_kses( $text, $allowed );
		return $text;
	}

	function code_trick( $text, $markdown ) {
		if ( $markdown )
			$text = preg_replace_callback("!(<pre><code>|<code>)(.*?)(</code></pre>|</code>)!s", array( __CLASS__,'decodeit'), $text);

		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		if ( !$markdown ) {
			// this gets the "inline" code blocks, but can't be used with markdown
			$text = preg_replace_callback("|(`)(.*?)`|", array( __CLASS__, 'encodeit'), $text);
			// this gets the "block level" code blocks and converts them to pre code
			$text = preg_replace_callback("!(^|\n)`(.*?)`!s", array( __CLASS__, 'encodeit'), $text);
		} else {
			// markdown can do inline code, we convert bbPress style block level code to markdown style
			$text = preg_replace_callback("!(^|\n)([ \t]*?)`(.*?)`!s", array( __CLASS__, 'indent'), $text);
		}
		return $text;
	}

	function indent( $matches ) {
		$text = $matches[3];
		$text = preg_replace('|^|m', $matches[2] . '    ', $text);
		return $matches[1] . $text;
	}

	function encodeit( $matches ) {
		if ( function_exists('encodeit') ) // bbPress native
			return encodeit( $matches );

		$text = trim($matches[2]);
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		$text = preg_replace("|\n\n\n+|", "\n\n", $text);
		$text = str_replace('&amp;lt;', '&lt;', $text);
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
		$trans_table = array_flip(get_html_translation_table(HTML_ENTITIES));
		$text = strtr($text, $trans_table);
		$text = str_replace('<br />', '', $text);
		$text = str_replace('&#38;', '&', $text);
		$text = str_replace('&#39;', "'", $text);
		if ( '<pre><code>' == $matches[1] )
			$text = "\n$text\n";
		return "`$text`";
	}

} // end class

}

?>
