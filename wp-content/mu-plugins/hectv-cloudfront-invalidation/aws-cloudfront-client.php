<?php
/**
 * Minimal CloudFront service classes for WP Offload Media's scoped AWS SDK.
 *
 * The bundled SDK is AWS SDK for PHP 3.62.6 and includes the CloudFront API
 * model, signer, HTTP stack, and ECS credential provider. The plugin vendor
 * package intentionally ships only its S3 service class, so the two standard
 * CloudFront service classes are supplied here without adding another SDK.
 */

namespace DeliciousBrains\WP_Offload_Media\Aws3\Aws\CloudFront\Exception {
	class CloudFrontException extends \DeliciousBrains\WP_Offload_Media\Aws3\Aws\Exception\AwsException {}
}

namespace DeliciousBrains\WP_Offload_Media\Aws3\Aws\CloudFront {
	class CloudFrontClient extends \DeliciousBrains\WP_Offload_Media\Aws3\Aws\AwsClient {}
}
