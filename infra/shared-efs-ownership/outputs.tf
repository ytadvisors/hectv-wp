output "retained_file_system_id" {
  value = aws_efs_file_system.production.id
}

output "retained_mount_target_security_group_id" {
  value = aws_security_group.efs_mount_target.id
}

output "retained_mount_target_ids" {
  value = {
    for availability_zone, target in aws_efs_mount_target.production :
    availability_zone => target.id
  }
}
