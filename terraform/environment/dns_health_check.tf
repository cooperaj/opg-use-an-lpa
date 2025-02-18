resource "aws_cloudwatch_metric_alarm" "viewer_health_check_alarm" {
  alarm_description   = "${local.environment_name} viewer health check"
  alarm_name          = "${local.environment_name}-viewer-healthcheck-alarm"
  actions_enabled     = false
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.viewer_health_check.id
  }

  provider = aws.us-east-1
}

resource "aws_route53_health_check" "viewer_health_check" {
  fqdn              = aws_route53_record.viewer-use-my-lpa.fqdn
  reference_name    = "${substr(local.environment_name, 0, 20)}-viewer"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  regions           = ["us-east-1", "us-west-1", "us-west-2", "eu-west-1", "ap-southeast-1", "ap-southeast-2", "ap-northeast-1", "sa-east-1"]
  provider          = aws.us-east-1
}

resource "aws_cloudwatch_metric_alarm" "actor_health_check_alarm" {
  alarm_description   = "${local.environment_name} actor health check"
  alarm_name          = "${local.environment_name}-actor-healthcheck-alarm"
  actions_enabled     = false
  comparison_operator = "LessThanThreshold"
  datapoints_to_alarm = 1
  evaluation_periods  = 1
  metric_name         = "HealthCheckStatus"
  namespace           = "AWS/Route53"
  period              = 60
  statistic           = "Minimum"
  threshold           = 1
  dimensions = {
    HealthCheckId = aws_route53_health_check.actor_health_check.id
  }

  provider = aws.us-east-1
}

resource "aws_route53_health_check" "actor_health_check" {
  fqdn              = aws_route53_record.actor-use-my-lpa.fqdn
  reference_name    = "${substr(local.environment_name, 0, 20)}-actor"
  port              = 443
  type              = "HTTPS"
  failure_threshold = 1
  request_interval  = 30
  measure_latency   = true
  regions           = ["us-east-1", "us-west-1", "us-west-2", "eu-west-1", "ap-southeast-1", "ap-southeast-2", "ap-northeast-1", "sa-east-1"]
  provider          = aws.us-east-1
}
