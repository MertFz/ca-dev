<?php

namespace Drupal\rest_api_authentication\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

class UpgradePlansForm extends FormBase {
    public function getFormId() {
        return 'rest_api_authentication_upgrade_plans_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'rest_api_authentication/rest_api_authentication.basic_style_settings';

        global $base_url;
        $module_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath("rest_api_authentication");
        $module_data = \Drupal::service('extension.list.module')->getExtensionInfo('rest_api_authentication');
    
    $features = [
      [
          'title' => 'Supports JSON & REST APIs',
          'description' => 'Enables secure authentication for both JSON:API and REST API endpoints',
      ],
      [
          'title' => '3rd Party/External IdP Token Based Authentication',
          'description' => 'Allows authentication using tokens issued by third-party or external Identity Providers (IdPs).',
      ],
      [
          'title' => 'Supports restriction of custom APIs',
          'description' => 'Enables access restriction and authentication for your custom API endpoints.',
      ],
      [
          'title' => 'Role Based Access',
          'description' => 'Control API access based on user roles. Each user can access APIs according to the role assigned to them',
      ],
      [
          'title' => 'JWT Based Authentication',
          'description' => 'Authenticate API requests using JSON Web Tokens (JWT). A valid JWT, issued after verifying user credentials, is required to access Drupal APIs until it expires.',
      ],
      [
          'title' => 'Authenticate private files and images',
          'description' => 'Secures and authenticates access to private files and images via API.',
      ],

    ];

    

    $related_products = [
        [
            'title' => 'SAML Single Sign-On',
            'description' => 'Engineered for critical infrastructures of Governments, Educational Entities, Healthcare, the Drupal SAML SSO module based on the SAML 2.0 protocol connects to all IDPs prevalent in high security settings like Okta, Azure, miniOrange and even Federal IDPs like Login.gov.',
            'image' => 'saml.webp',
            'link' => 'https://plugins.miniorange.com/drupal-saml-single-sign-on-sso'
        ],
        [
            'title' => 'Drupal OAuth/OIDC Client - SSO',
            'description' => 'The Drupal OAuth/OIDC Client module permits users to perform Single Sign-On (SSO) to your Drupal app via any Identity Providers (IdPs) such as Salesforce, Okta, Azure AD, and more.',
            'image' => 'oauth.webp',
            'link' => 'https://plugins.miniorange.com/drupal-sso-oauth-openid-single-sign-on'
        ],
       [
          'title' => 'User Provisioning & Sync (SCIM)',
          'description' => 'The Drupal User Provisioning and Sync module automates the creation, updating, and removal of user accounts across systems. It seamlessly syncs user roles and groups between the identity provider and your Drupal site, ensuring consistent access control and efficient user management.',
          'image' => 'UserProvisioning.webp',
          'link' => 'https://plugins.miniorange.com/drupal-user-provisioning-and-sync'
      ],
      [
          'title' => 'Two Factor Authentication',
          'description' => 'Second-Factor Authentication (TFA) adds a second layer of security with an option to configure truly Passwordless Login. You can configure the module to send an OTP to your preferred mode of communication like phone/email, integrate with TOTP Apps like Google Authenticator or configure hardware token 2FA method.',
          'image' => '2FA.webp',
          'link' => 'https://plugins.miniorange.com/drupal-two-factor-authentication-2fa'
      ],
      
      [
          'title' => 'Session Management',
          'description' => 'User Session Management module helps you to manage the Drupal user session-related operations. It efficiently handles user sessions and provides you with multiple features like terminating any user session from the admin section, auto-logout user on being idle for the configured amount of time, limiting the number of simultaneous sessions per user, IP-based login restrictions, and many more.',
          'image' => 'Session-Management.webp',
          'link' => 'https://plugins.miniorange.com/drupal-session-management'
      ],
      [
          'title' => 'Website Security Pro',
          'description' => 'The Website Security Pro module safeguards your Drupal site with enterprise-grade security. It protects against brute force and DDoS attacks, enforces strong passwords, monitors and blacklists suspicious IPs, and secures login and registration forms. Designed to block hackers and malware, it ensures your site stays secure, stable, and reliable.',
          'image' => 'Web-Security.webp',
          'link' => 'https://plugins.miniorange.com/drupal-web-security-pro',
      ],
      ];

    
        $form['current_plan_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['current-plan-section']
            ],
        ];
        $form['current_plan_section']['plan_info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['plan-info']],
        ];

        $form['current_plan_section']['plan_info']['plan_type'] = [
            '#type' => 'markup',
            '#markup' => 'Current Plan: Free Version',
        ];

        $form['current_plan_section']['plan_info']['module_info'] = [
            '#type' => 'markup',
            '#markup' => '<h3>REST API Authentication</h3>',
            '#attributes' => ['class' => ['module-info']],
        ];
        
        $form['current_plan_section']['plan_info']['version_info'] = [
            '#type' => 'markup',
            '#markup' => 'Version: ' .$module_data['version'], 
        ];

