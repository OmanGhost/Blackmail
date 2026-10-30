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
];
