<?php

namespace Drupal\eca_paybylink\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\ClientInterface;
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
      'url' => 'https://testapi.paybylink.eu/payment',
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
  public function execute($entity = NULL) : void {
    $url = $this->configuration['url'];
    $key = $this->configuration['key'];
    $user = $this->configuration['user'];
    $password = $this->configuration['password'];
    $command = $this->configuration['command'];
    $reference = $this->configuration['reference'];
    $data = $this->configuration['data'];

    // Construct the URL based on the HTTP method selected.
    if (strtoupper($command) === 'POST') {
      // POST/create endpoint.
      $url .= '/create/' . $key;
    }
    else {
      // GET/retrieve endpoint.
      $url .= '/url/' . $key . '/' . $reference;
    }

    // Only send Authorization header; let Guzzle set Content-Type based on payload
    // (form_params will send application/x-www-form-urlencoded).
    $headers = [
      'Authorization' => 'Basic ' . base64_encode($user . ':' . $password),
    ];

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
          'headers' => $headers,
          'form_params' => $query,
        ]);
      }
      else {
        // GET request with query string.
        $response = $this->httpClient->request('GET', $url, [
          'headers' => $headers,
          'query' => $query,
        ]);
      }
      $body = $response->getBody()->getContents();

      // Return the full response body to make it available to other ECA actions.
      $this->setContextValue('paybylink_response', $body);

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
        $this->setContextValue('paybylink_url', $paybylink_url);
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
    return $account->hasPermission('administer site configuration');
  }

}