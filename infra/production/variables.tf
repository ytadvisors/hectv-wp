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

variable "origin_hostname" {
  type    = string
  default = "prod-wp-ecs.hectv.org"
}

variable "production_hostname" {
  type    = string
  default = "prod-wp.hectv.org"
}

variable "allowed_ipv4_cidrs" {
  type        = list(string)
  description = "CIDRs allowed to reach the public production origin."
}

variable "certificate_arn" {
  type    = string
  default = "arn:aws:acm:us-east-2:850335719356:certificate/14816e9d-ed69-43ec-aa83-68e25026e613"
}

variable "container_image" {
  type        = string
  description = "Immutable ECR image URI including a digest or commit tag."
}

variable "production_secret_arn" {
  type        = string
  description = "Secrets Manager JSON secret containing production runtime values."
  sensitive   = true
}

variable "efs_file_system_id" {
  type    = string
  default = "fs-4243883b"

  validation {
    condition     = var.efs_file_system_id == "fs-4243883b"
    error_message = "Production must remain pinned to the retained shared EFS fs-4243883b."
  }
}

variable "efs_security_group_id" {
  type    = string
  default = "sg-26c1f14c"

  validation {
    condition     = var.efs_security_group_id == "sg-26c1f14c"
    error_message = "Production must remain pinned to the retained EFS mount-target security group sg-26c1f14c."
  }
}

variable "efs_posix_uid" {
  type        = number
  default     = 498
  description = "Verified UID owning the live Elastic Beanstalk EFS uploads root."
}

variable "efs_posix_gid" {
  type        = number
  default     = 496
  description = "Verified GID owning the live Elastic Beanstalk EFS uploads root."
}

variable "aurora_security_group_id" {
  type    = string
  default = "sg-81c2f2eb"
}

variable "desired_count" {
  type        = number
  default     = 0
  description = "Keep zero until the parallel production origin is ready for validation."

  validation {
    condition     = contains([0, 1, 2], var.desired_count)
    error_message = "desired_count must be 0, 1, or 2."
  }
}

variable "validation_mode" {
  type        = bool
  default     = true
  description = "Disables WordPress cron and outbound PHP mail before cutover."
}
