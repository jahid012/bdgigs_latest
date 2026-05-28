<?php

namespace App\Support;

use Illuminate\Support\Str;

class EmailTemplateDefaults
{
    public const VARIABLES = [
        'user_name',
        'buyer_name',
        'seller_name',
        'sender_name',
        'admin_name',
        'order_id',
        'order_title',
        'order_amount',
        'gig_title',
        'custom_offer_title',
        'custom_offer_price',
        'delivery_time',
        'deadline',
        'transaction_id',
        'invoice_id',
        'payment_method',
        'platform_fee',
        'seller_earning',
        'login_time',
        'ip_address',
        'browser',
        'device',
        'location',
        'withdrawal_id',
        'dispute_id',
        'review_deadline',
        'notification_title',
        'notification_detail',
        'conversation_subject',
        'dashboard_url',
        'order_url',
        'conversation_url',
        'support_url',
        'preferences_url',
        'unsubscribe_url',
        'action_url',
        'platform_name',
    ];

    public const ALWAYS_SEND_KEYS = [
        'email_verification',
        'password_reset',
        'password_changed',
        'login_alert',
        'account_deactivated',
        'account_suspended',
        'account_reactivated',
        'payment_successful',
        'payment_failed',
        'order_placed_successfully',
        'new_order_created',
        'order_cancelled',
        'order_cancellation_requested',
        'order_cancellation_accepted',
        'order_cancellation_rejected',
        'order_refunded',
        'dispute_opened',
        'dispute_updated',
        'dispute_resolved',
        'dispute_rejected_closed',
        'refund_issued_from_dispute',
        'withdrawal_approved',
        'withdrawal_rejected',
        'withdrawal_paid_sent',
        'identity_verification_rejected',
    ];

    public const ALIASES = [
        'order_created' => 'new_order_created',
        'order_delivery_submitted' => 'order_delivered',
        'order_revision_requested' => 'revision_requested',
        'order_requirements_submitted' => 'requirements_submitted',
        'order_review_submitted' => 'review_submitted_confirmation',
        'order_time_extension_requested' => 'time_extension_requested',
        'order_time_extension_accepted' => 'time_extension_accepted',
        'order_time_extension_rejected' => 'time_extension_rejected',
        'custom_offer_received' => 'custom_offer_received',
        'custom_offer_accepted' => 'custom_offer_accepted',
        'custom_offer_paid' => 'custom_offer_paid',
        'custom_offer_declined' => 'custom_offer_declined',
        'custom_offer_cancelled' => 'custom_offer_cancelled_by_seller',
        'dispute_updated' => 'dispute_updated',
        'withdrawal' => 'withdrawal_status_updated',
    ];

    public static function categories(): array
    {
        return [
            'account' => 'Account & authentication',
            'security' => 'Security',
            'orders' => 'Orders',
            'custom_offers' => 'Custom offers',
            'messages' => 'Messages & inbox',
            'payments' => 'Payments & wallet',
            'time_extensions' => 'Time extensions',
            'disputes' => 'Resolution Center',
            'reviews' => 'Reviews',
            'gigs' => 'Gigs & services',
            'admin' => 'Admin & moderation',
            'identity' => 'Identity verification',
            'marketing' => 'Marketing & engagement',
        ];
    }

    public static function all(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(function (array $definition, string $key) {
                [$name, $category, $subject, $summary, $cta] = $definition + [null, null, null, null, 'Open BDGigs'];

                return [$key => [
                    'key' => $key,
                    'name' => $name,
                    'subject' => $subject,
                    'html_body' => self::htmlBody($name, $summary, $cta),
                    'text_body' => self::textBody($name, $summary),
                    'available_variables' => self::VARIABLES,
                    'category' => $category,
                    'is_active' => true,
                ]];
            })
            ->all();
    }

    public static function get(string $key): ?array
    {
        $resolved = self::resolveKey($key);

        return self::all()[$resolved] ?? null;
    }

    public static function resolveKey(string $key): string
    {
        return self::ALIASES[$key] ?? $key;
    }

    public static function categoryForPreferenceKey(string $preferenceKey): string
    {
        return match ($preferenceKey) {
            'inboxMessages', 'orderMessages' => 'messages',
            'orderUpdates' => 'orders',
            'ratingReminders' => 'reviews',
            'buyerBriefs', 'savedServices' => 'marketing',
            'accountUpdates' => 'account',
            'gigUpdates' => 'gigs',
            'payouts' => 'payments',
            default => Str::snake($preferenceKey),
        };
    }

