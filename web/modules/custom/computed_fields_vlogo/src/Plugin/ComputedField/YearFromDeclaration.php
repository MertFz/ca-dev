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
  id: 'computed_field_declaration_year',
  label: new TranslatableMarkup('Declaration Year'),
  field_type: 'integer',
  no_ui: FALSE,
  cardinality: 1,
)]
class YearFromDeclaration extends ComputedFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(): string {
    return 'integer';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): ?string {
    return 'field_declaration_year';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Declaration Year';
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
    return [];
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
    if ($host_entity->hasField('field_verklaring_verstuurd')) {
        $items = $host_entity->get('field_verklaring_verstuurd')->getValue();
        if (!empty($items[0]['value'])) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $items[0]['value'])
                ?: \DateTime::createFromFormat('Y-m-d', $items[0]['value']);
            if ($date) {
                return [['value' => (int) $date->format('Y')]];
            }
        }
    }
    return [];
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
