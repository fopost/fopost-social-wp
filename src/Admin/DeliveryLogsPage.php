<?php

declare(strict_types=1);

namespace Fopost\Social\Wp\Admin;

defined( 'ABSPATH' ) || exit;

use Fopost\Social\Wp\Database\DeliveryLog;

/**
 * Admin page for viewing delivery logs.
 */
class DeliveryLogsPage
{
    /**
     * Register the delivery logs submenu page.
     */
    public function register(): void
    {
        add_submenu_page(
            parent_slug: 'fopost-social',
            page_title: __('Delivery Logs', 'fopost-social'),
            menu_title: __('Delivery Logs', 'fopost-social'),
            capability: 'manage_options',
            menu_slug: 'fopost-social-logs',
            callback: [$this, 'render'],
        );
    }

    /**
     * Render the delivery logs page.
     */
    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Handle bulk delete.
        if (
            isset($_POST['fopost_social_bulk_action']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handleBulkDelete().
            && sanitize_text_field(wp_unslash($_POST['fopost_social_bulk_action'])) === 'delete' // phpcs:ignore WordPress.Security.NonceVerification.Missing
        ) {
            $this->handleBulkDelete();
        }

        // Handle single delete.
        if (
            isset($_GET['action'], $_GET['log_id']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in handleSingleDelete().
            && sanitize_text_field(wp_unslash($_GET['action'])) === 'delete' // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            $this->handleSingleDelete();
        }

        // Build query args from read-only display filters (no state changes).
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only filters, no data modification.
        $args = [
            'per_page' => 20,
            'page'     => max(1, absint($_GET['paged'] ?? 1)),
            'platform' => sanitize_key(wp_unslash($_GET['platform'] ?? '')),
            'status'   => sanitize_key(wp_unslash($_GET['status'] ?? '')),
            'orderby'  => sanitize_key(wp_unslash($_GET['orderby'] ?? 'created_at')),
            'order'    => sanitize_key(wp_unslash($_GET['order'] ?? 'DESC')),
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $result = DeliveryLog::query($args);
        $items = $result['items'];
        $total = $result['total'];
        $totalPages = (int) ceil($total / $args['per_page']);

        require __DIR__ . '/views/delivery-logs-page.php';
    }

    private function handleBulkDelete(): void
    {
        if (
            ! isset($_POST['fopost_social_logs_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fopost_social_logs_nonce'])), 'fopost_social_logs_bulk')
        ) {
            return;
        }

        $ids = isset($_POST['log_ids']) && is_array($_POST['log_ids'])
            ? array_map('intval', wp_unslash($_POST['log_ids']))
            : [];

        foreach ($ids as $id) {
            DeliveryLog::delete($id);
        }

        add_settings_error(
            'fopost_social_logs',
            'bulk_deleted',
            sprintf(
                /* translators: %d: number of deleted entries */
                __('%d log entries deleted.', 'fopost-social'),
                count($ids),
            ),
            'success',
        );
    }

    private function handleSingleDelete(): void
    {
        $logId = absint($_GET['log_id'] ?? 0);

        if (
            $logId <= 0
            || ! isset($_GET['_wpnonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'fopost_social_delete_log_' . $logId)
        ) {
            return;
        }

        DeliveryLog::delete($logId);

        add_settings_error(
            'fopost_social_logs',
            'deleted',
            __('Log entry deleted.', 'fopost-social'),
            'success',
        );
    }
}
