<?php

return [
    'language' => 'Language', 'dashboard' => 'Dashboard', 'content' => 'Content Management', 'operations' => 'Operations & Support', 'directory' => 'Legal Directory', 'system' => 'System Management',
    'resources' => ['articles' => 'Articles', 'article_translations' => 'Article Translations', 'categories' => 'Categories', 'consultations' => 'Consultation Requests', 'countries' => 'Countries', 'educationals' => 'Educational Resources', 'faqs' => 'FAQs', 'homepage' => 'Homepage', 'legal' => 'Legal References', 'authorities' => 'Reporting Authorities', 'settings' => 'Site Settings', 'users' => 'Users'],
    'fields' => [
        'article' => 'Article',
        'approved_by' => 'Approved by',
        'category' => 'Category',
        'country' => 'Country',
        'verification_status' => 'Verification status',
        'verified_by' => 'Verified by',
    ],
    'statuses' => ['unverified' => 'Unverified', 'in_review' => 'In review', 'verified' => 'Verified', 'expired' => 'Expired'],
    'educational_types' => ['guide' => 'Guide', 'tool' => 'Tool', 'checklist' => 'Checklist', 'video' => 'Video'],
    'overview' => [
        'kicker' => 'Platform control center', 'welcome' => 'Welcome, :name', 'intro' => 'Manage requests, organize content, and oversee platform services in one place.',
        'today' => 'Today', 'view_site' => 'View website', 'quick_actions' => 'Quick actions',
        'action_requests' => 'Consultation requests', 'action_operations' => 'Operations board', 'action_article' => 'New post', 'action_event' => 'New event', 'action_countries' => 'Country directory', 'action_homepage' => 'Homepage sections',
        'attention' => 'Needs your attention', 'attention_urgent' => 'Urgent or critical', 'attention_unassigned' => 'Unassigned', 'attention_overdue' => 'No reply within 24h', 'all_clear' => 'No pending requests need action right now.',
        'new_requests' => 'New requests', 'open_requests' => ':count open requests', 'published_articles' => 'Published posts', 'of_total' => 'Out of :count total',
        'members' => 'Members', 'upcoming_events' => ':count upcoming events', 'verified_countries' => 'Verified countries',
        'requests' => 'Requests', 'trend_heading' => 'Request activity', 'trend_description' => 'Requests received per day over the last 14 days (Oman time)',
        'status_heading' => 'Status breakdown', 'status_description' => 'All requests by current status',
        'latest_heading' => 'Latest open requests', 'latest_description' => 'Most recent requests that are not closed yet. Click a row to open it.', 'latest_empty' => 'No open requests',
        'request_number' => 'Request no.', 'priority' => 'Priority', 'status' => 'Status', 'received' => 'Received',
        'categories' => ['blackmail' => 'Online blackmail', 'account' => 'Account compromise', 'privacy' => 'Digital privacy', 'evidence' => 'Evidence preservation', 'other' => 'Other help'],
        'urgency' => ['normal' => 'Normal', 'urgent' => 'Urgent', 'critical' => 'Critical'],
    ],
];
