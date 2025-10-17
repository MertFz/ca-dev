<?php

namespace Drupal\rest_api_authentication\Services;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
 * Service for logging REST API authentication attempts.
 */
class RestApiLogger {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RestApiLogger object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Log an authentication attempt.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param string $authentication_method
   *   The authentication method used.
   * @param string $status
   *   The authentication status (success, failure, blocked, etc.).
   * @param int $response_code
   *   The HTTP response code.
   * @param \Drupal\user\Entity\User|null $user
   *   The authenticated user object (null for anonymous/failed attempts).
   * @param string|null $error_message
   *   Error message if authentication failed.
   */
  public function logAuthenticationAttempt(
    Request $request,
    string $authentication_method,
    string $status,
    int $response_code,
    ?User $user = NULL,
    ?string $error_message = NULL
  ) {
    try {
      // Ensure the table exists before attempting to log
      if (!$this->ensureTableExists()) {
        \Drupal::logger('rest_api_authentication')->error('Cannot log authentication attempt: table does not exist and could not be created.');
        return;
      }

      $fields = [
        'timestamp' => time(),
        'username' => $user ? $user->getAccountName() : 'anonymous',
        'client_ip' => $request->getClientIp(),
        'request_method' => $request->getMethod(),
        'endpoint_url' => $request->getRequestUri(),
        'authentication_method' => $authentication_method,
        'status' => $status,
        'response_code' => $response_code,
        'error_message' => $error_message,
        'user_agent' => $request->headers->get('User-Agent'),
      ];

      $this->database->insert('rest_api_authentication_logs')
        ->fields($fields)
        ->execute();

    } catch (\Exception $e) {
      \Drupal::logger('rest_api_authentication')->error('Failed to log authentication attempt: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Get authentication logs with filters.
   *
   * @param array $filters
   *   Array of filters to apply.
   * @param int $limit
   *   Number of records to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of log records.
   */
  public function getLogs(array $filters = [], int $limit = 50, int $offset = 0): array {
    $query = $this->database->select('rest_api_authentication_logs', 'l')
      ->fields('l')
      ->orderBy('timestamp', 'DESC')
      ->range($offset, $limit);

    // Apply filters
    if (!empty($filters['username'])) {
      $query->condition('username', '%' . $this->database->escapeLike($filters['username']) . '%', 'LIKE');
    }
    if (!empty($filters['client_ip'])) {
      $query->condition('client_ip', '%' . $this->database->escapeLike($filters['client_ip']) . '%', 'LIKE');
    }
    if (!empty($filters['authentication_method'])) {
      $query->condition('authentication_method', $filters['authentication_method']);
    }
    if (!empty($filters['status'])) {
      $query->condition('status', $filters['status']);
    }
    if (!empty($filters['response_code'])) {
      $query->condition('response_code', $filters['response_code']);
    }
    if (!empty($filters['date_from'])) {
      $query->condition('timestamp', strtotime($filters['date_from']), '>=');
    }
    if (!empty($filters['date_to'])) {
      $query->condition('timestamp', strtotime($filters['date_to'] . ' 23:59:59'), '<=');
    }

    return $query->execute()->fetchAll();
  }

  /**
   * Get total count of logs with filters.
   *
   * @param array $filters
   *   Array of filters to apply.
   *
   * @return int
   *   Total count of matching records.
   */
  public function getLogsCount(array $filters = []): int {
    $query = $this->database->select('rest_api_authentication_logs', 'l');

    // Apply same filters as getLogs
    if (!empty($filters['username'])) {
      $query->condition('username', '%' . $this->database->escapeLike($filters['username']) . '%', 'LIKE');
    }
    if (!empty($filters['client_ip'])) {
      $query->condition('client_ip', '%' . $this->database->escapeLike($filters['client_ip']) . '%', 'LIKE');
    }
    if (!empty($filters['authentication_method'])) {
      $query->condition('authentication_method', $filters['authentication_method']);
    }
    if (!empty($filters['status'])) {
      $query->condition('status', $filters['status']);
    }
    if (!empty($filters['response_code'])) {
      $query->condition('response_code', $filters['response_code']);
    }
    if (!empty($filters['date_from'])) {
      $query->condition('timestamp', strtotime($filters['date_from']), '>=');
    }
    if (!empty($filters['date_to'])) {
      $query->condition('timestamp', strtotime($filters['date_to'] . ' 23:59:59'), '<=');
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Delete all authentication logs.
   *
   * @return int
   *   Number of deleted records.
   */
  public function deleteAllLogs(): int {
    try {
      // Ensure the table exists before attempting any operations
      if (!$this->ensureTableExists()) {
        \Drupal::logger('rest_api_authentication')->error('Cannot delete logs: table does not exist and could not be created.');
        return 0;
      }

      // Get total count of logs
      $count = $this->database->select('rest_api_authentication_logs', 'l')
        ->countQuery()
        ->execute()
        ->fetchField();
      
      if ($count == 0) {
        \Drupal::logger('rest_api_authentication')->info('No logs found to delete.');
        return 0;
      }
      
      // Log the operation before executing
      \Drupal::logger('rest_api_authentication')->info('Attempting to delete all @count authentication log entries.', [
        '@count' => $count,
      ]);
      
      // Execute the delete operation
      $deleted_count = $this->database->delete('rest_api_authentication_logs')->execute();
      
      // Log the result
      \Drupal::logger('rest_api_authentication')->info('Successfully deleted @count authentication log entries.', [
        '@count' => $deleted_count,
      ]);
      
      return $deleted_count;
      
    } catch (\Exception $e) {
      \Drupal::logger('rest_api_authentication')->error('Failed to delete logs: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Get authentication method options for filter dropdown.
   *
   * @return array
   *   Array of authentication method options.
   */
  public function getAuthenticationMethodOptions(): array {
    return [
      'basic_auth' => 'Basic Authentication',
      'api_key' => 'API Key',
      'oauth' => 'OAuth/Access Token',
      'jwt' => 'JWT',
      'external_oauth' => 'External Identity Provider',
    ];
  }

  /**
   * Get status options for filter dropdown.
   *
   * @return array
   *   Array of status options.
   */
  public function getStatusOptions(): array {
    return [
      'success' => 'Success',
      'failure' => 'Failure',
      'blocked' => 'Blocked',
      'invalid_credentials' => 'Invalid Credentials',
      'expired_token' => 'Expired Token',
      'ip_blocked' => 'IP Blocked',
      'access_denied' => 'Access Denied',
    ];
  }

  /**
   * Get response code options for filter dropdown.
   *
   * @return array
   *   Array of response code options.
   */
  public function getResponseCodeOptions(): array {
    return [
      200 => '200 - OK',
      201 => '201 - Created',
      400 => '400 - Bad Request',
      401 => '401 - Unauthorized',
      403 => '403 - Forbidden',
      404 => '404 - Not Found',
      429 => '429 - Too Many Requests',
      500 => '500 - Internal Server Error',
    ];
  }

  /**
   * Check if the logs table exists and create it if it doesn't.
   *
   * @return bool
   *   TRUE if table exists or was created successfully, FALSE otherwise.
   */
  public function ensureTableExists(): bool {
    try {
      if (!$this->database->schema()->tableExists('rest_api_authentication_logs')) {
        \Drupal::logger('rest_api_authentication')->warning('Logs table does not exist. Attempting to create it.');
        
        // Get the schema from the install file
        $schema = rest_api_authentication_schema();
        if (isset($schema['rest_api_authentication_logs'])) {
          $this->database->schema()->createTable('rest_api_authentication_logs', $schema['rest_api_authentication_logs']);
          \Drupal::logger('rest_api_authentication')->info('Successfully created rest_api_authentication_logs table.');
          return TRUE;
        } else {
          \Drupal::logger('rest_api_authentication')->error('Could not find schema definition for rest_api_authentication_logs table.');
          return FALSE;
        }
      }
      return TRUE;
    } catch (\Exception $e) {
      \Drupal::logger('rest_api_authentication')->error('Failed to ensure logs table exists: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get debug information about the logs table.
   *
   * @return array
   *   Array containing debug information.
   */
  public function getDebugInfo(): array {
    try {
      // Ensure the table exists before attempting any operations
      if (!$this->ensureTableExists()) {
        return [
          'error' => 'Table does not exist and could not be created.',
        ];
      }

      $total_count = $this->database->select('rest_api_authentication_logs', 'l')
        ->countQuery()
        ->execute()
        ->fetchField();

      $oldest_log = $this->database->select('rest_api_authentication_logs', 'l')
        ->fields('l', ['timestamp'])
        ->orderBy('timestamp', 'ASC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      $newest_log = $this->database->select('rest_api_authentication_logs', 'l')
        ->fields('l', ['timestamp'])
        ->orderBy('timestamp', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      $current_time = time();
      $cutoff_30_days = $current_time - (30 * 24 * 60 * 60);
      $cutoff_7_days = $current_time - (7 * 24 * 60 * 60);

      $count_older_than_30_days = $this->database->select('rest_api_authentication_logs', 'l')
        ->condition('timestamp', $cutoff_30_days, '<')
        ->countQuery()
        ->execute()
        ->fetchField();

      $count_older_than_7_days = $this->database->select('rest_api_authentication_logs', 'l')
        ->condition('timestamp', $cutoff_7_days, '<')
        ->countQuery()
        ->execute()
        ->fetchField();

      $count_newer_than_7_days = $this->database->select('rest_api_authentication_logs', 'l')
        ->condition('timestamp', $cutoff_7_days, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      $count_newer_than_1_day = $this->database->select('rest_api_authentication_logs', 'l')
        ->condition('timestamp', $current_time - (1 * 24 * 60 * 60), '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      return [
        'total_logs' => $total_count,
        'oldest_log_timestamp' => $oldest_log,
        'newest_log_timestamp' => $newest_log,
        'current_time' => $current_time,
        'cutoff_30_days' => $cutoff_30_days,
        'cutoff_7_days' => $cutoff_7_days,
        'logs_older_than_30_days' => $count_older_than_30_days,
        'logs_older_than_7_days' => $count_older_than_7_days,
        'logs_newer_than_7_days' => $count_newer_than_7_days,
        'logs_newer_than_1_day' => $count_newer_than_1_day,
        'oldest_log_date' => $oldest_log ? date('Y-m-d H:i:s', $oldest_log) : 'N/A',
        'newest_log_date' => $newest_log ? date('Y-m-d H:i:s', $newest_log) : 'N/A',
        'current_date' => date('Y-m-d H:i:s', $current_time),
        'cutoff_30_days_date' => date('Y-m-d H:i:s', $cutoff_30_days),
        'cutoff_7_days_date' => date('Y-m-d H:i:s', $cutoff_7_days),
      ];
    } catch (\Exception $e) {
      return [
        'error' => $e->getMessage(),
      ];
    }
  }
} 