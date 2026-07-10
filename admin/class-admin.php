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

	/**
	 * Wire admin hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'add_meta_boxes', array( self::class, 'register_meta_boxes' ) );
		add_action( 'save_post_db_badge', array( Badge_Class::class, 'save_meta' ) );
		add_action( 'admin_post_db_issue_badges', array( self::class, 'handle_issue_badges' ) );
		add_action( 'admin_post_db_revoke_assertion', array( self::class, 'handle_revoke_assertion' ) );
		add_action( 'admin_post_db_unrevoke_assertion', array( self::class, 'handle_unrevoke_assertion' ) );
		add_action( 'admin_post_db_delete_assertion', array( self::class, 'handle_delete_assertion' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Issue Badges', 'fenton-digital-badges' ),
			__( 'Issue Badges', 'fenton-digital-badges' ),
			'manage_options',
			'fenton-digital-badges-issue',
			array( self::class, 'render_issue_page' )
		);

		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Assertions', 'fenton-digital-badges' ),
			__( 'Assertions', 'fenton-digital-badges' ),
			'manage_options',
			'fenton-digital-badges-assertions',
			array( self::class, 'render_assertions_page' )
		);

		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Settings', 'fenton-digital-badges' ),
			__( 'Settings', 'fenton-digital-badges' ),
			'manage_options',
			'fenton-digital-badges-settings',
			array( self::class, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings API fields for the issuer.
	 */
	public static function register_settings(): void {
		register_setting(
			'fenton_digital_badges_issuer_group',
			Issuer::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Issuer::class, 'sanitize' ),
				'default'           => Issuer::defaults(),
			)
		);

		add_settings_section(
			'fenton_digital_badges_issuer_section',
			__( 'Issuing organization', 'fenton-digital-badges' ),
			static function (): void {
				echo '<p>' . esc_html__( 'This organization appears on every Open Badge issued from this site.', 'fenton-digital-badges' ) . '</p>';
				echo '<p>' . esc_html__( 'Issuer JSON:', 'fenton-digital-badges' ) . ' <code>' . esc_html( Issuer::json_url() ) . '</code></p>';
			},
			'fenton-digital-badges-settings'
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
				'fenton_digital_badges_issuer_' . $key,
				$label,
				array( self::class, 'render_issuer_field' ),
				'fenton-digital-badges-settings',
				'fenton_digital_badges_issuer_section',
				array( 'key' => $key )
			);
		}
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

		$type = 'email' === $key ? 'email' : ( 'url' === $key ? 'url' : 'text' );

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
		<div class="db-logo-picker" data-db-logo-picker>
			<input
				type="hidden"
				name="<?php echo esc_attr( $input_name ); ?>"
				id="db-issuer-logo-id"
				value="<?php echo esc_attr( (string) $image_id ); ?>"
				data-db-logo-id
			/>
			<div class="db-logo-picker__preview" data-db-logo-preview <?php echo $has_image ? '' : 'hidden'; ?>>
				<?php if ( $has_image ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
				<?php endif; ?>
			</div>
			<p class="db-logo-picker__actions">
				<button type="button" class="button" data-db-logo-select>
					<?php echo $has_image ? esc_html__( 'Change logo', 'fenton-digital-badges' ) : esc_html__( 'Select logo', 'fenton-digital-badges' ); ?>
				</button>
				<button type="button" class="button-link-delete" data-db-logo-remove <?php echo $has_image ? '' : 'hidden'; ?>>
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
				settings_fields( 'fenton_digital_badges_issuer_group' );
				do_settings_sections( 'fenton-digital-badges-settings' );
				submit_button( __( 'Save issuer', 'fenton-digital-badges' ) );
				?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Find badges page', 'fenton-digital-badges' ); ?></h2>
			<p>
				<?php esc_html_e( 'Public lookup URL:', 'fenton-digital-badges' ); ?>
				<code><a href="<?php echo esc_url( home_url( '/badges/find/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/badges/find/' ) ); ?></a></code>
			</p>
			<p>
				<?php esc_html_e( 'Or use the shortcode', 'fenton-digital-badges' ); ?>
				<code>[fenton_digital_badges_find]</code>
			</p>
		</div>
		<?php
	}

	/**
	 * Register badge meta box.
	 */
	public static function register_meta_boxes(): void {
		add_meta_box(
			'db_badge_ob_meta',
			__( 'Open Badges', 'fenton-digital-badges' ),
			array( self::class, 'render_badge_meta_box' ),
			'db_badge',
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
		wp_nonce_field( 'db_badge_meta', 'db_badge_meta_nonce' );

		$criteria = get_post_meta( $post->ID, Badge_Class::META_CRITERIA_URL, true );
		$tags     = get_post_meta( $post->ID, Badge_Class::META_TAGS, true );
		$criteria = is_string( $criteria ) ? $criteria : '';
		$tags     = is_string( $tags ) ? $tags : '';
		?>
		<p>
			<label for="db_criteria_url"><strong><?php esc_html_e( 'Criteria URL', 'fenton-digital-badges' ); ?></strong></label><br />
			<input type="url" class="widefat" name="db_criteria_url" id="db_criteria_url" value="<?php echo esc_attr( $criteria ); ?>" />
			<span class="description"><?php esc_html_e( 'Leave blank to use this badge’s permalink.', 'fenton-digital-badges' ); ?></span>
		</p>
		<p>
			<label for="db_tags"><strong><?php esc_html_e( 'Tags', 'fenton-digital-badges' ); ?></strong></label><br />
			<input type="text" class="widefat" name="db_tags" id="db_tags" value="<?php echo esc_attr( $tags ); ?>" />
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
				'post_type'      => 'db_badge',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$issued = 0;
		$errors = array();
		$notice = get_transient( 'db_issue_notice_' . get_current_user_id() );

		if ( is_array( $notice ) ) {
			$issued = isset( $notice['issued'] ) ? absint( $notice['issued'] ) : 0;
			if ( isset( $notice['errors'] ) && is_array( $notice['errors'] ) ) {
				$errors = $notice['errors'];
			}
			delete_transient( 'db_issue_notice_' . get_current_user_id() );
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
						esc_url( admin_url( 'edit.php?post_type=db_badge&page=fenton-digital-badges-settings' ) )
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
				<input type="hidden" name="action" value="db_issue_badges" />
				<?php wp_nonce_field( 'db_issue_badges', 'db_issue_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="db_badge_id"><?php esc_html_e( 'Badge', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<select name="db_badge_id" id="db_badge_id" required>
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
						<th scope="row"><label for="db_csv_text"><?php esc_html_e( 'CSV data', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<textarea class="large-text code" rows="12" name="db_csv_text" id="db_csv_text" placeholder="email,name,evidence,expires"></textarea>
							<p class="description">
								<?php esc_html_e( 'Columns: email (required), name, evidence, expires (YYYY-MM-DD). Header row optional — you can paste email,name on its own. Email addresses are hashed and never stored.', 'fenton-digital-badges' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="db_csv_file"><?php esc_html_e( 'Or upload CSV', 'fenton-digital-badges' ); ?></label></th>
						<td>
							<input type="file" name="db_csv_file" id="db_csv_file" accept=".csv,text/csv" />
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

		check_admin_referer( 'db_issue_badges', 'db_issue_nonce' );

		$badge_id = isset( $_POST['db_badge_id'] ) ? absint( $_POST['db_badge_id'] ) : 0;
		$csv_text = isset( $_POST['db_csv_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['db_csv_text'] ) ) : '';

		$error = isset( $_FILES['db_csv_file']['error'] ) ? (int) $_FILES['db_csv_file']['error'] : UPLOAD_ERR_NO_FILE;
		$tmp   = isset( $_FILES['db_csv_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( (string) $_FILES['db_csv_file']['tmp_name'] ) )
			: '';

		if ( UPLOAD_ERR_OK === $error && '' !== $tmp && is_uploaded_file( $tmp ) ) {
			$contents = file_get_contents( $tmp );
			if ( is_string( $contents ) && '' !== trim( $contents ) ) {
				$csv_text = sanitize_textarea_field( $contents );
			}
		}

		$result = Csv_Issuer::issue_from_csv( $badge_id, $csv_text );

		set_transient(
			'db_issue_notice_' . get_current_user_id(),
			array(
				'issued' => $result['issued'],
				'errors' => $result['errors'],
			),
			MINUTE_IN_SECONDS
		);

		$redirect = add_query_arg(
			array(
				'post_type' => 'db_badge',
				'page'      => 'fenton-digital-badges-issue',
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
		$badge_id    = isset( $_GET['badge_id'] ) ? absint( $_GET['badge_id'] ) : 0;
		$result      = Assertion_Repository::list_assertions( $page, 20, $badge_id );
		$total_pages = (int) ceil( $result['total'] / 20 );

		$notice = get_transient( 'db_assertion_notice_' . get_current_user_id() );
		if ( is_string( $notice ) && '' !== $notice ) {
			delete_transient( 'db_assertion_notice_' . get_current_user_id() );
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
						<tr><td colspan="6"><?php esc_html_e( 'No assertions yet.', 'fenton-digital-badges' ); ?></td></tr>
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
											<input type="hidden" name="action" value="db_revoke_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'db_revoke_assertion_' . $row->uid, 'db_revoke_nonce' ); ?>
											<button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Revoke this assertion?', 'fenton-digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Revoke', 'fenton-digital-badges' ); ?>
											</button>
										</form>
									<?php else : ?>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="db_unrevoke_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'db_unrevoke_assertion_' . $row->uid, 'db_unrevoke_nonce' ); ?>
											<button type="submit" class="button-link" onclick="return confirm('<?php echo esc_js( __( 'Restore this assertion?', 'fenton-digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Restore', 'fenton-digital-badges' ); ?>
											</button>
										</form>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="db_delete_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'db_delete_assertion_' . $row->uid, 'db_delete_nonce' ); ?>
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

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'current'   => $page,
									'total'     => $total_pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								)
							) ?: ''
						);
						?>
					</div>
				</div>
			<?php endif; ?>
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
		check_admin_referer( 'db_revoke_assertion_' . $uid, 'db_revoke_nonce' );

		if ( '' !== $uid ) {
			Assertion_Repository::revoke( $uid, __( 'Revoked by administrator', 'fenton-digital-badges' ) );
			set_transient( 'db_assertion_notice_' . get_current_user_id(), 'revoked', MINUTE_IN_SECONDS );
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
		check_admin_referer( 'db_unrevoke_assertion_' . $uid, 'db_unrevoke_nonce' );

		if ( '' !== $uid && Assertion_Repository::unrevoke( $uid ) ) {
			set_transient( 'db_assertion_notice_' . get_current_user_id(), 'unrevoked', MINUTE_IN_SECONDS );
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
		check_admin_referer( 'db_delete_assertion_' . $uid, 'db_delete_nonce' );

		if ( '' !== $uid && Assertion_Repository::delete_revoked( $uid ) ) {
			set_transient( 'db_assertion_notice_' . get_current_user_id(), 'deleted', MINUTE_IN_SECONDS );
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
					'post_type' => 'db_badge',
					'page'      => 'fenton-digital-badges-assertions',
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

		$is_badge_screen = $screen && 'db_badge' === $screen->post_type;
		$is_plugin_page  = false !== strpos( $hook_suffix, 'fenton-digital-badges' );
		$is_settings     = false !== strpos( $hook_suffix, 'fenton-digital-badges-settings' );

		if ( ! $is_badge_screen && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'fenton-digital-badges-admin',
			FENTON_DIGITAL_BADGES_URL . 'admin/css/admin.css',
			array(),
			FENTON_DIGITAL_BADGES_VERSION
		);

		$script_deps = array();

		if ( $is_settings ) {
			wp_enqueue_media();
			$script_deps[] = 'jquery';
		}

		wp_enqueue_script(
			'fenton-digital-badges-admin',
			FENTON_DIGITAL_BADGES_URL . 'admin/js/admin.js',
			$script_deps,
			FENTON_DIGITAL_BADGES_VERSION,
			true
		);

		if ( $is_settings ) {
			wp_localize_script(
				'fenton-digital-badges-admin',
				'digitalBadgesAdmin',
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
