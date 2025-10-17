<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\rest_api_authentication\Utilities;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\rest_api_authentication\MiniorangeApiAuthConstants;

/**
 * Provides a form for configuring MiniOrange API Authentication module.
 */
class MiniOrangeAPIAuth extends FormBase {
  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Request Stack Service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Email validator interface object.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    MessengerInterface $messenger,
    EmailValidatorInterface $email_validator,
  ) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('email.validator'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'rest_api_authentication_config_client';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  
  $form['markup_library'] = [
    '#attached' => [
      'library' => [
        'rest_api_authentication/rest_api_authentication.basic_style_settings',
      ],
      'drupalSettings' => [
        'restApiAuth' => [
          'cardAnimation' => TRUE,
        ],
      ],
    ],
  ];

 
  $config_name = 'rest_api_authentication.settings';
  $config = \Drupal::config($config_name);

  $request = $this->requestStack->getCurrentRequest();
  $app_id = $request->query->get('app_id');
  $new_app = $request->query->get('new_app');
  $auth_method_param = $request->query->get('auth_method');
  $app_name_param = $request->query->get('app_name');
  $editing_app = NULL;

  $form_mode = $form_state->get('form_mode') ?: 'list';

  if ($app_id) {
    $form_mode = 'edit';
    $form_state->set('form_mode', 'edit')
      ->set('editing_app_id', $app_id);

    $applications = $config->get('applications') ?? [];
    $editing_app = $applications[$app_id] ?? NULL;
  }
  elseif ($new_app) {
    $form_mode = 'add';
    $form_state->set('form_mode', 'add');
  }

  $defaults = [
    'authentication_method' => 0,
    'name' => '',
    'enable_authentication' => FALSE,
    'whitelist_get_apis' => FALSE,
    'out_of_the_box_authentication' => FALSE,
    'rest_api_auth_type' => 0,
    'jwt_method_username_attribute' => '',
    'jwt_method_jwks_uri' => '',
    'user_info_endpoint' => '',
    'username_attribute' => '',
    'oauth_type' => 'module_specific',
  ];

  $values = [];
  foreach ($defaults as $key => $default) {
    if ($editing_app) {
      $values[$key] = $editing_app[$key] ?? $default;
    }
    else {
      $values[$key] = $config->get($key) ?? $default;
    }
  }

  if ($auth_method_param !== null) {
    $values['authentication_method'] = $auth_method_param;
  }
  if ($app_name_param !== null) {
    $values['name'] = $app_name_param;
  }

  \Drupal::configFactory()
    ->getEditable($config_name)
    ->set('rest_api_authentication_support_request_flag', 'APIAuthTab')
    ->save();

  $applications = $this->getAllApplications();
  $show_config_form = ($form_mode === 'edit') || ($form_mode === 'add') || empty($applications);

  $this->buildApplicationsList($form, $applications, $form_mode);
 
  $config_values = [
    'auth_method' => $values['authentication_method'],
        'application_name' => $values['name'],
        'enable_authentication' => $values['enable_authentication'],
        'whitelist_get_apis' => $values['whitelist_get_apis'],
        'rest_api_auth_type' => $values['rest_api_auth_type'],
        'jwt_method_username_attribute' => $values['jwt_method_username_attribute'],
        'jwt_method_jwks_uri' => $values['jwt_method_jwks_uri'],
        'user_info_endpoint' => $values['user_info_endpoint'],
        'username_attribute' => $values['username_attribute'],
        'out_of_the_box_authentication' => $values['out_of_the_box_authentication'],
        'oauth_type' => $values['oauth_type'],
  ];

  if ($show_config_form) {
    $this->buildConfigurationForm($form,$form_state,$form_mode,$app_id,$config_values);
  }
  
  return $form;
   }


  private function getAllApplications()
  {
    $config = \Drupal::config('rest_api_authentication.settings');
    $applications = $config->get('applications');
    
    if (is_string($applications)) {
      $applications = unserialize($applications) ?: [];
    } elseif (!is_array($applications)) {
      $applications = [];
    }

    if (empty($applications)) {
      $application_name = $config->get('application_name');
      $authentication_method = $config->get('authentication_method');

      if (!empty($application_name) && $authentication_method !== null && $authentication_method !== '') {
        $app_id = $this->generateApplicationId();
        $auth_method_names = [
          0 => 'Basic Authentication',
          1 => 'API Key',
        ];

        $auth_method_name = isset($auth_method_names[$authentication_method]) ? $auth_method_names[$authentication_method] : 'Not Selected';
        $header_info = $this->getHeaderInfo($authentication_method, $config);

        $applications[$app_id] = [
          'id' => $app_id,
          'name' => $application_name,
          'auth_method' => $auth_method_name,
          'authentication_method' => $authentication_method,
          'header_info' => $header_info,
          'is_default' => FALSE,
        ];
        
        \Drupal::configFactory()->getEditable('rest_api_authentication.settings')
          ->set('applications', $applications)
          ->save();
      }
    }

    return $applications;
  }

  /**
   * Generate a unique application ID.
   *
   * @return string
   *   A unique application ID.
   */
  private function generateApplicationId() {
    $uuid_service = \Drupal::service('uuid');
    return substr($uuid_service->generate(), 0, 8);
  }

  private function buildApplicationsList(array &$form, array $applications, string $form_mode)
  {
    if (!empty($applications) && $form_mode === 'list') {
      $form['applications_section'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configured Applications'),
        '#collapsible' => FALSE,
        '#attributes' => ['class' => ['applications-section']],
      ];
      $form['applications_section']['auth_note'] = [
        '#type' => 'markup',
        '#markup' => $this->t(
          '<div class="custom-warning">
            <strong>Note:</strong>
            <ul>
              <li>When authenticating, include the unique header shown in the <em>"Unique Header (Key: Value)"</em> column, using the exact key and value in your API requests.</li>
              <li>If you choose <b>Default</b>, you do not need to pass the unique header. Only one application can be set as <b>Default</b>.</li>
            </ul>
          </div>'
        ),
      ];

      $form['applications_section']['add_new_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add New Application'),
        '#submit' => ['::showAddForm'],
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
        '#prefix' => '<div style="margin-bottom: 20px;">',
        '#suffix' => '</div>',
      ];

      if (!empty($applications)) {
        $form['applications_section']['applications_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('ID'),
            $this->t('Application Name'),
            $this->t('Selected Auth Method'),
            $this->t('Unique Header (Key: Value)'),
            $this->t('Actions'),
          ],
          '#attributes' => ['class' => ['applications-table']],
        ];

        foreach ($applications as $app_id_table => $app_data) {
          $is_default = isset($app_data['is_default']) && $app_data['is_default'];
          
          $actions = [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => \Drupal\Core\Url::fromRoute('rest_api_authentication.auth_settings', [], [
                'query' => [
                  'tab' => 'edit-api-auth',
                  'app_id' => $app_id_table
                ]
              ]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => \Drupal\Core\Url::fromRoute('rest_api_authentication.delete_application', ['app_id' => $app_id_table]),
            ],
          ];
          
          if (!$is_default) {
            $actions['set_default'] = [
              'title' => $this->t('Default'),
              'url' => \Drupal\Core\Url::fromRoute('rest_api_authentication.set_default_application', ['app_id' => $app_id_table]),
            ];
          }
          
          $application_name = $app_data['name'] ?: 'Not Set';
          if ($is_default) {
            $application_name .= ' (Default)';
          }
          
          $form['applications_section']['applications_table'][$app_id_table] = [
            'id' => ['#plain_text' => $app_data['id']],
            'application_name' => ['#plain_text' => $application_name],
            'auth_method' => ['#plain_text' => $app_data['auth_method']],
            'header_info' => ['#plain_text' => (MiniorangeApiAuthConstants::AUTH_METHOD ?: 'Not Set') . ': ' . $app_data['id']],
            'actions' => [
              '#type' => 'operations',
              '#links' => $actions,
            ],
          ];
        }
      } else {
        $form['applications_section']['no_applications'] = [
          '#markup' => $this->t('<p>No applications configured yet. Click "Add New Application" to get started.</p>'),
        ];
      }
    }
  }
  private function buildConfigurationForm(array &$form, FormStateInterface $form_state, string $form_mode, $app_id, array $config_values)
  {
  
    $form['#attached']['library'][] = 'rest_api_authentication/rest_api_authentication.main';
    if ($app_id) {
      $form['editing_app_id'] = [
        '#type' => 'hidden',
        '#value' => $app_id,
      ];
    }

    $this->buildBasicConfigurationFields($form, $config_values);
    $this->buildAuthenticationMethodSelector($form, $config_values['auth_method']);
    $this->buildAuthenticationMethodFields($form, $config_values);
    
    $form['config_form_container']['save_all_configurations'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $form_mode === 'edit' ? $this->t('Update Application') : ($form_mode === 'add' ? $this->t('Create Application') : $this->t('Save Configuration')),
      '#submit' => array('::rest_api_authentication_save_all_configurations'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#states' => [
        'disabled' => [
          [
            ':input[name="active"]' => ['value' => 3],
          ],
          'or',
          [
            ':input[name="active"]' => ['value' => 4],
          ],
          'or',
          [
            ':input[name="active"]' => ['value' => 2],
          ],
        ],
      ],
    );
  }


  private function buildBasicConfigurationFields(array &$form, array $config_values)
  {
    // Get module path for icon URL
    global $base_url;
    $module_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath("rest_api_authentication");
    $info_icon_url = $module_path . '/includes/images/icon3.png';

    $form['config_form_container']['basic_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic Configuration <span class="setup_guide_link"><a target="_blank" href="'.MiniorangeApiAuthConstants::SETUP_GUIDE_LINK.'">How To Setup?</a></span>'),
      '#collapsible' => FALSE,
      '#attributes' => ['class' => ['basic-config-fieldset']],
    ];

   $form['config_form_container']['basic_config']['form_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-row']],
    ];

    $form['config_form_container']['basic_config']['form_row']['enable_authentication_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-col', 'checkbox-with-tooltip']],
    ];

    $form['config_form_container']['basic_config']['form_row']['enable_authentication_container']['enable_authentication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Authentication <div class="ns_tooltip"><img src=":info_icon_url" alt="info icon" height="20px" width="15px"></div><div class="ns_tooltiptext">Enable this option to enforce authentication for all API requests. This acts as the master switch for API authentication.</div>', [':info_icon_url' => $info_icon_url]),
      '#default_value' => $config_values['enable_authentication'],
    ];

    $form['config_form_container']['basic_config']['form_row']['whitelist_get_apis_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-col', 'checkbox-with-tooltip']],
    ];

    $form['config_form_container']['basic_config']['form_row']['whitelist_get_apis_container']['whitelist_get_apis'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Whitelist all GET APIs <span class="premium-badge">PREMIUM</span> <div class="ns_tooltip"><img src=":info_icon_url" alt="info icon" height="20px" width="15px"></div><div class="ns_tooltiptext">Allow all GET API requests without authentication. Useful for public read-only endpoints.</div>', [':info_icon_url' => $info_icon_url]),
      '#default_value' => $config_values['whitelist_get_apis'],
      '#disabled' => TRUE,
    ];

    $form['config_form_container']['basic_config']['application_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#default_value' => $config_values['application_name'],
      '#placeholder' => $this->t('Enter the Application Name'),
      '#attributes' => ['class' => ['form-control']],
      '#required' => TRUE,
    ];

    $form['config_form_container']['basic_config']['custom_header'] = [
        '#type' => 'textfield',
        '#disabled' => 'disabled',
        '#title' => $this->t('Custom header for authentication'. ' <span class="premium-badge">PREMIUM</span>'),
        '#default_value' => \Drupal::config('rest_api_authentication.settings')->get('custom_header'),
        '#attributes' => ['class' => ['form-control']],
        '#description' => $this->t('Add your own header for authentication to make API access safer and more reliable.)'),
        '#placeholder' => $this->t('e.g., X-Custom-Auth'),
      ];
  
      $form['config_form_container']['basic_config']['token_expiry_time'] = [
        '#type' => 'textfield',
        '#disabled' => 'disabled',
        '#title' => $this->t('Token Expiry Time (In minutes)'. ' <span class="premium-badge">PREMIUM</span>'),
        '#default_value' => \Drupal::config('rest_api_authentication.settings')->get('token_expiry'),
        '#attributes' => ['class' => ['form-control']],
        '#description' => $this->t('Eligible for OAuth 2.0 and JWT Authentication. Enter the token expiry time in minutes (e.g., 60 for 1 hour, 1440 for 24 hours)'),
        '#placeholder' => $this->t('e.g., 60'),
      ];
  }

  /**
   * Build authentication method selector with card-like UI.
   */
  private function buildAuthenticationMethodSelector(array &$form, $auth_method)
  {
    $form['config_form_container']['auth_method_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication Method'),
      '#collapsible' => FALSE,
      '#attributes' => ['class' => ['auth-method-section']],
    ];

    $form['config_form_container']['auth_method_section']['description'] = [
      '#markup' => '<p>Choose your preferred authentication method:</p>',
    ];

    $form['config_form_container']['auth_method_section']['active'] = [
      '#type' => 'radios',
      '#title' => '',
      '#default_value' => $auth_method,
      '#options' => $this->getAuthMethodOptions(),
      '#attributes' => ['class' => ['auth-method-radios']],
      '#prefix' => '<div class="auth-method-cards">',
      '#suffix' => '</div>',
    ];

  }
  /**
   * Get authentication method options.
   */
  private function getAuthMethodOptions()
  {
    return [
      0 => $this->t('Basic Authentication') . '<br><small>Standard HTTP Basic Authentication using username and password.</small>',
      1 => $this->t('API Key') . '<br><small>Authenticate API requests by including the API key in the Authorization header, using either the API Key method or Basic Authentication</small>',
      2 => $this->t('OAuth/Access Token') . '<br><small>OAuth 2.0 bearer token authentication for secure API access.</small>',
      3 => $this->t('JWT') . '<br><small>Authenticate using an external JWT provided by your Identity Provider, or generate your own JWT to access the APIs.</small>',
      4 => $this->t('External Identity Provider') . '<br><small>Authenticate the access token received from your external Identity Provider.</small>',
    ];
  }

