<?php die( 'These aren\'t the droids you\'re looking for.' );

/**
 * Extract of translatable strings from html/setup.html.
 */
_x( 'Business Social Accounts (Optional)', 'html header', 'wpsso' );
_x( 'Author Social Accounts (Optional)', 'html header', 'wpsso' );
_x( 'Review Essential Settings', 'html header', 'wpsso' );
_x( 'Keep an Eye on Notifications', 'html header', 'wpsso' );
_x( 'Schema Markup vs Google Rich Results', 'html header', 'wpsso' );
_x( 'Validation Tools', 'html header', 'wpsso' );
_x( 'User Interface and General Usage', 'html header', 'wpsso' );
_x( 'Documentation and Resources', 'html header', 'wpsso' );
_x( 'BuddyPress Integration', 'html header', 'wpsso' );
_x( 'WooCommerce Integration', 'html header', 'wpsso' );
_x( 'Taking the time to read this guide and review the <em><strong>SSO &gt; Essential Settings</strong></em> page will help improve your click-through rates and rankings in Google search results. If you use the WooCommerce plugin, don\'t forget to check the <a href="#documentation-and-resources">Documentation and Resources section below</a> for additional integration notes specific to WooCommerce.', 'html paragraph', 'wpsso' );
_x( 'If you haven\'t already done so, create a Facebook Page and Twitter account for your business. You can enter all your business social account information in the <em><strong>SSO &gt; Social Pages</strong></em> settings page. The social account URLs are used for meta tags and Schema Organization markup (which may appear in Google Search results to highlight your business social pages).', 'html paragraph', 'wpsso' );
_x( '<strong>Related information:</strong>', 'html paragraph', 'wpsso' );
_x( 'Ask your content authors to enter their Facebook and Twitter contact information in their WordPress user profile page. Each author\'s contact information can appear in various meta tags and Schema markup for Facebook, Twitter, and Google, <em>but only if they complete their user profile</em>.', 'html paragraph', 'wpsso' );
_x( 'Review the website description on the <em><strong>SSO &gt; Essential Settings</strong></em> page and select a default image ID or URL. The default image is used for archive pages, and as a fallback for posts and pages that do not have a suitable custom image, featured image, attached image, or an image available in their content.', 'html paragraph', 'wpsso' );
_x( 'WPSSO Core attempts to keep notifications to a minimum, issuing only informational, warning, or error notifications when required. Messages from the default WordPress notication system can feel intrusive and over-used, and are not compatible with the new block editor in WordPress v5, so WPSSO Core includes its own (more discreet) notification system in the admin toolbar.', 'html paragraph', 'wpsso' );
_x( 'Look for the SSO notification icon in the admin toolbar - it will be grey with a 0 notification count by default. If there are notifications, the count will increase and the SSO icon be shown on a red, yellow, or blue background. WPSSO notifications are context sensitive - they relate directly to the content shown in the current webpage.', 'html paragraph', 'wpsso' );
_x( 'Schema (aka "Schema.org") is a collaborative, community lead standard for structured data markup. Schema markup is classified by type, and each type is associated with a set of properties. The types are arranged in a hierarchy and <a href="https://schema.org/docs/schemas.html">the Schema vocabulary consists of over 700 types and 1400 properties</a>. The Schema vocabulary can be used with many different encodings, including HTML meta tags, RDFa, Microdata, and JSON-LD. WPSSO Core adds Schema markup to webpages using the latest (and preferred) JSON-LD encoding format for Google and other search engines.', 'html paragraph', 'wpsso' );
_x( '<a href="https://developers.google.com/search/docs/guides/intro-structured-data">Google Rich Results are a set of requirements for a small selection of Schema types</a>, which includes specific images dimensions, a limited set of values for some Schema properties, and limited Schema types for some property relations. Google Rich Results require several layers of related markup (aka a multi-dimensional arrays) that must be expressed using JSON-LD in the webpage head section (preferred by Google), or with RDFa / Microdata markup in theme templates (deprecated standard).', 'html paragraph', 'wpsso' );
_x( 'If your theme templates include incomplete or incorrect RDFa / Microdata markup (a common problem), you should enable the <a href="https://wordpress.org/plugins/wpsso-strip-schema-microdata/">WPSSO Strip Schema Microdata add-on</a> to remove the incomplete or incorrect Microdata markup. Schema markup provided by WPSSO Core will include a \'#sso/\' value in the Schema "@id" property, making it easy to tell which Schema JSON-LD markup is provided by WPSSO Core, and which is not.', 'html paragraph', 'wpsso' );
_x( 'Submit the home page URL, along with a post, page, and archive page URL to the <a href="https://validator.w3.org/">W3C Markup Validator</a> to verify the HTML of your theme templates. Social and search engine crawlers expect properly formatted HTML that conforms to current HTML / XHTML standards. If your webpages contain serious HTML markup errors, social and search crawlers may be unable to read your meta tags and Schema markup. You should report any template HTML markup errors to your theme author.', 'html paragraph', 'wpsso' );
_x( 'Submit a few post and page URLs to the <a href="https://developers.facebook.com/tools/debug/">Facebook Sharing Debugger</a> to verify your Open Graph meta tags. A link to the Facebook debugger is available under the Validate tab in the Document SSO metabox (on post, term, and user editing pages). The Facebook debugger can also be used to clear Facebook\'s cache (after clicking the "Fetch new scrape information" button). If you\'re validating older posts / pages, pre-dating the activation of WPSSO Core, note that <a href="https://wpsso.com/docs/plugins/wpsso/faqs/why-does-facebook-show-the-wrong-image-text/">Facebook may continue to use the old meta tag values from its cache</a>, even after fetching the new scrape information.', 'html paragraph', 'wpsso' );
_x( 'Submit an example post and/or page to the <a href="http://developers.pinterest.com/rich_pins/validator/">Pinterest Rich Pins Validator</a>. Note that the Pinterest validator recognizes only known Rich Pin types (article, recipe, product, etc.) - your home page and WordPress archive pages are generally not valid Rich Pin types. If you haven\'t already requested Rich Pin approval from Pinterest for your website, you can submit a request when validating your first example post or page.', 'html paragraph', 'wpsso' );
_x( 'Submit the home page URL, along with a post, page, and archive page URL to the <a href="https://search.google.com/test/rich-results">Google Rich Results Test Tool</a> or the <a href="https://validator.schema.org/">Schema Markup Validator</a>. If any information is missing from the Schema markup, use the Document SSO metabox in the WordPress editing page to complete the missing information.', 'html paragraph', 'wpsso' );
_x( '<strong>Markup Validators:</strong>', 'html paragraph', 'wpsso' );
_x( 'On most WordPress admin pages you\'ll find a "Screen Options" drop-down tab on the upper right-hand side of the page. You can use these screen options to include / exclude specific metaboxes and columns from the current page. For example, when viewing the posts or pages list you can use the "Screen Options" drop-down to hide / view the "Schema" and "SSO Image" columns. You can also enable / disable these columns globally under the <em><strong>SSO &gt; Advanced Settings &gt; Interface</strong></em> tab.', 'html paragraph', 'wpsso' );
_x( 'When editing a post, page, category, tag, user profile, etc., you\'ll find a Document SSO metabox below the content area where you can customize default texts, images, and videos. The Document SSO metabox shows a different set of options based on the content type selected (ie. Schema type or Open Graph type), allowing you to customize the details of articles, events, e-Commerce products, recipes, reviews, and more. The "Preview Social" tab shows how this webpage might look when shared on Facebook, the "Markup Preview" tab shows a complete list of meta tags and Schema markup created by the WPSSO Core plugin, and the "Markup Validators" tab allows you to submit the current webpage URL to several test and validation tools (mentioned above).', 'html paragraph', 'wpsso' );
_x( 'BuddyPress was not originally created as a WordPress plugin, and consequently BuddyPress is not well integrated with WordPress features and functions. There are specific <a href="https://wpsso.com/docs/plugins/wpsso/installation/integration/buddypress-integration/">BuddyPress Integration Notes</a> available to help you with some known BuddyPress integration issues.', 'html paragraph', 'wpsso' );
_x( 'The WooCommerce plugin alone does not provide sufficient Schema markup for Google Rich Results. The WPSSO Core Premium edition reads WooCommerce product data and provides complete Schema Product JSON-LD markup for Google Rich Results, including product image galleries, product variations, product information (brand, color, condition, EAN, dimensions, GTIN-8/12/13/14, ISBN, material, MPN, pattern, size, SKU, volume, weight, etc), product reviews, product ratings, sale start / end dates, sale prices, pre-tax prices, VAT prices, shipping rates, shipping times, and much, much more.', 'html paragraph', 'wpsso' );
_x( 'The <a href="https://wpsso.com/docs/plugins/wpsso/installation/integration/woocommerce-integration/">WooCommerce Integration Notes</a> are very useful if you would like to include additional information in your meta tags and Schema markup, like the product brand, color, condition, material, pattern, size, etc.', 'html paragraph', 'wpsso' );
_x( '<a href="https://www.facebook.com/business/learn/set-up-facebook-page">Setup a Facebook Business Page</a>', 'html list item', 'wpsso' );
_x( '<a href="https://business.twitter.com/en/basics/create-a-twitter-business-profile.html">Create a Twitter Business Profile</a>', 'html list item', 'wpsso' );
_x( '<a href="https://business.google.com/create">Add or claim your business on Google My Business</a> (recommended)', 'html list item', 'wpsso' );
_x( '<a href="https://www.pinterest.com/business/create/">Create a Pinterest Business Account</a>', 'html list item', 'wpsso' );
_x( '<a href="https://developers.facebook.com/tools/debug/">Facebook Sharing Debugger</a>', 'html list item', 'wpsso' );
_x( '<a href="https://business.facebook.com/ads/microdata/debug">Facebook Microdata Debug Tool</a>', 'html list item', 'wpsso' );
_x( '<a href="https://search.google.com/test/rich-results">Google Rich Results Test</a>', 'html list item', 'wpsso' );
_x( '<a href="https://www.linkedin.com/post-inspector/inspect/">LinkedIn Post Inspector</a>', 'html list item', 'wpsso' );
_x( '<a href="https://developers.pinterest.com/tools/url-debugger/">Pinterest Rich Pins Validator</a>', 'html list item', 'wpsso' );
_x( '<a href="https://cards-dev.twitter.com/validator">Twitter Card Validator</a>', 'html list item', 'wpsso' );
_x( '<a href="https://validator.schema.org/">Schema Markup Validator</a>', 'html list item', 'wpsso' );
_x( '<a href="https://validator.w3.org/">W3C Markup Validator</a> - recommended to check your theme templates for HTML markup issues.', 'html list item', 'wpsso' );
_x( '<a href="https://developers.google.com/speed/pagespeed/insights/">Google PageSpeed Insights</a> - recommended for general site and webpage health checks (results influence SEO ranking).', 'html list item', 'wpsso' );
_x( '<a href="https://support.google.com/webmasters/answer/7451184">Google\'s Search Engine Optimization (SEO) Starter Guide</a> - essential reading to understand Google SEO ranking factors.', 'html list item', 'wpsso' );
_x( '<a href="https://www.webpagetest.org/">Webpage Performance Test with Waterfall</a> - recommended to check and diagnose basic performance issues.', 'html list item', 'wpsso' );
_x( '<a href="https://www.ssllabs.com/ssltest/">SSL Server Test by SSL Labs</a> - recommended test for any site using https.', 'html list item', 'wpsso' );
