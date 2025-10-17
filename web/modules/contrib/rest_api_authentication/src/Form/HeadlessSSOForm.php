<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rest_api_authentication\MiniorangeApiAuthConstants;
use Drupal\rest_api_authentication\Utilities;
use Drupal\Core\Render\Markup;

/**
 * Provides a form for Headless SSO configuration.
 */
class HeadlessSSOForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_api_authentication_headless_sso';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   
    $form['markup_library_1'] = [
      '#attached' => [
        'library' => [
          "rest_api_authentication/rest_api_authentication.style_settings",
          "rest_api_authentication/rest_api_authentication.basic_style_settings",
        ],
      ],
    ];

    $form['headless_sso_details'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#group' => 'verticaltabs',
    ];

    $this->headlessSsoFieldset($form, $form_state);    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Headless SSO settings saved successfully.'));
  }

  /**
   * Defines the form elements for the Headless SSO fieldset.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function headlessSsoFieldset(array &$form, FormStateInterface $form_state) {
    
    $form['headless_sso_details']['premium_notice'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['premium-notice'],
      ],
      '#value' => t('<strong>Premium Feature:</strong> Headless SSO (Single Sign On) is available in the premium version. <a href="upgrade-plans" class="upgrade-link">Upgrade to Premium</a> to unlock this feature.'),
    ];

    $form['headless_sso_details']['headless_sso'] = [
      '#markup' => t('<b>Headless SSO (Single Sign On) </b> <span class="premium-badge">PREMIUM</span><a style="float: right;" href=":guideUrl" target="_blank" class="button button--small" >setup guide</a>',
        [
          ':guideUrl' => MiniorangeApiAuthConstants::HEADLESS_SSO_GUIDE_LINK,
        ]),
        '#prefix' => '<hr>',
    ];

    $form['headless_sso_details']['headless_sso']['sso_protocol'] = [
      '#prefix' => t('<p  style="font-size: small"> This section help you to setup the headless sso with the help of the <a href=":oauthClient" target="_blank">Drupal OAuth Client</a> or <a href=":saml" target="_blank">miniOrange SAML</a>.</p>',
        [
          ':oauthClient' => MiniorangeApiAuthConstants::OAUTH_CLIENT_MODULE_LINK,
          ':saml' => MiniorangeApiAuthConstants::SAML_SP_MODULE_LINK,
        ]),
    ];

    self::addHeadlessSsoFields($form, $form_state);

    $form['headless_sso_details']['headless_sso']['save_button'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => 'Save Settings',
      '#disabled' => TRUE,
    ];
  }

  /**
   * Adds the Headless SSO configuration fields to the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function addHeadlessSsoFields(array &$form, FormStateInterface $form_state) {
    
    $base_url = Utilities::getBaseUrl();

    $form['headless_sso_details']['headless_sso']['headless_sso_module'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Module'),
      '#options' => [
        0 => $this->t('OAuth Client'),
        1 => $this->t('SAML SP'),
      ],
      '#attributes' => ['class' => ['container-inline']],
      '#disabled' => TRUE,
    ];

    $form['headless_sso_details']['headless_sso']['headless_sso_frontend_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend redirect URL'),
      '#description' => $this->t('After SSO, users will be redirected to this URL with a one-time use code appended as a query parameter.'),
      '#attributes' => ['style' => 'width:50%'],
      '#disabled' => TRUE,
    ];

    
    $form['headless_sso_details']['headless_sso']['headless_sso_get_token_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID Token Endpoint'),
      '#default_value' => $base_url . 'get-token',
      '#disabled' => TRUE,
      '#description' => $this->t('This endpoint validates the one-time code and returns a JWT for the authenticated user.'),
    ];

    $form['headless_sso_details']['headless_sso']['headless_sso_send_jwt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select JWT type'),
      '#options' => [
        0 => $this->t('Send JWT created by the module'),
        1 => $this->t('Send JWT received from the OAuth Server'),
      ],
      '#attributes' => ['class' => ['container-inline']],
      '#disabled' => TRUE,
    ];
  }

} 