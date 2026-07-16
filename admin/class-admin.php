<?php
/**
 * Admin-facing functionality.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin hooks and assets.
 */
final class Admin {

	/** Default assertions list page size. */
	private const ASSERTIONS_DEFAULT_PER_PAGE = 20;

	/** Allowed assertions list page sizes. */
	private const ASSERTIONS_PER_PAGE_OPTIONS = array( 20, 50, 100 );

	/**
	 * Wire admin hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'add_meta_boxes', array( self::class, 'register_meta_boxes' ) );
		add_action( 'save_post_fendigibadge_badge', array( Badge_Class::class, 'save_meta' ) );
		add_action( 'admin_post_fendigibadge_issue_badges', array( self::class, 'handle_issue_badges' ) );
		add_action( 'admin_post_fendigibadge_revoke_assertion', array( self::class, 'handle_revoke_assertion' ) );
		add_action( 'admin_post_fendigibadge_unrevoke_assertion', array( self::class, 'handle_unrevoke_assertion' ) );
		add_action( 'admin_post_fendigibadge_delete_assertion', array( self::class, 'handle_delete_assertion' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=fendigibadge_badge',
			__( 'Issue Badges', 'fenton-digital-badges' ),
			__( 'Issue Badges', 'fenton-digital-badges' ),
			'manage_options',
			'fendigibadge-issue',
			array( self::class, 'render_issue_page' )
		);

		add_submenu_page(
			'edit.php?post_type=fendigibadge_badge',
			__( 'Assertions', 'fenton-digital-badges' ),
			__( 'Assertions', 'fenton-digital-badges' ),
			'manage_options',
			'fendigibadge-assertions',
			array( self::class, 'render_assertions_page' )
		);

		add_submenu_page(
			'edit.php?post_type=fendigibadge_badge',
			__( 'Settings', 'fenton-digital-badges' ),
			__( 'Settings', 'fenton-digital-badges' ),
			'manage_options',
			'fendigibadge-settings',
			array( self::class, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings API fields for the issuer.
	 */
	public static function register_settings(): void {
		register_setting(
			'fendigibadge_issuer_group',
			Issuer::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Issuer::class, 'sanitize' ),
				'default'           => Issuer::defaults(),
			)
		);

		register_setting(
			'fendigibadge_attestation_group',
			Public_Facing::ATTESTATION_PAGE_OPTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( self::class, 'sanitize_page_id' ),
				'default'           => 0,
			)
		);

		add_settings_section(
			'fendigibadge_issuer_section',
			__( 'Issuing organization', 'fenton-digital-badges' ),
			static function (): void {
				echo '<p>' . esc_html__( 'This organization appears on every Open Badge issued from this site.', 'fenton-digital-badges' ) . '</p>';
				echo '<p>' . esc_html__( 'Issuer JSON:', 'fenton-digital-badges' ) . ' <code>' . esc_html( Issuer::json_url() ) . '</code></p>';
			},
			'fendigibadge-settings'
		);

		add_settings_section(
			'fendigibadge_attestation_section',
			__( 'Attestation page', 'fenton-digital-badges' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Optionally choose a WordPress page to supply the layout for /badges/assertion/{uid}/. Edit that page’s template in the Site Editor to control header, spacing, and chrome. The certificate is added automatically if the page does not already include the shortcode.', 'fenton-digital-badges' ) . '</p>';
				echo '<p>' . esc_html__( 'Example URL:', 'fenton-digital-badges' ) . ' <code>' . esc_html( home_url( '/badges/assertion/{uid}/' ) ) . '</code></p>';
			},
			'fendigibadge-attestation'
		);

		$fields = array(
			'name'                     => __( 'Name', 'fenton-digital-badges' ),
			'url'                      => __( 'Website URL', 'fenton-digital-badges' ),
			'email'                    => __( 'Contact email', 'fenton-digital-badges' ),
			'description'              => __( 'Description', 'fenton-digital-badges' ),
			'image_id'                 => __( 'Logo', 'fenton-digital-badges' ),
			'linkedin_organization_id' => __( 'LinkedIn organization ID', 'fenton-digital-badges' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'fendigibadge_issuer_' . $key,
				$label,
				array( self::class, 'render_issuer_field' ),
				'fendigibadge-settings',
				'fendigibadge_issuer_section',
				array( 'key' => $key )
			);
		}

		add_settings_section(
			'fendigibadge_email_section',
			__( 'Email settings', 'fenton-digital-badges' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Configure the From address and the body of emails sent when badges are issued. Leave From fields blank to use the WordPress defaults. Leave body fields blank to use the plugin defaults.', 'fenton-digital-badges' ) . '</p>';
			},
			'fendigibadge-settings'
		);

		$email_from_fields = array(
			'sending_email'        => __( 'Sending email', 'fenton-digital-badges' ),
			'sending_display_name' => __( 'Sending display name', 'fenton-digital-badges' ),
		);

		foreach ( $email_from_fields as $key => $label ) {
			add_settings_field(
				'fendigibadge_issuer_' . $key,
				$label,
				array( self::class, 'render_issuer_field' ),
				'fendigibadge-settings',
				'fendigibadge_email_section',
				array( 'key' => $key )
			);
		}

		add_settings_field(
			'fendigibadge_issuer_email_templates',
			__( 'Email templates', 'fenton-digital-badges' ),
			array( self::class, 'render_email_templates_field' ),
			'fendigibadge-settings',
			'fendigibadge_email_section'
		);

		add_settings_field(
			'fendigibadge_attestation_page',
			__( 'Page template', 'fenton-digital-badges' ),
			array( self::class, 'render_attestation_page_field' ),
			'fendigibadge-attestation',
			'fendigibadge_attestation_section'
		);
	}

	/**
	 * Sanitize a settings page ID.
	 *
	 * @param mixed $value Raw option value.
	 */
	public static function sanitize_page_id( $value ): int {
		$page_id = absint( $value );

		if ( $page_id <= 0 ) {
			return 0;
		}

		$page = get_post( $page_id );

		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type ) {
			return 0;
		}

		return $page_id;
	}

	/**
	 * Render the attestation page dropdown.
	 */
	public static function render_attestation_page_field(): void {
		self::render_page_dropdown(
			Public_Facing::ATTESTATION_PAGE_OPTION,
			'[fendigibadge_attestation]'
		);
	}

	/**
	 * Render a page-template settings dropdown.
	 *
	 * @param string $option_key Option key for the selected page ID.
	 * @param string $shortcode  Shortcode shown in the description.
	 */
	private static function render_page_dropdown( string $option_key, string $shortcode ): void {
		$selected = absint( get_option( $option_key, 0 ) );

		wp_dropdown_pages(
			array(
				'name'              => esc_attr( $option_key ),
				'id'                => esc_attr( $option_key ),
				'selected'          => esc_attr( (string) $selected ),
				'show_option_none'  => esc_html__( '— Plugin default —', 'fenton-digital-badges' ),
				'option_none_value' => '0',
			)
		);

		echo '<p class="description">' . esc_html__( 'Leave as plugin default to use the built-in layout with your theme header and footer. Or use the shortcode', 'fenton-digital-badges' ) . ' <code>' . esc_html( $shortcode ) . '</code> ' . esc_html__( 'on the selected page.', 'fenton-digital-badges' ) . '</p>';
	}

	/**
	 * Render a single issuer settings field.
	 *
	 * @param array{key?: string} $args Field args.
	 */
	public static function render_issuer_field( array $args ): void {
		$key    = isset( $args['key'] ) ? (string) $args['key'] : '';
		$issuer = Issuer::get();
		$name   = Issuer::OPTION_KEY . '[' . $key . ']';
		$value  = $issuer[ $key ] ?? '';

		if ( 'description' === $key ) {
			printf(
				'<textarea class="large-text" rows="3" name="%s" id="%s">%s</textarea>',
				esc_attr( $name ),
				esc_attr( $name ),
				esc_textarea( (string) $value )
			);

			return;
		}

		if ( 'image_id' === $key ) {
			self::render_logo_field( $name, absint( $value ) );
			return;
		}

		$type = in_array( $key, array( 'email', 'sending_email' ), true )
			? 'email'
			: ( 'url' === $key ? 'url' : 'text' );

		printf(
			'<input class="regular-text" type="%s" name="%s" id="%s" value="%s" />',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( (string) $value )
		);

		if ( 'linkedin_organization_id' === $key ) {
			echo '<p class="description">' . esc_html__( 'Optional numeric LinkedIn company ID for Add to Profile links.', 'fenton-digital-badges' ) . '</p>';
		}

		if ( 'sending_email' === $key ) {
			echo '<p class="description">' . esc_html__( 'From address used when the plugin sends email. Leave blank for the WordPress default.', 'fenton-digital-badges' ) . '</p>';
		}

		if ( 'sending_display_name' === $key ) {
			echo '<p class="description">' . esc_html__( 'From display name used when the plugin sends email. Leave blank for the WordPress default.', 'fenton-digital-badges' ) . '</p>';
		}
	}

	/**
	 * Render the issue-badge email template fields.
	 */
	public static function render_email_templates_field(): void {
		$issuer = Issuer::get();
		$option = Issuer::OPTION_KEY;
		?>
		<div class="fendigibadge-email-templates">
			<p>
				<label for="<?php echo esc_attr( $option . '[issue_email]' ); ?>"><strong><?php esc_html_e( 'Issue badge email', 'fenton-digital-badges' ); ?></strong></label>
			</p>
			<textarea class="large-text" rows="4" name="<?php echo esc_attr( $option . '[issue_email]' ); ?>" id="<?php echo esc_attr( $option . '[issue_email]' ); ?>" placeholder="<?php echo esc_attr( Badge_Mailer::default_issue_intro() ); ?>"><?php echo esc_textarea( $issuer['issue_email'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Opening text before the list of newly issued badges. Leave blank for the default message.', 'fenton-digital-badges' ); ?></p>

			<p>
				<label for="<?php echo esc_attr( $option . '[issue_email_signoff]' ); ?>"><strong><?php esc_html_e( 'Issue badge email sign-off', 'fenton-digital-badges' ); ?></strong></label>
			</p>
			<textarea class="large-text" rows="4" name="<?php echo esc_attr( $option . '[issue_email_signoff]' ); ?>" id="<?php echo esc_attr( $option . '[issue_email_signoff]' ); ?>" placeholder="<?php echo esc_attr( Badge_Mailer::default_issue_signoff() ); ?>"><?php echo esc_textarea( $issuer['issue_email_signoff'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Closing text after the list of newly issued badges. Leave blank for the default sign-off.', 'fenton-digital-badges' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render media-library logo picker.
	 */
	private static function render_logo_field( string $input_name, int $image_id ): void {
		$image_url = '';
		if ( $image_id > 0 ) {
			$url = wp_get_attachment_image_url( $image_id, 'medium' );
			$image_url = is_string( $url ) ? $url : '';
		}

		$has_image = '' !== $image_url;
		?>
		<div class="fendigibadge-logo-picker" data-fendigibadge-logo-picker>
			<input
				type="hidden"
				name="<?php echo esc_attr( $input_name ); ?>"
				id="fendigibadge-issuer-logo-id"
				value="<?php echo esc_attr( (string) $image_id ); ?>"
				data-fendigibadge-logo-id
			/>
			<div class="fendigibadge-logo-picker__preview" data-fendigibadge-logo-preview <?php echo $has_image ? '' : 'hidden'; ?>>
				<?php if ( $has_image ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
				<?php endif; ?>
			</div>
			<p class="fendigibadge-logo-picker__actions">
				<button type="button" class="button" data-fendigibadge-logo-select>
					<?php echo $has_image ? esc_html__( 'Change logo', 'fenton-digital-badges' ) : esc_html__( 'Select logo', 'fenton-digital-badges' ); ?>
				</button>
				<button type="button" class="button-link-delete" data-fendigibadge-logo-remove <?php echo $has_image ? '' : 'hidden'; ?>>
					<?php esc_html_e( 'Remove', 'fenton-digital-badges' ); ?>
				</button>
			</p>
			<p class="description"><?php esc_html_e( 'Choose an image from the media library for the issuer logo.', 'fenton-digital-badges' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'fendigibadge_issuer_group' );
				do_settings_sections( 'fendigibadge-settings' );
				submit_button( __( 'Save settings', 'fenton-digital-badges' ) );
				?>
			</form>
			<hr />
			<form action="options.php" method="post">
				<?php
				settings_fields( 'fendigibadge_attestation_group' );
				do_settings_sections( 'fendigibadge-attestation' );
				submit_button( __( 'Save attestation page', 'fenton-digital-badges' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register badge meta box.
	 */
	public static function register_meta_boxes(): void {
		add_meta_box(
			'fendigibadge_badge_ob_meta',
			__( 'Open Badges', 'fenton-digital-badges' ),
			array( self::class, 'render_badge_meta_box' ),
			'fendigibadge_badge',
			'side',
			'default'
		);
	}

	/**
	 * Render Open Badges meta box on badge edit screen.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public static function render_badge_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'fendigibadge_badge_meta', 'fendigibadge_badge_meta_nonce' );

		$criteria = get_post_meta( $post->ID, Badge_Class::META_CRITERIA_URL, true );
		$earn_url = get_post_meta( $post->ID, Badge_Class::META_EARN_URL, true );
		$tags     = get_post_meta( $post->ID, Badge_Class::META_TAGS, true );
		$criteria = is_string( $criteria ) ? $criteria : '';
		$earn_url = is_string( $earn_url ) ? $earn_url : '';
		$tags     = is_string( $tags ) ? $tags : '';
		?>
		<p>
			<label for="fendigibadge_criteria_url"><strong><?php esc_html_e( 'Criteria URL', 'fenton-digital-badges' ); ?></strong></label><br />
			<input type="url" class="widefat" name="fendigibadge_criteria_url" id="fendigibadge_criteria_url" value="<?php echo esc_attr( $criteria ); ?>" />
			<span class="description"><?php esc_html_e( 'Leave blank to use this badge’s permalink.', 'fenton-digital-badges' ); ?></span>
		</p>
		<p>
			<label for="fendigibadge_earn_url"><strong><?php esc_html_e( 'Earn URL', 'fenton-digital-badges' ); ?></strong></label><br />
			<input type="url" class="widefat" name="fendigibadge_earn_url" id="fendigibadge_earn_url" value="<?php echo esc_attr( $earn_url ); ?>" />
			<span class="description"><?php esc_html_e( 'Where can someone go to earn this badge? Shown as a link wherever the badge is displayed.', 'fenton-digital-badges' ); ?></span>
		</p>
		<p>
			<label for="fendigibadge_tags"><strong><?php esc_html_e( 'Tags', 'fenton-digital-badges' ); ?></strong></label><br />
			<input type="text" class="widefat" name="fendigibadge_tags" id="fendigibadge_tags" value="<?php echo esc_attr( $tags ); ?>" />
			<span class="description"><?php esc_html_e( 'Comma-separated tags.', 'fenton-digital-badges' ); ?></span>
		</p>
		<?php if ( 'publish' === $post->post_status ) : ?>
			<p>
				<strong><?php esc_html_e( 'BadgeClass JSON', 'fenton-digital-badges' ); ?></strong><br />
				<code style="word-break:break-all;"><?php echo esc_html( Badge_Class::json_url( (int) $post->ID ) ); ?></code>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Issue Badges admin page.
	 */
	public static function render_issue_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$badges = get_posts(
			array(
				'post_type'      => Post_Types::BADGE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$issued  = 0;
		$skipped = 0;
		$errors  = array();
		$notice  = get_transient( 'fendigibadge_issue_notice_' . get_current_user_id() );

		if ( is_array( $notice ) ) {
			$issued  = isset( $notice['issued'] ) ? absint( $notice['issued'] ) : 0;
			$skipped = isset( $notice['skipped'] ) ? absint( $notice['skipped'] ) : 0;
			if ( isset( $notice['errors'] ) && is_array( $notice['errors'] ) ) {
				$errors = $notice['errors'];
			}
			delete_transient( 'fendigibadge_issue_notice_' . get_current_user_id() );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! Issuer::is_configured() ) : ?>
				<div class="notice notice-warning"><p>
					<?php
					printf(
						/* translators: %s: settings URL */
						wp_kses_post( __( 'Configure your <a href="%s">issuing organization</a> before issuing badges.', 'fenton-digital-badges' ) ),
						esc_url( admin_url( 'edit.php?post_type=fendigibadge_badge&page=fendigibadge-settings' ) )
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $issued > 0 ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					printf(
						/* translators: %d: number of assertions created */
						esc_html( _n( '%d badge issued.', '%d badges issued.', $issued, 'fenton-digital-badges' ) ),
						absint( $issued )
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $skipped > 0 ) : ?>
				<div class="notice notice-info is-dismissible"><p>
					<?php
					printf(
						/* translators: %d: number of duplicate rows skipped */
						esc_html( _n( '%d row skipped (already issued).', '%d rows skipped (already issued).', $skipped, 'fenton-digital-badges' ) ),
						absint( $skipped )
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $errors ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Some rows could not be issued:', 'fenton-digital-badges' ); ?></p>
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( (string) $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="fendigibadge_issue_badges" />
				<?php wp_nonce_field( 'fendigibadge_issue_badges', 'fendigibadge_issue_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fendigibadge_badge_id"><?php esc_html_e( 'Badge', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<select name="fendigibadge_badge_id" id="fendigibadge_badge_id" required>
								<option value=""><?php esc_html_e( 'Select a badge…', 'fenton-digital-badges' ); ?></option>
								<?php foreach ( $badges as $badge ) : ?>
									<?php
									$issuable = Badge_Class::is_issuable( (int) $badge->ID );
									$label    = get_the_title( $badge );
									if ( ! $issuable ) {
										$label .= ' ' . __( '(missing image or criteria)', 'fenton-digital-badges' );
									}
									?>
									<option value="<?php echo esc_attr( (string) $badge->ID ); ?>" <?php disabled( ! $issuable ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fendigibadge_csv_text"><?php esc_html_e( 'CSV data', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<textarea class="large-text code" rows="12" name="fendigibadge_csv_text" id="fendigibadge_csv_text" placeholder="email,name,evidence,expires"></textarea>
							<p class="description">
								<?php esc_html_e( 'Columns: email (required), name, evidence, expires (YYYY-MM-DD). Header row optional — you can paste email,name on its own. Email addresses are hashed and never stored.', 'fenton-digital-badges' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fendigibadge_csv_file"><?php esc_html_e( 'Or upload CSV', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<input type="file" name="fendigibadge_csv_file" id="fendigibadge_csv_file" accept=".csv,text/csv" />
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Issue badges', 'fenton-digital-badges' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle CSV issue form submission.
	 */
	public static function handle_issue_badges(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'fenton-digital-badges' ) );
		}

		check_admin_referer( 'fendigibadge_issue_badges', 'fendigibadge_issue_nonce' );

		$badge_id = isset( $_POST['fendigibadge_badge_id'] ) ? absint( $_POST['fendigibadge_badge_id'] ) : 0;
		$csv_text = isset( $_POST['fendigibadge_csv_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fendigibadge_csv_text'] ) ) : '';

		$error = isset( $_FILES['fendigibadge_csv_file']['error'] ) ? (int) $_FILES['fendigibadge_csv_file']['error'] : UPLOAD_ERR_NO_FILE;
		$tmp   = isset( $_FILES['fendigibadge_csv_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( (string) $_FILES['fendigibadge_csv_file']['tmp_name'] ) )
			: '';

		if ( UPLOAD_ERR_OK === $error && '' !== $tmp && is_uploaded_file( $tmp ) ) {
			$contents = file_get_contents( $tmp );
			if ( is_string( $contents ) && '' !== trim( $contents ) ) {
				$csv_text = sanitize_textarea_field( $contents );
			}
		}

		$result = Csv_Issuer::issue_from_csv( $badge_id, $csv_text );

		set_transient(
			'fendigibadge_issue_notice_' . get_current_user_id(),
			array(
				'issued'  => $result['issued'],
				'skipped' => $result['skipped'],
				'errors'  => $result['errors'],
			),
			MINUTE_IN_SECONDS
		);

		$redirect = add_query_arg(
			array(
				'post_type' => Post_Types::BADGE,
				'page'      => 'fendigibadge-issue',
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render Assertions list admin page.
	 */
	public static function render_assertions_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination/filter.
		$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination/filter.
		$badge_id = isset( $_GET['badge_id'] ) ? absint( $_GET['badge_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination/filter.
		$email_filter = isset( $_GET['fendigibadge_email'] ) ? sanitize_email( wp_unslash( (string) $_GET['fendigibadge_email'] ) ) : '';
		$per_page       = self::assertions_per_page_from_request();

		$lookup_filter = '';
		$email_invalid = false;

		if ( '' !== $email_filter ) {
			if ( Identity::is_valid_email( $email_filter ) ) {
				$lookup_filter = Identity::lookup_hash( $email_filter );
			} else {
				$email_invalid = true;
			}
		}

		$badges = get_posts(
			array(
				'post_type'      => Post_Types::BADGE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( $badge_id > 0 ) {
			$listed_ids = array_map(
				static function ( $badge ): int {
					return $badge instanceof \WP_Post ? (int) $badge->ID : 0;
				},
				$badges
			);

			if ( ! in_array( $badge_id, $listed_ids, true ) ) {
				$selected_badge = get_post( $badge_id );
				if ( $selected_badge instanceof \WP_Post && Post_Types::BADGE === $selected_badge->post_type ) {
					$badges[] = $selected_badge;
				}
			}
		}

		$result      = Assertion_Repository::list_assertions( $page, $per_page, $badge_id, $lookup_filter );
		$total       = (int) $result['total'];
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 0;

		if ( $total_pages > 0 && $page > $total_pages ) {
			$page        = $total_pages;
			$result      = Assertion_Repository::list_assertions( $page, $per_page, $badge_id, $lookup_filter );
			$total       = (int) $result['total'];
			$total_pages = (int) ceil( $total / $per_page );
		}

		$has_filters      = $badge_id > 0 || '' !== $email_filter || self::ASSERTIONS_DEFAULT_PER_PAGE !== $per_page;
		$has_list_filters = $badge_id > 0 || '' !== $lookup_filter;

		$notice = get_transient( 'fendigibadge_assertion_notice_' . get_current_user_id() );
		if ( is_string( $notice ) && '' !== $notice ) {
			delete_transient( 'fendigibadge_assertion_notice_' . get_current_user_id() );
		} else {
			$notice = '';
		}

		$notice_messages = array(
			'revoked'   => __( 'Assertion revoked.', 'fenton-digital-badges' ),
			'unrevoked' => __( 'Assertion restored.', 'fenton-digital-badges' ),
			'deleted'   => __( 'Assertion deleted.', 'fenton-digital-badges' ),
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $notice_messages[ $notice ] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice_messages[ $notice ] ); ?></p></div>
			<?php endif; ?>

			<?php if ( $email_invalid ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Enter a valid email address to search.', 'fenton-digital-badges' ); ?></p></div>
			<?php elseif ( '' !== $lookup_filter && array() === $result['items'] ) : ?>
				<div class="notice notice-info"><p><?php esc_html_e( 'No badges found for that email address.', 'fenton-digital-badges' ); ?></p></div>
			<?php endif; ?>

			<form class="fendigibadge-assertions-filter" method="get">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( Post_Types::BADGE ); ?>" />
				<input type="hidden" name="page" value="fendigibadge-assertions" />
				<label class="screen-reader-text" for="fendigibadge_badge_filter"><?php esc_html_e( 'Filter by badge', 'fenton-digital-badges' ); ?></label>
				<select name="badge_id" id="fendigibadge_badge_filter">
					<option value="0"><?php esc_html_e( 'All badges', 'fenton-digital-badges' ); ?></option>
					<?php foreach ( $badges as $badge ) : ?>
						<?php if ( ! $badge instanceof \WP_Post ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( (string) $badge->ID ); ?>" <?php selected( $badge_id, (int) $badge->ID ); ?>>
							<?php echo esc_html( get_the_title( $badge ) ?: '#' . (string) $badge->ID ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="fendigibadge_email_filter"><?php esc_html_e( 'Find badges by recipient email', 'fenton-digital-badges' ); ?></label>
				<input type="email" class="regular-text" name="fendigibadge_email" id="fendigibadge_email_filter" value="<?php echo esc_attr( $email_filter ); ?>" placeholder="<?php esc_attr_e( 'Find badges by recipient email…', 'fenton-digital-badges' ); ?>" />
				<label class="screen-reader-text" for="fendigibadge_per_page"><?php esc_html_e( 'Items per page', 'fenton-digital-badges' ); ?></label>
				<select name="per_page" id="fendigibadge_per_page">
					<?php foreach ( self::ASSERTIONS_PER_PAGE_OPTIONS as $option ) : ?>
						<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( $per_page, $option ); ?>>
							<?php
							printf(
								/* translators: %d: number of assertions per page */
								esc_html__( '%d per page', 'fenton-digital-badges' ),
								absint( $option )
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Filter', 'fenton-digital-badges' ), 'secondary', 'fendigibadge_assertions_filter', false ); ?>
				<?php if ( $has_filters ) : ?>
					<a class="button" href="<?php echo esc_url( self::assertions_list_url() ); ?>"><?php esc_html_e( 'Clear filters', 'fenton-digital-badges' ); ?></a>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Compares a hash of the entered email against stored recipient hashes. The address itself is not stored or emailed.', 'fenton-digital-badges' ); ?></p>
			</form>

			<?php self::render_assertions_tablenav( $page, $per_page, $total, $total_pages, $badge_id, $email_filter, 'top' ); ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'UID', 'fenton-digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Badge', 'fenton-digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Recipient name', 'fenton-digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Issued', 'fenton-digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Status', 'fenton-digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'fenton-digital-badges' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( array() === $result['items'] ) : ?>
						<tr>
							<td colspan="6">
								<?php
								echo esc_html(
									$has_list_filters
										? __( 'No assertions match your filters.', 'fenton-digital-badges' )
										: __( 'No assertions yet.', 'fenton-digital-badges' )
								);
								?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $result['items'] as $row ) : ?>
							<?php
							$badge_title = get_the_title( (int) $row->badge_post_id );
							$is_revoked  = ! empty( $row->revoked );
							?>
							<tr>
								<td><code><?php echo esc_html( (string) $row->uid ); ?></code></td>
								<td><?php echo esc_html( $badge_title ? $badge_title : '#' . (string) $row->badge_post_id ); ?></td>
								<td><?php echo esc_html( (string) ( $row->recipient_name ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) $row->issued_on ); ?></td>
								<td>
									<?php
									echo $is_revoked
										? esc_html__( 'Revoked', 'fenton-digital-badges' )
										: esc_html__( 'Active', 'fenton-digital-badges' );
									?>
								</td>
								<td>
									<a href="<?php echo esc_url( Assertion_Repository::attestation_url( (string) $row->uid ) ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'View', 'fenton-digital-badges' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( Assertion_Repository::json_url( (string) $row->uid ) ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'JSON', 'fenton-digital-badges' ); ?>
									</a>
									<?php if ( ! $is_revoked ) : ?>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="fendigibadge_revoke_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'fendigibadge_revoke_assertion_' . $row->uid, 'fendigibadge_revoke_nonce' ); ?>
											<button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Revoke this assertion?', 'fenton-digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Revoke', 'fenton-digital-badges' ); ?>
											</button>
										</form>
									<?php else : ?>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="fendigibadge_unrevoke_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'fendigibadge_unrevoke_assertion_' . $row->uid, 'fendigibadge_unrevoke_nonce' ); ?>
											<button type="submit" class="button-link" onclick="return confirm('<?php echo esc_js( __( 'Restore this assertion?', 'fenton-digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Restore', 'fenton-digital-badges' ); ?>
											</button>
										</form>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="fendigibadge_delete_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'fendigibadge_delete_assertion_' . $row->uid, 'fendigibadge_delete_nonce' ); ?>
											<button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Permanently delete this assertion? This cannot be undone.', 'fenton-digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Delete', 'fenton-digital-badges' ); ?>
											</button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php self::render_assertions_tablenav( $page, $per_page, $total, $total_pages, $badge_id, $email_filter, 'bottom' ); ?>
		</div>
		<?php
	}

	/**
	 * Parse assertions list page size from the request.
	 */
	private static function assertions_per_page_from_request(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination/filter.
		if ( ! isset( $_GET['per_page'] ) ) {
			return self::ASSERTIONS_DEFAULT_PER_PAGE;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination/filter.
		$requested = absint( $_GET['per_page'] );

		return in_array( $requested, self::ASSERTIONS_PER_PAGE_OPTIONS, true )
			? $requested
			: self::ASSERTIONS_DEFAULT_PER_PAGE;
	}

	/**
	 * Build query args for the Assertions admin list URL.
	 *
	 * @return array<string, int|string>
	 */
	private static function assertions_list_query_args( int $page = 0, int $per_page = 0, int $badge_id = 0, string $email_filter = '' ): array {
		$args = array(
			'post_type' => Post_Types::BADGE,
			'page'      => 'fendigibadge-assertions',
		);

		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		$per_page = $per_page > 0 ? $per_page : self::ASSERTIONS_DEFAULT_PER_PAGE;
		if ( self::ASSERTIONS_DEFAULT_PER_PAGE !== $per_page ) {
			$args['per_page'] = $per_page;
		}

		if ( $badge_id > 0 ) {
			$args['badge_id'] = $badge_id;
		}

		if ( '' !== $email_filter ) {
			$args['fendigibadge_email'] = $email_filter;
		}

		return $args;
	}

	/**
	 * Build the Assertions admin list URL.
	 *
	 * @param array<string, int|string> $overrides Optional query args to merge.
	 */
	private static function assertions_list_url( array $overrides = array() ): string {
		return add_query_arg(
			array_merge(
				self::assertions_list_query_args(),
				$overrides
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Render pagination and item count for the Assertions list.
	 */
	private static function render_assertions_tablenav(
		int $page,
		int $per_page,
		int $total,
		int $total_pages,
		int $badge_id,
		string $email_filter,
		string $position
	): void {
		$list_url = self::assertions_list_url(
			self::assertions_list_query_args( 0, $per_page, $badge_id, $email_filter )
		);
		?>
		<div class="tablenav <?php echo esc_attr( $position ); ?>">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					if ( 0 === $total ) {
						esc_html_e( '0 items', 'fenton-digital-badges' );
					} else {
						$start = ( ( $page - 1 ) * $per_page ) + 1;
						$end   = min( $page * $per_page, $total );
						printf(
							/* translators: 1: first item number, 2: last item number, 3: total items */
							esc_html__( 'Showing %1$s–%2$s of %3$s', 'fenton-digital-badges' ),
							esc_html( number_format_i18n( $start ) ),
							esc_html( number_format_i18n( $end ) ),
							esc_html( number_format_i18n( $total ) )
						);
					}
					?>
				</span>
				<?php if ( $total_pages > 1 ) : ?>
					<span class="pagination-links">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%', $list_url ),
									'format'    => '',
									'current'   => $page,
									'total'     => $total_pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								)
							) ?: ''
						);
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle assertion revoke.
	 */
	public static function handle_revoke_assertion(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'fenton-digital-badges' ) );
		}

		$uid = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['uid'] ) ) : '';
		check_admin_referer( 'fendigibadge_revoke_assertion_' . $uid, 'fendigibadge_revoke_nonce' );

		if ( '' !== $uid ) {
			Assertion_Repository::revoke( $uid, __( 'Revoked by administrator', 'fenton-digital-badges' ) );
			set_transient( 'fendigibadge_assertion_notice_' . get_current_user_id(), 'revoked', MINUTE_IN_SECONDS );
		}

		self::redirect_to_assertions();
	}

	/**
	 * Handle assertion un-revoke (restore).
	 */
	public static function handle_unrevoke_assertion(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'fenton-digital-badges' ) );
		}

		$uid = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['uid'] ) ) : '';
		check_admin_referer( 'fendigibadge_unrevoke_assertion_' . $uid, 'fendigibadge_unrevoke_nonce' );

		if ( '' !== $uid && Assertion_Repository::unrevoke( $uid ) ) {
			set_transient( 'fendigibadge_assertion_notice_' . get_current_user_id(), 'unrevoked', MINUTE_IN_SECONDS );
		}

		self::redirect_to_assertions();
	}

	/**
	 * Handle permanent deletion of a revoked assertion.
	 */
	public static function handle_delete_assertion(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'fenton-digital-badges' ) );
		}

		$uid = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['uid'] ) ) : '';
		check_admin_referer( 'fendigibadge_delete_assertion_' . $uid, 'fendigibadge_delete_nonce' );

		if ( '' !== $uid && Assertion_Repository::delete_revoked( $uid ) ) {
			set_transient( 'fendigibadge_assertion_notice_' . get_current_user_id(), 'deleted', MINUTE_IN_SECONDS );
		}

		self::redirect_to_assertions();
	}

	/**
	 * Redirect back to the Assertions admin list.
	 */
	private static function redirect_to_assertions(): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => Post_Types::BADGE,
					'page'      => 'fendigibadge-assertions',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Enqueue admin CSS/JS.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		$is_badge_screen = $screen && Post_Types::BADGE === $screen->post_type;
		$is_plugin_page  = false !== strpos( $hook_suffix, 'fendigibadge' );
		$is_settings     = false !== strpos( $hook_suffix, 'fendigibadge-settings' );

		if ( ! $is_badge_screen && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'fendigibadge-admin',
			FENDIGIBADGE_URL . 'admin/css/admin.css',
			array(),
			FENDIGIBADGE_VERSION
		);

		$script_deps = array();

		if ( $is_settings ) {
			wp_enqueue_media();
			$script_deps[] = 'jquery';
		}

		wp_enqueue_script(
			'fendigibadge-admin',
			FENDIGIBADGE_URL . 'admin/js/admin.js',
			$script_deps,
			FENDIGIBADGE_VERSION,
			true
		);

		if ( $is_settings ) {
			wp_localize_script(
				'fendigibadge-admin',
				'fendigibadgeAdmin',
				array(
					'selectLogoTitle'  => __( 'Select issuer logo', 'fenton-digital-badges' ),
					'selectLogoButton' => __( 'Use this logo', 'fenton-digital-badges' ),
					'changeLogo'       => __( 'Change logo', 'fenton-digital-badges' ),
					'selectLogo'       => __( 'Select logo', 'fenton-digital-badges' ),
				)
			);
		}
	}
}
