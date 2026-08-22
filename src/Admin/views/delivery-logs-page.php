<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array $items */
/** @var int $total */
/** @var int $totalPages */
/** @var array $args */
?>
<div class="wrap fopost-social-delivery-logs">
    <h1><?php esc_html_e('FoPost Social Delivery Logs', 'fopost-social'); ?></h1>

    <?php settings_errors('fopost_social_logs'); ?>

    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" class="fopost-social-logs-filter">
            <input type="hidden" name="page" value="fopost-social-logs" />

            <select name="platform">
                <option value=""><?php esc_html_e('All Platforms', 'fopost-social'); ?></option>
                <?php foreach (['telegram', 'twitter', 'facebook'] as $fopost_social_p) : ?>
                    <option value="<?php echo esc_attr($fopost_social_p); ?>" <?php selected($args['platform'], $fopost_social_p); ?>>
                        <?php echo esc_html(ucfirst($fopost_social_p === 'twitter' ? 'X (Twitter)' : $fopost_social_p)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'fopost-social'); ?></option>
                <?php foreach (['pending', 'publishing', 'published', 'failed'] as $fopost_social_status) : ?>
                    <option value="<?php echo esc_attr($fopost_social_status); ?>" <?php selected($args['status'], $fopost_social_status); ?>>
                        <?php echo esc_html(ucfirst($fopost_social_status)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php submit_button(__('Filter', 'fopost-social'), 'secondary', 'filter', false); ?>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                printf(
                    /* translators: %s: number of items */
                    esc_html(_n('%s item', '%s items', $total, 'fopost-social')),
                    esc_html(number_format_i18n($total)),
                );
                ?>
            </span>
            <?php if ($totalPages > 1) : ?>
                <?php
                echo wp_kses_post((string) paginate_links([
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $args['page'],
                    'total'     => $totalPages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]));
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log table -->
    <form method="post">
        <?php wp_nonce_field('fopost_social_logs_bulk', 'fopost_social_logs_nonce'); ?>
        <input type="hidden" name="fopost_social_bulk_action" value="" />

        <table class="wp-list-table widefat fixed striped fopost-social-logs-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1" />
                    </td>
                    <th><?php esc_html_e('Date', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('Post', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('Platform', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('Status', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('External URL', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('Error', 'fopost-social'); ?></th>
                    <th><?php esc_html_e('Actions', 'fopost-social'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No delivery logs found.', 'fopost-social'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $fopost_social_item) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr((string) $fopost_social_item->id); ?>" />
                            </th>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($fopost_social_item->created_at))); ?></td>
                            <td>
                                <?php if ($fopost_social_item->post_id) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link((int) $fopost_social_item->post_id) ?? '#'); ?>">
                                        <?php echo esc_html(get_the_title((int) $fopost_social_item->post_id) ? get_the_title((int) $fopost_social_item->post_id) : "#{$fopost_social_item->post_id}"); ?>
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fopost-social-platform fopost-social-platform-<?php echo esc_attr($fopost_social_item->platform); ?>">
                                    <?php echo esc_html(ucfirst($fopost_social_item->platform === 'twitter' ? 'X (Twitter)' : $fopost_social_item->platform)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="fopost-social-status fopost-social-status-<?php echo esc_attr($fopost_social_item->status); ?>">
                                    <?php echo esc_html(ucfirst($fopost_social_item->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fopost_social_item->external_url) : ?>
                                    <a href="<?php echo esc_url($fopost_social_item->external_url); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e('View', 'fopost-social'); ?> &#8599;
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fopost_social_item->error) : ?>
                                    <span class="fopost-social-error-text" title="<?php echo esc_attr($fopost_social_item->error); ?>">
                                        <?php echo esc_html(mb_strimwidth($fopost_social_item->error, 0, 80, '...')); ?>
                                    </span>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $fopost_social_delete_url = wp_nonce_url(
                                    add_query_arg(
                                        ['action' => 'delete', 'log_id' => $fopost_social_item->id],
                                        admin_url('admin.php?page=fopost-social-logs'),
                                    ),
                                    'fopost_social_delete_log_' . $fopost_social_item->id,
                                );
                                ?>
                                <a href="<?php echo esc_url($fopost_social_delete_url); ?>" class="fopost-social-delete-link" onclick="return confirm('<?php esc_attr_e('Delete this log entry?', 'fopost-social'); ?>');">
                                    <?php esc_html_e('Delete', 'fopost-social'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <button type="submit" class="button" onclick="this.form.fopost_social_bulk_action.value='delete'; return confirm('<?php esc_attr_e('Delete selected entries?', 'fopost-social'); ?>');">
                <?php esc_html_e('Delete Selected', 'fopost-social'); ?>
            </button>
        </div>
    </form>
</div>
