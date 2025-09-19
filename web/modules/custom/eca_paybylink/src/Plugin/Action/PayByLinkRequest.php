<?php

namespace Drupal\eca_paybylink\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EcaAction(
 *   id = "paybylink_request",
 *   label = @Translation("Make PayByLink Request"),
 *   type = "system"
 * )
 */
class PayByLinkRequest extends ActionBase {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  public function defaultConfiguration() {
    return [
      'url' => 'https://testapi.paybylink.eu/payment',
      'key' => '',
      'user' => '',
      'password' => '',
      'command' => 'GET',
      'reference' => '',
      'data' => '',
    ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->configuration['url'],
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
    ];
    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data'),
      '#default_value' => $this->configuration['data'],
    ];
    return $form;
  }

  public function execute($entity = NULL) {
    $url = $this->configuration['url'];
    $key = $this->configuration['key'];
    $user = $this->configuration['user'];
    $password = $this->configuration['password'];
    $command = $this->configuration['command'];
    $reference = $this->configuration['reference'];
    $data = $this->configuration['data'];

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
      \Drupal::messenger()->addMessage("PayByLink response: " . $body);
    }
    catch (\Exception $e) {
      \Drupal::logger('eca_paybylink')->error($e->getMessage());
      \Drupal::messenger()->addError("PayByLink request failed.");
    }
  }

  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $account->hasPermission('administer site configuration');
  }
}
