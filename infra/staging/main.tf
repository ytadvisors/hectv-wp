locals {
  name = "hectv-wp-staging"
  secret_keys = toset([
    "API_KEY",
    "API_URL",
    "AUTH_KEY",
    "AUTH_SALT",
    "BUILD_TOKEN",
    "BUILD_URL",
    "JWT_AUTH_SECRET_KEY",
    "LOGGED_IN_KEY",
    "LOGGED_IN_SALT",
    "NONCE_KEY",
    "NONCE_SALT",
    "RDS_DB_NAME",
    "RDS_HOSTNAME",
    "RDS_PASSWORD",
    "RDS_USERNAME",
    "SECURE_AUTH_KEY",
    "SECURE_AUTH_SALT",
    "STRIPE_KEY",
    "STRIPE_SECRET_KEY",
  ])
}

data "aws_caller_identity" "current" {}

resource "aws_ecr_repository" "wordpress" {
  name                 = local.name
  image_tag_mutability = "IMMUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_cloudwatch_log_group" "wordpress" {
  name              = "/ecs/${local.name}"
  retention_in_days = 30
}

resource "aws_ecs_cluster" "wordpress" {
  name = "hectv-wp"
}

resource "aws_iam_role" "execution" {
  name = "${local.name}-execution"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
      Action = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy_attachment" "execution" {
  role       = aws_iam_role.execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role_policy" "execution_secret" {
  name = "read-staging-runtime-secrets"
  role = aws_iam_role.execution.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = ["secretsmanager:GetSecretValue"]
      Resource = [
        var.staging_secret_arn,
        var.staging_admin_secret_arn,
      ]
    }]
  })
}

resource "aws_iam_role" "task" {
  name = "${local.name}-task"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
      Action = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "task_efs" {
  name = "mount-staging-uploads"
  role = aws_iam_role.task.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = var.public_read_only_mode ? [
        "elasticfilesystem:ClientMount",
        ] : [
        "elasticfilesystem:ClientMount",
        "elasticfilesystem:ClientWrite",
      ]
      Resource = "arn:aws:elasticfilesystem:${var.aws_region}:${data.aws_caller_identity.current.account_id}:file-system/${var.efs_file_system_id}"
      Condition = {
        StringEquals = {
          "elasticfilesystem:AccessPointArn" = aws_efs_access_point.staging.arn
        }
      }
    }]
  })
}

resource "aws_iam_role" "admin_task" {
  name = "${local.name}-admin-task"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
      Action = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "admin_task_efs" {
  name = "mount-write-staging-uploads"
  role = aws_iam_role.admin_task.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "elasticfilesystem:ClientMount",
        "elasticfilesystem:ClientWrite",
      ]
      Resource = "arn:aws:elasticfilesystem:${var.aws_region}:${data.aws_caller_identity.current.account_id}:file-system/${var.efs_file_system_id}"
      Condition = {
        StringEquals = {
          "elasticfilesystem:AccessPointArn" = aws_efs_access_point.staging.arn
        }
      }
    }]
  })
}

resource "aws_security_group" "alb" {
  name        = "${local.name}-alb"
  description = "Public HTTPS for the on-demand HEC staging origin"
  vpc_id      = var.vpc_id
}

resource "aws_security_group_rule" "alb_http" {
  type              = "ingress"
  security_group_id = aws_security_group.alb.id
  protocol          = "tcp"
  from_port         = 80
  to_port           = 80
  cidr_blocks       = var.allowed_ipv4_cidrs
}

resource "aws_security_group_rule" "alb_https" {
  type              = "ingress"
  security_group_id = aws_security_group.alb.id
  protocol          = "tcp"
  from_port         = 443
  to_port           = 443
  cidr_blocks       = var.allowed_ipv4_cidrs
}

resource "aws_security_group_rule" "alb_to_task" {
  type                     = "egress"
  security_group_id        = aws_security_group.alb.id
  source_security_group_id = aws_security_group.task.id
  protocol                 = "tcp"
  from_port                = 80
  to_port                  = 80
}

resource "aws_security_group" "task" {
  name        = "${local.name}-task"
  description = "HEC staging WordPress tasks"
  vpc_id      = var.vpc_id
}

resource "aws_security_group_rule" "task_from_alb" {
  type                     = "ingress"
  security_group_id        = aws_security_group.task.id
  source_security_group_id = aws_security_group.alb.id
  protocol                 = "tcp"
  from_port                = 80
  to_port                  = 80
}

resource "aws_security_group_rule" "task_egress" {
  type              = "egress"
  security_group_id = aws_security_group.task.id
  protocol          = "-1"
  from_port         = 0
  to_port           = 0
  cidr_blocks       = ["0.0.0.0/0"]
}

resource "aws_security_group_rule" "efs_from_staging" {
  type                     = "ingress"
  security_group_id        = var.efs_security_group_id
  source_security_group_id = aws_security_group.task.id
  protocol                 = "tcp"
  from_port                = 2049
  to_port                  = 2049
  description              = "NFS from HEC staging ECS tasks"
}

