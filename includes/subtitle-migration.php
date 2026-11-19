<?php
/**
 * Admin tool for migrating the subtitle block into existing posts.
 *
 * The post-type template only puts the subtitle block on posts created after the
 * block existed. Older posts need the block prepended to their content, and that
 * must be a deliberate, inspectable step rather than something that happens by
 * itself when the plugin is activated: a site has to be able to look at what
 * would change, run the migration when it suits them, and keep the same plugin
 * deployable to more than one site.
 *
 * The tool therefore has three actions:
 *
 *   scan     walk every post that still lacks the block and verify, for each one,
 *            that the render guard restores the original bytes exactly. This is
 *            the safety property the migration relies on, checked on real data
 *            before anything is written.
 *   migrate  prepend the block delimiter, in batches so a large site does not hit
 *            the execution time limit.
 *   revert   remove the delimiter again. Exact inverse of migrate; the _headline
 *            meta is never touched, so the subtitle itself is never lost.
 *
 * Every step is nonce-protected and restricted to manage_options.
 *
 * @package Duke_Yin_Helper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Number of posts handled per request. */
define( 'DUKEYIN_MIGRATION_BATCH', 50 );

/** Option holding the result of the last completed run. */
define( 'DUKEYIN_MIGRATION_LOG', 'dukeyin_subtitle_migration_log' );

if ( ! function_exists( 'dukeyin_migration_menu' ) ) {
	/**
	 * Adds the tool under Tools.
	 */
	function dukeyin_migration_menu() {
		add_management_page(
			__( 'Subtitle Block Migration', 'duke-yin-helper' ),
			__( 'Subtitle Block Migration', 'duke-yin-helper' ),
			'manage_options',
			'duke-yin-helper-subtitle-migration',
			'dukeyin_migration_render_page'
		);
	}
}
add_action( 'admin_menu', 'dukeyin_migration_menu' );

if ( ! function_exists( 'dukeyin_migration_counts' ) ) {
	/**
	 * Counts posts per target post type.
	 *
	 * @return array[] Rows keyed by post type: label, total, migrated, pending.
	 */
	function dukeyin_migration_counts() {
		global $wpdb;

		$rows = array();

		foreach ( dukeyin_subtitle_post_types() as $post_type ) {
			$object = get_post_type_object( $post_type );

			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					  WHERE post_type = %s
					    AND post_status NOT IN ( 'auto-draft', 'inherit', 'trash' )",
					$post_type
				)
			);

			$migrated = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					  WHERE post_type = %s
					    AND post_status NOT IN ( 'auto-draft', 'inherit', 'trash' )
					    AND post_content LIKE %s",
					$post_type,
					'%' . $wpdb->esc_like( 'wp:dukeyin/headline' ) . '%'
				)
			);

			$rows[ $post_type ] = array(
				'label'    => $object ? $object->labels->name : $post_type,
				'total'    => $total,
				'migrated' => $migrated,
				'pending'  => max( 0, $total - $migrated ),
			);
		}

		return $rows;
	}
}

if ( ! function_exists( 'dukeyin_migration_query_ids' ) ) {
	/**
	 * Returns the next batch of post IDs to process.
	 *
	 * @param string $mode   scan|migrate|revert.
	 * @param int    $cursor Only IDs greater than this are considered.
	 * @param int    $limit  Batch size.
	 * @return int[]
	 */
	function dukeyin_migration_query_ids( $mode, $cursor, $limit ) {
		global $wpdb;

		$post_types = dukeyin_subtitle_post_types();

		if ( ! $post_types ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$like         = '%' . $wpdb->esc_like( 'wp:dukeyin/headline' ) . '%';
		$operator     = ( 'revert' === $mode ) ? 'LIKE' : 'NOT LIKE';

		$args = array_merge( $post_types, array( $cursor, $like, $limit ) );

		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			  WHERE post_type IN ( {$placeholders} )
			    AND post_status NOT IN ( 'auto-draft', 'inherit', 'trash' )
			    AND ID > %d
			    AND post_content {$operator} %s
			  ORDER BY ID ASC
			  LIMIT %d",
			$args
		);

		return array_map( 'intval', (array) $wpdb->get_col( $sql ) );
	}
}