/**
   * Build authentication method specific fields.
   */
  private function buildAuthenticationMethodFields(array &$form, array $config_values)
  {
    $this->buildApiKeyFields($form, $config_values);
    $this->buildOAuthFields($form, $config_values);
    $this->buildJwtFields($form, $config_values);
    $this->buildExternalProviderFields($form, $config_values);
  }

  /**
   * Build API Key specific fields.
   */
  private function buildApiKeyFields(array &$form, array $config_values)
  {
    $form['config_form_container']['api_key_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Key Configuration'),
      '#states' => ['visible' => [':input[name="active"]' => ['value' => 1]]],
      '#attributes' => ['class' => ['auth-config-section']],
    ];


    $form['config_form_container']['api_key_config']['rest_api_auth_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Authentication Type:'),
      '#options' => [
        0 => 'Basic Authentication',
        1 => 'API Key Authentication',
      ],
      '#default_value' => $config_values['rest_api_auth_type'],
      '#attributes' => ['class' => ['form-control']],
    ];

    $form['config_form_container']['api_key_config']['api_key_note_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [
            ':input[name="active"]' => ['value' => 1],
            ':input[name="rest_api_auth_type"]' => ['value' => 1],
          ],
        ],
      ],
    ];

    $form['config_form_container']['api_key_config']['api_key_note_container_basic'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [
            ':input[name="active"]' => ['value' => 1],
            ':input[name="rest_api_auth_type"]' => ['value' => 0],
          ],
        ],
      ],
    ];

    $form['config_form_container']['api_key_config']['api_key_note_container']['api_key_note'] = [
      '#theme' => 'api_key_note',
    ];

    $form['config_form_container']['api_key_config']['api_key_note_container_basic']['basic_auth_note'] = [
      '#theme' => 'basic_auth_note',
    ];

    $form['config_form_container']['api_key_config']['api_key_row'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['api-key-row'],
        'style' => 'display: flex; align-items: center; gap: 10px;',
      ],
    ];

    $form['config_form_container']['api_key_config']['api_key_row']['api_key_display'] = [
      '#type' => 'textfield',
      '#id'  => 'rest_api_authentication_token_key',
      '#states' => ['visible' => [':input[name = "active"]' => ['value' => 1]]],
      '#disabled' => TRUE,
      '#title' => t('API Key required for Authentication:'),
      '#default_value' => \Drupal::config('rest_api_authentication.settings')->get('api_token'),
      '#attributes' => [
        'style' => 'width: 100%; font-family: monospace;',
      ],
    ];

    $form['config_form_container']['api_key_config']['api_key_row']['copy_button'] = [
      '#type' => 'button',
      '#value' => t('Copy'),
      '#attributes' => [
        'class' => ['button', 'copy-btn'],
        'onclick' => 'copyApiKey(); return false;',
        'style' => 'margin-left: 10px; margin-top: 40px;',
      ],
    ];

    $form['config_form_container']['api_key_config']['rest_api_authentication_generate_key'] = [
      '#type' => 'submit',
      '#value' => t('Generate New API Key'),
      '#states' => ['visible' => [':input[name = "active"]' => ['value' => 1]]],
      '#submit' => ['::restApiAuthenticationGenerateApiToken'],
    ];

    $form['config_form_container']['api_key_config']['username_selection'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-row']],
    ];

    $form['config_form_container']['api_key_config']['username_selection']['rest_api_authentication_key'] = [
      '#title' => $this->t('Enter Username:'.'<span class="premium-badge">PREMIUM</span>'),
      '#type' => 'entity_autocomplete',
      '#attributes' => [
        'placeholder' => 'Enter specific username to generate API Key',
        'class' => ['form-control'],
      ],
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#disabled' => TRUE,
    ];

    $form['config_form_container']['api_key_config']['username_selection']['rest_api_authentication_generate_key'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#disabled' => TRUE,
      '#attributes' => ['style' => 'margin-top:20px'],
    ];

  }

  /**
   * Build OAuth specific fields.
   */
   private function buildOAuthFields(array &$form, array $config_values) {
    $form['config_form_container']['oauth_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Configuration') . ' <span class="premium-badge">PREMIUM</span>',
      '#states' => ['visible' => [':input[name="active"]' => ['value' => 2]]],
      '#attributes' => ['class' => ['auth-config-section', 'premium-feature']],
    ];

    $form['config_form_container']['oauth_config']['premium_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['premium-notice'],
      ],
      '#value' => $this->t('<strong>Premium Feature:</strong> OAuth/Access Token authentication is available in the premium version. <a href="upgrade-plans" class="upgrade-link">Upgrade to Premium</a> to unlock this feature.'),
    ];

    $form['config_form_container']['oauth_config']['oauth_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use Access Token Generated By:'),
      '#options' => [
        'module_specific' => $this->t('Rest API Authentication'),
        'oauth_server' => $this->t('OAuth Server'),
      ],
      '#default_value' => 'module_specific',
      '#attributes' => ['class' => ['horizontal-radio']],
      '#prefix' => '<div class="oauth-type-selection">',
      '#suffix' => '</div>',
    //  '#disabled' => TRUE,
    ];
  
    $form['config_form_container']['oauth_config']['module_specific_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="oauth_type"]' => ['value' => 'module_specific'],
        ],
      ],
    ];
  
    $form['config_form_container']['oauth_config']['module_specific_container']['oauth_fields'] = [
      '#type' => 'container',
    ];
  
    $form['config_form_container']['oauth_config']['module_specific_container']['oauth_fields']['rest_api_authentication_oauth_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#disabled' => TRUE,
      '#wrapper_attributes' => ['class' => ['form-col']],
    ];
  
    $form['config_form_container']['oauth_config']['module_specific_container']['oauth_fields']['rest_api_authentication_oauth_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#disabled' => TRUE,
      '#wrapper_attributes' => ['class' => ['form-col']],
    ];
  
    $form['config_form_container']['oauth_config']['module_specific_container']['rest_api_authentication_generate_and_secret'] = [
      '#type' => 'submit',
      '#value' => t('Generate a new Client ID and Secret'),
      '#attributes' => ['class' => ['button']],
      '#disabled' => TRUE,
    ];
  
    $form['config_form_container']['oauth_config']['oauth_server_note'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="oauth_type"]' => ['value' => 'oauth_server'],
        ],
      ],
      '#attributes' => ['class' => ['oauth-server-note']],
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['custom-warning'],
        ],
        '#value' => $this->t('OAuth Server module is not installed. Please install it to use this option.'),
      ],
    ];
  }
  

  /**
   * Build JWT specific fields.
   */
  private function buildJwtFields(array &$form, array $config_values)
  {
    $form['config_form_container']['jwt_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JWT Configuration') . ' <span class="premium-badge">PREMIUM</span>',
      '#states' => ['visible' => [':input[name="active"]' => ['value' => 3]]],
      '#attributes' => ['class' => ['auth-config-section', 'premium-feature']],
    ];

    $form['config_form_container']['jwt_config']['premium_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['premium-notice'],
      ],
      '#value' => $this->t('<strong>Premium Feature:</strong> JWT authentication is available in the premium version. <a href="upgrade-plans" class="upgrade-link">Upgrade to Premium</a> to unlock this feature.'),
    ];

    $dropdown_options = [
      'RS256' => $this->t('RS256'),
      'HS256' => $this->t('HS256'),
    ];

    $form['config_form_container']['jwt_config']['jwt_basic_config'] = [
      '#type' => 'container',
    ];

    $form['config_form_container']['jwt_config']['jwt_basic_config']['jwt_method_username_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username Attribute:'),
      '#default_value' => '',
      '#attributes' => [
        'class' => ['form-control'],
        'style' => 'width:49%',
      ],
      '#description' => t('Enter the attribute name from the JWT payload (issued by your IdP) that contains the username.'),
      '#wrapper_attributes' => ['class' => ['jwt-username-field']],
      '#disabled' => TRUE,
      '#required' => TRUE,
    ];

    $form['config_form_container']['jwt_config']['jwt_basic_config']['algorithm_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Signing Algorithm:'),
      '#options' => $dropdown_options,
      '#default_value' => 'RS256',
      '#attributes' => [
        'id' => 'algo',
        'style' => 'width:49%',
      ],
      '#wrapper_attributes' => ['class' => ['jwt-algorithm-field']],
    
    ];

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['jwt-fieldsets-container']],
      '#states' => [
        'visible' => [
          ':input[name="active"]' => ['value' => 3],
        ],
      ],
    ];

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['jwks_fieldset'] = array(
      '#type' => 'details',
      '#title' => t('For External JWT'),
      '#attributes' => ['class' => ['jwt-external-fieldset']],
    );


    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['jwks_fieldset']['external_jwt_note'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['jwt-note', 'jwt-external-note']],
      '#markup' => '<div class="custom-warning"><strong>Note:</strong> Enter the below fields only when you get the<strong>JWT</strong>token from any external IDP not from our module.</div>',
    ];

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['jwks_fieldset']['jwt_method_jwks_uri'] = array(
      '#type' => 'url',
      '#id' => 'rest_api_authentication_token_key',
      '#states' => array(
        'visible' => array(
          ':input[name="active"]' => array('value' => '3'),
          ':input[id="algo"]' => array('value' => 'RS256'),
        ),
      ),
      '#title' => $this->t('JWKS URI: <i>(Optional)</i>'),
      '#default_value' => '',
      '#attributes' => array('style' => 'width:100%'),
      '#disabled' => TRUE,
    );

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['jwks_fieldset']['or_option'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'text-align: center;'
      ],
      '#markup' => '<div><b>OR</b></div>',
      '#states' => [
        'visible' => [
          ':input[name="active"]' => ['value' => '3'],
          ':input[id="algo"]' => array('value' => 'RS256'),
        ],
      ],
    ];
    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['jwks_fieldset']['certificate_or_secret_key'] = array(
      '#type' => 'textarea',
      '#default_value' => '',
      '#states' => array(
        'visible' => array(
          ':input[name = "active"]' => array('value' => 3),
        ),
      ),
      '#title' => $this->t('Certificate/Secret Key: <i>(Optional)</i>'),
      '#attributes' => array('style' => 'width:100%'),
      '#description' => t('<b>Note:</b> Give certificate when RS256 is selected and secret key for HS256 '),
      '#disabled' => TRUE,
    );

    
    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['custom_generate'] = array(
      '#type' => 'details',
      '#title' => t('Generate Custom Keys'),
      '#attributes' => ['class' => ['jwt-custom-fieldset']],
    );

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['custom_generate']['custom_generate_note'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['jwt-note', 'jwt-custom-note']],
      '#markup' => '<div class="custom-warning"><strong>Note:</strong>Click the <b>Generate Keys</b> button to create keys automatically, or enter your own keys in the fields below</div>',
    ];

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['custom_generate']['rest_api_authentication_private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Private Key:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#placeholder' => t('Enter the private key for the JWT or generate keys'),
      '#disabled' => TRUE,
    ];
    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['custom_generate']['rest_api_authentication_public_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Public Key:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#placeholder' => t('Enter the public key for the JWT or generate keys'),
      '#disabled' => TRUE,
    ];

    $form['config_form_container']['jwt_config']['jwt_fieldsets_container']['custom_generate']['rest_api_authentication_generate_keys'] = [
      '#type' => 'submit',
      '#value' => t('Generate Keys'),
      '#attributes' => ['class' => ['button']],
      '#disabled' => TRUE,
    ];
  }

  /**
   * Build External Identity Provider specific fields.
   */
  private function buildExternalProviderFields(array &$form, array $config_values)
  {
    $form['config_form_container']['external_provider_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('External Identity Provider Configuration') . ' <span class="premium-badge">PREMIUM</span>',
      '#states' => ['visible' => [':input[name="active"]' => ['value' => 4]]],
      '#attributes' => ['class' => ['auth-config-section', 'premium-feature']],
    ];

    $form['config_form_container']['external_provider_config']['premium_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['premium-notice'],
      ],
      '#value' => $this->t('<strong>Premium Feature:</strong> External Identity Provider authentication is available in the premium version. <a href="upgrade-plans" class="upgrade-link">Upgrade to Premium</a> to unlock this feature.'),
    ];

    $form['config_form_container']['external_provider_config']['rest_api_authentication_ext_oauth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Info Endpoint:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#description' => 'Enter the user info endpoint of your Identity Provider so the module can fetch the user\'s information using the provided token.',
      '#disabled' => TRUE,
    ];

    $form['config_form_container']['external_provider_config']['rest_api_authentication_ext_oauth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username Attribute:'),
      '#default_value' => '',
      '#attributes' => ['class' => ['form-control']],
      '#description' => 'Enter the attribute name from your Identity Provider’s response that contains the user’s username.',
      '#disabled' => TRUE,
    ];
  }

  public function restApiAuthenticationGenerateApiToken(array &$form, FormStateInterface $form_state) {
    
    $api_key = Utilities::generateRandom(64);

    $this->configFactory->getEditable('rest_api_authentication.settings')
      ->set('api_token', $api_key)
      ->save();

    $this->messenger->addMessage($this->t('New API Key generated successfully.'));
    
    $query_params = ['tab' => 'edit-api-auth'];
    if ($app_id = $form_state->get('editing_app_id')) {
      $query_params['app_id'] = $app_id;
    }
    if ($auth_method = $form_state->getValue('active')) {
      $query_params['auth_method'] = $auth_method;
    }
    if ($app_name = $form_state->getValue('application_name')) {
      $query_params['app_name'] = $app_name;
    }
    
    $form_state->setRedirect('rest_api_authentication.auth_settings', [], [
      'query' => $query_params,
    ]);
  }
  function rest_api_authentication_save_all_configurations(array &$form, FormStateInterface $form_state)
  {
    global $base_url;
    $form_values = $form_state->getValues();
    $authentication_method = $form_values['active'];

    $application_name = $form_values['application_name'];
    $enable_authentication = $form_values['enable_authentication'];
    

    $editing_app_id = $form_values['editing_app_id'] ?? $form_state->get('editing_app_id') ?? null;

    $config = \Drupal::configFactory()->getEditable('rest_api_authentication.settings');
    $applications = $config->get('applications') ?: [];

    $auth_method_names = [
      0 => 'Basic Authentication',
      1 => 'API Key',
    ];

    $auth_method_name = isset($auth_method_names[$authentication_method]) ? $auth_method_names[$authentication_method] : 'Not Selected';

    $header_info = $this->getHeaderInfoFromAppData($authentication_method, $form_values);

    $app_data = [
      'name' => $application_name,
      'auth_method' => $auth_method_name,
      'authentication_method' => $authentication_method,
      'header_info' => $header_info,
      'enable_authentication' => $enable_authentication,
    ];

    switch ($authentication_method) {
      case 1:
        $app_data['rest_api_auth_type'] = $form_values['rest_api_auth_type'];
        break;
    }
    
    if ($editing_app_id && isset($applications[$editing_app_id])) { 
      $app_data['id'] = $editing_app_id;
      $app_data['is_default'] = $applications[$editing_app_id]['is_default'] ?? FALSE;
      $applications[$editing_app_id] = $app_data;
      \Drupal::logger('rest_api_authentication')->notice('Updating existing application with ID: @id', ['@id' => $editing_app_id]);
      $message = $this->t('Application updated successfully.');
    } else {
      $uuid_service = \Drupal::service('uuid');
      $app_id = substr($uuid_service->generate(), 0, 8);
      $app_data['id'] = $app_id;
      $app_data['is_default'] = FALSE; 
      $applications[$app_id] = $app_data;
      \Drupal::logger('rest_api_authentication')->notice('Creating new application with ID: @id', ['@id' => $app_id]);
      $message = $this->t('New application created successfully.');
    }

    \Drupal::logger('rest_api_authentication')->notice('Saving app data: @data', ['@data' => print_r($app_data, TRUE)]);

    $config->set('application_name', $application_name)
           ->set('enable_authentication', $enable_authentication)
           ->set('authentication_method', $authentication_method)
           ->set('applications', $applications);

    switch ($authentication_method) {
      case 1: 
        $config->set('rest_api_auth_type', $form_values['rest_api_auth_type']);
        break;
    }

    $config->save();

    switch ($authentication_method) {
      case 0:
        $message .= ' ' . $this->t('Basic Authentication configuration saved.');
        break;

      case 1:
        $message .= ' ' . $this->t('API Key configuration saved.');
        break;

      default:
        $this->messenger->addError($this->t('Invalid authentication method selected.'));
        break;
    }

    $this->messenger->addMessage($message);

    $form_state->set('form_mode', 'list');
    $form_state->setRedirect('rest_api_authentication.auth_settings', [], ['query' => ['tab' => 'edit-api-auth']]);
  }

  private function getHeaderInfoFromAppData($authentication_method, $form_values)
  {
    switch ($authentication_method) {
      case 0: 
        return 'Authorization: Basic {base64_encoded_credentials}';

      case 1: 
        $auth_type = $form_values['rest_api_auth_type'] ?? 0;
        if ($auth_type == 1) {
          return 'X-API-Key: {your_api_key}';
        } else {
          return 'Authorization: Basic {base64_encoded_credentials}';
        }

      default:
        return 'No authentication method selected';
    }
  }
  private function getHeaderInfo($authentication_method, $config)
  {
    switch ($authentication_method) {
      case 0: 
        return 'Authorization: Basic {base64_encoded_credentials}';

      case 1: 
        $auth_type = $config->get('rest_api_auth_type');
        if ($auth_type == 1) {
          return 'X-API-Key: {your_api_key}';
        } else {
          return 'Authorization: Basic {base64_encoded_credentials}';
        }

      default:
        return 'No authentication method selected';
    }
  }
  /**
   * Submit handler to show add form.
   */
  function showAddForm(array &$form, FormStateInterface $form_state)
  {
    $form_state->set('form_mode', 'add');
    $form_state->setRebuild(TRUE);
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