resource "aws_security_group_rule" "aurora_from_staging" {
  type                     = "ingress"
  security_group_id        = var.aurora_security_group_id
  source_security_group_id = aws_security_group.task.id
  protocol                 = "tcp"
  from_port                = 3306
  to_port                  = 3306
  description              = "MySQL from HEC staging ECS tasks"
}

resource "aws_efs_access_point" "staging" {
  file_system_id = var.efs_file_system_id

  posix_user {
    uid = 33
    gid = 33
  }

  root_directory {
    path = "/staging-uploads"
    creation_info {
      owner_uid   = 33
      owner_gid   = 33
      permissions = "0755"
    }
  }

  tags = {
    Name        = "${local.name}-uploads"
    Environment = "staging"
  }
}

resource "aws_lb" "wordpress" {
  name               = local.name
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = var.public_subnet_ids

  lifecycle {
    precondition {
      condition     = !contains(var.allowed_ipv4_cidrs, "0.0.0.0/0") || var.public_read_only_mode
      error_message = "Public staging ingress requires public_read_only_mode=true."
    }
  }
}

resource "aws_lb_target_group" "wordpress" {
  name        = local.name
  port        = 80
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = var.vpc_id

  health_check {
    enabled             = true
    path                = "/healthz"
    matcher             = "200-399"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 10
    interval            = 30
  }
}

resource "aws_lb_target_group" "admin" {
  name        = "hectv-wp-stg-admin"
  port        = 80
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = var.vpc_id

  health_check {
    enabled             = true
    path                = "/healthz"
    matcher             = "200-399"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 10
    interval            = 30
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.wordpress.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"
    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.wordpress.arn
  port              = 443
  protocol          = "HTTPS"
  certificate_arn   = var.certificate_arn
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.wordpress.arn
  }
}

resource "aws_lb_listener_rule" "allow_staging_graphql" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 10

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.wordpress.arn
  }

  condition {
    path_pattern {
      values = ["/graphql"]
    }
  }

  condition {
    http_request_method {
      # OPTIONS is required for browser CORS preflight. WordPress/WPGraphQL
      # remains responsible for the allow-origin response, while application
      # mutations continue to be rejected by the public-read-only MU plugin.
      values = ["GET", "POST", "OPTIONS"]
    }
  }
}

resource "aws_lb_listener_rule" "allow_staging_rest_reads" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 20

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.wordpress.arn
  }

  condition {
    http_request_method {
      values = ["GET"]
    }
  }

  condition {
    path_pattern {
      values = [
        "/wp-json/wp/v2/*",
        "/wp-json/wp-api-menus/v2/*",
        "/wp-json/hectv/v1/livevideos/live",
        "/wp-content/uploads/*",
      ]
    }
  }
}

resource "aws_lb_listener_rule" "allow_staging_admin_rest_writes" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 25

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.admin.arn
  }

  condition {
    http_request_method {
      values = ["POST", "PUT", "PATCH", "DELETE"]
    }
  }

  condition {
    path_pattern {
      values = ["/wp-json/*"]
    }
  }
}

resource "aws_lb_listener_rule" "allow_staging_admin_rest_authenticated_reads" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 26

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.admin.arn
  }

  condition {
    http_request_method {
      values = ["GET"]
    }
  }

  condition {
    path_pattern {
      values = ["/wp-json/*"]
    }
  }

  condition {
    http_header {
      http_header_name = "Cookie"
      values = [
        "*wordpress_logged_in_*",
        "*wordpress_sec_*",
      ]
    }
  }
}

resource "aws_lb_listener_rule" "allow_staging_admin" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 30

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.admin.arn
  }

  condition {
    path_pattern {
      values = [
        "/wp-admin",
        "/wp-admin/*",
        "/wp-login.php",
      ]
    }
  }
}

resource "aws_lb_listener_rule" "block_all_other_staging_requests" {
  count        = var.public_read_only_mode ? 1 : 0
  listener_arn = aws_lb_listener.https.arn
  priority     = 100

  action {
    type = "fixed-response"
    fixed_response {
      content_type = "text/plain"
      message_body = "This staging route is not public."
      status_code  = "403"
    }
  }

  condition {
    path_pattern {
      values = ["/*"]
    }
  }
}

