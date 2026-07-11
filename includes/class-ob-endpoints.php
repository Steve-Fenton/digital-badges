<?php
/**
 * Public Open Badges JSON endpoints and human pages.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules and template_redirect handlers for OB + claim UI.
 */
final class Ob_Endpoints {

	/**
	 * Wire endpoint hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
		add_action( 'template_redirect', array( self::class, 'handle_request' ) );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Add body classes on plugin public pages.
	 *
	 * @param list<string> $classes Body classes.
	 * @return list<string>
	 */
	public static function body_class( array $classes ): array {
		$type = get_query_var( 'fendigibadge_ob' );

		if ( ! is_string( $type ) || '' === $type ) {
			return $classes;
		}

		$classes[] = 'fendigibadge';
		$classes[] = 'fendigibadge--' . sanitize_html_class( $type );

		return $classes;
	}

	/**
	 * Register rewrite rules.
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule( '^ob/issuer\.json$', 'index.php?fendigibadge_ob=issuer', 'top' );
		add_rewrite_rule( '^ob/badges/([0-9]+)\.json$', 'index.php?fendigibadge_ob=badge&fendigibadge_ob_id=$matches[1]', 'top' );
		add_rewrite_rule( '^ob/assertions/([^/]+)\.json$', 'index.php?fendigibadge_ob=assertion&fendigibadge_ob_uid=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/assertion/([^/]+)/?$', 'index.php?fendigibadge_ob=attestation&fendigibadge_ob_uid=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/find/?$', 'index.php?fendigibadge_ob=find', 'top' );
		add_rewrite_rule( '^badges/claim-name/([^/]+)/?$', 'index.php?fendigibadge_ob=claim_name&fendigibadge_ob_token=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/unsubscribe/([^/]+)/?$', 'index.php?fendigibadge_ob=unsubscribe&fendigibadge_ob_token=$matches[1]', 'top' );
	}

	/**
	 * Register custom query vars.
	 *
	 * @param list<string> $vars Query vars.
	 * @return list<string>
	 */
	public static function register_query_vars( array $vars ): array {
		$vars[] = 'fendigibadge_ob';
		$vars[] = 'fendigibadge_ob_id';
		$vars[] = 'fendigibadge_ob_uid';
		$vars[] = 'fendigibadge_ob_token';

		return $vars;
	}

	/**
	 * Dispatch endpoint requests.
	 */
	public static function handle_request(): void {
		$type = get_query_var( 'fendigibadge_ob' );

		if ( ! is_string( $type ) || '' === $type ) {
			return;
		}

		switch ( $type ) {
			case 'issuer':
				self::serve_issuer_json();
				break;
			case 'badge':
				self::serve_badge_json();
				break;
			case 'assertion':
				self::serve_assertion_json();
				break;
			case 'attestation':
				self::serve_attestation_page();
				break;
			case 'find':
				self::serve_find_page();
				break;
			case 'claim_name':
				self::serve_claim_name_page();
				break;
			case 'unsubscribe':
				self::serve_unsubscribe_page();
				break;
		}
	}

	/**
	 * Serve IssuerOrganization JSON.
	 */
	private static function serve_issuer_json(): void {
		if ( ! Issuer::is_configured() ) {
			self::json_error( 404, array( 'error' => 'Issuer not configured' ) );
		}

		self::json_response( Issuer::to_open_badges() );
	}

	/**
	 * Serve BadgeClass JSON.
	 */
	private static function serve_badge_json(): void {
		$id   = absint( get_query_var( 'fendigibadge_ob_id' ) );
		$data = Badge_Class::to_open_badges( $id );

		if ( null === $data ) {
			self::json_error( 404, array( 'error' => 'Badge not found' ) );
		}

		self::json_response( $data );
	}

