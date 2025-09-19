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
  id: 'computed_field_declaration_expiration_date',
  label: new TranslatableMarkup('Declaration Expiration Date'),
  field_type: 'datetime',
  no_ui: FALSE,
  cardinality: 1,
)]
class DeclarationExpirationDate extends ComputedFieldBase {

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
    return 'field_declaration_expiration_date';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel(): string {
    return 'Declaration Expiration Date';
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
    $new_date = new \DateTime();

    if ($host_entity->hasField('field_verklaring_verstuurd')) {
      $items = $host_entity->get('field_verklaring_verstuurd')->getValue();
      if (!empty($items[0]['value'])) {
        $new_date = \DateTime::createFromFormat('Y-m-d H:i:s', $items[0]['value']);
        if (!$new_date) {
          $new_date = \DateTime::createFromFormat('Y-m-d', $items[0]['value']);
        }
      }
    }

    $new_date->add(new \DateInterval('P5Y'));

    return [['value' => $new_date->format('Y-m-d')]];
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
