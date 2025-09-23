<?php

namespace Drupal\eca_paybylink\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Action(
 * id = "eca_paybylink_request",
 * label = @Translation("Make PayByLink Request"),
 * type = "system"
 * )
 */
class PayByLinkRequest extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new PayByLinkRequest object.
   *
   * @param array $configuration
   * A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   * The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   * The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   * The Guzzle HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'url' => 'https://testapi.paybylink.eu/payment',
      'key' => '9df037b1-244e-4150-8b2e-b3c05ef00de1',
      'user' => '8d0f2ca458834858',
      'password' => 'hx2PD#uG297h&eVv',
      'command' => 'GET',
      'reference' => '',
      'data' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
      '#title' => $this->t('Command'),
      '#options' => ['GET' => 'GET', 'CREATE' => 'CREATE'],
      '#default_value' => $this->configuration['command'],
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
  public function execute($entity = NULL) {
    $url = $this->configuration['url'];
    $key = $this->configuration['key'];
    $user = $this->configuration['user'];
    $password = $this->configuration['password'];
    $command = $this->configuration['command'];
    $reference = $this->configuration['reference'];
    $data = $this->configuration['data'];

    // Construct the URL based on the command.
    switch ($command) {
      case 'CREATE':
        $url .= '/create/' . $key;
        break;
      default:
        $url .= '/url/' . $key . '/' . $reference;
    }

    $headers = [
      'Content-Type' => 'application/json',
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
      $response = $this->httpClient->request('GET', $url, [
        'headers' => $headers,
        'query' => $query,
      ]);
      $body = $response->getBody()->getContents();

      // Return the response body to make it available to other ECA actions.
      $this->setContextValue('paybylink_response', $body);
      \Drupal::logger('eca_paybylink')->info("PayByLink request successful. Response: @response", ['@response' => $body]);
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