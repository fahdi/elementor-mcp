# EMCP Tools 3.17.0 preparation

Status: prepared locally. No push, tag, GitHub release, Freemius upload or website deployment is authorized by this preparation task.

3.17.0 has not been published. This release combines the earlier History improvements with all subsequent builder and Widget Builder work.

## Contents

- History creation undo, page settings and CSS recording, conflict guards and verified rollback restoration.
- Twelve standalone builder integrations including Elementor and BeBuilder, plus Gutenberg and five independently enabled block packs. Vendor feature coverage and supported versions are integration-specific; refer to each integration guide and bundled skill.
- Completed Oxygen workflows and added Kirki, WPBakery, Beaver Builder, Visual Composer, Blocksy separation and Otter Free/Pro support.
- Widget Builder query, shortcode and remote-source data controls, remote-key management, registry synchronization and reserved Elementor control-name validation. Changes made with Claude are retained.
- Runtime and downloadable skills, native API probes, MCP fixtures and regression tests.
- Templates navigation now follows its module toggle even when another builder is selected.
- Shared agent guidance stays in AGENTS.md, imported by CLAUDE.md. Existing widget QA screenshots are retained under docs/qa/2026-09-widget-builder, outside the installable archives.

## Checks

The preparation runs both PHPUnit suites, Node proxy tests, syntax checks of changed PHP, whitespace validation, manifest completeness and both release ZIP verifiers. ZIPs are built after committing Pro first and updating the parent gitlink, because the free archive is derived from committed HEAD. No local credentials or MCP configuration are included.

Pre-build results: 2,888 Pro tests and 12,654 assertions, with six existing skips; 232 public tests and 602 assertions; all 17 proxy tests passed. All 70 changed PHP files passed syntax checks. Manifest review added coverage for three pre-existing Pro-only files: the WooCommerce adapter, FunnelKit adapter and Pro usage client.

Build command: `bash pro/tools/build-release.sh`. Outputs: `releases/emcp-tools-3.17.0.zip` and `releases/emcp-pro-3.17.0.zip`. Local package hashes are recorded in `releases/SHA256SUMS-3.17.0.txt` after verification.

## Scope limits

Live integration results describe the particular installed vendor versions and fixtures; not every premium add-on or vendor feature has an authoring API. Visual Composer needs paired native data/render artifacts. Otter static blocks require native Gutenberg save markup, while Atomic Wind uses its own browser CSS compiler. Individual guides record further exclusions. The six pre-existing skipped Pro tests remain skipped, so a passing run is not full coverage of those paths.

The integration pages were tested on the local test site through fresh MCP processes. Some recent integrations used process-local source bridges rather than a complete plugin deployment; archive checks are additional packaging validation, not a new installed-ZIP acceptance test.

## Publication handoff

After explicit authorization, push the private Pro commit before the public parent, then perform the established tag, free GitHub ZIP release and premium Freemius upload workflow. Keep Freemius's generated free build unreleased. The normal release-finish social copy and website changelog rebuild occur only as part of that authorized publication workflow.
