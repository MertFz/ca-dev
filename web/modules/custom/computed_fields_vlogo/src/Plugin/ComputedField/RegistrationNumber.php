<?php

namespace Drupal\computed_fields_vlogo\Plugin\ComputedField;

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
  id: 'computed_field_registration_number',
  label: new TranslatableMarkup('Registration Number'),
  field_type: 'string',
  no_ui: FALSE,
  cardinality: 1,
)]
class RegistrationNumber extends ComputedFieldBase {

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
    return 'field_registration_number';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Registration Number';
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
    $serial = NULL;
    if ($host_entity->hasField('field_serienummer')) {
      $serial_items = $host_entity->get('field_serienummer')->getValue();
      if (!empty($serial_items[0]['value'])) {
        $serial = (int) $serial_items[0]['value'];
      }
    }

    if ($host_entity->bundle() === 'verlenging_logo_verklaring' && $serial !== NULL) {
      $serial += 500000;
    }

    $datum = '';
    if ($host_entity->hasField('field_verklaring_verstuurd')) {
      $date_items = $host_entity->get('field_verklaring_verstuurd')->getValue();
      if (!empty($date_items[0]['value'])) {
        $datum = substr($date_items[0]['value'], 2, 2);
      }
    }

    if ($serial === NULL || $datum === '') {
      return [];
    }

    $value = $datum . sprintf('%06d', $serial);
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