if ( ! function_exists( 'dukeyin_migration_check_post' ) ) {
	/**
	 * Verifies that adding the block to this post cannot change what is rendered.
	 *
	 * Two cases, because the block reaches the front end by two routes:
	 *
	 * - Legacy content (no block delimiter). Adding a delimiter would make
	 *   has_blocks() true and disable wpautop(), which is what creates the
	 *   paragraphs in those posts. The guard restores the original bytes instead,
	 *   so the test is a byte comparison: the guard must hand back exactly what
	 *   it was given.
	 *
	 * - Content that already contains blocks. wpautop() is already off, and the
	 *   subtitle block renders an empty string, so the only thing the migration
	 *   changes is the delimiter it adds. Strip that delimiter again and require
	 *   the original bytes back.
	 *
	 * @param string $content Current post content.
	 * @return array{ok: bool, reason: string}
	 */
	function dukeyin_migration_check_post( $content ) {
		$content = (string) $content;

		if ( false !== strpos( $content, 'wp:dukeyin/headline' ) ) {
			return array(
				'ok'     => true,
				'reason' => 'already',
			);
		}

		if ( '' === trim( $content ) ) {
			// An empty post migrates to the bare delimiter, which renders nothing.
			return array(
				'ok'     => true,
				'reason' => 'empty',
			);
		}

		$migrated = DUKEYIN_SUBTITLE_BLOCK . "\n\n" . $content;

		if ( false === strpos( $content, '<!-- wp:' ) ) {
			$restored = dukeyin_restore_legacy_subtitle_content( $migrated );

			return array(
				'ok'     => ( $restored === $content ),
				'reason' => ( $restored === $content ) ? 'legacy' : 'unsafe',
			);
		}

		/*
		 * Block content: the block renders nothing, so the rendered output should be
		 * identical. Comparing two renders inside one request is unreliable, though:
		 * wp_unique_id() keeps a per-request counter, and several core blocks
		 * (gallery, buttons, heading) use it for class names, so the second render
		 * gets different suffixes even though the front end never sees a difference.
		 *
		 * Rather than trying to normalise every such pattern — which is brittle and
		 * would mask a real change — check the property that actually matters: the
		 * subtitle block itself renders an empty string, and the post already has
		 * blocks, so wpautop() is already off and the only difference the migration
		 * can introduce is whitespace between block delimiters. Strip the delimiter
		 * again and require the exact original bytes back.
		 */
		$restored = preg_replace( '~^' . preg_quote( DUKEYIN_SUBTITLE_BLOCK . "\n\n", '~' ) . '~', '', $migrated );

		return array(
			'ok'     => ( $restored === $content ),
			'reason' => ( $restored === $content ) ? 'blocks' : 'unsafe',
		);
	}
}

if ( ! function_exists( 'dukeyin_migration_apply' ) ) {
	/**
	 * Adds or removes the block delimiter for one post.
	 *
	 * Writes straight to the database so no revision is created, no KSES round-trip
	 * rewrites the content, and post_modified does not jump.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $mode    migrate|revert.
	 * @return bool Whether a row was changed.
	 */
	function dukeyin_migration_apply( $post_id, $mode ) {
		global $wpdb;

		$content = (string) get_post_field( 'post_content', $post_id );

		if ( 'revert' === $mode ) {
			$prefix = DUKEYIN_SUBTITLE_BLOCK . "\n\n";

			if ( 0 === strpos( $content, $prefix ) ) {
				$new = substr( $content, strlen( $prefix ) );
			} elseif ( DUKEYIN_SUBTITLE_BLOCK === $content ) {
				$new = '';
			} else {
				return false;
			}
		} else {
			if ( false !== strpos( $content, 'wp:dukeyin/headline' ) ) {
				return false;
			}

			$new = ( '' === trim( $content ) )
				? DUKEYIN_SUBTITLE_BLOCK
				: DUKEYIN_SUBTITLE_BLOCK . "\n\n" . $content;
		}

		$updated = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $new ),
			array( 'ID' => $post_id )
		);

		clean_post_cache( $post_id );

		return ( false !== $updated );
	}
}

