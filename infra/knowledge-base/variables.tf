variable "aws_profile" {
  type        = string
  default     = "hecadmin"
  description = "Local AWS profile for the HEC Media account."
}

variable "aws_region" {
  type        = string
  default     = "us-east-1"
  description = "Bedrock and S3 Vectors region."

  validation {
    condition     = var.aws_region == "us-east-1"
    error_message = "The existing HEC transcript knowledge base is pinned to us-east-1."
  }
}

variable "vector_bucket_name" {
  type    = string
  default = "hecmedia-srt-vectors"

  validation {
    condition     = var.vector_bucket_name == "hecmedia-srt-vectors"
    error_message = "This state may manage only the reviewed HEC transcript vector bucket."
  }
}

variable "vector_index_name" {
  type    = string
  default = "srt-transcripts"

  validation {
    condition     = var.vector_index_name == "srt-transcripts"
    error_message = "This state may manage only the reviewed HEC transcript vector index."
  }
}

variable "source_bucket_name" {
  type    = string
  default = "srtlibrary-hecmedia"

  validation {
    condition     = var.source_bucket_name == "srtlibrary-hecmedia"
    error_message = "The replacement must read the existing HEC transcript source bucket."
  }
}

variable "source_prefix" {
  type    = string
  default = "vimeo_captions/"

  validation {
    condition     = var.source_prefix == "vimeo_captions/"
    error_message = "The replacement must remain scoped to the existing transcript prefix."
  }
}
