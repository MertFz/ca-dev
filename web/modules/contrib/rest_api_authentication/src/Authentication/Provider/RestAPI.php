<?php

namespace Drupal\rest_api_authentication\Authentication\Provider;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest_api_authentication\ApiAuthenticationApiToken;
use Drupal\rest_api_authentication\ApiAuthenticationBasicAuth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Miniorange authentication provider.
 */
class RestAPI implements AuthenticationProviderInterface {

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new restAPI object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('rest_api_authentication.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $enable_authentication = $this->config->get('enable_authentication');
    if ($enable_authentication == 1) {
      if (strpos($request->getRequestUri(), '/admin/config/services/jsonapi/') !== FALSE) {
        return FALSE;
      }
      if (strpos($request->getRequestUri(), '/jsonapi/') !== FALSE) {
        return TRUE;
      }
      if (strpos($request->getRequestUri(), '?_format=') !== FALSE) {
        return TRUE;
      }
      return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {

    $api_error = [];
    $logger_service = \Drupal::service('rest_api_authentication.logger');
    $auth_method = 'unknown';
    if ($request->getPathInfo() == "/user/login") {
      return NULL;
    }
    $all_headers = $request->headers->all();
    $application_id = null;
    
    if (isset($all_headers['auth-method'][0])) {
      $application_id = trim($all_headers['auth-method'][0]);
    } else {
      $config = \Drupal::config('rest_api_authentication.settings');
      $default_app_id = $config->get('default_application_id');
      
      if ($default_app_id) {
        $applications = $config->get('applications') ?: [];
        if (is_string($applications)) {
          $applications = unserialize($applications) ?: [];
        }
        
        if (isset($applications[$default_app_id]) && isset($applications[$default_app_id]['is_default']) && $applications[$default_app_id]['is_default']) {
          $application_id = $default_app_id;
          \Drupal::logger('rest_api_authentication')->notice('Using default application: @app_id', ['@app_id' => $application_id]);
        }
      }
      
      if (!$application_id) {
        $applications = $config->get('applications') ?: [];
        if (is_string($applications)) {
          $applications = unserialize($applications) ?: [];
        }
          $api_error = [
            'status' => 'error',
            'http_code' => 400,
            'error' => 'MISSING_HEADER',
            'error_description' => 'Missing required unique header. It should contain the application ID, or a default application must be configured.',
          ];
        
          \Drupal::logger('rest_api_authentication')->notice('Missing unique header in request and no default application configured');
          
          $logger_service->logAuthenticationAttempt(
            $request,
            $auth_method,
            'failure',
            $api_error['http_code'],
            NULL,
            $api_error['error_description']
          );
              
          echo json_encode($api_error, JSON_PRETTY_PRINT);
          http_response_code($api_error['http_code']);
          exit;
        
      }
    }

    $config = \Drupal::config('rest_api_authentication.settings');
    $applications = $config->get('applications') ?: [];
 
    if (!isset($applications[$application_id])) {
      $api_error = [
        'status' => 'error',
        'http_code' => 400,
        'error' => 'INVALID_APPLICATION_ID',
        'error_description' => 'The provided application ID is not valid or not configured.',
      ];
      
      \Drupal::logger('rest_api_authentication')->notice('Invalid application ID provided: @app_id', ['@app_id' => $application_id]);
      
      $logger_service->logAuthenticationAttempt(
        $request,
        $auth_method,
        'failure',
        $api_error['http_code'],
        NULL,
        $api_error['error_description']
      );
        
      echo json_encode($api_error, JSON_PRETTY_PRINT);
      http_response_code($api_error['http_code']);
      exit;
    }

    $app_data = $applications[$application_id];
    $authentication_method = $app_data['authentication_method'] ?? 0;
    \Drupal::logger('rest_api_authentication')->notice('Using application: @app_name with auth method: @auth_method', [
      '@app_name' => $app_data['name'] ?? 'Unknown',
      '@auth_method' => $authentication_method
    ]);

    $auth_method_names = [
      0 => 'basic_auth',
      1 => 'api_key', 
      2 => 'oauth',
      3 => 'jwt',
      4 => 'external_oauth',
    ];
    $auth_method = $auth_method_names[$authentication_method] ?? 'unknown';

    switch ($authentication_method) {
      case 0:
        $api_error = ApiAuthenticationBasicAuth::validateApiRequest($request, $app_data);
        break;

      case 1:
        $api_error = ApiAuthenticationApiToken::validateApiRequest($request, $app_data);
        break;

      default:
        return NULL;
    }

    if (isset($api_error['status']) && $api_error['status'] == 'error') {

      if ((isset($api_error['message']) && trim($api_error['message']) != '') || (isset($api_error['error_description']) && trim($api_error['error_description']) != '')) {
      
        $auth_method_names = [
          0 => 'basic_auth',
          1 => 'api_key', 
          2 => 'oauth',
          3 => 'jwt',
          4 => 'external_oauth',
        ];
        $auth_method = $auth_method_names[$authentication_method] ?? 'unknown';

        $logger_service->logAuthenticationAttempt(  $request,$auth_method,  'failure',  $api_error['http_code'],
          NULL,
          $api_error['error_description'] ?? $api_error['message'] ?? 'Authentication failed'
        );

        echo json_encode($api_error, JSON_PRETTY_PRINT);
        http_response_code($api_error['http_code']);
        exit;
      }
      else {
        $logger_service->logAuthenticationAttempt(
          $request,
          $auth_method,
          'failure',
          403,
          NULL,
          'Access denied'
        );
        throw new AccessDeniedHttpException();
      }
    }
    $account = $api_error['user'];
    $uid = $account->id();

    try {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($uid);
      $logger_service->logAuthenticationAttempt(
        $request,
        $auth_method,
        'success',
        200,
        $user,
        NULL
      );
      return $user;
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setThrowable(new UnauthorizedHttpException('Invalid consumer origin.', $exception));
      return TRUE;
    }
    return FALSE;
  }

}
