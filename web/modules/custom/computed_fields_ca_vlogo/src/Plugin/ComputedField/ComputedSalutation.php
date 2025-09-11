<?php

namespace Drupal\computed_fields_ca_vlogo\Plugin\ComputedField;

use Drupal\computed_field\Attribute\ComputedField;
use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[ComputedField(
  id: 'computed_field_salutation',
  label: new TranslatableMarkup('Salutation'),
  field_type: 'string',
  no_ui: FALSE,
  cardinality: 1,
)]
class ComputedSalutation extends ComputedFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(): string {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): ?string {
    return 'field_salutation';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Salutation';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCardinality(): int {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinitionSettings(): array {
    return [
      'max_length' => 32,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitionSettings(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): array {
    $gender = 0;
    if ($host_entity->hasField('field_gender')) {
      $field_gender = $host_entity->get('field_gender')->getValue();
      if (!empty($field_gender[0]['tid'])) {
        $gender = $field_gender[0]['tid'];
      }
    }

    switch ($gender) {
      case 3:
        $aanhef = "dhr.";
        break;
      case 4:
        $aanhef = "mevr.";
        break;
      default:
        $aanhef = "";
        break;
    }

    return [['value' => $aanhef]];
  }

  /**
   * {@inheritdoc}
   */
  public function useLazyBuilder(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheability(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): ?CacheableMetadata {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function attachAsBaseField(&$fields, EntityTypeInterface $entity_type): void {
    // No-op for now.
  }

  /**
   * {@inheritdoc}
   */
  public function attachAsBundleField(&$fields, EntityTypeInterface $entity_type, string $bundle): void {
    // No-op for now.
  }

}