<?php

namespace Drupal\computed_fields_ca_vlogo\Plugin\ComputedField;

use Drupal\computed_field\Attribute\ComputedField;
use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * TODO: class docs.
 */
#[ComputedField(
  id: 'computed_field_amount',
  label: new TranslatableMarkup('Amount'),
  field_type: 'decimal',
  no_ui: FALSE,
  cardinality: 1,
)]
class ComputedAmount extends ComputedFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(): string {
    return 'decimal';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): ?string {
    return 'field_amount_computed';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Amount';
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
      'precision' => 10,
      'scale' => 2,
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
    $field_a = $host_entity->get('field_bedrag')->getValue();
    $value = !empty($field_a[0]["value"]) ? $field_a[0]["value"] / 100 : NULL;

    if ($value === NULL) {
      return [];
    }

    return [['value' => $value]];
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