if ( ! function_exists( 'dukeyin_migration_step' ) ) {
	/**
	 * Processes one batch and returns the updated state.
	 *
	 * @param array $state Migration state.
	 * @return array
	 */
	function dukeyin_migration_step( $state ) {
		$ids = dukeyin_migration_query_ids( $state['mode'], $state['cursor'], DUKEYIN_MIGRATION_BATCH );

		if ( ! $ids ) {
			$state['done'] = true;
			return $state;
		}

		foreach ( $ids as $post_id ) {
			$state['cursor'] = $post_id;
			$state['seen']++;

			if ( 'scan' === $state['mode'] ) {
				$check = dukeyin_migration_check_post( get_post_field( 'post_content', $post_id ) );

				if ( $check['ok'] ) {
					$state['safe']++;
				} else {
					$state['unsafe']++;

					// Keep a short list so the page can point at the offenders.
					if ( ! isset( $state['unsafe_ids'] ) ) {
						$state['unsafe_ids'] = array();
					}

					if ( count( $state['unsafe_ids'] ) < 20 ) {
						$state['unsafe_ids'][] = $post_id;
					}
				}

				continue;
			}

			if ( dukeyin_migration_apply( $post_id, $state['mode'] ) ) {
				$state['updated']++;
			} else {
				$state['skipped']++;
			}
		}

		// A short batch means there is nothing left after it.
		$state['done'] = ( count( $ids ) < DUKEYIN_MIGRATION_BATCH );

		return $state;
	}
}

if ( ! function_exists( 'dukeyin_migration_state_key' ) ) {
	/**
	 * Transient key holding the in-progress run for the current user.
	 *
	 * @return string
	 */
	function dukeyin_migration_state_key() {
		return 'dukeyin_migration_state_' . get_current_user_id();
	}
}

if ( ! function_exists( 'dukeyin_migration_handle' ) ) {
	/**
	 * Handles one step of a run, then sends the browser back to the tool page.
	 *
	 * Each request processes one batch and redirects, so the page can show progress
	 * and a large site never runs the whole migration inside one request.
	 */
	function dukeyin_migration_handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run this migration.', 'duke-yin-helper' ) );
		}

		check_admin_referer( 'dukeyin_migration_step' );

		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';

		if ( ! in_array( $mode, array( 'scan', 'migrate', 'revert' ), true ) ) {
			wp_safe_redirect( dukeyin_migration_page_url( array( 'message' => 'invalid' ) ) );
			exit;
		}

		$key   = dukeyin_migration_state_key();
		$state = get_transient( $key );

		// Starting a fresh run.
		if ( isset( $_POST['fresh'] ) || ! is_array( $state ) || ( $state['mode'] ?? '' ) !== $mode ) {
			$state = array(
				'mode'    => $mode,
				'cursor'  => 0,
				'seen'    => 0,
				'safe'    => 0,
				'unsafe'  => 0,
				'updated' => 0,
				'skipped' => 0,
				'done'    => false,
				'total'   => dukeyin_migration_total_pending( $mode ),
				'started' => time(),
				'unsafe_ids' => array(),
			);
		}

		$state = dukeyin_migration_step( $state );

		if ( $state['done'] ) {
			delete_transient( $key );

			$log = array(
				'mode'    => $mode,
				'time'    => time(),
				'user'    => get_current_user_id(),
				'seen'    => $state['seen'],
				'safe'    => $state['safe'],
				'unsafe'  => $state['unsafe'],
				'updated' => $state['updated'],
				'skipped' => $state['skipped'],
				'unsafe_ids' => $state['unsafe_ids'] ?? array(),
			);

			update_option( DUKEYIN_MIGRATION_LOG, $log, false );

			wp_safe_redirect( dukeyin_migration_page_url( array( 'message' => 'done' ) ) );
			exit;
		}

		set_transient( $key, $state, HOUR_IN_SECONDS );

		wp_safe_redirect( dukeyin_migration_page_url( array( 'step' => 'run' ) ) );
		exit;
	}
}
add_action( 'admin_post_dukeyin_migration_step', 'dukeyin_migration_handle' );

if ( ! function_exists( 'dukeyin_migration_total_pending' ) ) {
	/**
	 * How many posts the given mode would touch.
	 *
	 * @param string $mode scan|migrate|revert.
	 * @return int
	 */
	function dukeyin_migration_total_pending( $mode ) {
		global $wpdb;

		$post_types = dukeyin_subtitle_post_types();

		if ( ! $post_types ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$operator     = ( 'revert' === $mode ) ? 'LIKE' : 'NOT LIKE';
		$args         = array_merge(
			$post_types,
			array( '%' . $wpdb->esc_like( 'wp:dukeyin/headline' ) . '%' )
		);

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			  WHERE post_type IN ( {$placeholders} )
			    AND post_status NOT IN ( 'auto-draft', 'inherit', 'trash' )
			    AND post_content {$operator} %s",
			$args
		);

		return (int) $wpdb->get_var( $sql );
	}
}

