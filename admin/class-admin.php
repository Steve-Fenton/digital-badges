<?php
/**
 * Admin-facing functionality.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

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
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Issue Badges', 'digital-badges' ),
			__( 'Issue Badges', 'digital-badges' ),
			'manage_options',
			'digital-badges-issue',
			array( self::class, 'render_issue_page' )
		);

		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Assertions', 'digital-badges' ),
			__( 'Assertions', 'digital-badges' ),
			'manage_options',
			'digital-badges-assertions',
			array( self::class, 'render_assertions_page' )
		);

		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Settings', 'digital-badges' ),
			__( 'Settings', 'digital-badges' ),
			'manage_options',
			'digital-badges-settings',
			array( self::class, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings API fields for the issuer.
	 */
	public static function register_settings(): void {
		register_setting(
			'digital_badges_issuer_group',
			Issuer::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Issuer::class, 'sanitize' ),
				'default'           => Issuer::defaults(),
			)
		);

		add_settings_section(
			'digital_badges_issuer_section',
			__( 'Issuing organization', 'digital-badges' ),
			static function (): void {
				echo '<p>' . esc_html__( 'This organization appears on every Open Badge issued from this site.', 'digital-badges' ) . '</p>';
				echo '<p>' . esc_html__( 'Issuer JSON:', 'digital-badges' ) . ' <code>' . esc_html( Issuer::json_url() ) . '</code></p>';
			},
			'digital-badges-settings'
		);

		$fields = array(
			'name'                     => __( 'Name', 'digital-badges' ),
			'url'                      => __( 'Website URL', 'digital-badges' ),
			'email'                    => __( 'Contact email', 'digital-badges' ),
			'description'              => __( 'Description', 'digital-badges' ),
			'image_id'                 => __( 'Logo', 'digital-badges' ),
			'linkedin_organization_id' => __( 'LinkedIn organization ID', 'digital-badges' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'digital_badges_issuer_' . $key,
				$label,
				array( self::class, 'render_issuer_field' ),
				'digital-badges-settings',
				'digital_badges_issuer_section',
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
			echo '<p class="description">' . esc_html__( 'Optional numeric LinkedIn company ID for Add to Profile links.', 'digital-badges' ) . '</p>';
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
					<?php echo $has_image ? esc_html__( 'Change logo', 'digital-badges' ) : esc_html__( 'Select logo', 'digital-badges' ); ?>
				</button>
				<button type="button" class="button-link-delete" data-db-logo-remove <?php echo $has_image ? '' : 'hidden'; ?>>
					<?php esc_html_e( 'Remove', 'digital-badges' ); ?>
				</button>
			</p>
			<p class="description"><?php esc_html_e( 'Choose an image from the media library for the issuer logo.', 'digital-badges' ); ?></p>
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
				settings_fields( 'digital_badges_issuer_group' );
				do_settings_sections( 'digital-badges-settings' );
				submit_button( __( 'Save issuer', 'digital-badges' ) );
				?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Find badges page', 'digital-badges' ); ?></h2>
			<p>
				<?php esc_html_e( 'Public lookup URL:', 'digital-badges' ); ?>
				<code><?php echo esc_html( home_url( '/badges/find/' ) ); ?></code>
			</p>
			<p>
				<?php esc_html_e( 'Or use the shortcode', 'digital-badges' ); ?>
				<code>[digital_badges_find]</code>
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
			__( 'Open Badges', 'digital-badges' ),
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
			<label for="db_criteria_url"><strong><?php esc_html_e( 'Criteria URL', 'digital-badges' ); ?></strong></label><br />
			<input type="url" class="widefat" name="db_criteria_url" id="db_criteria_url" value="<?php echo esc_attr( $criteria ); ?>" />
			<span class="description"><?php esc_html_e( 'Leave blank to use this badge’s permalink.', 'digital-badges' ); ?></span>
		</p>
		<p>
			<label for="db_tags"><strong><?php esc_html_e( 'Tags', 'digital-badges' ); ?></strong></label><br />
			<input type="text" class="widefat" name="db_tags" id="db_tags" value="<?php echo esc_attr( $tags ); ?>" />
			<span class="description"><?php esc_html_e( 'Comma-separated tags.', 'digital-badges' ); ?></span>
		</p>
		<?php if ( 'publish' === $post->post_status ) : ?>
			<p>
				<strong><?php esc_html_e( 'BadgeClass JSON', 'digital-badges' ); ?></strong><br />
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

		$issued = isset( $_GET['issued'] ) ? absint( $_GET['issued'] ) : 0;
		$errors = array();

		if ( isset( $_GET['db_errors'] ) ) {
			$raw = get_transient( 'db_issue_errors_' . get_current_user_id() );
			if ( is_array( $raw ) ) {
				$errors = $raw;
			}
			delete_transient( 'db_issue_errors_' . get_current_user_id() );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! Issuer::is_configured() ) : ?>
				<div class="notice notice-warning"><p>
					<?php
					printf(
						/* translators: %s: settings URL */
						wp_kses_post( __( 'Configure your <a href="%s">issuing organization</a> before issuing badges.', 'digital-badges' ) ),
						esc_url( admin_url( 'edit.php?post_type=db_badge&page=digital-badges-settings' ) )
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $issued > 0 ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					printf(
						/* translators: %d: number of assertions created */
						esc_html( _n( '%d badge issued.', '%d badges issued.', $issued, 'digital-badges' ) ),
						$issued
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $errors ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Some rows could not be issued:', 'digital-badges' ); ?></p>
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
						<th scope="row"><label for="db_badge_id"><?php esc_html_e( 'Badge', 'digital-badges' ); ?></label></th>
						<td>
							<select name="db_badge_id" id="db_badge_id" required>
								<option value=""><?php esc_html_e( 'Select a badge…', 'digital-badges' ); ?></option>
								<?php foreach ( $badges as $badge ) : ?>
									<?php
									$issuable = Badge_Class::is_issuable( (int) $badge->ID );
									$label    = get_the_title( $badge );
									if ( ! $issuable ) {
										$label .= ' ' . __( '(missing image or criteria)', 'digital-badges' );
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
						<th scope="row"><label for="db_csv_text"><?php esc_html_e( 'CSV data', 'digital-badges' ); ?></label></th>
						<td>
							<textarea class="large-text code" rows="12" name="db_csv_text" id="db_csv_text" placeholder="email,name,evidence,expires"></textarea>
							<p class="description">
								<?php esc_html_e( 'Columns: email (required), name, evidence, expires (YYYY-MM-DD). Header row optional — you can paste email,name on its own. Email addresses are hashed and never stored.', 'digital-badges' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="db_csv_file"><?php esc_html_e( 'Or upload CSV', 'digital-badges' ); ?></label></th>
						<td>
							<input type="file" name="db_csv_file" id="db_csv_file" accept=".csv,text/csv" />
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Issue badges', 'digital-badges' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle CSV issue form submission.
	 */
	public static function handle_issue_badges(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'digital-badges' ) );
		}

		check_admin_referer( 'db_issue_badges', 'db_issue_nonce' );

		$badge_id = isset( $_POST['db_badge_id'] ) ? absint( $_POST['db_badge_id'] ) : 0;
		$csv_text = isset( $_POST['db_csv_text'] ) ? (string) wp_unslash( $_POST['db_csv_text'] ) : '';

		if ( isset( $_FILES['db_csv_file'] ) && is_array( $_FILES['db_csv_file'] ) ) {
			$file = $_FILES['db_csv_file'];
			$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
			$tmp   = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

			if ( UPLOAD_ERR_OK === $error && '' !== $tmp && is_uploaded_file( $tmp ) ) {
				$contents = file_get_contents( $tmp );
				if ( is_string( $contents ) && '' !== trim( $contents ) ) {
					$csv_text = $contents;
				}
			}
		}

		$result = Csv_Issuer::issue_from_csv( $badge_id, $csv_text );

		if ( $result['errors'] ) {
			set_transient( 'db_issue_errors_' . get_current_user_id(), $result['errors'], MINUTE_IN_SECONDS );
		}

		$redirect = add_query_arg(
			array(
				'post_type' => 'db_badge',
				'page'      => 'digital-badges-issue',
				'issued'    => $result['issued'],
				'db_errors' => $result['errors'] ? 1 : 0,
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

		$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$badge_id = isset( $_GET['badge_id'] ) ? absint( $_GET['badge_id'] ) : 0;
		$result   = Assertion_Repository::list_assertions( $page, 20, $badge_id );
		$total_pages = (int) ceil( $result['total'] / 20 );

		$revoked = isset( $_GET['revoked'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['revoked'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( '1' === $revoked ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Assertion revoked.', 'digital-badges' ); ?></p></div>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'UID', 'digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Badge', 'digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Recipient name', 'digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Issued', 'digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Status', 'digital-badges' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'digital-badges' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( array() === $result['items'] ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No assertions yet.', 'digital-badges' ); ?></td></tr>
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
										? esc_html__( 'Revoked', 'digital-badges' )
										: esc_html__( 'Active', 'digital-badges' );
									?>
								</td>
								<td>
									<a href="<?php echo esc_url( Assertion_Repository::attestation_url( (string) $row->uid ) ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'View', 'digital-badges' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( Assertion_Repository::json_url( (string) $row->uid ) ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'JSON', 'digital-badges' ); ?>
									</a>
									<?php if ( ! $is_revoked ) : ?>
										|
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="db_revoke_assertion" />
											<input type="hidden" name="uid" value="<?php echo esc_attr( (string) $row->uid ); ?>" />
											<?php wp_nonce_field( 'db_revoke_assertion_' . $row->uid, 'db_revoke_nonce' ); ?>
											<button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Revoke this assertion?', 'digital-badges' ) ); ?>');">
												<?php esc_html_e( 'Revoke', 'digital-badges' ); ?>
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
			wp_die( esc_html__( 'Forbidden.', 'digital-badges' ) );
		}

		$uid = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['uid'] ) ) : '';
		check_admin_referer( 'db_revoke_assertion_' . $uid, 'db_revoke_nonce' );

		if ( '' !== $uid ) {
			Assertion_Repository::revoke( $uid, __( 'Revoked by administrator', 'digital-badges' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'db_badge',
					'page'      => 'digital-badges-assertions',
					'revoked'   => '1',
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
		$is_plugin_page  = false !== strpos( $hook_suffix, 'digital-badges' );
		$is_settings     = false !== strpos( $hook_suffix, 'digital-badges-settings' );

		if ( ! $is_badge_screen && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'digital-badges-admin',
			DIGITAL_BADGES_URL . 'admin/css/admin.css',
			array(),
			DIGITAL_BADGES_VERSION
		);

		$script_deps = array();

		if ( $is_settings ) {
			wp_enqueue_media();
			$script_deps[] = 'jquery';
		}

		wp_enqueue_script(
			'digital-badges-admin',
			DIGITAL_BADGES_URL . 'admin/js/admin.js',
			$script_deps,
			DIGITAL_BADGES_VERSION,
			true
		);

		if ( $is_settings ) {
			wp_localize_script(
				'digital-badges-admin',
				'digitalBadgesAdmin',
				array(
					'selectLogoTitle'  => __( 'Select issuer logo', 'digital-badges' ),
					'selectLogoButton' => __( 'Use this logo', 'digital-badges' ),
					'changeLogo'       => __( 'Change logo', 'digital-badges' ),
					'selectLogo'       => __( 'Select logo', 'digital-badges' ),
				)
			);
		}
	}
}
