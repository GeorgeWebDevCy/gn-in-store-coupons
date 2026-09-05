# Agent Instructions

## Living Project Guide

This file is maintained as work progresses. During each task, update it when
verified information changes how future work should be done.

- Record lasting project conventions, architecture decisions, working build and
  test commands, and recurring pitfalls as they are confirmed.
- Update or remove stale guidance when the implementation changes.
- Keep entries concise and actionable. Consolidate existing guidance instead of
  appending duplicates.
- Preserve user instructions and unrelated contributions.
- Do not record secrets, temporary task status, speculative findings, or a running
  transcript. Do not make an edit solely to mark that a task occurred.
- Before finishing a task, check whether its discoveries or decisions warrant an
  update to this file. Include any warranted update in the same task.

## Development Tooling

- Keep agent and development tooling outside the plugin repository. Never commit
  or ship it with the plugin.
- Install tools such as Playwright in a separate tools directory or use an
  existing MCP installation. Do not add tooling dependencies, package manifests,
  lockfiles, browser binaries, or node_modules to this repository.
- Keep browser profiles, credentials, MCP configuration, temporary test scripts,
  screenshots, traces, reports, and caches outside the repository and release ZIPs.
- Use available browser tooling to inspect and verify UI changes visually.
- Review staged files before every commit to ensure tooling and generated
  artifacts are excluded. Only plugin runtime dependencies belong in the plugin.

## Plugin Updates

- Marketing lives below the settings form: native canvas PNG exports (1080 square
  and 1080x1920 story) and captions, Greek and English, use saved offer values.
  Graphics are promotional, never redeemable codes. Keep exports/tooling outside
  the repo; the canvas generator is plugin runtime code.
- The Mail Mint announcement is one bilingual campaign, Greek first then English.
  Leave campaigns draft, unscheduled and unsent unless the user explicitly asks
  to send. The campaign is independent of automatic coupon issuance and must be
  reviewed separately when offer settings change. Store its ID in the Options API
  under gn_coupons_marketing_campaign_id, never hardcode site campaign IDs.
- Mail Mint 1.31.1 exposes CampaignTools::upsertCampaign/composeCampaignEmail;
  these native APIs write editable builder JSON and HTML. Use draft status and
  preserve the unsubscribe footer. Its edit route is #/campaign/regular/edit/ID.
  Empty recipient lists revert to All Contacts in the editor after reload; an
  empty selection is not a send safeguard. Verify draft/unscheduled status and
  require audience review before sending.
- The standard offer is EUR 15 off purchases of EUR 150 or more, with no expiry.
  Snapshot fixed amount, EUR currency, and minimum purchase on issuance. Staff
  verify the pre-discount purchase total in store; no till integration exists.
  Preserve legacy percentage offer snapshots when rendering older records.
- Coupons have no product category restrictions. Do not add category selectors,
  category validation, or category captions to coupon settings, views, or emails.
- Coupon references format the permanent ledger primary key as CPN-000123.
  They are search/display identifiers, not public bearer tokens or new redemption
  codes. Search supports formatted references and bare numeric IDs; keep the
  original random code and secret link unchanged. Previews use CPN-PREVIEW.
- Sample recipient is a saved sample_user_id, never a hardcoded name or email.
  Preview and sample emails use that user's full name (display-name fallback)
  and email. Samples are non-redeemable, have no bearer link, do not enter the
  ledger, and never consume eligibility. Sample sends require admin POST + nonce.
  Customer-facing coupons and emails now display the customer's name and email.
- Coupons are in-store only, one ever per normalized email and linked user ID.
  Never create WooCommerce shop_coupon records. Never delete the issuance ledger
  on redemption, revocation, expiry, deactivation, or uninstall.
- The custom gn_in_store_coupons table provides unique email-hash/user-ID/code
  indexes and conditional redemption writes. This table is required for atomic
  lifetime deduplication; settings use the Options API.
- Automatic issuance starts paused. Enabling sends to existing subscribed Mail
  Mint contacts in selected lists and new WooCommerce customers. Never enable
  issuance on a live store as a side effect of testing or deployment.
- customer_enabled independently enables only new WooCommerce customer coupons;
  customer_list is their Mail Mint destination. Keep full enabled/list scanning
  paused when activating only registration. The sender filters by WooCommerce
  source in this mode. The hourly scan still drains this permitted email queue.
- Registration uses woocommerce_created_customer at priority 20 and a delayed
  user_register role check. Native ContactData/ContactModel and group pivot APIs
  add list membership. Per explicit store-owner instruction, new contacts default
  to subscribed, without recording explicit consent; never overwrite an existing
  subscription status. Sync failures retry
  three times through gn_coupons_customer. Lifetime ledger checks still apply.
- Use Mail Mint ContactModel/ContactGroupModel/ContactGroupPivotModel APIs.
  Hooks verified on the site: mailmint_list_applied (lists, contact IDs) and
  mint_subscriber_status_to_subscribed (contact ID). An hourly bounded scan covers
  existing contacts and bulk/status changes. WooCommerce uses
  woocommerce_created_customer; user_register defers a customer-role check.
