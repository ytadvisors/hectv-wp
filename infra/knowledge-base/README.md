# HEC transcript knowledge base

This state creates the S3 Vectors-backed replacement for the legacy HEC Media
Bedrock knowledge base. It intentionally does not import, modify, or delete the
legacy knowledge base (`ZKA5J7Y0WL`) or its OpenSearch Serverless collection.

The replacement preserves the retrieval inputs that affect relevance:

- source: `s3://srtlibrary-hecmedia/vimeo_captions/`
- embedding model: Titan Embeddings G1 - Text (`1,536` float dimensions)
- distance metric: Euclidean
- chunking: fixed `300` tokens with `20%` overlap
- data deletion policy: `RETAIN`

The S3 Vectors index marks `AMAZON_BEDROCK_TEXT` and
`AMAZON_BEDROCK_METADATA` as non-filterable, as required by the Bedrock
integration. The service role can invoke only the reviewed embedding model,
read only the transcript prefix, and use only the new vector index. A vector
bucket resource policy grants that same role the same five index operations.

## Governed rollout

Run only from a merged `main` commit after reviewing the exact plan:

```sh
cp backend.hcl.example backend.hcl
terraform init -backend-config=backend.hcl
terraform plan -out=hec-kb.tfplan
terraform apply hec-kb.tfplan
```

Start one ingestion job with the repository script, wait for `COMPLETE`, and
then compare the old and new knowledge bases with the fixed retrieval suite:

```sh
HEC_KB_MIGRATION_REQUEST_TASK_ID=<positive-id> \
NEW_KB_ID=<terraform-output> \
NEW_DATA_SOURCE_ID=<terraform-output> \
CONFIRMATION='INGEST HEC S3 VECTORS KNOWLEDGE BASE' \
bash ../../scripts/knowledge-base/start-s3-vectors-ingestion.sh

OLD_KB_ID=ZKA5J7Y0WL NEW_KB_ID=<terraform-output> \
bash ../../scripts/knowledge-base/compare-retrieval.sh ./retrieval-parity.json
```

Do not delete the legacy data source, knowledge base, or OpenSearch collection
until all of these are true:

1. The new ingestion job is `COMPLETE` with zero failed documents.
2. The new index contains vectors and every fixed query returns results.
3. Retrieval comparison passes the top-source overlap threshold.
4. Any consumer has been switched to the new knowledge base ID and verified.
5. A rollback record contains the legacy IDs, configurations, and source URI.

Rollback before decommissioning is simply switching consumers back to
`ZKA5J7Y0WL`. After decommissioning, the S3 transcripts remain canonical and
the legacy index can be rebuilt by creating a knowledge base and re-ingesting
the retained source files. The `prevent_destroy` guards on all replacement
resources require a separate reviewed code change before they can be removed.
