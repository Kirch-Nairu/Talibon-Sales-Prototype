<?php

return [
    'dataMode' => 'prototype',
    'sampleLabel' => 'PROTOTYPE SAMPLE DATA',
    'municipality' => 'Municipality of Talibon, Bohol',
    'hero' => [
        'eyebrow' => 'One Talibon Digital Government Portal',
        'title' => 'One Talibon',
        'lead' => 'Connected services. Clear information. Better coordination.',
        'description' => 'One Talibon brings municipal information and employee access into one portal. This public preview is for evaluation.',
    ],
    'services' => [
        [
            'title' => 'Business permits & licensing',
            'description' => 'Find guidance about local business and permit services available through the municipality.',
            'group' => 'services',
            'meta' => 'Information only',
            'action' => 'See contact information',
            'href' => '#contact',
        ],
        [
            'title' => 'Civil & community services',
            'description' => 'Find guidance about civil and community services available through the municipality.',
            'group' => 'services',
            'meta' => 'Information only',
            'action' => 'See contact information',
            'href' => '#contact',
        ],
        [
            'title' => 'Municipal offices',
            'description' => 'Find general guidance for identifying the municipal office responsible for a public concern.',
            'group' => 'services',
            'meta' => 'Directory preview',
            'action' => 'About Talibon',
            'href' => '#about',
        ],
        [
            'title' => 'News & public information',
            'description' => 'Read sample municipal announcements, notices, and public updates prepared for this prototype.',
            'group' => 'information',
            'meta' => 'Prototype content',
            'action' => 'Read news & notices',
            'href' => '#news',
        ],
        [
            'title' => 'Emergency advisories',
            'description' => 'See how public advisories are presented in this evaluation prototype. No active warning is issued here.',
            'group' => 'information',
            'meta' => 'Prototype content',
            'action' => 'View advisory preview',
            'href' => '#news',
        ],
        [
            'title' => 'Public documents & transparency',
            'description' => 'Review the prototype area for public documents, reports, and transparency resources.',
            'group' => 'information',
            'meta' => 'Prototype content',
            'action' => 'See public documents',
            'href' => '#transparency',
        ],
    ],
    'transparency' => [
        ['label' => 'Published Documents', 'value' => 'Sample library', 'note' => 'Sample library; no official documents are published here.'],
        ['label' => 'Municipal Reports', 'value' => 'Sample summaries', 'note' => 'Sample summaries; not official municipal reports.'],
        ['label' => 'Public Notices', 'value' => 'Sample notices', 'note' => 'Sample notices for evaluation.'],
    ],
    'projects' => [
        ['title' => 'Community Infrastructure', 'summary' => 'Sample summary of a municipal infrastructure project.', 'tag' => 'Sample project'],
        ['title' => 'Service Modernization', 'summary' => 'Sample update on municipal digital services.', 'tag' => 'Prototype concept'],
        ['title' => 'Public Information Access', 'summary' => 'Sample initiative to improve access to public information.', 'tag' => 'Sample initiative'],
    ],
    'dashboard' => [
        ['label' => 'Public projects', 'value' => 'Sample view', 'detail' => 'Future public project summaries'],
        ['label' => 'Service availability', 'value' => 'Prototype', 'detail' => 'Public service-information status'],
        ['label' => 'Published information', 'value' => 'Sample view', 'detail' => 'Approved public documents and notices'],
        ['label' => 'Announcements', 'value' => 'Prototype', 'detail' => 'Public news and advisories'],
    ],
    'news' => [
        ['type' => 'Advisory', 'title' => 'Municipal advisory preview', 'summary' => 'Sample advisory for evaluation. No active warning is issued here.', 'date' => 'Prototype'],
        ['type' => 'Event', 'title' => 'Community event preview', 'summary' => 'Sample event for evaluation. An official schedule has not been published.', 'date' => 'Prototype'],
        ['type' => 'News', 'title' => 'Municipal update preview', 'summary' => 'Sample municipal update for evaluation.', 'date' => 'Prototype'],
    ],
    'contact' => [
        'heading' => 'Municipality of Talibon',
        'description' => 'Use Employee Login to open the intra-office portal. Public contact details await municipal confirmation.',
        'location' => 'Talibon, Bohol, Philippines',
    ],
];
