# Modularity Module Groups

Adds the verified Municipio LTS module-group workflow to modern Municipio
without changing the existing `modularity-modules` storage format.

## Compatibility contract

- Composer package: `municipio/wp-plugin-modularity-module-groups`
- Installer name and WordPress directory: `modularity-module-groups`
- Plugin name: **Modularity Module Groups**
- Text domain: `modularity-module-groups`
- Verified Municipio versions: `>=6.43.2 <6.44.0`

Each placed module keeps its existing row key plus `postid`, `hidden`, and
`columnWidth`. The optional `background` remains stored on that same row.
Adjacent rows with the same normalized background form a group. Reading or
activating the plugin does not write post metadata or options.

The editor integration is intentionally disabled outside the verified Municipio
6.43 patch range. In that case the original Modularity editor remains active,
an admin notice explains the incompatibility, and saved data remains untouched.

Frontend group boundaries are part of Modularity's fragment-cache context.
This keeps wrappers tied to each placed row when the same module post is reused
in several groups. Municipio versions without that cache-context contract fall
back to disabled fragment caching only on pages that contain grouped layouts.

## Development

```console
composer install
composer format
composer test
composer lint
```
