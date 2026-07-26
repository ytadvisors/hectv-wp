output "ecr_repository_url" {
  value = aws_ecr_repository.wordpress.repository_url
}

output "ecs_cluster_name" {
  value = aws_ecs_cluster.wordpress.name
}

output "ecs_service_name" {
  value = aws_ecs_service.wordpress.name
}

output "origin_url" {
  value = "https://${var.origin_hostname}"
}

output "production_url" {
  value = "https://${var.production_hostname}"
}
