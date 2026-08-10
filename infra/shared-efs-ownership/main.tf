locals {
  account_id = "850335719356"
  vpc_id     = "vpc-d90971b0"

  mount_targets = {
    us-east-2a = {
      id         = "fsmt-994a81e0"
      subnet_id  = "subnet-49a4ea20"
      ip_address = "172.31.11.70"
    }
    us-east-2b = {
      id         = "fsmt-a74a81de"
      subnet_id  = "subnet-e1f7629a"
      ip_address = "172.31.25.49"
    }
    us-east-2c = {
      id         = "fsmt-a44a81dd"
      subnet_id  = "subnet-8377a9ce"
      ip_address = "172.31.36.220"
    }
  }

  evidence_gate_passed = (
    data.aws_caller_identity.current.account_id == local.account_id &&
    can(regex("^[1-9][0-9]*$", var.approval_task_id)) &&
    can(regex("^[0-9a-f]{64}$", var.approved_manifest_sha256)) &&
    can(regex("^[0-9a-f]{64}$", var.cloudformation_detach_receipt_sha256)) &&
    var.import_confirmation == "IMPORT RETAINED HEC EFS"
  )
}

data "aws_caller_identity" "current" {}

# Static import blocks bind all five exact physical resources. If a physical ID
# is absent, planning fails instead of proposing a replacement EFS resource.
import {
  to = aws_efs_file_system.production
  id = "fs-4243883b"
}

resource "aws_efs_file_system" "production" {
  lifecycle {
    prevent_destroy = true

    # The 2018 file system's immutable/runtime attributes must be captured by
    # the privileged inventory and preserved during import. This root owns its
    # identity and deletion protection; it does not modernize the live volume.
    ignore_changes = [
      availability_zone_name,
      creation_token,
      encrypted,
      kms_key_id,
      performance_mode,
      provisioned_throughput_in_mibps,
      throughput_mode,
      tags,
    ]

    precondition {
      condition     = local.evidence_gate_passed
      error_message = "EFS import requires a positive approval receipt, approved-manifest and CloudFormation-detach SHA-256 receipts, and the exact import confirmation."
    }

    postcondition {
      condition     = self.id == "fs-4243883b"
      error_message = "Imported EFS identity differs from the approved fs-4243883b manifest."
    }
  }
}

import {
  to = aws_security_group.efs_mount_target
  id = "sg-26c1f14c"
}

resource "aws_security_group" "efs_mount_target" {
  description = "Security group for mount target"
  vpc_id      = local.vpc_id

  lifecycle {
    prevent_destroy = true

    # Production and staging manage their NFS ingress as standalone rule
    # resources in their own states. Do not convert those rules to inline SG
    # ownership during the physical-group import.
    ignore_changes = [
      egress,
      ingress,
      name,
      name_prefix,
      tags,
    ]

    precondition {
      condition     = local.evidence_gate_passed
      error_message = "EFS security-group import requires all approval and detach receipts."
    }

    postcondition {
      condition     = self.id == "sg-26c1f14c"
      error_message = "Imported EFS security-group identity differs from sg-26c1f14c."
    }
  }
}

import {
  to = aws_efs_mount_target.production["us-east-2a"]
  id = "fsmt-994a81e0"
}

import {
  to = aws_efs_mount_target.production["us-east-2b"]
  id = "fsmt-a74a81de"
}

import {
  to = aws_efs_mount_target.production["us-east-2c"]
  id = "fsmt-a44a81dd"
}

resource "aws_efs_mount_target" "production" {
  for_each = local.mount_targets

  file_system_id  = aws_efs_file_system.production.id
  subnet_id       = each.value.subnet_id
  ip_address      = each.value.ip_address
  security_groups = [aws_security_group.efs_mount_target.id]

  lifecycle {
    prevent_destroy = true

    precondition {
      condition     = local.evidence_gate_passed
      error_message = "EFS mount-target import requires all approval and detach receipts."
    }

    postcondition {
      condition     = self.id == each.value.id
      error_message = "Imported EFS mount-target identity differs from the exact approved manifest."
    }
  }
}
