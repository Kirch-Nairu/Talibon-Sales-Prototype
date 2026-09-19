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
        ['label' => 'Public document library preview', 'value' => 'Document preview', 'note' => 'No official documents are published in this prototype.'],
        ['label' => 'Municipal report preview', 'value' => 'Report preview', 'note' => 'Sample report information for layout evaluation; not an official municipal report.'],
        ['label' => 'Public notice preview', 'value' => 'Notice preview', 'note' => 'Sample notice information for evaluation; not an official municipal notice.'],
    ],
    'projects' => [
        ['title' => 'Community infrastructure update preview', 'summary' => 'Sample project-update structure for evaluation; no official project record is published here.', 'tag' => 'Project preview'],
        ['title' => 'Service modernization update preview', 'summary' => 'Sample municipal digital-service update for evaluation.', 'tag' => 'Program preview'],
        ['title' => 'Public information access update preview', 'summary' => 'Sample public-information initiative update for evaluation.', 'tag' => 'Initiative preview'],
    ],
    'dashboard' => [
        ['label' => 'Public projects', 'value' => 'Sample view', 'detail' => 'Future public project summaries'],
        ['label' => 'Service availability', 'value' => 'Prototype', 'detail' => 'Public service-information status'],
        ['label' => 'Published information', 'value' => 'Sample view', 'detail' => 'Approved public documents and notices'],
        ['label' => 'Announcements', 'value' => 'Prototype', 'detail' => 'Public news and advisories'],
    ],
    'news' => [
        ['type' => 'Advisory', 'title' => 'Municipal advisory preview', 'summary' => 'Sample advisory for evaluation. No active warning is issued here.', 'date' => ''],
        ['type' => 'Event', 'title' => 'Community event preview', 'summary' => 'Sample event for evaluation. An official schedule has not been published.', 'date' => ''],
        ['type' => 'News', 'title' => 'Municipal update preview', 'summary' => 'Sample municipal update for evaluation.', 'date' => ''],
    ],
    'contact' => [
        'heading' => 'Municipality of Talibon',
        'description' => 'Use Employee Login to open the intra-office portal. Public contact details await municipal confirmation.',
        'location' => 'Talibon, Bohol, Philippines',
    ],
];
