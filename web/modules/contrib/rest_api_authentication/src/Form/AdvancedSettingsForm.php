<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rest_api_authentication\AdvancedSettingsForm as AdvancedSettingsFormClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest_api_authentication\MiniorangeApiAuthConstants;
use Drupal\rest_api_authentication\AjaxTables;
use Drupal\user\Entity\Role;

/**
 * Provides a form for Advanced Settings configuration.
 */
class AdvancedSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_api_authentication_advanced_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['markup_library_1'] = [
      '#attached' => [
        'library' => [
          "rest_api_authentication/rest_api_authentication.basic_style_settings",
        ],
      ],
    ];

    $form['main_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['advanced-settings-container'],
      ],
    ];

    $form['main_container']['advanced_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced Settings Configuration <span class="setup_guide_link"><a target="_blank" href="'.MiniorangeApiAuthConstants::FEATURE_GUIDE_LINK.'">How To Setup?</a></span>'),
    ];

    $config = \Drupal::config('rest_api_authentication.settings');
    
    $form['main_container']['advanced_settings_fieldset']['premium_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['premium-notice'],
      ],
      '#value' => t('<strong>Premium Feature:</strong> These features are available in the premium version. <a href="upgrade-plans" class="upgrade-link">Upgrade to Premium</a> to unlock this feature.'),
    ];
    
    $form['main_container']['advanced_settings_fieldset']['advancedsettings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'custom_headers',
    ];

    $form['main_container']['advanced_settings_fieldset']['restrict_roles'] = [
      '#type' => 'details',
      '#title' => t('Role Based Access'),
      '#group' => 'advancedsettings',
      '#description' => $this->t('<strong>Note:</strong> This setting allows you to restrict access to specific API routes based on user roles in Drupal.
                 <ul>
                    <li><strong>Access Control:</strong> Only the roles explicitly listed here will be allowed access. All other roles will be denied by default.</li>
                    <li><strong>API Path Format:</strong> Use relative API paths (e.g., <code>/api/data</code>), and list each route on a separate line.</li>
                    <li><strong>Wildcard Support:</strong> Use <code>*</code> to apply the rule to all routes under a specific path (e.g., <code>/api/*</code> to cover all APIs under <code>/api/</code>).</li>
                 </ul>'),
    ];
  
    $m_role =  Role::loadMultiple();
    $role_names = [];
    foreach ($m_role as $role) {
      $role_names[$role->id()] = $role->label();
    }
    $anonymous_role = array_search('Anonymous user', $role_names);
    if ($anonymous_role !== false) {
      unset($role_names[$anonymous_role]);
    }
    $map_value = \Drupal::config('rest_api_authentication.settings')->get('rolebased_restrict_map_array');
    $rolebased_mapping_rows = is_string($map_value) ? json_decode($map_value, TRUE) : ($map_value ?: self::emptyRow('role-based-table'));
    $role_names_array = [1 => $role_names];
    $role_based_fields = self::RoleBasedFields();
    $role_based_header = self::RoleBasedHeader();
    $custom_unique_id_array = AjaxTables::getUniqueID($form_state->get('role-based-table-id-array'), $rolebased_mapping_rows);
    $form_state->set('role-based-table-id-array', $custom_unique_id_array);
    $form['main_container']['advanced_settings_fieldset']['restrict_roles']['mo_restapi_role_based_table'] = AjaxTables::generateTables('role-based-table', $role_based_fields, $custom_unique_id_array, $rolebased_mapping_rows, $role_based_header, $role_names_array);
    $form['main_container']['advanced_settings_fieldset']['restrict_roles']['mo_restapi_role_based_add_more'] = AjaxTables::generateAddButton('Add', '::addRowNew', '::ajaxCallback', 'role-based-table','',TRUE);

    
    $form['main_container']['advanced_settings_fieldset']['restrict_roles']['token_submit_key3'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#value' => t('Save Role Based Restrictions'),
      '#submit' => ['::role_restriction_submit'],
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_apis'] = [
      '#type' => 'details',
      '#title' => t('Restrict custom APIs'),
      '#description' => t('<b>Note: </b>To enable this feature, please check the <b>Any Other/Custom APIs</b> checkbox under the <b>APIs to be Restricted</b> details.<br>'),
      '#group' => 'advancedsettings',
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_apis']['custom_api_textarea'] = [
      '#type' => 'textarea',
      '#disabled' => true,
      '#default_value' => $config->get('list_of_apis'),
      '#title' => t('You can add the APIs here:'),
      '#attributes' => [
        'style' => 'width:100%',
        'placeholder' => 'You can also add multiple APIs, each on a new line.',
      ],
      '#description' => t("Specify page paths one per line and use <b>'*'</b> as a wildcard. For example, to restrict access to all routes under <b>'/abc'</b>, use the wildcard url <b>'/abc/*'</b>."),
    ];

    $form['main_container']['advanced_settings_fieldset']['list_apis']['token_submit_key3'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#name' => 'submit',
      '#value' => t('Save Configuration'),
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_ip'] = [
      '#type' => 'details',
      '#title' => t('Allow/Restrict Ip Addresses'),
      '#group' => 'advancedsettings',
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_ip']['settings'] = [
      '#type' => 'radios',
      '#disabled' => true,
      '#default_value' => $config->get('ip_access_type'),
      '#title' => '',
      '#options' => [
        0 => t('Allowed IP Addresses'),
        1 => t('Blocked IP Addresses'),
      ],
      '#attributes' => ['class' => ['container-inline']],
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_ip']['ip_textarea'] = [
      '#type' => 'textarea',
      '#disabled' => true,
      '#default_value' => $config->get('list_of_ips'),
      '#title' => t('You can add the IP Addresses here:'),
      '#attributes' => [
        'style' => 'width:100%',
        'placeholder' => 'You can also add multiple APIs using a ; as a seperator',
      ],
    ];
    
    $form['main_container']['advanced_settings_fieldset']['list_ip']['token_submit_key3'] = [
      '#type' => 'submit',
      '#disabled' => true,
      '#button_type' => 'primary',
      '#name' => 'submit',
      '#value' => t('Save IP Configuration'),
      '#submit' => ['::ip_restriction_submit'],
    ];

    $form['main_container']['advanced_settings_fieldset']['api_auth'] = [
      '#type' => 'details',
      '#title' => t('APIs to be Restricted'),
      '#group' => 'advancedsettings',
    ];

    $form['main_container']['advanced_settings_fieldset']['api_auth']['enable_JSON_apis'] = [
      '#type' => 'checkbox',
      '#default_value' => true,
      '#title' => '<b><a href="' . MiniorangeApiAuthConstants::DRUPAL_SITE . '/docs/core-modules-and-themes/core-modules/jsonapi-module" target="_blank">JSON:API module APIs</a></b> (Always specify the <b><u>/jsonapi/</u></b> in the API, e.g. http://example.com/jsonapi/node/article/{{article_uuid}})',
      '#disabled' => true,
    ];
    
    $form['main_container']['advanced_settings_fieldset']['api_auth']['enable_REST_apis'] = [
      '#type' => 'checkbox',
      '#default_value' => true,
      '#title' => '<b><a target="_blank" href="' . MiniorangeApiAuthConstants::DRUPAL_SITE . '/docs/8/core/modules/rest">RESTful Web Services APIs</a></b> (Always specify the <b>?_format=</b> query argument, e.g. http://example.com/node/1?_format=json)',
      '#disabled' => true,
    ];
    
    $form['main_container']['advanced_settings_fieldset']['api_auth']['head_graphql_options'] = [
      '#type' => 'checkbox',
      '#title' => '<b><a target="_blank" href="' . MiniorangeApiAuthConstants::DRUPAL_SITE . '/docs/contributed-modules/graphql">GraphQL APIs</a></b>',
      '#default_value' => 0,
      '#disabled' => true,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['main_container']['advanced_settings_fieldset']['api_auth']['head_customapi_options'] = [
      '#type' => 'checkbox',
      '#title' => 'Other/ Drupal Views/ Custom APIs Authentication',
      '#default_value' => false,
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['api_auth']['head_customapi_options_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#name' => 'submit',
      '#value' => t('Save Configuration'),
    ];

   

    $form['main_container']['advanced_settings_fieldset']['flood_control'] = [
      '#type' => 'details',
      '#title' => t('Flood Control'),
      '#group' => 'advancedsettings',
    ];
    
    $form['main_container']['advanced_settings_fieldset']['flood_control']['limit'] = [
      '#type' => 'number',
      '#title' => t('Maximum failed attempts'),
      '#default_value' => $config->get('flood_control_limit') ?? 5,
      '#min' => 1,
      '#description' => t('Maximum number of failed attempts before blocking an IP address.'),
      '#disabled' => true,
    ];
    
    $form['main_container']['advanced_settings_fieldset']['flood_control']['window'] = [
      '#type' => 'number',
      '#title' => t('Time window (seconds)'),
      '#default_value' => $config->get('flood_control_window') ?? 900,
      '#min' => 60,
      '#description' => t('Time window in seconds for tracking failed attempts.'),
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['flood_control']['flood_control_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#name' => 'submit',
      '#value' => t('Save Configuration'),
    ];

    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $form['main_container']['advanced_settings_fieldset']['token_revocation'] = [
      '#type' => 'details',
      '#title' => t('Token Revocation'),
      '#group' => 'advancedsettings',
    ];

    $form['main_container']['advanced_settings_fieldset']['token_revocation']['token_revocation_info'] = [
      '#theme' => 'token_revocation_info',
      '#endpoint' => $base_url . '/rest_api/revoke',
    ];

    $form['main_container']['advanced_settings_fieldset']['refresh_token_settings'] = 
    ['#type' => 'details',
      '#title' => t('Refresh Token Configuration'),
      '#open' => true,
      '#group' => 'advancedsettings',
    ];

    $form['main_container']['advanced_settings_fieldset']['refresh_token_settings']['enable_refresh_token'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Refresh Token Support'),
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['refresh_token_settings']['refresh_token_lifetime'] = [
      '#type' => 'number',
      '#title' => t('Refresh Token Lifetime (in seconds)'),
      '#default_value' => 900,
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['refresh_token_settings']['refresh_token_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#name' => 'submit',
      '#value' => t('Save Configuration'),
    ];

    $form['main_container']['advanced_settings_fieldset']['scope_settings'] = [
      '#type' => 'details',
      '#title' => t('Scope-Based Access Control'),
      '#open' => true,
      '#group' => 'advancedsettings',
    ];
    
    $form['main_container']['advanced_settings_fieldset']['scope_settings']['available_scopes'] = [
      '#type' => 'textarea',
      '#title' => t('Available Scopes'),
      '#description' => t('Define all available scopes, one per line. Example: read:user, write:post'),
      '#default_value' => implode("\n", ['read:user', 'write:post']),
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['scope_settings']['default_scopes'] = [
      '#type' => 'checkboxes',
      '#title' => t('Default Scopes for New Tokens'),
      '#options' => array_combine(['read:user', 'write:post'], ['read:user', 'write:post']),
      '#default_value' => ['read:user', 'write:post'],
      '#disabled' => true,
    ];

    $form['main_container']['advanced_settings_fieldset']['scope_settings']['scope_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#disabled' => true,
      '#name' => 'submit',
      '#value' => t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
  public function RoleBasedFields()
  {

    return [
      'user_role' => [
        'type' => 'select',        
      ],

      'user_apis' => [
        'type' => 'textarea',
        'rows' =>1,
        'attributes' => [
          'placeholder' => 'Enter the APIs each in new line'
        ],
        'element_validate' => ['::validateCustomApi'],
      ],

      'delete_button_role' => [
        'type' => 'submit',
        'submit' => '::removeRow',
        'callback' => '::ajaxCallback',
        'wrapper' => 'role-based-table',
        'disabled' => TRUE,
      ],
    ];
  }
  public function RoleBasedHeader()
  {
    return [
      ['data' => t('Drupal Role')],
      ['data' => t('Enter the APIs to be Authenticated')],
      ['data' => t('Operation')],
    ];
  }
  public function ajaxCallback(array &$form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_id = $triggering_element['#ajax']['wrapper'];
    
    switch ($wrapper_id) {
      case 'role-based-table':
        return $form['restrict_roles']['mo_restapi_role_based_table'];
        break;
    }
  }
  public function removeRow(array &$form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $id = $triggering_element['#name'];
    $wrapper_id = $triggering_element['#ajax']['wrapper'] ?? 'role-based-table';
    
    $var_value = $wrapper_id . '-id-array';

    $unique_id = $form_state->get($var_value);
    $unique_id = array_diff($unique_id, [$id]);

    if (empty($unique_id)) {
      $uuid_service = \Drupal::service('uuid');
      $unique_id[0] = $uuid_service->generate();
    }

    $form_state->set($var_value, $unique_id);
    $form_state->setRebuild();
  }
  public function addRowNew(array &$form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $add_button = $triggering_element['#name'];
    $wrapper_id = $triggering_element['#ajax']['wrapper'] ?? 'role-based-table';

    $rows = $form_state->getValue('total_rows_' . $add_button);
    $unique_array_id = $form_state->get($wrapper_id . '-id-array');
    
    for ($count = 1; $count <= $rows; $count++) {
      $uuid_service = \Drupal::service('uuid');
      $uuid = $uuid_service->generate();
      $unique_array_id[] = $uuid;
    }

    $form_state->set($wrapper_id . '-id-array', $unique_array_id);
    $form_state->setRebuild();
  }
  public static function emptyRow($table_name) {
    switch ($table_name) {
      case 'role-based-table' :
        return json_decode('{"first_row_custom_attribute":{"user_role":"","user_apis":"","delete_button_role":"Remove"}}', TRUE);
        break;
    }
  }

} 