- All staff mutations require manage_woocommerce, POST and a nonce. Settings and
  previews require manage_options. Retried emails reuse the same code.
- Issued offer terms are snapshots; expiry is stored in UTC. Coupon links use a
  256-bit bearer token and no-cache/noindex headers, with no theme analytics.

- Activation requires WooCommerce (woocommerce) and Mail Mint (mail-mint),
  declared in the bootstrap Requires Plugins header. WordPress 6.5 is the minimum
  for native dependency enforcement. Mail Mint Pro is optional, not a substitute.

- GitHub updates are registered on plugins_loaded in gn-in-store-coupons.php.
  The source is GeorgeWebDevCy/gn-in-store-coupons with main as the fallback branch.
- Plugin Update Checker 5.7 is bundled in includes/plugin-update-checker as a
  runtime dependency. Keep its upstream files and MIT license intact. Upstream
  tag v5.7 points to commit 275a96a2a18d03c34c87f35cb68673c8c49ac3b1.
- PUC prefers the latest non-prerelease GitHub release, then the highest version
  tag, then main. Once releases exist, publish a newer release to deliver updates;
  pushing only main will not supersede an existing release. Avoid prerelease
  version tags because the tag fallback does not filter them out.
- Keep the plugin Version header, GN_IN_STORE_COUPONS_VERSION, and README.txt
  stable tag aligned. Add a changelog entry with each version bump.
- Updates use GitHub source archives; include the runtime library in every
  version. .gitattributes excludes AGENTS.md from these archives. Keep tooling
  outside the repository and never include credentials in update configuration.
- PHP syntax check: run php -l for each PHP file through rtk. Keep any integration
  test harness outside the repository.

## Commits and Pushes

- Commit and push completed work as we go, at meaningful checkpoints and before
  finishing each task that changes files.
- Run checks appropriate to the change before committing, and use clear commit
  messages describing the completed work.
- Routine commits and pushes are authorized by the user; do not ask for repeated
  confirmation. Include only changes belonging to the task.
- If a commit or push fails, resolve the issue when possible and report any
  remaining blocker. Do not force-push unless explicitly authorized.

<!-- codebase-memory-mcp:start -->
# Codebase Memory

## Codebase Knowledge Graph (codebase-memory-mcp)

This project uses codebase-memory-mcp to maintain a knowledge graph of the codebase.
ALWAYS prefer MCP graph tools over grep/glob/file-search for code discovery.
Run `index_repository` first if the project is not indexed.

### Priority Order
1. `search_graph` - find functions, classes, routes, variables by pattern
2. `trace_path` - trace who calls a function or what it calls
3. `get_code_snippet` - read specific function/class source code
4. `check_index_coverage` - validate candidate paths and missed ranges before claims
5. `query_graph` - run Cypher queries for complex patterns
6. `get_architecture` - high-level project summary

### Evidence tiers
- **Scout (Tier 1):** quick positive lookup with few calls and targeted source checks. Mark it provisional; do not make negative or exhaustive claims.
- **Verify (Tier 2, default):** task-directed graph evidence, relevant trace directions, exact snippets for material claims, and relevant pagination.
- **Auditor (Tier 3):** bounded-scope full verification with current generation, complete relevant pagination, both call directions and broader relationships when material, and every limitation disclosed.
- After candidate paths are known in any tier, call `check_index_coverage` once with every evidence path. Add relevant scopes for negative or exhaustive claims. A clean result means no recorded gap, not proof of completeness. For partial, skipped, excluded, stale, pending, or unknown coverage, read/grep the reported ranges or scope before relying on graph results.

### When to fall back to grep/glob
- Searching for string literals, error messages, config values
- Searching non-code files (Dockerfiles, shell scripts, configs)
- When MCP tools return insufficient results or are unavailable; disclose relevant evidence limitations

### Examples
- Find a handler: `search_graph(name_pattern=".*OrderHandler.*")`
- Who calls it: `trace_path(function_name="OrderHandler", direction="inbound")`
- Read source: `get_code_snippet(qualified_name="pkg/orders.OrderHandler")`

### Session resets and subagents
- At session start or after compaction, confirm the nearest graph project and generation with `list_projects` or `index_status`, then choose Scout, Verify, or Auditor.
- Before spawning a subagent, query the graph and coverage in the parent. Pass the tier, project, generation/freshness, bounded scope, queries and pagination state, qualified symbols, paths, call-chain findings, coverage evidence with ranges/reasons, source fallback already performed, and unresolved questions in the delegated task context.
- Do not assume subagents inherit MCP access or the parent conversation. If a child lacks MCP tools, it must not call or claim MCP access. It should use the supplied evidence and read/grep exact source, especially every reported missed-coverage range.
<!-- codebase-memory-mcp:end -->

## Shell Commands

Read and follow `C:\Users\georg\.codex\RTK.md`.
Always prefix shell commands with `rtk`. Use `rtk proxy <command>` when a raw
command is needed without filtering.

@C:\Users\georg\.codex\RTK.md
