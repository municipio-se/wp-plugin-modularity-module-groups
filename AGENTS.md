# Repository instructions

## Scope

This plugin ports the verified Municipio LTS module-group editor and frontend
behavior to modern Municipio. Keep it independent of Municipio Cloud and the
deprecated standalone Modularity plugin.

Preserve the `modularity-modules` row keys and the existing `postid`, `hidden`,
`columnWidth`, and `background` values. Do not add activation migrations, write
data during reads, or copy private Municipio/Modularity core code into the
plugin.

The editor adapter is deliberately bound to the verified Municipio version. An
unsupported version must leave Modularity's editor untouched.

## Verification

Run these commands after changing PHP or runtime behavior:

```console
composer format
composer test
composer lint
```
