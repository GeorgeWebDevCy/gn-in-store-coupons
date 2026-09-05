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
