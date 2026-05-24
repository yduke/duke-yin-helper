<?php
/*
Plugin Name: Blog To Keep Minutes Sync
Description: Sync posts to keep minutes micro blog site.
Version: 1.1
*/

$dukeyin_options = get_site_option( 'options-page', true, false );
$app_password    = isset( $dukeyin_options['km-app-password'] ) ? $dukeyin_options['km-app-password'] : null;
$km_url          = isset( $dukeyin_options['km-url'] )          ? $dukeyin_options['km-url']          : null;
$username        = isset( $dukeyin_options['km_username'] )     ? $dukeyin_options['km_username']     : null;

if ( $app_password && $km_url && $username ) {

    // -------------------------------------------------------------------------
    // 需要同步的文章类型
    // -------------------------------------------------------------------------
    $post_types = [
        'post',
        'film_review',
        'tvshow_review',
        'book_review',
        'game_review',
        'product_review',
        'portfolio',
        'poem',
    ];

    // publish_{type} 在文章首次发布（draft→publish）以及
    // 已发布文章再次保存（publish→publish）时都会触发，因此
    // 同一个 hook 可以同时处理"新建同步"和"更新同步"两种情况。
    foreach ( $post_types as $type ) {
        add_action( "publish_{$type}", 'btm_schedule_sync', 10, 2 );
    }

    /**
     * 调度同步任务。
     *
     * - 新文章：尚未设置 _synced_to_keepmins → 按原逻辑延迟600秒执行首次同步。
     * - 已同步文章被修改：已设置 _synced_to_keepmins → 延迟600秒调度一次更新同步。
     *   用 wp_next_scheduled 防止同一篇文章短时间内重复入队。
     */
    function btm_schedule_sync( $post_id, $post ) {
        $delay_seconds    = 600; // 同步延迟时间（秒）

        // 避免修订版本触发
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // 仅处理已发布状态
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        $already_synced = get_post_meta( $post_id, '_synced_to_keepmins', true );

        if ( $already_synced ) {
            // ── 更新路径 ──────────────────────────────────────────────────────
            // 防止同一篇文章在队列中重复出现
            if ( wp_next_scheduled( 'btm_delayed_sync', [ $post_id ] ) ) {
                return;
            }
            wp_schedule_single_event( time() + (int) $delay_seconds, 'btm_delayed_sync', [ $post_id ] );

        } else {
            // ── 新建路径 ──────────────────────────────────────────────────────
            // 防止首次发布时重复调度（例如各 hook 触发时序导致的多次调用）
            if ( get_post_meta( $post_id, '_sync_scheduled', true ) ) {
                return;
            }
            wp_schedule_single_event( time() + (int) $delay_seconds, 'btm_delayed_sync', [ $post_id ] );
            update_post_meta( $post_id, '_sync_scheduled', 1 );
        }
    }

    add_action( 'btm_delayed_sync', 'btm_execute_sync' );

    // -------------------------------------------------------------------------
    // 辅助函数：在目标站点查找或创建 notification_categories 分类项
    // 返回目标站 term ID（int），失败时返回 null。
    // -------------------------------------------------------------------------
    function btm_get_or_create_km_term( $post_type, $km_url, $auth ) {

        $term_slug    = sanitize_title( $post_type );
        $term_api_url = trailingslashit( $km_url ) . 'wp-json/wp/v2/notification_categories';

        // 先尝试通过 slug 查找已存在的 term
        $get_response = wp_remote_get(
            add_query_arg( 'slug', $term_slug, $term_api_url ),
            [
                'headers' => [ 'Authorization' => 'Basic ' . $auth ],
                'timeout' => 15,
            ]
        );

        if ( ! is_wp_error( $get_response ) ) {
            $terms = json_decode( wp_remote_retrieve_body( $get_response ), true );
            if ( ! empty( $terms ) && isset( $terms[0]['id'] ) ) {
                return (int) $terms[0]['id'];
            }
        }

        // Term 不存在，尝试在目标站创建
        $create_response = wp_remote_post(
            $term_api_url,
            [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( [
                    'name' => $post_type,
                    'slug' => $term_slug,
                ] ),
                'timeout' => 15,
            ]
        );

        if ( ! is_wp_error( $create_response ) ) {
            $term = json_decode( wp_remote_retrieve_body( $create_response ), true );
            if ( isset( $term['id'] ) ) {
                return (int) $term['id'];
            }
        }

        error_log( 'Blog To Keep Minutes Sync: Failed to get or create notification_categories term for post type: ' . $post_type );
        return null;
    }

    // -------------------------------------------------------------------------
    // 核心执行函数：向目标站发起创建或更新请求
    // -------------------------------------------------------------------------
    function btm_execute_sync( $post_id ) {

        // 避免空文章 / 已删除文章触发
        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }

        // 重新从数据库读取凭据（避免使用外层作用域中可能已过期的变量）
        $dukeyin_options = get_site_option( 'options-page', true, false );
        $app_password    = isset( $dukeyin_options['km-app-password'] ) ? $dukeyin_options['km-app-password'] : null;
        $km_url          = isset( $dukeyin_options['km-url'] )          ? $dukeyin_options['km-url']          : null;
        $username        = isset( $dukeyin_options['km_username'] )     ? $dukeyin_options['km_username']     : null;

        if ( ! $app_password || ! $km_url || ! $username ) {
            error_log( 'Blog To Keep Minutes Sync: Missing credentials during sync execution for post ID ' . $post_id );
            return;
        }

        $auth      = base64_encode( $username . ':' . $app_password );
        $post_type = get_post_type( $post_id );

        // 摘要
        $excerpt = get_the_excerpt( $post_id )
            ? get_the_excerpt( $post_id )
            : wp_trim_words( $post->post_content, 55, '...' );

        // 来源 Logo（按文章类型区分）
        $source_logo = null;
        switch ( $post_type ) {
            case 'film_review':
            case 'tvshow_review':
                $source_logo = get_post_meta( $post_id, '_r_f_logo', true ) ?: null;
                break;
            case 'game_review':
                $source_logo = get_post_meta( $post_id, 'logo', true ) ?: null;
                break;
        }

        // ── notification_categories：将源站 post type 同步为目标站分类 ──────
        // 在目标站查找或创建对应 term，获取其 ID 后附入请求体。
        $category_term_id = btm_get_or_create_km_term( $post_type, $km_url, $auth );

        // 请求数据
        $body = [
            'title'   => $post->post_title,
            'content' => $excerpt ?: '',
            'status'  => 'publish',
            'meta'    => [
                'source_post_id'      => $post_id,
                'source_site'         => home_url(),
                'source_author'       => get_the_author_meta( 'display_name', $post->post_author ),
                'source_author_email' => get_the_author_meta( 'user_email', $post->post_author ),
                'source_post_type'    => $post_type,
                'source_permalink'    => wp_get_shortlink( $post_id ) ?: get_permalink( $post_id ),
                'source_image'        => get_the_post_thumbnail_url( $post_id, 'large' ) ?: null,
                'source_logo'         => $source_logo,
                'source_modified_gmt' => get_post_modified_time( 'c', true, $post_id ),
                'source_subtitle'     => get_post_meta( $post_id, '_headline', true ) ?: null,
                'source_rating_score' => get_post_meta( $post_id, 'ranking-score', true ) ?: null,
                'source_rating_now'   => get_post_meta( $post_id, '_r_now', true ) !== ''
                                            ? get_post_meta( $post_id, '_r_now', true )
                                            : null,
            ],
        ];

        // 附加 taxonomy 分类（term ID 数组）
        if ( $category_term_id ) {
            $body['notification_categories'] = [ $category_term_id ];
        }

        // ── 判断是新建还是更新 ────────────────────────────────────────────────
        // 已有 _synced_to_keepmins 且存有目标站 notification ID → 执行更新（POST to /notification/{id}）
        // 否则 → 执行新建（POST to /notification）
        $already_synced  = get_post_meta( $post_id, '_synced_to_keepmins', true );
        $notification_id = get_post_meta( $post_id, '_km_notification_id', true );
        $is_update       = $already_synced && $notification_id;

        if ( $is_update ) {
            // 更新已有 notification
            $api_url = trailingslashit( $km_url ) . 'wp-json/wp/v2/notification/' . intval( $notification_id );
        } else {
            // 创建新 notification
            $api_url = trailingslashit( $km_url ) . 'wp-json/wp/v2/notification';
        }

        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 20,
            ]
        );

        // ── 处理响应 ──────────────────────────────────────────────────────────
        if ( is_wp_error( $response ) ) {
            error_log( 'Blog To Keep Minutes Sync Error (post ' . $post_id . '): ' . $response->get_error_message() );
            return;
        }

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( $response_code >= 200 && $response_code < 300 ) {

            // 标记为已同步，清理调度标记
            update_post_meta( $post_id, '_synced_to_keepmins', 1 );
            delete_post_meta( $post_id, '_sync_scheduled' );

            // 首次同步成功后，将目标站返回的 notification ID 存入 meta，
            // 供后续更新操作使用（避免在目标站重复创建条目）。
            if ( ! $is_update ) {
                $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $response_body['id'] ) ) {
                    update_post_meta( $post_id, '_km_notification_id', intval( $response_body['id'] ) );
                }
            }

            $action = $is_update ? 'updated' : 'created';
            error_log( 'Blog To Keep Minutes Sync: Notification ' . $action . ' successfully for post ID ' . $post_id . '.' );

        } else {
            error_log( 'Blog To Keep Minutes Sync: Unexpected HTTP ' . $response_code . ' for post ID ' . $post_id . '. Response: ' . wp_remote_retrieve_body( $response ) );
        }
    }

} else {
    error_log( 'Blog To Keep Minutes Sync: Application password, KM URL, or username not set. Please set them in the Duke theme settings.' );
    return;
}