<?php

return [
    'cache_key' => 'platform_settings.values',

    'groups' => [
        [
            'key' => 'finance_commission',
            'title' => 'Finance & Commission',
            'description' => 'Control marketplace fees, referral rewards, payout holds, and refund behavior.',
            'settings' => [
                ['type' => 'number', 'name' => 'platform_commission', 'label' => 'Platform commission', 'description' => 'Commission collected from each completed seller order.', 'value' => 20, 'suffix' => '%'],
                ['type' => 'number', 'name' => 'buyer_service_fee', 'label' => 'Buyer service fee', 'description' => 'Service fee added to buyer checkout totals.', 'value' => 5, 'suffix' => '%'],
                ['type' => 'number', 'name' => 'referral_commission', 'label' => 'Referral commission', 'description' => 'Reward rate for valid referral conversions.', 'value' => 5, 'suffix' => '%'],
                ['type' => 'select', 'name' => 'referral_duration', 'label' => 'Referral reward duration', 'description' => 'How long a referrer can earn from a referred user.', 'value' => 'First paid order', 'options' => ['First paid order', '30 days', 'Lifetime']],
                ['type' => 'number', 'name' => 'minimum_payout', 'label' => 'Minimum payout amount', 'description' => 'Minimum cleared balance before a seller can withdraw.', 'value' => 25, 'prefix' => '$'],
                ['type' => 'number', 'name' => 'payout_hold_period', 'label' => 'Payout hold period', 'description' => 'How long funds stay pending after buyer approval.', 'value' => 7, 'suffix' => 'days'],
                ['type' => 'select', 'name' => 'refund_fee_behavior', 'label' => 'Refund fee behavior', 'description' => 'Decide whether platform fees are returned during refunds.', 'value' => 'Manual review', 'options' => ['Return full amount', 'Keep platform fee', 'Manual review']],
            ],
        ],
        [
            'key' => 'seller_gig_rules',
            'title' => 'Seller & Gig Rules',
            'description' => 'Define publishing, verification, portfolio, and catalog quality controls.',
            'settings' => [
                ['type' => 'toggle', 'name' => 'manual_gig_approval', 'label' => 'Manual gig approval', 'description' => 'Review new gigs before they are listed publicly.', 'value' => true],
                ['type' => 'toggle', 'name' => 'gig_edit_reapproval', 'label' => 'Gig edits require re-approval', 'description' => 'Send edited gig titles, prices, and galleries back to moderation.', 'value' => true],
                ['type' => 'toggle', 'name' => 'verification_before_payout', 'label' => 'Seller verification before payout', 'description' => 'Require verified identity before sellers can withdraw funds.', 'value' => true],
                ['type' => 'toggle', 'name' => 'verification_before_publishing', 'label' => 'Seller verification before publishing gigs', 'description' => 'Block public gig publishing until identity checks are complete.', 'value' => false],
                ['type' => 'number', 'name' => 'max_active_gigs', 'label' => 'Maximum active gigs per seller', 'description' => 'Default active gig limit for standard sellers.', 'value' => 20, 'suffix' => 'gigs'],
                ['type' => 'select', 'name' => 'featured_gig_eligibility', 'label' => 'Featured gig eligibility', 'description' => 'Minimum seller quality level for homepage or category promotion.', 'value' => 'Level 2 and above', 'options' => ['All verified sellers', 'Level 1 and above', 'Level 2 and above', 'Top rated only']],
            ],
        ],
        [
            'key' => 'orders_disputes',
            'title' => 'Orders & Disputes',
            'description' => 'Tune delivery automation, buyer reminders, revisions, and resolution windows.',
            'settings' => [
                ['type' => 'number', 'name' => 'auto_complete_days', 'label' => 'Auto-complete delivered orders', 'description' => 'Complete orders automatically if buyers do not respond.', 'value' => 3, 'suffix' => 'days'],
                ['type' => 'number', 'name' => 'late_warning_hours', 'label' => 'Late delivery warning threshold', 'description' => 'Warn sellers before orders become late.', 'value' => 12, 'suffix' => 'hours'],
                ['type' => 'number', 'name' => 'requirements_cancel_days', 'label' => 'Auto-cancel incomplete requirements', 'description' => 'Cancel orders when buyers do not submit requirements.', 'value' => 7, 'suffix' => 'days'],
                ['type' => 'number', 'name' => 'dispute_window_days', 'label' => 'Dispute opening window', 'description' => 'How long buyers can open a dispute after delivery.', 'value' => 14, 'suffix' => 'days'],
                ['type' => 'toggle', 'name' => 'dispute_auto_escalation', 'label' => 'Dispute auto-escalation', 'description' => 'Escalate high-priority disputes after the SLA window.', 'value' => true],
            ],
        ],
        [
            'key' => 'referral_growth',
            'title' => 'Referral & Growth',
            'description' => 'Manage acquisition incentives, signup bonuses, and fraud review rules.',
            'settings' => [
                ['type' => 'toggle', 'name' => 'enable_referrals', 'label' => 'Enable referral program', 'description' => 'Allow users to invite others and earn referral rewards.', 'value' => true],
                ['type' => 'number', 'name' => 'signup_bonus', 'label' => 'New user signup bonus', 'description' => 'Credit applied to new eligible accounts.', 'value' => 10, 'prefix' => '$'],
                ['type' => 'number', 'name' => 'first_order_discount', 'label' => 'First order discount', 'description' => 'Discount available on a user first purchase.', 'value' => 15, 'suffix' => '%'],
                ['type' => 'number', 'name' => 'referral_reward_cap', 'label' => 'Maximum referral reward cap', 'description' => 'Maximum reward a single user can earn per month.', 'value' => 250, 'prefix' => '$'],
                ['type' => 'toggle', 'name' => 'referral_fraud_review', 'label' => 'Referral fraud review', 'description' => 'Flag unusual referral activity for manual review.', 'value' => true],
            ],
        ],
        [
            'key' => 'trust_platform',
            'title' => 'Trust, Safety & Platform',
            'description' => 'Protect marketplace communication, uploads, localization, and admin access.',
            'settings' => [
                ['type' => 'toggle', 'name' => 'block_external_contact', 'label' => 'Block external contact sharing', 'description' => 'Detect and restrict messages that move work outside the platform.', 'value' => true],
                ['type' => 'number', 'name' => 'file_upload_limit', 'label' => 'Maximum file upload size', 'description' => 'Largest file size allowed in messages and order delivery.', 'value' => 50, 'suffix' => 'MB'],
                ['type' => 'text', 'name' => 'allowed_file_types', 'label' => 'Allowed file types', 'description' => 'Comma-separated extensions allowed in delivery and messages.', 'value' => 'jpg, png, pdf, zip, mp4'],
                ['type' => 'select', 'name' => 'default_language', 'label' => 'Default platform language', 'description' => 'Fallback language for public pages and dashboards.', 'value' => 'English', 'options' => ['English', 'Bangla']],
                ['type' => 'select', 'name' => 'default_currency', 'label' => 'Default currency', 'description' => 'Default marketplace currency display.', 'value' => 'USD', 'options' => ['USD', 'BDT', 'EUR', 'GBP']],
                ['type' => 'number', 'name' => 'admin_session_timeout', 'label' => 'Admin session timeout', 'description' => 'Sign out inactive admins after this duration.', 'value' => 30, 'suffix' => 'minutes'],
                ['type' => 'textarea', 'name' => 'maintenance_message', 'label' => 'Maintenance mode message', 'description' => 'Shown to users when maintenance mode is enabled.', 'value' => 'We are improving bdgigs and will be back shortly.'],
            ],
        ],
    ],
];
