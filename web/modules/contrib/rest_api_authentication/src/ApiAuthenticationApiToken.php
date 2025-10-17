<?php

namespace Drupal\rest_api_authentication;

use Drupal\Component\Utility\Html;

/**
 * Validate the request when API key Authentication method configured in module.
 */
class ApiAuthenticationApiToken {

  /**
   * Validates an API request by checking the authorization header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing the headers.
   * @param array $app_data
   *   The application-specific configuration data.
   *
   * @return array|string[]
   *   Array containing the status, HTTP code, error, and error description.
   */
  public static function validateApiRequest($request, array $app_data = []): array {
    $config = \Drupal::config('rest_api_authentication.settings');
    $expected_token = $config->get('api_token');

    $auth_type = $app_data['rest_api_auth_type'] ?? $config->get('rest_api_auth_type');

    // --- API KEY Header Authentication ---
    if ($auth_type == 1) {
      $api_key_header = $request->headers->get('api-key');
      if (!$api_key_header) {
        return self::errorResponse('MISSING_API_KEY_HEADER', 'API key header is missing.', 401);
      }
      $decoded_string = base64_decode($api_key_header, TRUE);
      if ($decoded_string === FALSE || strpos($decoded_string, ':') === FALSE) {
        return self::errorResponse('INVALID_API_KEY_FORMAT', 'API key format is invalid.', 401);
      }
      $decoded_header = explode(':', $decoded_string, 2);
      $username = $decoded_header[0];
      $provided_token = $decoded_header[1];
      return self::validateUserAndToken($username, $provided_token, $expected_token);
    }

    // --- Basic Authorization Header Authentication ---
    $auth_header = $request->headers->get('Authorization') ?: $request->headers->get('Authorisation');
    if (!$auth_header) {
      return self::errorResponse('MISSING_AUTHORIZATION_HEADER', 'Authorization header not received', 401);
    }

    if (stripos($auth_header, 'Basic ') !== 0) {
      return self::errorResponse('INVALID_AUTHORIZATION_HEADER_TYPE', 'Authorization header must be of type Basic.', 401);
    }

    $encoded_credentials = trim(substr($auth_header, 6));
    $decoded_string = base64_decode($encoded_credentials, TRUE);
    if ($decoded_string === FALSE || strpos($decoded_string, ':') === FALSE) {
      return self::errorResponse('INVALID_AUTHORIZATION_HEADER', 'Authorization header format is invalid.', 401);
    }
    $decoded_header = explode(':', $decoded_string, 2);
    $username = $decoded_header[0];
    $provided_token = $decoded_header[1];
    return self::validateUserAndToken($username, $provided_token, $expected_token);
  }

  /**
   * Helper function to validate user existence and token.
   */
  private static function validateUserAndToken($username, $provided_token, $expected_token): array {
    $user = user_load_by_name($username);

    if (empty($user)) {
      return self::errorResponse('USER_DOES_NOT_EXIST', 'The user does not exist.', 404);
    }

    if ($user->isBlocked()) {
      return self::errorResponse('USER_BLOCKED', 'The user is blocked or inactive.', 403);
    }

    if ($provided_token === $expected_token) {
      return [
        'status' => 'SUCCESS',
        'http_code' => 200,
        'user' => $user,
      ];
    }

    \Drupal::logger('rest_api_authentication')->warning('Token validation failed for user @username', ['@username' => $username]);
    return self::errorResponse('INVALID_API_KEY', 'Sorry, you are using an invalid API Key.', 401);
  }

  /**
   * Helper function to return a standardized error response.
   */
  private static function errorResponse($code, $description, $http_code): array {
    return [
      'status' => 'error',
      'http_code' => $http_code,
      'error' => $code,
      'error_description' => $description,
    ];
  }
}
