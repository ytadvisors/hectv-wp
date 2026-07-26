locals {
  name = "hectv-wp-production"
  secret_keys = toset([
    "API_KEY",
    "API_URL",
    "AUTH_KEY",
    "AUTH_SALT",
    "AWS_ACCESS_KEY_ID",
    "AWS_SECRET_ACCESS_KEY",
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
  name = local.name
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
  name = "read-production-runtime-secret"
  role = aws_iam_role.execution.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = ["secretsmanager:GetSecretValue"]
      Resource = var.production_secret_arn
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
  name = "mount-production-uploads"
  role = aws_iam_role.task.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "elasticfilesystem:ClientMount",
        "elasticfilesystem:ClientWrite",
      ]
      Resource = "arn:aws:elasticfilesystem:${var.aws_region}:850335719356:file-system/${var.efs_file_system_id}"
      Condition = {
        StringEquals = {
          "elasticfilesystem:AccessPointArn" = aws_efs_access_point.production.arn
        }
      }
    }]
  })
}

resource "aws_security_group" "alb" {
  name        = "${local.name}-alb"
  description = "Public HTTPS for the parallel HEC production origin"
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

  lifecycle {
    precondition {
      condition     = !var.validation_mode || !contains(var.allowed_ipv4_cidrs, "0.0.0.0/0")
      error_message = "validation_mode requires restricted office/VPN CIDRs; 0.0.0.0/0 is forbidden."
    }
  }
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
  description = "HEC production WordPress tasks"
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

resource "aws_security_group_rule" "efs_from_production" {
  type                     = "ingress"
  security_group_id        = var.efs_security_group_id
  source_security_group_id = aws_security_group.task.id
  protocol                 = "tcp"
  from_port                = 2049
  to_port                  = 2049
  description              = "NFS from HEC production ECS tasks"
}

resource "aws_security_group_rule" "aurora_from_production" {
  type                     = "ingress"
  security_group_id        = var.aurora_security_group_id
  source_security_group_id = aws_security_group.task.id
  protocol                 = "tcp"
  from_port                = 3306
  to_port                  = 3306
  description              = "MySQL from HEC production ECS tasks"
}

resource "aws_efs_access_point" "production" {
  file_system_id = var.efs_file_system_id

  posix_user {
    uid = var.efs_posix_uid
    gid = var.efs_posix_gid
  }

  root_directory {
    path = "/"
  }

  tags = {
    Name        = "${local.name}-uploads"
    Environment = "production"
  }
}

resource "aws_lb" "wordpress" {
  name               = local.name
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = var.public_subnet_ids
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

resource "aws_ecs_task_definition" "wordpress" {
  family                   = local.name
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 1024
  memory                   = 4096
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
        access_point_id = aws_efs_access_point.production.id
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
      { name = "DISABLE_WP_CRON", value = var.validation_mode ? "1" : "0" },
      { name = "FORCE_SSL_ADMIN", value = "1" },
      { name = "HECTV_ALLOWED_HOSTS", value = "${var.production_hostname},${var.origin_hostname}" },
      { name = "HECTV_CANONICAL_HOST", value = var.production_hostname },
      { name = "HECTV_DISABLE_OUTBOUND", value = var.validation_mode ? "1" : "0" },
      { name = "HECTV_DISABLE_PAYMENTS", value = var.validation_mode ? "1" : "0" },
      { name = "HECTV_ENVIRONMENT", value = "production" },
      { name = "HTTP_HOST", value = var.production_hostname },
      { name = "WP_DEBUG", value = "0" },
      { name = "WP_DEBUG_LOG", value = "1" },
    ]
    secrets = [
      for key in local.secret_keys : {
        name      = key
        valueFrom = "${var.production_secret_arn}:${key}::"
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

resource "aws_route53_record" "production" {
  zone_id = var.hosted_zone_id
  name    = var.origin_hostname
  type    = "A"

  alias {
    name                   = aws_lb.wordpress.dns_name
    zone_id                = aws_lb.wordpress.zone_id
    evaluate_target_health = true
  }
}