        $form['current_plan_section']['upgrade_button'] = [
            '#type' => 'link',
            '#title' => t('Upgrade Plan'),
            '#url' => \Drupal\Core\Url::fromUri('https://plugins.miniorange.com/drupal-rest-api-authentication'),
            '#attributes' => [
                'class' => ['button', 'button--primary'],
                'target' => '_blank',
            ],
        ];

        $form['premium_features_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['section-container'],
            ],
        ];

        $form['premium_features_section']['title'] = [
            '#type' => 'markup',
            '#markup' => '<h3>Premium Features</h3>',
            '#prefix' => '<div class="section-title">',
        ];

        $form['premium_features_section']['upgrade-button'] = [
            '#type' => 'link',
            '#title' => t('View All Features'),
            '#url' => \Drupal\Core\Url::fromUri('https://plugins.miniorange.com/drupal-rest-api-authentication'),
            '#attributes' => [
                'class' => ['button', 'button--primary'],
                'target' => '_blank',
            ],
            '#suffix' => '</div>',
        ];

        $form['premium_features_section']['features_grid'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['features-grid'],
            ],
        ];

        foreach ($features as $index => $feature) {
            $form['premium_features_section']['features_grid']['feature_' . $index] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['feature-box']
                ],
            ];

            $form['premium_features_section']['features_grid']['feature_' . $index]['content'] = [
                '#type' => 'markup',
                '#markup' => '<h5>' . $feature['title'] . '</h5>' . $feature['description'],
            ];
        }

        
    $rows = [
        [
          Markup::create(t('<b>1.</b> Click on <a href="https://plugins.miniorange.com/drupal-rest-api-authentication" target="_blank">Upgrade</a> Now button for required licensed plan and you will be redirected to miniOrange login console.</li>')),
          Markup::create(t('<b>5.</b> Uninstall and remove the free version of the module from the Drupal module directory')),
        ],
        [
          Markup::create(t('<b>2.</b> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.')),
          Markup::create(t('<b>6.</b> Now install the downloaded licensed version of the module.')),
        ],
        [
          Markup::create(t('<b>3.</b> Enter your card details and proceed for payment. On successful payment completion, the licensed version module(s) will be available to download.')),
          Markup::create(t('<b>7.</b> Clear Drupal Cache from <a href="@base_url" target="_blank">here</a>.', ['@base_url' => $base_url . '/admin/config/development/performance'])),
        ],
        [
          Markup::create(t('<b>4.</b> Download the licensed module(s) from Module <a href="https://portal.miniorange.com/downloads" target="_blank">Releases and Downloads</a> section.')),
          Markup::create(t('<b>8.</b> After enabling the licensed version of the module, login using the account you have registered with us.')),
        ],
      ];
  
      $form['markup_how to upgrade'] = [
        '#markup' => '<h3 class="rest-text-center">How to Upgrade to Licensed Version Module</h3>',
      ];
  
      $form['miniorange_how_to_upgrade_table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#rows' => $rows,
        '#attributes' => ['style' => 'border:groove', 'class' => ['mo_how_to_upgrade']],
      ];
        $form['related_products_section'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['section-container'],
            ],
        ];

        $form['related_products_section']['title'] = [
            '#type' => 'markup',
            '#markup' => '<h3>Related Products</h3>',
            '#prefix' => '<div class="section-title">',
        ];

        $form['related_products_section']['upgrade-button'] = [
            '#type' => 'link',
            '#title' => t('View All Products'),
            '#url' => \Drupal\Core\Url::fromUri('https://plugins.miniorange.com/drupal'),
            '#attributes' => [
                'class' => ['button', 'button--primary'],
                'target' => '_blank',
            ],
            '#suffix' => '</div>',
        ];

        $form['related_products_section']['features_grid'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['features-grid']
            ],
        ];

        foreach ($related_products as $index => $product) {
            $form['related_products_section']['features_grid']['feature_' . $index] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['feature-box']
                ],
            ];

            $form['related_products_section']['features_grid']['feature_' . $index]['title'] = [
                '#type' => 'markup',
                '#markup' => '<h5>' . $product['title'] . '</h5><hr>',
            ];

            $form['related_products_section']['features_grid']['feature_' . $index]['image'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<div><img class="feature-image" src=":module_path/includes/images/:image" alt=":title" style="width: 400px; height: 400px; object-fit: contain;"></div>', [
                    ':module_path' => $module_path,
                    ':image' => $product['image'],
                    ':title' => $product['title'],
                ]),
            ];

            $form['related_products_section']['features_grid']['feature_' . $index]['content'] = [
                '#type' => 'markup',
                '#markup' => '<span class="product-description">' . $product['description'] . '</span>',
            ];

            $form['related_products_section']['features_grid']['feature_' . $index]['button'] = [
                '#type' => 'link',
                '#title' => t('View Details'),
                '#url' => \Drupal\Core\Url::fromUri($product['link']),
                '#attributes' => [
                    'class' => ['button', 'button--primary'],
                    'target' => '_blank',
                ],
            ];
        }


        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }
}