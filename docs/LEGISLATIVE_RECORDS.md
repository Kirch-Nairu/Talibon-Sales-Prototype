# Legislative Records Module

The prototype centralizes searchable legislative metadata for ordinances, resolutions, executive orders, and other issuances.

All authenticated employees may search and read records intended for the internal repository. Only the Legislative Office role or System Administrator may add records in the prototype.

Search currently covers record number, title, summary, and keywords. PostgreSQL remains the production database direction; more advanced full-text indexing and document-file ingestion can be added during archival migration.

The prototype intentionally stores synthetic legislative examples. It must not present generated examples as actual Municipality of Talibon ordinances or resolutions.

Future work includes protected source-file upload, document version/history, related/superseding legislation links, retention rules, and bulk migration tooling.