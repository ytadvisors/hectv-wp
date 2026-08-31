output "knowledge_base_id" {
  value       = aws_bedrockagent_knowledge_base.transcripts.id
  description = "S3 Vectors-backed Bedrock knowledge base ID."
}

output "data_source_id" {
  value       = aws_bedrockagent_data_source.transcripts.data_source_id
  description = "Transcript data source ID."
}

output "vector_bucket_arn" {
  value       = aws_s3vectors_vector_bucket.transcripts.vector_bucket_arn
  description = "HEC transcript vector bucket ARN."
}

output "vector_index_arn" {
  value       = aws_s3vectors_index.transcripts.index_arn
  description = "HEC transcript vector index ARN."
}

output "bedrock_role_arn" {
  value       = aws_iam_role.bedrock_knowledge_base.arn
  description = "Least-privilege service role assumed by Bedrock."
}