    private static function definitions(): array
    {
        return [
            'email_verification' => ['Email verification', 'security', 'Verify your {{platform_name}} email', 'Confirm your email address so your account stays protected.', 'Verify email'],
            'welcome_email' => ['Welcome email', 'account', 'Welcome to {{platform_name}}, {{user_name}}', 'Your account is ready. Complete your profile and start using your marketplace dashboard.', 'Open dashboard'],
            'password_reset' => ['Password reset', 'security', 'Reset your {{platform_name}} password', 'We received a secure password reset request for your account.', 'Reset password'],
            'password_changed' => ['Password changed confirmation', 'security', 'Your {{platform_name}} password was changed', 'Your password was changed successfully. Contact support if this was not you.', 'Review security'],
            'login_alert' => ['Login alert', 'security', 'New login to your {{platform_name}} account', 'We noticed a login from a new device or location.', 'Review sessions'],
            'account_deactivated' => ['Account deactivated', 'account', 'Your {{platform_name}} account was deactivated', 'Your account is currently deactivated. You can contact support for help.', 'Contact support'],
            'account_suspended' => ['Account suspended', 'account', 'Important account status update', 'Your account has been suspended after a marketplace policy review.', 'Contact support'],
            'account_reactivated' => ['Account reactivated', 'account', 'Your {{platform_name}} account is active again', 'Your account access has been restored.', 'Open dashboard'],
            'profile_completion_reminder' => ['Profile completion reminder', 'account', 'Complete your {{platform_name}} profile', 'A complete profile helps buyers and sellers trust your account faster.', 'Complete profile'],
            'seller_application_submitted' => ['Seller application submitted', 'account', 'Seller application received', 'Your seller application is waiting for marketplace review.', 'Open seller settings'],
            'seller_account_approval' => ['Seller account approval', 'account', 'Your seller account is approved', 'Your seller profile is ready to receive buyer interest.', 'Open seller dashboard'],
            'seller_account_rejection' => ['Seller account rejection', 'account', 'Seller account review update', 'Your seller account needs changes before approval.', 'Review details'],

            'order_placed_successfully' => ['Order placed successfully', 'orders', 'Order #{{order_id}} placed successfully', 'Your order for {{order_title}} has been created.', 'View order'],
            'payment_successful' => ['Payment successful', 'payments', 'Payment successful for order #{{order_id}}', 'Your payment for {{order_title}} was processed successfully.', 'View receipt'],
            'payment_failed' => ['Payment failed', 'payments', 'Payment failed for {{order_title}}', 'We could not complete your payment. Please review your wallet or payment method.', 'Review payment'],
            'requirements_pending' => ['Requirements pending reminder', 'orders', 'Requirements needed for order #{{order_id}}', 'The seller needs your requirements before work can move forward.', 'Submit requirements'],
            'requirements_submitted' => ['Requirements submitted', 'orders', 'Requirements submitted for order #{{order_id}}', 'The buyer requirements have been submitted and the order can continue.', 'View order'],
            'seller_started_working' => ['Seller started working', 'orders', 'Work started on order #{{order_id}}', 'The seller has started working on your order.', 'View order'],
            'order_delivered' => ['Order delivered', 'orders', 'Delivery submitted for order #{{order_id}}', 'The seller submitted delivery for {{order_title}}.', 'Review delivery'],
            'revision_requested' => ['Revision requested', 'orders', 'Revision requested for order #{{order_id}}', 'A revision was requested for this order.', 'View revision'],
            'revision_delivered' => ['Revision delivered', 'orders', 'Revision delivered for order #{{order_id}}', 'The seller submitted an updated delivery.', 'Review delivery'],
            'order_completed' => ['Order completed', 'orders', 'Order #{{order_id}} completed', 'The order has been completed successfully.', 'View order'],
            'order_cancellation_requested' => ['Order cancellation requested', 'orders', 'Cancellation requested for #{{order_id}}', 'A cancellation request was opened for this order.', 'Review request'],
            'order_cancellation_accepted' => ['Order cancellation accepted', 'orders', 'Cancellation accepted for #{{order_id}}', 'The cancellation request was accepted.', 'View order'],
            'order_cancellation_rejected' => ['Order cancellation rejected', 'orders', 'Cancellation rejected for #{{order_id}}', 'The cancellation request was rejected.', 'View order'],
            'order_cancelled' => ['Order cancelled', 'orders', 'Order #{{order_id}} cancelled', 'This order has been cancelled.', 'View order'],
            'order_refunded' => ['Order refunded', 'payments', 'Refund processed for order #{{order_id}}', 'A refund has been added to your wallet or payment method.', 'View wallet'],
            'buyer_review_request' => ['Buyer review request', 'reviews', 'Review your order #{{order_id}}', 'Share feedback about your seller before {{review_deadline}}.', 'Leave review'],
            'review_deadline_reminder' => ['Review deadline reminder', 'reviews', 'Review deadline approaching for #{{order_id}}', 'Your review window closes on {{review_deadline}}.', 'Leave review'],
            'seller_submitted_review_for_buyer' => ['Seller submitted review for buyer', 'reviews', 'Seller review submitted for #{{order_id}}', 'The seller submitted their review for this order.', 'View reviews'],
            'new_order_created' => ['New order received', 'orders', 'New order #{{order_id}} received', 'You received a new order for {{order_title}}.', 'View order'],
            'buyer_updated_requirements' => ['Buyer updated requirements', 'orders', 'Requirements updated for #{{order_id}}', 'The buyer updated the order requirements.', 'View requirements'],
            'order_deadline_reminder' => ['Order deadline reminder', 'orders', 'Deadline reminder for #{{order_id}}', 'This order deadline is approaching: {{deadline}}.', 'View order'],
            'order_overdue_alert' => ['Order overdue alert', 'orders', 'Order #{{order_id}} is overdue', 'This order has passed its delivery deadline.', 'View order'],
            'buyer_accepted_delivery' => ['Buyer accepted delivery', 'orders', 'Buyer accepted delivery for #{{order_id}}', 'The buyer accepted your delivery.', 'View order'],
            'earnings_added_or_pending' => ['Earnings added or pending', 'payments', 'Earnings update for #{{order_id}}', 'Your seller earnings have been updated for this order.', 'View earnings'],
            'dispute_opened_by_buyer' => ['Dispute opened by buyer', 'disputes', 'Buyer opened a dispute for #{{order_id}}', 'A Resolution Center case has been opened by the buyer.', 'View case'],
            'buyer_submitted_review' => ['Buyer submitted review', 'reviews', 'Buyer review submitted for #{{order_id}}', 'The buyer submitted a review. Submit yours to unlock both reviews.', 'Review buyer'],
            'review_buyer_reminder' => ['Review buyer reminder', 'reviews', 'Review the buyer for #{{order_id}}', 'You can now review the buyer before {{review_deadline}}.', 'Review buyer'],

            'custom_offer_sent_confirmation' => ['Custom offer sent confirmation', 'custom_offers', 'Custom offer sent: {{custom_offer_title}}', 'Your custom offer was sent to {{buyer_name}}.', 'Open conversation'],
            'custom_offer_received' => ['Custom offer received', 'custom_offers', 'You received a custom offer', '{{seller_name}} sent you a custom offer: {{custom_offer_title}}.', 'View offer'],
            'custom_offer_accepted' => ['Custom offer accepted', 'custom_offers', 'Custom offer accepted', '{{buyer_name}} accepted {{custom_offer_title}}.', 'Open conversation'],
            'custom_offer_declined' => ['Custom offer declined', 'custom_offers', 'Custom offer declined', 'The custom offer {{custom_offer_title}} was declined.', 'Open conversation'],
            'custom_offer_expired' => ['Custom offer expired', 'custom_offers', 'Custom offer expired', 'The custom offer {{custom_offer_title}} has expired.', 'Open conversation'],
            'custom_offer_cancelled_by_seller' => ['Custom offer cancelled by seller', 'custom_offers', 'Custom offer cancelled', 'The seller cancelled custom offer {{custom_offer_title}}.', 'Open conversation'],
            'custom_offer_paid' => ['Custom offer paid', 'custom_offers', 'Custom offer paid', '{{buyer_name}} paid for {{custom_offer_title}}.', 'View order'],
            'order_created_from_custom_offer' => ['Order created from custom offer', 'custom_offers', 'Order created from custom offer', 'A new order was created from {{custom_offer_title}}.', 'View order'],
            'custom_offer_payment_failed' => ['Custom offer payment failed', 'custom_offers', 'Custom offer payment failed', 'The payment for {{custom_offer_title}} could not be completed.', 'Review payment'],

            'new_message_received' => ['New message received', 'messages', 'New message from {{sender_name}}', 'You have a new inbox message about {{conversation_subject}}.', 'Open conversation'],
            'unread_message_reminder' => ['Unread message reminder', 'messages', 'You have unread messages', 'A conversation is waiting for your reply.', 'Open inbox'],
            'attachment_received_in_conversation' => ['Attachment received', 'messages', 'New attachment received', 'A new file was shared in your conversation.', 'Open conversation'],
            'custom_offer_message_received' => ['Custom offer message received', 'messages', 'Custom offer message received', 'A conversation includes a custom offer update.', 'Open conversation'],
            'admin_support_message_received' => ['Admin/support message received', 'messages', 'Support sent you a message', 'A support message has been added to your account.', 'Open support'],

            'balance_added_successfully' => ['Balance added successfully', 'payments', 'Balance added to your wallet', '{{order_amount}} was added to your {{platform_name}} wallet.', 'View wallet'],
            'balance_add_payment_failed' => ['Balance add/payment failed', 'payments', 'Wallet payment failed', 'We could not add balance to your wallet.', 'Try again'],
            'wallet_transaction_receipt' => ['Wallet transaction receipt', 'payments', 'Wallet receipt {{transaction_id}}', 'A wallet transaction has been recorded for your account.', 'View wallet'],
            'refund_added_to_wallet' => ['Refund added to wallet', 'payments', 'Refund added to your wallet', 'A refund has been credited to your wallet.', 'View wallet'],
            'withdrawal_request_submitted' => ['Withdrawal request submitted', 'payments', 'Withdrawal request submitted', 'Withdrawal {{withdrawal_id}} is waiting for review.', 'View earnings'],
            'withdrawal_approved' => ['Withdrawal approved', 'payments', 'Withdrawal approved', 'Withdrawal {{withdrawal_id}} has been approved.', 'View earnings'],
            'withdrawal_rejected' => ['Withdrawal rejected', 'payments', 'Withdrawal rejected', 'Withdrawal {{withdrawal_id}} was rejected.', 'View earnings'],
            'withdrawal_paid_sent' => ['Withdrawal paid/sent', 'payments', 'Withdrawal paid', 'Withdrawal {{withdrawal_id}} has been marked paid.', 'View earnings'],
            'withdrawal_status_updated' => ['Withdrawal status updated', 'payments', 'Withdrawal update', 'Your withdrawal status has changed.', 'View earnings'],
            'invoice_receipt_email' => ['Invoice/receipt email', 'payments', 'Receipt from {{platform_name}}', 'Your marketplace receipt is ready.', 'View receipt'],

            'time_extension_requested' => ['Time extension requested', 'time_extensions', 'Time extension requested for #{{order_id}}', '{{seller_name}} requested more time for this order.', 'Review request'],
            'time_extension_accepted' => ['Time extension accepted', 'time_extensions', 'Time extension accepted for #{{order_id}}', 'The delivery deadline has been updated to {{deadline}}.', 'View order'],
            'time_extension_rejected' => ['Time extension rejected', 'time_extensions', 'Time extension rejected for #{{order_id}}', 'The time extension request was rejected.', 'View order'],
            'time_extension_request_expired' => ['Time extension request expired', 'time_extensions', 'Time extension request expired', 'A time extension request for #{{order_id}} expired.', 'View order'],
            'order_deadline_updated' => ['Order deadline updated', 'time_extensions', 'Order deadline updated', 'The deadline for #{{order_id}} is now {{deadline}}.', 'View order'],

            'dispute_opened' => ['Dispute opened', 'disputes', 'Resolution case opened for #{{order_id}}', 'A Resolution Center case has been opened.', 'View case'],
            'dispute_response_received' => ['Dispute response received', 'disputes', 'New response in Resolution Center', 'A new response was added to your dispute.', 'View case'],
            'dispute_updated' => ['Dispute status updated', 'disputes', 'Resolution Center update', 'Your dispute has been updated.', 'View case'],
            'admin_joined_dispute' => ['Admin joined dispute', 'disputes', 'Support joined your dispute', 'An admin has joined the Resolution Center case.', 'View case'],
            'dispute_resolved' => ['Dispute resolved', 'disputes', 'Resolution case resolved', 'Your Resolution Center case has been resolved.', 'View case'],
            'dispute_rejected_closed' => ['Dispute rejected/closed', 'disputes', 'Resolution case closed', 'Your Resolution Center case has been closed.', 'View case'],
            'refund_issued_from_dispute' => ['Refund issued from dispute', 'disputes', 'Refund issued from dispute', 'A refund was issued from the Resolution Center case.', 'View wallet'],
            'dispute_evidence_requested' => ['Dispute evidence requested', 'disputes', 'Evidence requested', 'Please add evidence to support the Resolution Center case.', 'Upload evidence'],
            'dispute_evidence_submitted' => ['Dispute evidence submitted', 'disputes', 'Evidence submitted', 'New evidence was added to the Resolution Center case.', 'View case'],

            'buyer_review_submitted_confirmation' => ['Buyer review submitted confirmation', 'reviews', 'Your review was submitted', 'Thanks for reviewing order #{{order_id}}.', 'View reviews'],
            'seller_review_request' => ['Seller review request', 'reviews', 'Review the buyer for #{{order_id}}', 'The buyer submitted a review. Submit yours to make both reviews visible.', 'Review buyer'],
            'seller_review_submitted_confirmation' => ['Seller review submitted confirmation', 'reviews', 'Your seller review was submitted', 'Both reviews can now become visible.', 'View reviews'],
            'reviews_are_now_visible' => ['Reviews are now visible', 'reviews', 'Reviews are now visible', 'Both order reviews are complete and visible.', 'View reviews'],
            'review_submitted_confirmation' => ['Review submitted confirmation', 'reviews', 'Review submitted for #{{order_id}}', 'A review was submitted for this order.', 'View reviews'],
            'review_period_expired' => ['Review period expired', 'reviews', 'Review period expired', 'The review window for #{{order_id}} has closed.', 'View order'],

            'gig_created_successfully' => ['Gig created successfully', 'gigs', 'Gig created: {{gig_title}}', 'Your gig draft has been created successfully.', 'Manage gig'],
            'gig_submitted_for_review' => ['Gig submitted for review', 'gigs', 'Gig submitted for review', '{{gig_title}} is waiting for marketplace review.', 'Manage gig'],
            'gig_approved' => ['Gig approved', 'gigs', 'Gig approved: {{gig_title}}', 'Your gig is now eligible to appear in the marketplace.', 'View gig'],
            'gig_rejected' => ['Gig rejected', 'gigs', 'Gig review update', '{{gig_title}} needs changes before approval.', 'Manage gig'],
            'gig_paused_deactivated' => ['Gig paused/deactivated', 'gigs', 'Gig paused', '{{gig_title}} has been paused or deactivated.', 'Manage gig'],
            'gig_reactivated' => ['Gig reactivated', 'gigs', 'Gig reactivated', '{{gig_title}} is active again.', 'View gig'],
            'gig_edited_successfully' => ['Gig edited successfully', 'gigs', 'Gig updated', '{{gig_title}} was updated successfully.', 'Manage gig'],
            'gig_needs_update_reminder' => ['Gig needs update reminder', 'gigs', 'Keep your gig updated', '{{gig_title}} may need refreshed pricing, media, or scope.', 'Update gig'],
            'gig_received_inquiry_message' => ['Gig received inquiry/message', 'gigs', 'New inquiry for {{gig_title}}', 'A buyer sent an inquiry about your gig.', 'Open inbox'],
            'gig_performance_summary' => ['Gig performance summary', 'gigs', 'Gig performance summary', 'Your latest gig performance summary is ready.', 'View analytics'],

            'user_reported' => ['User reported', 'admin', 'User report received', 'A marketplace user report needs admin review.', 'Open admin'],
            'gig_reported' => ['Gig reported', 'admin', 'Gig report received', 'A gig report needs moderation review.', 'Open admin'],
            'order_reported' => ['Order reported', 'admin', 'Order report received', 'An order report needs support review.', 'Open admin'],
            'message_reported' => ['Message reported', 'admin', 'Message report received', 'A conversation message report needs moderation review.', 'Open admin'],
            'report_status_updated' => ['Report status updated', 'admin', 'Report status updated', 'A marketplace report has been reviewed.', 'Open dashboard'],
            'dispute_escalation_alert' => ['Dispute escalation alert', 'admin', 'Dispute escalation alert', 'A dispute has been escalated for admin review.', 'Open dispute'],
            'seller_verification_submitted' => ['Seller verification submitted', 'admin', 'Seller verification submitted', 'A seller verification request needs review.', 'Open admin'],
            'seller_verification_approved' => ['Seller verification approved', 'admin', 'Seller verification approved', 'Seller verification was approved.', 'Open admin'],
            'seller_verification_rejected' => ['Seller verification rejected', 'admin', 'Seller verification rejected', 'Seller verification was rejected.', 'Open admin'],
            'suspicious_activity_alert' => ['Suspicious activity alert', 'admin', 'Suspicious activity alert', 'A marketplace security signal needs attention.', 'Open admin'],
            'suspicious_activity_user_alert' => ['Suspicious activity user alert', 'security', 'Security alert for your {{platform_name}} account', 'We detected suspicious activity on your account.', 'Review security'],
            'new_withdrawal_request_alert_to_admin' => ['New withdrawal request alert', 'admin', 'New withdrawal request', 'A seller withdrawal request needs finance review.', 'Open withdrawals'],
            'new_dispute_alert_to_admin' => ['New dispute alert', 'admin', 'New dispute opened', 'A new dispute needs support review.', 'Open disputes'],

            'identity_verification_submitted' => ['Verification submitted', 'identity', 'Identity verification submitted', 'Your verification document was submitted successfully.', 'View verification'],
            'identity_verification_under_review' => ['Verification under review', 'identity', 'Identity verification under review', 'Your documents are now in manual review.', 'View verification'],
            'identity_verification_approved' => ['Verification approved', 'identity', 'Identity verification approved', 'Your identity verification was approved.', 'Open dashboard'],
            'identity_verification_rejected' => ['Verification rejected', 'identity', 'Identity verification rejected', 'Your identity verification needs attention.', 'View verification'],
            'additional_document_requested' => ['Additional document requested', 'identity', 'Additional document requested', 'Please upload an additional verification document.', 'Upload document'],
            'document_upload_failed_expired' => ['Document upload failed/expired', 'identity', 'Document upload issue', 'Your verification document upload failed or expired.', 'Try again'],

            'recommended_gigs_for_buyer' => ['Recommended gigs for buyer', 'marketing', 'Recommended gigs for you', 'We found services that may match your current needs.', 'Explore gigs'],
            'recently_viewed_gigs_reminder' => ['Recently viewed gigs reminder', 'marketing', 'Still interested in these services?', 'Your recently viewed services are waiting.', 'View services'],
            'saved_favorite_gig_reminder' => ['Saved/favorite gig reminder', 'marketing', 'Your saved services are waiting', 'Return to the services you saved recently.', 'View saved services'],
            'incomplete_order_checkout_reminder' => ['Incomplete order checkout reminder', 'marketing', 'Complete your order checkout', 'You left an order checkout unfinished.', 'Continue checkout'],
            'seller_tips_email' => ['Seller tips email', 'marketing', 'Seller tips from {{platform_name}}', 'Fresh tips to improve your seller profile and gigs.', 'Open seller dashboard'],
            'buyer_onboarding_tips' => ['Buyer onboarding tips', 'marketing', 'Buyer tips from {{platform_name}}', 'Learn how to hire safely and manage orders confidently.', 'Open dashboard'],
            'weekly_marketplace_digest' => ['Weekly marketplace digest', 'marketing', 'Your weekly {{platform_name}} digest', 'Here is your weekly marketplace summary.', 'Open digest'],
            'discount_promotion_email' => ['Discount/promotion email', 'marketing', 'Marketplace promotion', 'A new marketplace promotion is available.', 'Explore now'],
            'new_category_service_announcement' => ['New category/service announcement', 'marketing', 'New services on {{platform_name}}', 'New categories and services are now available.', 'Explore categories'],
            're_engagement_email_for_inactive_users' => ['Re-engagement email for inactive users', 'marketing', 'Come back to {{platform_name}}', 'Your marketplace dashboard has new activity waiting.', 'Open dashboard'],
        ];
    }

    private static function htmlBody(string $heading, string $summary, string $cta): string
    {
        return <<<HTML
<h2>{$heading}</h2>
<p>Hello {{user_name}},</p>
<p>{$summary}</p>
<p>{{notification_detail}}</p>
<p><a class="button" href="{{action_url}}">{$cta}</a></p>
HTML;
    }

    private static function textBody(string $heading, string $summary): string
    {
        return "{$heading}\n\nHello {{user_name}},\n\n{$summary}\n\n{{notification_detail}}\n\nOpen: {{action_url}}";
    }
}
