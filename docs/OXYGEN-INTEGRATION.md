# Oxygen 6 integration

## Plan and implementation

Target the installed Oxygen **6.1.3** on both local sites. Gate the adapter to
Oxygen mode and 6.1.3–6.1.x, excluding Oxygen Classic and Breakdance mode.

1. Verify native tree, storage keys, permissions, save/cache APIs and styles.
2. Register standalone Oxygen selection, dedicated tab, four sections and 15 tools.
3. Provide native page CRUD, element schemas, templates/components discovery,
   design-system reads, supported class-style schema and single-class save.
4. Keep seven write tools disabled on upgrade; preserve prior tool choices and
   independent Gutenberg block-pack toggles.
5. Bundle a self-contained guide for injection and both download formats.
6. Test validation/permissions/defaults and native editor/frontend. Test pages
   belong only on elementor-mcp.test and must be authored over MCP.

## Native contracts

- Shared Breakdance PHP namespace, but BREAKDANCE_MODE=oxygen.
- Native storage: _oxygen_data, _oxygen_futurelayer_meta,
  _oxygen_template_settings; no writes to _breakdance_* or classic ct_* data.
- Nested root tree, unique IDs and _nextNodeId. Native save_document handles
  modified timestamps, revisions, stylesheet and dependency caches.
- CSS classes are global oxy_selectors_json_string records. Elements attach
  class IDs at meta.classes. Class properties are breakpoint-first; saveSelectors
  manages global revisions and cache generation. Existing collections and other
  selectors are preserved. Hash checks and EMCP locks guard stale/concurrent calls.
- Native editor races after the hash check cannot be eliminated by EMCP locks;
  existing WordPress editor locks are honored for page writes.

## Coverage audit — September 17, 2026

The original 15-tool release was a page-building foundation, not complete
Oxygen coverage. The extension now has **21 tools across seven sections**.
Official documentation reviewed: documentation index, Creating Templates,
Components, Design Library and Creating Design Sets. Installed 6.1.3 source is
the authority for actual native storage, registration and save behavior.

| Area | Implemented | Remaining work |
|---|---|---|
| Pages and elements | Native CRUD, schemas, hashes, revisions, caches | Specialized elements and nesting |
| Template library | List/read/create and copy, tree editing | Rename/trash/restore, inheritance/content-area workflows |
| Headers and footers | Create/edit/copy, location/priority/disabled | Per-page condition authoring and frontend matching tests |
| Template conditions | Existing settings read and preserved | Typed condition discovery, value lookup, rule-group authoring |
| Components | Create/read/edit master, insert default native instance | Property definitions/overrides, child visibility, nesting |
| Design Library | Registered set/settings discovery, local copies | Remote browsing/import, dependency remapping, full-site import |
| Design Set publishing | Sharing flags read without passwords | Export/sharing configuration and password management |
| Classes | Core responsive class save and native schema | Nested selectors, pseudo states, complete effects/control coverage |
| Variables and globals | Read | Validated writes, collections and global import workflows |
| Dynamic data and queries | Native element discovery | Bindings, conditions, loops, pagination and filters |
| Interactions | Native discovery only | Actions, triggers, animations and responsive behavior |
| Optional extensions | Native registered-element discovery | Breakdance Elements/Forms extension-specific authoring |
| Settings | Version/access/breakpoint discovery | Custom fonts/breakpoints, preferences and settings writes |
| Revisions | Prior revisions on supported writes | Explicit list/restore tools and global restore workflows |
| Developer features | Native schema discovery | Code elements, Element Studio and custom integrations |

Do not label the integration complete against all Oxygen features until these
remaining workflows have been implemented and tested. Prioritize condition
editing and component properties, then remote Design Library dependency handling,
then global design controls. Test each with native-editor round trips and MCP
fixtures exclusively on elementor-mcp.test.

### Added operations

- get-template-schema: lazily registers Oxygen's native template locations for
  MCP requests, which do not otherwise initialize the native template admin UI.
- create-template: accepts template/header/footer/component, checks native type
  access and create/publish capabilities, initializes with save_document.
- update-template-settings: hash-checked location/priority/disabled patch,
  preserves conditions, prior revision, native save and readback.
- insert-component: validated native reference to a readable component master;
  rejects wrong post types, nesting and per-instance overrides.
- get-design-library: local settings and registered sets; no remote fetch.
- copy-library-item: same-site reuse with source hash and supported tree checks;
  retains class/component references and disables copied matching templates.

Oxygen forces library records to publish; template inactivity uses its disabled
flag. Pages remain drafts. Defaults migration 45 disables the four new write
tools without changing earlier choices.

## References

Installed plugin.php; plugin/data/{tree,save}.php; plugin/util/bdox.php;
plugin/breakdance-oxygen/selectors.{php,twig}; native element definitions and
builder class property panel. Official docs: https://oxygenbuilder.com/documentation/.
Public skill discovery found Respira's platform-specific workflow:
https://www.respira.press/skills/build-oxygen6-page. EMCP's guide is independently
written; no third-party skill source is redistributed.

## Validation completed

- Main Pro suite: 2,771 tests, 11,147 assertions, six existing skips, no failures.
- Public suite: 232 tests, 602 assertions, no failures.
- PHP lint passed for all eight changed runtime files. Existing unrelated
  trailing blank lines remain in three theme skills; Oxygen files are clean.
- Both skill archive formats tested against the runtime skill; MCP get-skill
  successfully returned emcp-oxygen.
- Twelve read-only runtime assertions passed: selected builder, Breakdance
  isolation, Gutenberg coexistence, draft status, Oxygen-only storage, revisions,
  unknown element rejection, disabled/inactive tools, post capability and anonymous access.
- Test draft 2001, FIELDWORK, created entirely through MCP with 46 native nodes
  and 15 dedicated classes. All seven write operations exercised, including
  class update; stale page and design hashes rejected. Temporary test element
  removed over MCP. No dev-site test page was created.
- Native editor exposes text, h1 tag and class association. Desktop frontend,
  tablet (754px content width) and phone (386px) previews checked; responsive
  heading sizes 76/62/44px and no horizontal content overflow after layout settles.
- Oxygen 6.1.3 URL-image alt quirk documented in the bundled guide. Existing
  theme/header configuration was not edited.
- Nine runtime/skill files copied to the flattened test plugin, backups retained
  in releases/test-site-backup-oxygen, and final hashes matched. Seven write tools
  restored to disabled defaults; final MCP create-page request was denied.

Preview: https://elementor-mcp.test/?page_id=2001&preview=true (signed-in draft).

## Extension validation — September 17, 2026

- Main Pro suite: 2,772 tests, 11,174 assertions, six existing skips; no failures.
- Public suite: 232 tests, 602 assertions; no failures. PHP lint passed.
- Both downloadable skill formats contain the updated runtime skill.
- MCP authored fixtures: header 2015, footer 2017, template 2019, component 2021,
  copied header 2030 and copied page 2032. No dev-site test content created.
- Native runtime checks: 14 passed, including real component rendering in page
  2032, populated template locations, inactive template records, stale-hash and
  unknown-location rejection, recursive/wrong-type component rejection.
- Original FIELDWORK page 2001 restored over MCP after temporary instance test.
- Updated runtime/skill files deployed to test plugin. Temporary tool opt-ins
  restored; create-template then rejected as disabled. Dev migration applied.
- Native editor round-trip checks for these added library workflows remain
  pending; the earlier desktop/tablet/mobile page checks are not counted as
  coverage of these new workflows.

Component/library test draft: https://elementor-mcp.test/?page_id=2032&preview=true
