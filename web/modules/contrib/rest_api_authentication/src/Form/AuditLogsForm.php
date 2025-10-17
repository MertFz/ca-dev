<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\rest_api_authentication\Utilities;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest_api_authentication\Services\RestApiLogger;

/**
 * Defines the REST API Authentication logs report form.
 */
class AuditLogsForm extends FormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The REST API logger service.
   *
   * @var \Drupal\rest_api_authentication\Services\RestApiLogger
   */
  protected $logger;

  /**
   * Constructs a new ReportSectionForm instance.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\rest_api_authentication\Services\RestApiLogger $logger
   *   The REST API logger service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RestApiLogger $logger) {
    $this->dateFormatter = $date_formatter;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('rest_api_authentication.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rest_api_authentication_audit_logs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    
    $request = \Drupal::request();
    
    $form['#attached']['library'][] = 'rest_api_authentication/rest_api_authentication.basic_style_settings';
    
    $filters = [
      'username' => $request->query->get('username', ''),
      'client_ip' => $request->query->get('client_ip', ''),
      'authentication_method' => $request->query->get('authentication_method', ''),
      'status' => $request->query->get('status', ''),
      'response_code' => $request->query->get('response_code', ''),
      'date_from' => $request->query->get('date_from', ''),
      'date_to' => $request->query->get('date_to', ''),
    ];

    $form['filters'] = $this->buildFiltersSection($filters);
    $form['results'] = $this->buildResultsTable($filters);

    return $form;
  }

  /**
   * Builds the filters section of the form.
   *
   * @param array $filters
   *   The current filter values.
   *
   * @return array
   *   The form elements for filters.
   */
  protected function buildFiltersSection(array $filters): array {
    return [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['form-wrapper']],
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      'container' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['filters-container']],
        'username' => [
          '#type' => 'textfield',
          '#title' => $this->t('Username'),
          '#default_value' => $filters['username'],
          '#placeholder' => $this->t('Filter by username'),
          '#size' => 20,
        ],
        'client_ip' => [
          '#type' => 'textfield',
          '#title' => $this->t('Client IP Address'),
          '#default_value' => $filters['client_ip'],
          '#placeholder' => $this->t('Filter by IP address'),
          '#size' => 20,
        ],
        'authentication_method' => [
          '#type' => 'select',
          '#title' => $this->t('Authentication Method'),
          '#options' => ['' => $this->t('- Any -')] + $this->logger->getAuthenticationMethodOptions(),
          '#default_value' => $filters['authentication_method'],
        ],
        'status' => [
          '#type' => 'select',
          '#title' => $this->t('Status'),
          '#options' => ['' => $this->t('- Any -')] + $this->logger->getStatusOptions(),
          '#default_value' => $filters['status'],
        ],
        'response_code' => [
          '#type' => 'select',
          '#title' => $this->t('Response Code'),
          '#options' => ['' => $this->t('- Any -')] + $this->logger->getResponseCodeOptions(),
          '#default_value' => $filters['response_code'],
        ],
        'date_from' => [
          '#type' => 'date',
          '#title' => $this->t('Date From'),
          '#default_value' => $filters['date_from'],
        ],
        'date_to' => [
          '#type' => 'date',
          '#title' => $this->t('Date To'),
          '#default_value' => $filters['date_to'],
        ],
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['filters-actions']],
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Apply Filters'),
          '#attributes' => [
            'class' => ['button'],
          ],
        ],
        'reset' => [
          '#type' => 'submit',
          '#value' => $this->t('Reset'),
          '#submit' => ['::resetFiltersSubmit'],
          '#attributes' => [
            'class' => ['button'],
          ],
        ],
        'download' => [
          '#type' => 'submit',
          '#value' => $this->t('Download CSV'),
          '#submit' => ['::downloadCsvSubmit'],
          '#attributes' => [
            'class' => ['button', 'button--primary'],
          ],
        ],
        'delete' => [
          '#type' => 'link',
          '#title' => $this->t('Delete'),
          '#url' => Url::fromRoute('rest_api_authentication.delete_logs_confirm'),
          '#attributes' => [
            'class' => ['button', 'button--danger'],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('rest_api_authentication.audit_logs', [], [
      'query' => [
        'username' => trim($form_state->getValue('username')),
        'client_ip' => trim($form_state->getValue('client_ip')),
        'authentication_method' => $form_state->getValue('authentication_method'),
        'status' => $form_state->getValue('status'),
        'response_code' => $form_state->getValue('response_code'),
        'date_from' => $form_state->getValue('date_from'),
        'date_to' => $form_state->getValue('date_to'),
      ],
    ]);
  }

  /**
   * Submit handler for CSV download.
   */
  public function downloadCsvSubmit(array &$form, FormStateInterface $form_state): void {
    try {
      $filters = [
        'username' => trim($form_state->getValue('username')),
        'client_ip' => trim($form_state->getValue('client_ip')),
        'authentication_method' => $form_state->getValue('authentication_method'),
        'status' => $form_state->getValue('status'),
        'response_code' => $form_state->getValue('response_code'),
        'date_from' => $form_state->getValue('date_from'),
        'date_to' => $form_state->getValue('date_to'),
      ];

      $logs = $this->logger->getLogs($filters, 10000, 0); 

      $filename = 'rest_api_auth_logs_' . date('Ymd_His') . '.csv';
      $response = new Response();
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

      $handle = fopen('php://temp', 'r+');
      fputcsv($handle, [
        'Timestamp',
        'Date/Time',
        'Username',
        'Client IP Address',
        'Request Method',
        'Endpoint URL',
        'Authentication Method',
        'Status',
        'Response Code',
        'Error Message',
        'User Agent'
      ]);

      foreach ($logs as $record) {
        fputcsv($handle, [
          $record->timestamp,
          $this->dateFormatter->format($record->timestamp, 'short'),
          $record->username,
          $record->client_ip,
          $record->request_method,
          $record->endpoint_url,
          $record->authentication_method,
          $record->status,
          $record->response_code,
          $record->error_message ?? '',
          $record->user_agent ?? '',
        ]);
      }

      rewind($handle);
      $response->setContent(stream_get_contents($handle));
      fclose($handle);

      $response->send();
      exit;
    } catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Error generating CSV: @error', ['@error' => $e->getMessage()]));
    }
  }

  /**
   * Submit handler for resetting filters.
   */
  public function resetFiltersSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('rest_api_authentication.audit_logs');
  }

  /**
   * Build the results table.
   *
   * @param array $filters
   *   The filter values.
   *
   * @return array
   *   The table render array.
   */
  protected function buildResultsTable(array $filters): array {
    $page = \Drupal::request()->query->get('page', 0);
    $limit = 50;
    $offset = $page * $limit;

    $logs = $this->logger->getLogs($filters, $limit, $offset);
    $total_count = $this->logger->getLogsCount($filters);

    $rows = [];
          foreach ($logs as $record) {
        $rows[] = [
          'data' => [
            $this->dateFormatter->format($record->timestamp, 'short'),
            Html::escape($record->username),
            Html::escape($record->client_ip),
            Html::escape($record->request_method),
            Html::escape($record->endpoint_url),
            Html::escape($record->authentication_method),
            Html::escape($record->status),
            $record->response_code,
            $record->error_message ? Html::escape($record->error_message) : '-',
          ],
          'class' => ['rest-api-log-row'],
        ];
      }

    return [
      'logs_table' => [
        '#type' => 'table',
        '#header' => [
          ['data' => $this->t('Timestamp'), 'class' => ['column-timestamp']],
          ['data' => $this->t('Username'), 'class' => ['column-username']],
          ['data' => $this->t('Client IP Address'), 'class' => ['column-client-ip']],
          ['data' => $this->t('Request Method'), 'class' => ['column-request-method']],
          ['data' => $this->t('Endpoint URL'), 'class' => ['column-endpoint-url']],
          ['data' => $this->t('Authentication Method'), 'class' => ['column-auth-method']],
          ['data' => $this->t('Status'), 'class' => ['column-status']],
          ['data' => $this->t('Response Code'), 'class' => ['column-response-code']],
          ['data' => $this->t('Error Message'), 'class' => ['column-error-message']],
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No authentication logs found matching your criteria.'),
        '#attributes' => ['class' => ['rest-api-auth-logs-table']],
        '#sticky' => TRUE,
      ],
      'pager' => [
        '#type' => 'pager',
        '#quantity' => 5,
      ],
      'summary' => [
        '#markup' => $this->t('<p class="rest-api-logs-summary">Showing @start to @end of @total records.</p>', [
          '@start' => $offset + 1,
          '@end' => min($offset + $limit, $total_count),
          '@total' => $total_count,
        ]),
      ],
    ];
  }
}
