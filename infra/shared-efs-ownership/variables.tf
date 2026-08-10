variable "aws_profile" {
  type    = string
  default = "hecadmin"
}

variable "aws_region" {
  type    = string
  default = "us-east-2"

  validation {
    condition     = var.aws_region == "us-east-2"
    error_message = "The retained HEC production EFS exists only in us-east-2."
  }
}

variable "approval_task_id" {
  type        = string
  default     = ""
  description = "Approved HEC/Yomi change-ticket or queue-task receipt required before import planning."
}

variable "approved_manifest_sha256" {
  type        = string
  default     = ""
  description = "SHA-256 of the exact approved decommission manifest required before import planning."
}

variable "cloudformation_detach_receipt_sha256" {
  type        = string
  default     = ""
  description = "SHA-256 of the verified CloudFormation retain/detach receipt required before import planning."
}

variable "import_confirmation" {
  type        = string
  default     = ""
  description = "Required literal confirmation for the retained-resource import plan."
}
