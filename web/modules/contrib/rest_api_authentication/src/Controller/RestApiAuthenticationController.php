<?php

namespace Drupal\rest_api_authentication\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the rest api authentication module.
 */
class RestApiAuthenticationController extends ControllerBase {
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Request Stack Service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(FormBuilderInterface $form_builder, RequestStack $request_stack) {
    $this->formBuilder = $form_builder;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * Opens the support request form in a modal dialog.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function openSupportRequestForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\rest_api_authentication\Form\MiniornageAPIAuthnRequestSupport');
    $request = $this->requestStack->getCurrentRequest();
    if ($request->isXmlHttpRequest()) {
      $response->addCommand(new OpenModalDialogCommand($this->t('Support Request/Contact Us'), $modal_form, ['width' => '40%']));
    }
    else {
      $response = $modal_form;
    }
    return $response;
  }

  /**
   * Opens the trial request form in a modal dialog.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function openTrialRequestForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\rest_api_authentication\Form\MiniornageAPIAuthnRequestTrial');
    $request = $this->requestStack->getCurrentRequest();

    if ($request->isXmlHttpRequest()) {
      $response->addCommand(new OpenModalDialogCommand('Request 7-Days Full Feature Trial License', $modal_form, ['width' => '40%']));
    }
    else {
      $response = $modal_form;
    }
    return $response;
  }
  public function deleteApplication($app_id) {
    $config = \Drupal::configFactory()->getEditable('rest_api_authentication.settings');
    $applications = $config->get('applications');
   
    if (is_string($applications)) {
      $applications = unserialize($applications) ?: [];
    } elseif (!is_array($applications)) {
      $applications = [];
    }
    
    if (isset($applications[$app_id])) {
      unset($applications[$app_id]);
      
      
      $config->set('application_name', '')
             ->set('authentication_method', null)
             ->set('applications', $applications)
             ->save();
      
      \Drupal::messenger()->addMessage(t('Application deleted successfully.'));
    } else {
      \Drupal::messenger()->addError(t('Application not found.'));
    }
  
    return new RedirectResponse('/admin/config/people/rest_api_authentication/auth_settings?tab=edit-api-auth');
  }

  /**
   * Sets an application as the default application.
   *
   * @param string $app_id
   *   The application ID to set as default.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function setDefaultApplication($app_id) {
    $config = \Drupal::configFactory()->getEditable('rest_api_authentication.settings');
    $applications = $config->get('applications');
   
    if (is_string($applications)) {
      $applications = unserialize($applications) ?: [];
    } elseif (!is_array($applications)) {
      $applications = [];
    }
    
    if (isset($applications[$app_id])) {
      foreach ($applications as $key => $app) {
        $applications[$key]['is_default'] = FALSE;
      }
      
      $applications[$app_id]['is_default'] = TRUE;
      
      $config->set('applications', $applications)
             ->set('default_application_id', $app_id)
             ->save();
      
      \Drupal::messenger()->addMessage(t('Application set as default.'));
    } else {
      \Drupal::messenger()->addError(t('Application not found.'));
    }
  
    return new RedirectResponse('/admin/config/people/rest_api_authentication/auth_settings?tab=edit-api-auth');
  }

  /**
   * Revokes an access token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return void
   *   JSON response is output directly.
   */
  public function revokeToken(Request $request) {
    $authorization_header = $request->headers->get('Authorization') ?: $request->headers->get('Authorisation');
   
    if (empty($authorization_header) || stripos($authorization_header, 'Basic ') !== 0) {
      $api_response = [
        'status' => 'error',
        'http_code' => '401',
        'error' => 'MISSING_BASIC_AUTH',
        'error_description' => 'Basic Authentication is required for this endpoint.',
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

    $encoded_credentials = trim(substr($authorization_header, 6));
    $decoded_credentials = base64_decode($encoded_credentials, TRUE);
    
    if ($decoded_credentials === FALSE || strpos($decoded_credentials, ':') === FALSE) {
      $api_response = [
        'status' => 'error',
        'http_code' => '401',
        'error' => 'INVALID_BASIC_AUTH_FORMAT',
        'error_description' => 'Invalid Basic Authentication format.',
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

    $credentials = explode(':', $decoded_credentials, 2);
    $username = $credentials[0];
    $password = $credentials[1];

    if (empty($username) || empty($password)) {
      $api_response = [
        'status' => 'error',
        'http_code' => '401',
        'error' => 'MISSING_CREDENTIALS',
        'error_description' => 'Username and password are required.',
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

    if (!(\Drupal::service('user.auth')->authenticate($username, $password))) {
      $api_response = [
        'status' => 'error',
        'http_code' => '401',
        'error' => 'INVALID_CREDENTIALS',
        'error_description' => 'Invalid username or password.',
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

    $user = user_load_by_name($username);
    if ($user->isBlocked()) {
      $api_response = [
        'status' => 'error',
        'http_code' => '403',
        'error' => 'USER_BLOCKED',
        'error_description' => 'The user account is blocked.',
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

    if ($request->getMethod() !== 'POST') {
      $api_response = [
        'status' => 'error', 
        'http_code' => '405',
        'error' => 'INVALID_HTTP_METHOD', 
        'error_description' => 'The used HTTP method should be a POST.'
      ];
      echo json_encode($api_response, JSON_PRETTY_PRINT);
      http_response_code($api_response['http_code']);
      exit;
    }

   
    $api_response = [
      'status' => 'error',
      'http_code' => '403',
      'error' => 'PREMIUM_FEATURE',
      'error_description' => 'Token revocation is a premium feature. Please upgrade to premium to use this functionality.',
    ];
    echo json_encode($api_response, JSON_PRETTY_PRINT);
    http_response_code($api_response['http_code']);
    exit;
  }

}
