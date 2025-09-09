<?php

namespace Drupal\active_menu_item_by_path\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains the settings for active menu item by path form.
 */
class ActiveMenuItemByPathSettingsForm extends ConfigFormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an ActiveMenuItemByPathSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Returns the unique ID of the form.
    return 'active_menu_item_by_path_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    // Returns the configuration names that will be editable for this form.
    return [
      'active_menu_item_by_path.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load all menus and extract their labels.
    $menu_list = array_map(function ($menu) {
      return $menu->label();
    }, $this->entityTypeManager->getStorage('menu')->loadMultiple());

    // Sort the menu list alphabetically.
    asort($menu_list);

    // Load configuration settings.
    $config = $this->config('active_menu_item_by_path.settings');

    // Form element for selecting menu types.
    $form['menu_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Menu types to enables active item for'),
      '#options' => $menu_list,
      '#default_value' => (!is_null($config->get('allowed_types')) ? $config->get('allowed_types') : []),
    ];

    // Call parent buildForm method.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve selected menu types from form state.
    $selected_allowed_types = $form_state->getValue('menu_types');

    // Sort selected menu types alphabetically.
    asort($selected_allowed_types);

    // Save selected menu types to configuration.
    $this->config('active_menu_item_by_path.settings')
      ->set('allowed_types', $selected_allowed_types)
      ->save();

    // Call parent submitForm method.
    parent::submitForm($form, $form_state);
  }

}
