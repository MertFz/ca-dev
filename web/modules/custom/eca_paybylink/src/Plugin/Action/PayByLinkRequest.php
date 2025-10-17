<?php

namespace Drupal\eca_paybylink\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @Action(
 *   id = "eca_paybylink_request",
 *   label = @Translation("Make PayByLink Request"),
 *   type = "custom",
 * )
 */
class PayByLinkRequest extends ConfigurableActionBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'url' => 'https://testapi.paybylink.com/payment',
      'key' => '9df037b1-244e-4150-8b2e-b3c05ef00de1',
      'user' => '8d0f2ca458834858',
      'password' => 'hx2PD#uG297h&eVv',
  // Default to POST (create) since the API expects form data on create.
  'command' => 'POST',
      'reference' => '',
      'data' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Let the base class build any base form and ensure configuration is
    // properly attached/saved.
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->configuration['url'],
      '#description' => $this->t('The base URL for the PayByLink API.'),
    ];
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->configuration['key'],
    ];
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $this->configuration['user'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
    ];
    $form['command'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP Method'),
      '#options' => ['POST' => 'POST (create)', 'GET' => 'GET (retrieve)'],
      '#default_value' => $this->configuration['command'],
      '#description' => $this->t('Use POST to create a PayByLink (sends form data). Use GET to retrieve by reference.'),
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reference'),
      '#default_value' => $this->configuration['reference'],
      '#description' => $this->t('The PayByLink reference, only for GET command.'),
    ];
    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data'),
      '#default_value' => $this->configuration['data'],
      '#description' => $this->t("The request body, formatted as 'param=value&param=value&...' or one 'param=value' per line."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Read top-level values (form fields) and persist into plugin configuration.
    $keys = ['url','key','user','password','command','reference','data'];
    foreach ($keys as $k) {
      $val = $form_state->getValue($k);
      if ($val !== NULL) {
        $this->configuration[$k] = $val;
      }
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) : void {
    // Debug: log effective configuration so we can see which values are used
    // at runtime. Remove or lower this logging level in production.
    \Drupal::logger('eca_paybylink')->debug('Executing PayByLinkRequest with configuration: @config', ['@config' => json_encode($this->configuration)]);

    // Use the saved instance configuration (set via the form) so that
    // custom parameters configured in the ECA UI are used rather than
    // the class defaults.
  $url = $this->configuration['url'] ?? $this->getConfiguration()['url'] ?? '';
  $key = $this->configuration['key'] ?? $this->getConfiguration()['key'] ?? '';
  $user = $this->configuration['user'] ?? $this->getConfiguration()['user'] ?? '';
  $password = $this->configuration['password'] ?? $this->getConfiguration()['password'] ?? '';
  $command = $this->configuration['command'] ?? $this->getConfiguration()['command'] ?? 'POST';
  $reference = $this->configuration['reference'] ?? $this->getConfiguration()['reference'] ?? '';
  $data = $this->configuration['data'] ?? $this->getConfiguration()['data'] ?? '';

  // Replace tokens (token module or manual replacements) in the configured
  // data so placeholders like [node:id] or [current_form:values:email]
  // are resolved to actual values before sending.
  $data = $this->replaceTokensInString($data, $entity);

    // Construct the URL based on the HTTP method selected.
    if (strtoupper($command) === 'POST') {
      // POST/create endpoint.
      $url .= '/create/' . $key;
    }
    else {
      // GET/retrieve endpoint.
      $url .= '/url/' . $key . '/' . $reference;
    }

    // Prepare the query data from the user input.
    $query = [];
    if (!empty($data)) {
      foreach (preg_split('/[\r\n&]+/', $data) as $item) {
        if (strpos($item, '=') !== FALSE) {
          list($name, $value) = explode('=', $item, 2);
          $query[$name] = $value;
        }
      }
    }

    try {
      if (strtoupper($command) === 'POST') {
        // POST to create endpoint with form-encoded body (preserve field names/case).
       $response = $this->httpClient->request('POST', $url, [
          'auth' => [$user, $password],  // Basic Auth
          'form_params' => $query,
        ]);
      }
      else {
        // GET request with query string.
        $response = $this->httpClient->request('GET', $url, [
          'auth' => [$user, $password],
          'query' => $query,
        ]);
      }
      $body = $response->getBody()->getContents();

  // Return the full response body to make it available to other ECA actions.
  // Using the ECA token service is more reliable here because this plugin
  // does not expose runtime context definitions the Context API expects,
  // which caused "not a valid context" exceptions. Tokens are intended
  // for sharing arbitrary data between ECA actions.
  $this->tokenService->addTokenData('paybylink_response', $body);

      // Try to extract a payment URL from the response and expose it as a
      // separate context value `paybylink_url` so downstream ECA actions can
      // save it on entities.
      $paybylink_url = NULL;
      $decoded = json_decode($body, TRUE);
      if (is_array($decoded)) {
        // Common keys that might contain the URL.
        $candidates = ['url', 'Url', 'paymentUrl', 'PaymentUrl', 'link', 'Link', 'data'];
        foreach ($candidates as $k) {
          if (isset($decoded[$k])) {
            if (is_string($decoded[$k])) {
              $paybylink_url = $decoded[$k];
              break;
            }
            if (is_array($decoded[$k]) && isset($decoded[$k]['url'])) {
              $paybylink_url = $decoded[$k]['url'];
              break;
            }
          }
        }
        // If not found, scan recursively for the first string that looks like a URL.
        if (!$paybylink_url) {
          $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($decoded));
          foreach ($iterator as $val) {
            if (is_string($val) && preg_match('#https?://#i', $val)) {
              $paybylink_url = $val;
              break;
            }
          }
        }
      }
      else {
        // Not JSON: try to find a URL in the raw body.
        if (preg_match('#https?://[^\s"\']+#i', $body, $m)) {
          $paybylink_url = $m[0];
        }
      }

      if ($paybylink_url) {
        // Store the extracted URL as an ECA token so downstream actions can
        // consume it (for example the Token: set value action or Token-based
        // entity saves).
        $this->tokenService->addTokenData('paybylink_url', $paybylink_url);
        \Drupal::logger('eca_paybylink')->info('PayByLink request successful. Extracted URL: @url', ['@url' => $paybylink_url]);
      }
      else {
        \Drupal::logger('eca_paybylink')->info('PayByLink request successful. Response: @response', ['@response' => $body]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('eca_paybylink')->error("PayByLink request failed with error: @error", ['@error' => $e->getMessage()]);
    }
  }

  /**
   * {@inheritdoc}
   */
public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
  // Zorg dat er altijd een account is.
  $account = $account ?: \Drupal::currentUser();

  if ($account && $account->hasPermission('use paybylink action')) {
    return AccessResult::allowed();
  }

  return AccessResult::forbidden();
}

  /**
   * Replace common tokens in a string using the token service when available.
   *
   * Supports basic replacements for [node:id] and [current_form:values:FIELD].
   */
  protected function replaceTokensInString(string $text, $entity = NULL): string {
    // If token module is available, use it for broader replacement support.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      try {
        $tokens = [];
        $data = [];
        if ($entity) {
          $data['node'] = $entity;
        }
        // Use the token service.
        $text = \Drupal::token()->replace($text, $data, ['clear' => TRUE]);
      }
      catch (\Exception $e) {
        // Fall back to manual replacement below.
      }
    }

    // Manual replacements: [node:id]
    if ($entity && is_object($entity) && method_exists($entity, 'id')) {
      $text = str_replace('[node:id]', $entity->id(), $text);
    }

    // Replace basic current_form tokens like [current_form:values:email]
    if (preg_match_all('/\[current_form:values:([a-zA-Z0-9_]+)\]/', $text, $matches)) {
      foreach ($matches[1] as $i => $field) {
        // Try to find form values in the current request (POST).
        $value = NULL;
        $request = \Drupal::request();
        $post = $request->request->all();
        if (isset($post[$field])) {
          $value = $post[$field];
        }
        // Fallback: empty string
        $text = str_replace($matches[0][$i], $value ?? '', $text);
      }
    }

    return $text;
  }


}