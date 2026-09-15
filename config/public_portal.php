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
        ['title' => 'Business and Permits', 'description' => 'Information about business and permit services.', 'status' => 'Service information'],
        ['title' => 'Civil and Community Services', 'description' => 'Office guidance for civil and community services.', 'status' => 'Service information'],
        ['title' => 'Public Information', 'description' => 'Municipal announcements, notices and public documents.', 'status' => 'Public information'],
        ['title' => 'Emergency and Advisories', 'description' => 'A preview of how municipal advisories will appear.', 'status' => 'Information preview'],
        ['title' => 'Municipal Departments', 'description' => 'Find the office responsible for a municipal service.', 'status' => 'Directory preview'],
        ['title' => 'Transparency Resources', 'description' => 'A preview of approved public records and reports.', 'status' => 'Prototype preview'],
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
