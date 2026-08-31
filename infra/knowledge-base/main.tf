locals {
  account_id         = "850335719356"
  embedding_model_id = "amazon.titan-embed-text-v1"
  embedding_model_arn = format(
    "arn:aws:bedrock:%s::foundation-model/%s",
    var.aws_region,
    local.embedding_model_id,
  )
  source_bucket_arn = "arn:aws:s3:::${var.source_bucket_name}"
  tags = {
    Client      = "HEC Media"
    Environment = "production"
    ManagedBy   = "Terraform"
    Purpose     = "SRT knowledge base"
  }
}

data "aws_caller_identity" "current" {}

check "hec_account" {
  assert {
    condition     = data.aws_caller_identity.current.account_id == local.account_id
    error_message = "Refusing to manage the HEC knowledge base outside AWS account 850335719356."
  }
}

resource "aws_s3vectors_vector_bucket" "transcripts" {
  vector_bucket_name = var.vector_bucket_name

  encryption_configuration {
    sse_type = "AES256"
  }

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_s3vectors_index" "transcripts" {
  vector_bucket_name = aws_s3vectors_vector_bucket.transcripts.vector_bucket_name
  index_name         = var.vector_index_name
  data_type          = "float32"
  dimension          = 1536
  distance_metric    = "euclidean"

  metadata_configuration {
    non_filterable_metadata_keys = [
      "AMAZON_BEDROCK_TEXT",
      "AMAZON_BEDROCK_METADATA",
    ]
  }

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_iam_role" "bedrock_knowledge_base" {
  name = "hecmedia-bedrock-srt-kb-s3vectors"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid    = "BedrockKnowledgeBaseAssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "bedrock.amazonaws.com"
      }
      Action = "sts:AssumeRole"
      Condition = {
        StringEquals = {
          "aws:SourceAccount" = local.account_id
        }
        ArnLike = {
          "AWS:SourceArn" = "arn:aws:bedrock:${var.aws_region}:${local.account_id}:knowledge-base/*"
        }
      }
    }]
  })
}

resource "aws_iam_role_policy" "bedrock_knowledge_base" {
  name = "hecmedia-srt-kb-s3vectors"
  role = aws_iam_role.bedrock_knowledge_base.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid      = "InvokeOnlyTheReviewedEmbeddingModel"
        Effect   = "Allow"
        Action   = "bedrock:InvokeModel"
        Resource = local.embedding_model_arn
      },
      {
        Sid      = "ListOnlyTheTranscriptSourceBucket"
        Effect   = "Allow"
        Action   = "s3:ListBucket"
        Resource = local.source_bucket_arn
        Condition = {
          StringEquals = {
            "aws:ResourceAccount" = local.account_id
          }
          StringLike = {
            "s3:prefix" = [
              var.source_prefix,
              "${var.source_prefix}*",
            ]
          }
        }
      },
      {
        Sid      = "ReadOnlyTranscriptSourceObjects"
        Effect   = "Allow"
        Action   = "s3:GetObject"
        Resource = "${local.source_bucket_arn}/${var.source_prefix}*"
        Condition = {
          StringEquals = {
            "aws:ResourceAccount" = local.account_id
          }
        }
      },
      {
        Sid    = "UseOnlyTheTranscriptVectorIndex"
        Effect = "Allow"
        Action = [
          "s3vectors:PutVectors",
          "s3vectors:GetVectors",
          "s3vectors:DeleteVectors",
          "s3vectors:QueryVectors",
          "s3vectors:GetIndex",
        ]
        Resource = aws_s3vectors_index.transcripts.index_arn
      },
    ]
  })
}

resource "aws_s3vectors_vector_bucket_policy" "transcripts" {
  vector_bucket_arn = aws_s3vectors_vector_bucket.transcripts.vector_bucket_arn

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid    = "AllowOnlyTheHecBedrockKnowledgeBaseRole"
      Effect = "Allow"
      Principal = {
        AWS = aws_iam_role.bedrock_knowledge_base.arn
      }
      Action = [
        "s3vectors:PutVectors",
        "s3vectors:GetVectors",
        "s3vectors:DeleteVectors",
        "s3vectors:QueryVectors",
        "s3vectors:GetIndex",
      ]
      Resource = aws_s3vectors_index.transcripts.index_arn
    }]
  })
}

resource "aws_bedrockagent_knowledge_base" "transcripts" {
  name        = "SRT-Knowledge-base-s3-vectors"
  description = "Cost-optimized replacement for the HEC Media transcript knowledge base."
  role_arn    = aws_iam_role.bedrock_knowledge_base.arn

  knowledge_base_configuration {
    type = "VECTOR"

    vector_knowledge_base_configuration {
      embedding_model_arn = local.embedding_model_arn

      embedding_model_configuration {
        bedrock_embedding_model_configuration {
          dimensions          = 1536
          embedding_data_type = "FLOAT32"
        }
      }
    }
  }

  storage_configuration {
    type = "S3_VECTORS"

    s3_vectors_configuration {
      index_arn = aws_s3vectors_index.transcripts.index_arn
    }
  }

  depends_on = [
    aws_iam_role_policy.bedrock_knowledge_base,
    aws_s3vectors_vector_bucket_policy.transcripts,
  ]

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_bedrockagent_data_source" "transcripts" {
  knowledge_base_id    = aws_bedrockagent_knowledge_base.transcripts.id
  name                 = "srtlibrary-hecmedia-s3-vectors"
  description          = "HEC Media Vimeo caption transcripts"
  data_deletion_policy = "RETAIN"

  data_source_configuration {
    type = "S3"

    s3_configuration {
      bucket_arn         = local.source_bucket_arn
      inclusion_prefixes = [var.source_prefix]
    }
  }

  vector_ingestion_configuration {
    chunking_configuration {
      chunking_strategy = "FIXED_SIZE"

      fixed_size_chunking_configuration {
        max_tokens         = 300
        overlap_percentage = 20
      }
    }
  }

  lifecycle {
    prevent_destroy = true
  }
}
