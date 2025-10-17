<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest_api_authentication\Services\RestApiLogger;

/**
 * Confirmation form for deleting old REST API authentication logs.
 */
class DeleteLogsConfirmForm extends ConfirmFormBase {

  /**
   * The REST API logger service.
   *
   * @var \Drupal\rest_api_authentication\Services\RestApiLogger
   */
  protected $logger;

  /**
   * Constructs a new DeleteLogsConfirmForm instance.
   *
   * @param \Drupal\rest_api_authentication\Services\RestApiLogger $logger
   *   The REST API logger service.
   */
  public function __construct(RestApiLogger $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rest_api_authentication.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_api_authentication_delete_logs_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete all REST API authentication logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('rest_api_authentication.audit_logs');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will permanently delete ALL REST API authentication logs. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form = parent::buildForm($form, $form_state);
    $debug_info = $this->logger->getDebugInfo();

    if (!isset($debug_info['error'])) {
      $form['debug_info'] = [
        '#type' => 'details',
        '#title' => $this->t('Current Log Status'),
        '#open' => TRUE,
        'info' => [
          '#markup' => $this->t('<p><strong>Total logs to be deleted:</strong> @total</p>', [
            '@total' => $debug_info['total_logs'],
          ]),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $deleted_count = $this->logger->deleteAllLogs();
      
      $this->messenger()->addStatus($this->t('Successfully deleted @count authentication log entries.', [
        '@count' => $deleted_count,
      ]));
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error deleting logs: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRedirect('rest_api_authentication.audit_logs');
  }
} 