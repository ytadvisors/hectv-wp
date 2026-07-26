variable "aws_profile" {
  type    = string
  default = "hecadmin"
}

variable "aws_region" {
  type    = string
  default = "us-east-2"
}

variable "vpc_id" {
  type    = string
  default = "vpc-d90971b0"
}

variable "public_subnet_ids" {
  type    = list(string)
  default = ["subnet-49a4ea20", "subnet-e1f7629a", "subnet-8377a9ce"]
}

variable "hosted_zone_id" {
  type    = string
  default = "Z292F4Q328R4CJ"
}

variable "staging_hostname" {
  type    = string
  default = "staging-wp.hectv.org"
}

variable "allowed_ipv4_cidrs" {
  type        = list(string)
  description = "CIDRs allowed to reach staging. Public ingress requires public_read_only_mode."

  validation {
    condition     = length(var.allowed_ipv4_cidrs) > 0
    error_message = "Provide at least one staging ingress CIDR."
  }
}

variable "public_read_only_mode" {
  type        = bool
  default     = false
  description = "Allows public HTTPS only when the database user is SELECT-only and sensitive WordPress paths are blocked."
}

variable "certificate_arn" {
  type    = string
  default = "arn:aws:acm:us-east-2:850335719356:certificate/14816e9d-ed69-43ec-aa83-68e25026e613"
}

variable "container_image" {
  type        = string
  description = "Immutable ECR image URI including a digest or commit tag."
}

variable "staging_secret_arn" {
  type        = string
  description = "Secrets Manager JSON secret containing staging runtime values."
  sensitive   = true
}

variable "efs_file_system_id" {
  type    = string
  default = "fs-4243883b"
}

variable "efs_security_group_id" {
  type    = string
  default = "sg-26c1f14c"
}

variable "aurora_security_group_id" {
  type    = string
  default = "sg-81c2f2eb"
}

variable "desired_count" {
  type        = number
  default     = 0
  description = "Keep zero except during an explicit staging review window."

  validation {
    condition     = contains([0, 1, 2], var.desired_count)
    error_message = "desired_count must be 0, 1, or 2."
  }
}