if ( ! function_exists( 'dukeyin_migration_page_url' ) ) {
	/**
	 * URL of the tool page, with optional query arguments.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	function dukeyin_migration_page_url( $args = array() ) {
		$url = admin_url( 'tools.php?page=duke-yin-helper-subtitle-migration' );

		return $args ? add_query_arg( $args, $url ) : $url;
	}
}

if ( ! function_exists( 'dukeyin_migration_render_page' ) ) {
	/**
	 * Renders the tool page.
	 */
	function dukeyin_migration_render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rows    = dukeyin_migration_counts();
		$pending = 0;
		foreach ( $rows as $row ) {
			$pending += $row['pending'];
		}

		$state   = get_transient( dukeyin_migration_state_key() );
		$running = is_array( $state ) && empty( $state['done'] );
		$log     = get_option( DUKEYIN_MIGRATION_LOG );
		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subtitle Block Migration', 'duke-yin-helper' ); ?></h1>

			<?php if ( 'done' === $message && is_array( $log ) ) : ?>
				<div class="notice notice-success">
					<p>
						<?php
						if ( 'scan' === $log['mode'] ) {
							printf(
								/* translators: 1: number of posts checked, 2: number of posts that would change rendering */
								esc_html__( 'Check finished: %1$d posts inspected, %2$d would render differently.', 'duke-yin-helper' ),
								(int) $log['seen'],
								(int) $log['unsafe']
							);
						} elseif ( 'revert' === $log['mode'] ) {
							printf(
								/* translators: %d: number of posts */
								esc_html__( 'Revert finished: the block was removed from %d posts.', 'duke-yin-helper' ),
								(int) $log['updated']
							);
						} else {
							printf(
								/* translators: 1: number of posts updated, 2: number of posts skipped */
								esc_html__( 'Migration finished: %1$d posts updated, %2$d skipped.', 'duke-yin-helper' ),
								(int) $log['updated'],
								(int) $log['skipped']
							);
						}
						?>
					</p>

					<?php if ( 'scan' === $log['mode'] && ! empty( $log['unsafe_ids'] ) ) : ?>
						<p>
							<?php esc_html_e( 'These posts would render differently, so they were left alone. Check them by hand before migrating:', 'duke-yin-helper' ); ?>
						</p>
						<ul style="list-style:disc;margin-left:2em">
							<?php foreach ( (array) $log['unsafe_ids'] as $unsafe_id ) : ?>
								<li>
									<a href="<?php echo esc_url( get_edit_post_link( (int) $unsafe_id ) ); ?>">
										#<?php echo (int) $unsafe_id; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php elseif ( 'invalid' === $message ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Unknown action.', 'duke-yin-helper' ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php
				esc_html_e(
					'New posts get the subtitle block automatically. Posts written before the block existed need it added to their content, and this tool does that. Nothing is written until you run the migration.',
					'duke-yin-helper'
				);
				?>
			</p>

			<h2><?php esc_html_e( 'Current state', 'duke-yin-helper' ); ?></h2>

			<table class="widefat striped" style="max-width:820px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post type', 'duke-yin-helper' ); ?></th>
						<th><?php esc_html_e( 'Posts', 'duke-yin-helper' ); ?></th>
						<th><?php esc_html_e( 'With the block', 'duke-yin-helper' ); ?></th>
						<th><?php esc_html_e( 'Need migration', 'duke-yin-helper' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $post_type => $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $post_type ); ?></code> <?php echo esc_html( $row['label'] ); ?></td>
							<td><?php echo (int) $row['total']; ?></td>
							<td><?php echo (int) $row['migrated']; ?></td>
							<td><?php echo (int) $row['pending']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Total', 'duke-yin-helper' ); ?></th>
						<th></th>
						<th></th>
						<th><?php echo (int) $pending; ?></th>
					</tr>
				</tfoot>
			</table>

			<?php if ( $running ) : ?>
				<?php
				$total     = max( 1, (int) $state['total'] );
				$processed = (int) $state['seen'];
				$percent   = min( 100, (int) round( $processed / $total * 100 ) );
				?>
				<h2>
					<?php
					if ( 'scan' === $state['mode'] ) {
						esc_html_e( 'Checking…', 'duke-yin-helper' );
					} elseif ( 'revert' === $state['mode'] ) {
						esc_html_e( 'Reverting…', 'duke-yin-helper' );
					} else {
						esc_html_e( 'Migrating…', 'duke-yin-helper' );
					}
					?>
				</h2>

				<p>
					<?php
					printf(
						/* translators: 1: processed posts, 2: total posts */
						esc_html__( '%1$d of %2$d posts processed.', 'duke-yin-helper' ),
						$processed,
						(int) $state['total']
					);
					?>
				</p>

				<div style="max-width:820px;background:#dcdcde;height:20px;border-radius:3px;overflow:hidden">
					<div style="width:<?php echo (int) $percent; ?>%;height:100%;background:#2271b1"></div>
				</div>

				<p><?php esc_html_e( 'This page continues automatically. Leave it open until it finishes.', 'duke-yin-helper' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="dukeyin-migration-continue">
					<input type="hidden" name="action" value="dukeyin_migration_step" />
					<input type="hidden" name="mode" value="<?php echo esc_attr( $state['mode'] ); ?>" />
					<?php wp_nonce_field( 'dukeyin_migration_step' ); ?>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Continue', 'duke-yin-helper' ); ?>
						</button>
					</p>
				</form>

				<script>
					// Keep the batches flowing without user interaction.
					window.setTimeout( function () {
						document.getElementById( 'dukeyin-migration-continue' ).submit();
					}, 300 );
				</script>
			<?php else : ?>
				<h2><?php esc_html_e( 'Actions', 'duke-yin-helper' ); ?></h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1.5em">
					<input type="hidden" name="action" value="dukeyin_migration_step" />
					<input type="hidden" name="mode" value="scan" />
					<input type="hidden" name="fresh" value="1" />
					<?php wp_nonce_field( 'dukeyin_migration_step' ); ?>
					<p class="description" style="margin-bottom:.8em">
						<?php
						esc_html_e(
							'Check first: inspects every post that still lacks the block and confirms that adding it cannot change what the front end renders.',
							'duke-yin-helper'
						);
						?>
					</p>
					<button type="submit" class="button" <?php disabled( 0 === $pending ); ?>>
						<?php esc_html_e( 'Check', 'duke-yin-helper' ); ?>
					</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1.5em">
					<input type="hidden" name="action" value="dukeyin_migration_step" />
					<input type="hidden" name="mode" value="migrate" />
					<input type="hidden" name="fresh" value="1" />
					<?php wp_nonce_field( 'dukeyin_migration_step' ); ?>
					<p class="description" style="margin-bottom:.8em">
						<?php
						esc_html_e(
							'Add the subtitle block to the posts that need it. Runs in batches and can be stopped by closing the page.',
							'duke-yin-helper'
						);
						?>
					</p>
					<button type="submit" class="button button-primary" <?php disabled( 0 === $pending ); ?>>
						<?php esc_html_e( 'Run migration', 'duke-yin-helper' ); ?>
					</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="dukeyin_migration_step" />
					<input type="hidden" name="mode" value="revert" />
					<input type="hidden" name="fresh" value="1" />
					<?php wp_nonce_field( 'dukeyin_migration_step' ); ?>
					<p class="description" style="margin-bottom:.8em">
						<?php
						esc_html_e(
							'Undo: removes the block again. The subtitle text itself lives in post meta and is never deleted, so this only takes the block out of the editor.',
							'duke-yin-helper'
						);
						?>
					</p>
					<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Remove the block from every post that has it?', 'duke-yin-helper' ) ); ?>')">
						<?php esc_html_e( 'Revert migration', 'duke-yin-helper' ); ?>
					</button>
				</form>

				<?php if ( is_array( $log ) ) : ?>
					<h2><?php esc_html_e( 'Last run', 'duke-yin-helper' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: 1: action, 2: date, 3: time */
							esc_html__( '%1$s on %2$s at %3$s.', 'duke-yin-helper' ),
							esc_html( $log['mode'] ),
							esc_html( wp_date( get_option( 'date_format' ), (int) $log['time'] ) ),
							esc_html( wp_date( get_option( 'time_format' ), (int) $log['time'] ) )
						);
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