	/**
	 * Serve BadgeAssertion JSON (or 410 when revoked).
	 */
	private static function serve_assertion_json(): void {
		$uid = sanitize_text_field( (string) get_query_var( 'fendigibadge_ob_uid' ) );
		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row ) {
			self::json_error( 404, array( 'error' => 'Assertion not found' ) );
		}

		if ( ! empty( $row->revoked ) ) {
			status_header( 410 );
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( array( 'revoked' => true ) );
			exit;
		}

		$data = Assertion_Repository::to_open_badges( $row );

		if ( null === $data ) {
			self::json_error( 404, array( 'error' => 'Assertion not available' ) );
		}

		self::json_response( $data );
	}

	/**
	 * Serve public attestation HTML page.
	 */
	private static function serve_attestation_page(): void {
		$uid  = sanitize_text_field( (string) get_query_var( 'fendigibadge_ob_uid' ) );
		$vars = self::attestation_vars_for_uid( $uid );

		if ( null === $vars ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		$page = Public_Facing::get_attestation_page();
		if ( $page instanceof \WP_Post ) {
			self::render_attestation_as_page( $page, $vars );
			return;
		}

		self::render_view( 'attestation', $vars );
	}

	/**
	 * Build template vars for an attestation page, or null when unavailable.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function attestation_vars_for_uid( string $uid ): ?array {
		if ( '' === $uid ) {
			return null;
		}

		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row || ! empty( $row->revoked ) ) {
			return null;
		}

		$badge = get_post( (int) $row->badge_post_id );

		if ( ! $badge || Post_Types::BADGE !== $badge->post_type ) {
			return null;
		}

		$issuer          = Issuer::get();
		$linkedin_url    = LinkedIn::certification_url( $row, $badge );
		$attestation_url = Assertion_Repository::attestation_url( $uid );
		$json_url        = Assertion_Repository::json_url( $uid );
		$image_url       = Badge_Class::image_url( (int) $badge->ID );

		$assertion_data = Assertion_Repository::to_open_badges( $row );
		$assertion_json = is_array( $assertion_data )
			? (string) wp_json_encode( $assertion_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
			: '';

		return array(
			'assertion'       => $row,
			'badge'           => $badge,
			'issuer'          => $issuer,
			'linkedin_url'    => $linkedin_url,
			'attestation_url' => $attestation_url,
			'json_url'        => $json_url,
			'image_url'       => $image_url,
			'assertion_json'  => $assertion_json,
			'share_text'      => sprintf(
				/* translators: 1: badge name, 2: issuer name */
				__( 'I earned the %1$s badge from %2$s', 'fenton-digital-badges' ),
				get_the_title( $badge ),
				$issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' )
			),
		);
	}

	/**
	 * Render /badges/assertion/{uid}/ using a selected WordPress page and its template.
	 *
	 * Keeps the assertion URL while letting Site Editor / page templates control
	 * chrome and layout. Injects the attestation shortcode when the page content
	 * does not already include it.
	 *
	 * @param \WP_Post             $page Page supplying the template.
	 * @param array<string, mixed> $vars Attestation template vars.
	 */
	private static function render_attestation_as_page( \WP_Post $page, array $vars ): void {
		global $wp_query, $post;

		$post = $page;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->init_query_flags();
			$wp_query->is_page           = true;
			$wp_query->is_singular       = true;
			$wp_query->is_404            = false;
			$wp_query->is_home           = false;
			$wp_query->is_front_page     = false;
			$wp_query->is_single         = false;
			$wp_query->is_archive        = false;
			$wp_query->posts             = array( $page );
			$wp_query->post              = $page;
			$wp_query->post_count        = 1;
			$wp_query->found_posts       = 1;
			$wp_query->max_num_pages     = 1;
			$wp_query->queried_object    = $page;
			$wp_query->queried_object_id = (int) $page->ID;
		}

		setup_postdata( $page );

		add_filter( 'the_content', array( self::class, 'maybe_append_attestation_shortcode' ), 5 );

		$attestation_url = isset( $vars['attestation_url'] ) && is_string( $vars['attestation_url'] )
			? $vars['attestation_url']
			: '';
		$title = ( isset( $vars['badge'] ) && $vars['badge'] instanceof \WP_Post )
			? get_the_title( $vars['badge'] )
			: '';

		remove_action( 'wp_head', 'rel_canonical' );
		if ( '' !== $attestation_url ) {
			add_action(
				'wp_head',
				static function () use ( $attestation_url ): void {
					printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $attestation_url ) );
				},
				2
			);
		}

		if ( '' !== $title ) {
			add_filter(
				'pre_get_document_title',
				static function () use ( $title ): string {
					return $title;
				},
				999
			);
		}

		Public_Facing::enqueue_assets();
		add_action( 'wp_head', array( self::class, 'print_public_styles' ), 5 );
		add_action( 'wp_footer', array( self::class, 'print_public_scripts' ), 20 );

		status_header( 200 );
		nocache_headers();

		$template = get_page_template();

		if ( ! is_string( $template ) || '' === $template || ! is_readable( $template ) ) {
			remove_filter( 'the_content', array( self::class, 'maybe_append_attestation_shortcode' ), 5 );
			self::render_view( 'attestation', $vars );
			return;
		}

		include $template;
		exit;
	}

	/**
	 * Ensure the selected attestation page outputs the certificate markup.
	 *
	 * @param string $content Post content.
	 */
	public static function maybe_append_attestation_shortcode( string $content ): string {
		if ( has_shortcode( $content, 'fendigibadge_attestation' ) ) {
			return $content;
		}

		return $content . "\n\n[fendigibadge_attestation]";
	}

	/**
	 * Serve the find-badges email unsubscribe page.
	 */
	private static function serve_unsubscribe_page(): void {
		self::prevent_caching();

		$token = sanitize_text_field( (string) get_query_var( 'fendigibadge_ob_token' ) );
		$token = rawurldecode( $token );

		$success = Email_Unsubscribe::process_token( $token );

		self::render_view(
			'unsubscribe',
			array(
				'fendigibadge_success' => $success,
			)
		);
	}

	/**
	 * Serve the one-time name claim page.
	 */
	private static function serve_claim_name_page(): void {
		self::prevent_caching();

		$token_from_url = Name_Claim::normalize_token(
			sanitize_text_field( (string) get_query_var( 'fendigibadge_ob_token' ) )
		);

		$error           = '';
		$step            = 'invalid';
		$token           = $token_from_url;
		$name            = '';
		$badge_title     = '';
		$attestation_url = '';
		$form_action     = '' !== $token_from_url
			? Name_Claim::claim_url( $token_from_url )
			: home_url( '/badges/claim-name/' );

		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';

		if ( 'POST' === $request_method ) {
			self::process_claim_name_request( $error, $step, $token, $name, $badge_title, $attestation_url, $form_action );
		} else {
			$row = Name_Claim::find_assertion( $token_from_url );

			if ( null !== $row ) {
				$step            = 'enter';
				$token           = $token_from_url;
				$form_action     = Name_Claim::claim_url( $token_from_url );
				$attestation_url = Assertion_Repository::attestation_url( (string) $row->uid );
				$badge           = get_post( (int) $row->badge_post_id );
				$badge_title     = ( $badge instanceof \WP_Post ) ? get_the_title( $badge ) : '';
			}
		}

		self::render_view(
			'claim-name',
			array(
				'fendigibadge_error'           => $error,
				'fendigibadge_step'            => $step,
				'fendigibadge_token'           => $token,
				'fendigibadge_name'            => $name,
				'fendigibadge_badge_title'     => $badge_title,
				'fendigibadge_form_action'     => $form_action,
				'fendigibadge_attestation_url' => $attestation_url,
			)
		);
	}

	/**
	 * Process name-claim POST (preview / edit / confirm).
	 *
	 * @param string $error           Error message by reference.
	 * @param string $step            UI step by reference.
	 * @param string $token           Active token by reference.
	 * @param string $name            Submitted name by reference.
	 * @param string $badge_title     Badge title by reference.
	 * @param string $attestation_url Attestation URL by reference.
	 * @param string $form_action     Form action URL by reference.
	 */
	private static function process_claim_name_request(
		string &$error,
		string &$step,
		string &$token,
		string &$name,
		string &$badge_title,
		string &$attestation_url,
		string &$form_action
	): void {
		$error = '';
		$step  = 'invalid';

		$nonce = isset( $_POST['fendigibadge_claim_name_nonce'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['fendigibadge_claim_name_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'fendigibadge_claim_name' ) ) {
			$error = __( 'Invalid request. Please try again.', 'fenton-digital-badges' );
			return;
		}

		$token = Name_Claim::normalize_token(
			isset( $_POST['fendigibadge_claim_token'] )
				? sanitize_text_field( wp_unslash( (string) $_POST['fendigibadge_claim_token'] ) )
				: ''
		);
		$name = isset( $_POST['fendigibadge_claim_name'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['fendigibadge_claim_name'] ) )
			: '';
		$action = isset( $_POST['fendigibadge_claim_action'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['fendigibadge_claim_action'] ) )
			: 'preview';

		$row = Name_Claim::find_assertion( $token );

		if ( null === $row ) {
			$error = __( 'This link is invalid, has already been used, or has expired.', 'fenton-digital-badges' );
			$step  = 'invalid';
			return;
		}

		$form_action     = Name_Claim::claim_url( $token );
		$attestation_url = Assertion_Repository::attestation_url( (string) $row->uid );
		$badge           = get_post( (int) $row->badge_post_id );
		$badge_title     = ( $badge instanceof \WP_Post ) ? get_the_title( $badge ) : '';

		if ( 'edit' === $action ) {
			$step = 'enter';
			return;
		}

		if ( '' === $name ) {
			$error = __( 'Please enter your name.', 'fenton-digital-badges' );
			$step  = 'enter';
			return;
		}

		if ( 'confirm' === $action ) {
			if ( ! Name_Claim::confirm_name( $token, $name ) ) {
				$error = __( 'We could not save your name. The link may have already been used.', 'fenton-digital-badges' );
				$step  = 'invalid';
				return;
			}

			$step = 'done';
			return;
		}

		// Default: preview / confirm step.
		$step = 'confirm';
	}

	/**
	 * Serve find-badges page (also used by shortcode via Public_Facing).
	 */
	private static function serve_find_page(): void {
		self::prevent_caching();

		$page = Public_Facing::get_find_page();
		if ( $page instanceof \WP_Post ) {
			self::render_find_as_page( $page );
			return;
		}

		$error    = '';
		$searched = false;

		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';

		if ( 'POST' === $request_method ) {
			$searched = true;
			self::process_lookup_request( $error );
		}

		self::render_view(
			'find',
			array(
				'error'                   => $error,
				'searched'                => $searched,
				'fendigibadge_form_action' => home_url( '/badges/find/' ),
				'fendigibadge_show_header' => true,
			)
		);
	}

	/**
	 * Render /badges/find/ using a selected WordPress page and its template.
	 *
	 * Keeps the /badges/find/ URL while letting Site Editor / page templates
	 * control chrome and layout. Injects the find shortcode when the page
	 * content does not already include it.
	 */
	private static function render_find_as_page( \WP_Post $page ): void {
		global $wp_query, $post;

		$post = $page;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->init_query_flags();
			$wp_query->is_page            = true;
			$wp_query->is_singular        = true;
			$wp_query->is_404             = false;
			$wp_query->is_home            = false;
			$wp_query->is_front_page      = false;
			$wp_query->is_single          = false;
			$wp_query->is_archive         = false;
			$wp_query->posts              = array( $page );
			$wp_query->post               = $page;
			$wp_query->post_count         = 1;
			$wp_query->found_posts        = 1;
			$wp_query->max_num_pages      = 1;
			$wp_query->queried_object     = $page;
			$wp_query->queried_object_id  = (int) $page->ID;
		}

		setup_postdata( $page );

		add_filter( 'the_content', array( self::class, 'maybe_append_find_shortcode' ), 5 );

		remove_action( 'wp_head', 'rel_canonical' );
		add_action(
			'wp_head',
			static function (): void {
				printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( home_url( '/badges/find/' ) ) );
			},
			2
		);

		Public_Facing::enqueue_assets();
		add_action( 'wp_head', array( self::class, 'print_public_styles' ), 5 );
		add_action( 'wp_footer', array( self::class, 'print_public_scripts' ), 20 );

		status_header( 200 );
		nocache_headers();

		$template = get_page_template();

		if ( ! is_string( $template ) || '' === $template || ! is_readable( $template ) ) {
			remove_filter( 'the_content', array( self::class, 'maybe_append_find_shortcode' ), 5 );

			$error    = '';
			$searched = false;

			$request_method = isset( $_SERVER['REQUEST_METHOD'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
				: '';

			if ( 'POST' === $request_method ) {
				$searched = true;
				self::process_lookup_request( $error );
			}

			self::render_view(
				'find',
				array(
					'error'                   => $error,
					'searched'                => $searched,
					'fendigibadge_form_action' => home_url( '/badges/find/' ),
					'fendigibadge_show_header' => true,
				)
			);
			return;
		}

		include $template;
		exit;
	}

	/**
	 * Ensure the selected find page outputs the lookup form.
	 *
	 * @param string $content Post content.
	 */
	public static function maybe_append_find_shortcode( string $content ): string {
		if ( has_shortcode( $content, 'fendigibadge_find' ) ) {
			return $content;
		}

		return $content . "\n\n[fendigibadge_find]";
	}

	/**
	 * Prevent page caches from storing nonce-bearing find forms.
	 */
	public static function prevent_caching(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Cache plugin convention (WP Super Cache, W3TC, etc.).
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Cache plugin convention (WP Super Cache, W3TC, etc.).
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Cache plugin convention (WP Super Cache, W3TC, etc.).
			define( 'DONOTCACHEDB', true );
		}

		nocache_headers();
	}

	/**
	 * Process email lookup POST (shared by page + shortcode).
	 *
	 * Always shows the same success path in the UI to avoid email enumeration.
	 * When matching badges exist, their attestation URLs are emailed to the address.
	 * Runs at most once per request so a double-rendered shortcode cannot send twice.
	 *
	 * @param string $error Error message by reference.
	 */
	public static function process_lookup_request( string &$error ): void {
		static $processed = false;
		static $cached_error = '';

		if ( $processed ) {
			$error = $cached_error;
			return;
		}

		$processed = true;
		$error     = '';

		$nonce = isset( $_POST['fendigibadge_find_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['fendigibadge_find_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'fendigibadge_find_badges' ) ) {
			$error         = __( 'Invalid request. Please try again.', 'fenton-digital-badges' );
			$cached_error  = $error;
			return;
		}

		$email = isset( $_POST['fendigibadge_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['fendigibadge_email'] ) ) : '';

		if ( ! Identity::is_valid_email( $email ) ) {
			$error         = __( 'Please enter a valid email address.', 'fenton-digital-badges' );
			$cached_error  = $error;
			return;
		}

		// Checked before hashing so opted-out addresses are never used for lookup.
		if ( Email_Unsubscribe::is_unsubscribed( $email ) ) {
			$cached_error = $error;
			unset( $email );
			return;
		}

		// Simple transient rate limit by IP.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'fendigibadge_find_' . md5( $ip );
		$hits = (int) get_transient( $key );

		if ( $hits >= 20 ) {
			$error         = __( 'Too many lookups. Please wait a few minutes and try again.', 'fenton-digital-badges' );
			$cached_error  = $error;
			return;
		}

		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		$lookup  = Identity::lookup_hash( $email );
		$results = Assertion_Repository::find_by_lookup( $lookup );

		if ( array() !== $results ) {
			self::send_lookup_email( $email, $results );
		}

		$cached_error = $error;

		// Discard plaintext email after use.
		unset( $email );
	}

	/**
	 * Email attestation URLs for badges found via the public lookup form.
	 *
	 * @param string       $email   Recipient email.
	 * @param list<object> $results Assertion rows.
	 */
	private static function send_lookup_email( string $email, array $results ): void {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$entries = array();
		$seen    = array();

		foreach ( $results as $row ) {
			$uid = (string) $row->uid;
			if ( isset( $seen[ $uid ] ) ) {
				continue;
			}
			$seen[ $uid ] = true;

			$url   = Assertion_Repository::attestation_url( $uid );
			$badge = get_post( (int) $row->badge_post_id );
			$name  = ( $badge instanceof \WP_Post ) ? get_the_title( $badge ) : '';

			if ( '' === $url ) {
				continue;
			}

			$claim_url = '';
			if ( '' === trim( (string) ( $row->recipient_name ?? '' ) ) ) {
				$claim_token = Name_Claim::issue_for_assertion( $row );
				if ( is_string( $claim_token ) && '' !== $claim_token ) {
					$claim_url = Name_Claim::claim_url( $claim_token );
				}
			}

			$entries[] = array(
				'name'      => '' !== $name ? $name : $uid,
				'url'       => $url,
				'claim_url' => $claim_url,
			);
		}

		if ( array() === $entries ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your badges on %s', 'fenton-digital-badges' ),
			$site_name
		);

		$blocks = array();
		foreach ( $entries as $entry ) {
			$block = $entry['name'] . "\n" . $entry['url'];

			if ( '' !== $entry['claim_url'] ) {
				$block .= "\n\n" . __( 'Claim your certificate and add your name with the following one-time link:', 'fenton-digital-badges' );
				$block .= "\n" . $entry['claim_url'];
			}

			$blocks[] = $block;
		}

		$issuer = Issuer::get();

		$intro = trim( $issuer['find_email'] );
		if ( '' === $intro ) {
			$intro = sprintf(
				/* translators: %s: site name */
				__( 'You searched for your badges on %s. We found the following badges.', 'fenton-digital-badges' ),
				$site_name
			);
		}

		$signoff = trim( $issuer['find_email_signoff'] );
		if ( '' === $signoff ) {
			$signoff = __( 'Enjoy your badges!', 'fenton-digital-badges' );
		}

		$body  = $intro;
		$body .= "\n\n" . implode( "\n\n", $blocks );
		$body .= "\n\n" . $signoff;

		$unsubscribe_url = Email_Unsubscribe::unsubscribe_url( $email );
		if ( '' !== $unsubscribe_url ) {
			$body .= "\n\n" . __( 'Stop all future notifications', 'fenton-digital-badges' );
			$body .= "\n" . $unsubscribe_url;
		}

		$body .= "\n";

		$from_email    = $issuer['sending_email'];
		$from_name     = $issuer['sending_display_name'];
		$from_email_cb = null;
		$from_name_cb  = null;

		if ( '' !== $from_email && is_email( $from_email ) ) {
			$from_email_cb = static function () use ( $from_email ): string {
				return $from_email;
			};
			add_filter( 'wp_mail_from', $from_email_cb );
		}

		if ( '' !== $from_name ) {
			$from_name_cb = static function () use ( $from_name ): string {
				return $from_name;
			};
			add_filter( 'wp_mail_from_name', $from_name_cb );
		}

		wp_mail( $email, $subject, $body );

		if ( null !== $from_email_cb ) {
			remove_filter( 'wp_mail_from', $from_email_cb );
		}

		if ( null !== $from_name_cb ) {
			remove_filter( 'wp_mail_from_name', $from_name_cb );
		}
	}

	/**
	 * Render a view template and exit.
	 *
	 * @param string               $view View name.
	 * @param array<string, mixed> $vars Template vars.
	 * @param bool                 $use_theme_chrome Whether to wrap with get_header/footer.
	 */
	private static function render_view( string $view, array $vars, bool $use_theme_chrome = true ): void {
		$path = Public_Facing::locate_view( $view );

		if ( ! is_readable( $path ) ) {
			status_header( 500 );
			exit;
		}

		self::prepare_front_end_request( $view, $vars );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template vars.
		extract( $vars, EXTR_SKIP );

		status_header( 200 );
		nocache_headers();

		if ( $use_theme_chrome ) {
			Public_Facing::enqueue_assets();
			// Print assets directly so theme/optimizers cannot drop them on these custom routes.
			add_action( 'wp_head', array( self::class, 'print_public_styles' ), 5 );
			add_action( 'wp_footer', array( self::class, 'print_public_scripts' ), 20 );
			get_header();
			include $path;
			get_footer();
		} else {
			include $path;
		}

		exit;
	}

	/**
	 * Print public CSS for attestation/find pages.
	 */
	public static function print_public_styles(): void {
		wp_print_styles( 'fendigibadge-public' );
	}

	/**
	 * Print public JS for attestation/find pages.
	 */
	public static function print_public_scripts(): void {
		wp_print_scripts( 'fendigibadge-public' );
	}

	/**
	 * Stop themes treating badge pages as another post/home, and set a sensible title.
	 *
	 * @param string               $view View name.
	 * @param array<string, mixed> $vars Template vars.
	 */
	private static function prepare_front_end_request( string $view, array $vars ): void {
		global $wp_query, $post;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->init_query_flags();
			$wp_query->is_404      = false;
			$wp_query->is_home     = false;
			$wp_query->is_front_page = false;
			$wp_query->is_singular = false;
			$wp_query->is_single   = false;
			$wp_query->is_page     = false;
			$wp_query->is_archive  = false;
			$wp_query->posts       = array();
			$wp_query->post_count  = 0;
			$wp_query->queried_object = null;
			$wp_query->queried_object_id = 0;
			$wp_query->post = null;
		}

		$post = null;

		// Avoid leaking another post's canonical/oEmbed into the head.
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

		$title = '';
		$canonical = '';

		if ( isset( $vars['badge'] ) && $vars['badge'] instanceof \WP_Post ) {
			$title = get_the_title( $vars['badge'] );
		} elseif ( 'find' === $view ) {
			$title = __( 'Find your badges', 'fenton-digital-badges' );
			$canonical = home_url( '/badges/find/' );
		} elseif ( 'claim-name' === $view ) {
			$title = __( 'Add your name', 'fenton-digital-badges' );
			if ( isset( $vars['fendigibadge_form_action'] ) && is_string( $vars['fendigibadge_form_action'] ) && '' !== $vars['fendigibadge_form_action'] ) {
				$canonical = $vars['fendigibadge_form_action'];
			}
		}

		if ( isset( $vars['attestation_url'] ) && is_string( $vars['attestation_url'] ) ) {
			$canonical = $vars['attestation_url'];
		}

		if ( '' !== $title ) {
			add_filter(
				'pre_get_document_title',
				static function () use ( $title ): string {
					return $title;
				},
				999
			);
		}

		if ( '' !== $canonical ) {
			add_action(
				'wp_head',
				static function () use ( $canonical ): void {
					printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
				},
				2
			);
		}
	}

	/**
	 * Output JSON and exit.
	 *
	 * @param array<string, mixed> $data Payload.
	 */
	private static function json_response( array $data ): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Output JSON error and exit.
	 *
	 * @param int                  $status HTTP status.
	 * @param array<string, mixed> $data Payload.
	 */
	private static function json_error( int $status, array $data ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data );
		exit;
	}
}