resource "aws_ecs_task_definition" "wordpress" {
  family                   = local.name
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 1024
  memory                   = 2048
  execution_role_arn       = aws_iam_role.execution.arn
  task_role_arn            = aws_iam_role.task.arn

  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture        = "ARM64"
  }

  volume {
    name = "uploads"
    efs_volume_configuration {
      file_system_id     = var.efs_file_system_id
      transit_encryption = "ENABLED"
      root_directory     = "/"

      authorization_config {
        access_point_id = aws_efs_access_point.staging.id
        iam             = "ENABLED"
      }
    }
  }

  container_definitions = jsonencode([{
    name      = "wordpress"
    image     = var.container_image
    essential = true
    portMappings = [{
      containerPort = 80
      hostPort      = 80
      protocol      = "tcp"
    }]
    environment = [
      { name = "DISABLE_WP_CRON", value = "1" },
      { name = "FORCE_SSL_ADMIN", value = "1" },
      { name = "HECTV_CANONICAL_HOST", value = var.staging_hostname },
      { name = "HECTV_DISABLE_OUTBOUND", value = "1" },
      { name = "HECTV_DISABLE_PAYMENTS", value = "1" },
      { name = "HECTV_ENVIRONMENT", value = "staging" },
      { name = "HECTV_PUBLIC_READ_ONLY", value = var.public_read_only_mode ? "1" : "0" },
      { name = "HTTP_HOST", value = var.staging_hostname },
      { name = "WP_DEBUG", value = "0" },
      { name = "WP_DEBUG_LOG", value = "1" },
    ]
    secrets = [
      for key in local.secret_keys : {
        name      = key
        valueFrom = "${var.staging_secret_arn}:${key}::"
      }
    ]
    mountPoints = [{
      sourceVolume  = "uploads"
      containerPath = "/var/www/html/wp-content/uploads"
      readOnly      = var.public_read_only_mode
    }]
    logConfiguration = {
      logDriver = "awslogs"
      options = {
        awslogs-group         = aws_cloudwatch_log_group.wordpress.name
        awslogs-region        = var.aws_region
        awslogs-stream-prefix = "wordpress"
      }
    }
  }])
}

resource "aws_ecs_task_definition" "admin" {
  family                   = "${local.name}-admin"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 1024
  memory                   = 2048
  execution_role_arn       = aws_iam_role.execution.arn
  task_role_arn            = aws_iam_role.admin_task.arn

  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture        = "ARM64"
  }

  volume {
    name = "uploads"
    efs_volume_configuration {
      file_system_id     = var.efs_file_system_id
      transit_encryption = "ENABLED"
      root_directory     = "/"

      authorization_config {
        access_point_id = aws_efs_access_point.staging.id
        iam             = "ENABLED"
      }
    }
  }

  container_definitions = jsonencode([{
    name      = "wordpress"
    image     = var.container_image
    essential = true
    portMappings = [{
      containerPort = 80
      hostPort      = 80
      protocol      = "tcp"
    }]
    environment = [
      { name = "DISABLE_WP_CRON", value = "1" },
      { name = "FORCE_SSL_ADMIN", value = "1" },
      { name = "HECTV_CANONICAL_HOST", value = var.staging_hostname },
      { name = "HECTV_DISABLE_OUTBOUND", value = "1" },
      { name = "HECTV_DISABLE_PAYMENTS", value = "1" },
      { name = "HECTV_ENVIRONMENT", value = "staging" },
      { name = "HECTV_PUBLIC_READ_ONLY", value = "0" },
      { name = "HTTP_HOST", value = var.staging_hostname },
      { name = "WP_DEBUG", value = "0" },
      { name = "WP_DEBUG_LOG", value = "1" },
    ]
    secrets = [
      for key in local.secret_keys : {
        name      = key
        valueFrom = "${var.staging_admin_secret_arn}:${key}::"
      }
    ]
    mountPoints = [{
      sourceVolume  = "uploads"
      containerPath = "/var/www/html/wp-content/uploads"
      readOnly      = false
    }]
    logConfiguration = {
      logDriver = "awslogs"
      options = {
        awslogs-group         = aws_cloudwatch_log_group.wordpress.name
        awslogs-region        = var.aws_region
        awslogs-stream-prefix = "wordpress"
      }
    }
  }])
}

resource "aws_ecs_service" "wordpress" {
  name            = local.name
  cluster         = aws_ecs_cluster.wordpress.id
  task_definition = aws_ecs_task_definition.wordpress.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [aws_security_group.task.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.wordpress.arn
    container_name   = "wordpress"
    container_port   = 80
  }

  depends_on = [aws_lb_listener.https]

  lifecycle {
    ignore_changes = [desired_count]
  }
}

resource "aws_ecs_service" "admin" {
  name            = "${local.name}-admin"
  cluster         = aws_ecs_cluster.wordpress.id
  task_definition = aws_ecs_task_definition.admin.arn
  desired_count   = var.admin_desired_count
  launch_type     = "FARGATE"

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [aws_security_group.task.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.admin.arn
    container_name   = "wordpress"
    container_port   = 80
  }

  depends_on = [aws_lb_listener.https]

  lifecycle {
    ignore_changes = [desired_count]
  }
}

resource "aws_route53_record" "staging" {
  zone_id = var.hosted_zone_id
  name    = var.staging_hostname
  type    = "A"

  alias {
    name                   = aws_lb.wordpress.dns_name
    zone_id                = aws_lb.wordpress.zone_id
    evaluate_target_health = true
  }
}
