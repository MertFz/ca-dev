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
  id: 'computed_field_closing_date',
  label: new TranslatableMarkup('Closing Date'),
  field_type: 'datetime',
  no_ui: FALSE,
  cardinality: 1,
)]
class ClosingDate extends ComputedFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(): string {
    return 'datetime';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): ?string {
    return 'field_closing_date';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Closing Date';
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
      'datetime_type' => 'date',
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
    $sluitingsdatum_a = strtotime('1970-01-01');
    $sluitingsdatum_b = strtotime('1970-01-01');

    $entity_type = $host_entity->getEntityTypeId();
    $entity = $host_entity;

    // Get field_afgewezen_op value.
    if ($host_entity->hasField('field_afgewezen_op')) {
      $items = $host_entity->get('field_afgewezen_op')->getValue();
      if (is_array($items) && !empty($items[0]['value'])) {
        $afgewezen_op = $items[0]['value'];
        $sluitingsdatum_a = strtotime($afgewezen_op . ' + 6 months');
      }
    }

    // Get field_verlengd_op value.
    if ($host_entity->hasField('field_verlengd_op')) {
      $items = $host_entity->get('field_verlengd_op')->getValue();
      if (is_array($items) && !empty($items[0]['value'])) {
        $verlengd_op = $items[0]['value'];
        $sluitingsdatum_b = strtotime($verlengd_op . ' + 6 months');
      }
    }

    if ($sluitingsdatum_a == strtotime('1970-01-01') && $sluitingsdatum_b == strtotime('1970-01-01')) {
      return [];
    }
    $final_date = ($sluitingsdatum_a >= $sluitingsdatum_b) ? $sluitingsdatum_a : $sluitingsdatum_b;
    // Return as Y-m-d for date field.
    return [['value' => date('Y-m-d', $final_date)]];
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
