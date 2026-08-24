# Coding Excellence Standard

This standard is required for changes to the HEC WordPress CMS and its GraphQL
contract.

## Definition of done

1. Reproduce the defect with an existing-post fixture whenever editors report
   that data disappears after saving.
2. Add a regression test that covers the actual compatibility condition, not
   only the new or empty-record path.
3. Run PHP structural/runtime contracts and the MBA Docker acceptance flow.
4. For editor fields, save, leave the post, reopen it, and verify the persisted
   database value and GraphQL result. A Gutenberg “Post updated” notice alone is
   not proof because REST and legacy ACF meta-box requests can succeed or fail
   independently.
5. Ship branch → PR → review → merge; deployment is a separate verified step.

## ACF and editor fields

- Git-owned ACF definitions are canonical. A database-only field edit is not a
  deliverable.
- Preserve production field and group keys. When overlaying a database group,
  register the complete same-key group so one local child cannot shadow its
  siblings.
- Optional-field validation must not reject an existing legacy sentinel and
  discard unrelated edits. Document sentinels such as `0 = unordered` in both
  field instructions and tests.
- Test add and replace paths separately for image fields. Verify the attachment
  ID and ACF reference key after reopening the editor.
- Keep video provider fields independent. Existing posts may intentionally have
  both YouTube and Vimeo IDs.

## GraphQL and media contracts

- Every exposed field needs a resolver test for populated and empty values.
- Coordinate contract changes with `ytadvisors/hecmedia`; backend and consumer
  changes need compatible tests before either side deploys.
- Keep article body media, shared card thumbnails, and article-only heroes as
  distinct roles. Do not silently remap one role onto another.
- Degrade optional schema features locally; do not let one unavailable field
  blank unrelated queries.

## Refactoring and verification

- Add characterization tests before refactoring legacy PHP or ACF behavior.
- Prefer small named functions and explicit compatibility comments over broad
  hooks with hidden side effects.
- Run syntax checks, structural tests, runtime contracts, and Docker acceptance.
- Docker volumes can retain an older plugin copy after an image rebuild. Verify
  the code actually loaded inside the running container before trusting a test.
- Remove diagnostic output, temporary environment flags, and local QA compose
  overrides before committing.

## Review checklist

- [ ] Existing-post and new-post paths are covered where relevant.
- [ ] Save/reopen, database, and GraphQL evidence agree.
- [ ] ACF keys and media/provider semantics remain compatible.
- [ ] PHP tests and MBA Docker acceptance pass.
- [ ] No production data, credentials, temporary diagnostics, or deploy state
      are included.
