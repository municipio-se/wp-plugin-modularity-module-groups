# Release process

## First stable release

The prepared first candidate is 1.0.0 with independent SemVer. It remains
unreleased pending explicit publication approval. Composer derives the release
version from the tag; do not add a version field to composer.json.

The stable contract covers documented grouping behavior, editor interactions and
preserved row keys, postid, hidden, columnWidth and background values.
Incompatible changes require a major release; compatible additions and fixes use
minor and patch releases. No activation or read-time data migration runs.

## Compatibility and acceptance

The editor adapter is restricted to Municipio >=6.43.2 <6.44.0. Do not widen
that gate without new compatibility tests. The primary reference target is
WordPress 6.9.4, PHP 8.3 and Municipio 6.43.3; fresh installation and a
reference without saved group data must also be covered. Record exact dependency
versions.

On 2026-09-14, the suite passed on PHP 8.3.33: 15 PHP tests / 37 assertions and
9 Node tests. Composer validation and lint passed with existing diagnostics. The
drag recovery fix received Nora stage acceptance on 2026-09-16 in Firefox on
macOS: invalid drops, moving existing modules, drops outside the editor,
restored controls, valid drops and save/reload. Upstream placement restrictions
were preserved. Other module types and placement combinations are accepted
support limitations, not verified cases. Runtime files remain identical to
deployed 565006d; only the plugin header changes. The existing tests cover empty
groups, absent group data, key preservation and save/reload round trips.

## Publication and rollback

After acceptance, align the changelog and plugin header for 1.0.0. Keep the
existing content-specific editor asset identifier unless its content changes.
Run composer validate --strict, composer format, composer test and composer
lint. Verify a clean Composer installation including editor assets and
autoloading.

Obtain approval for the exact commit, then publish an annotated 1.0.0 tag and
matching GitHub release. Verify Packagist indexing and source/dist references
against the peeled tag commit. Tags are immutable; corrections get a new tag.
Approved consumers require ^1.0 with a reviewed, committed lockfile and separate
deployment approval. Rollback restores the previous code and lockfile; stored
group metadata must remain readable and unchanged.
