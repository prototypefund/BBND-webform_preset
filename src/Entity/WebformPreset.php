<?php

namespace Drupal\webform_preset\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform_preset\Utility\CronTool;

/**
 * Defines the webform preset entity class.
 *
 * @ContentEntityType(
 *   id = "webform_preset",
 *   label = @Translation("Webform preset"),
 *   label_collection = @Translation("Webform presets"),
 *   label_singular = @Translation("webform preset"),
 *   label_plural = @Translation("webform presets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count webform presets",
 *     plural = "@count webform presets",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "webform_preset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class WebformPreset extends ContentEntityBase implements WebformPresetInterface {

  const QUERY = 'preset';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'));
    $fields['webform'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'webform')
      ->setRequired(TRUE)
      ->setLabel(t('Webform'));
    $fields['secret'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setDefaultValueCallback('\Drupal\webform_preset\Entity\WebformPreset::createConfirmationSecret')
      ->setLabel(t('Secret'));
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setRequired(TRUE)
      ->setLabel(t('Data'));

    return $fields;
  }

  public static function createSecret(): string {
    /** @noinspection PhpUnhandledExceptionInspection */
    return bin2hex(random_bytes(16));
  }

  public static function createItem(WebformInterface $webform, array $data, int $expires = NULL) {
    return static::create([
      'webform' => $webform->id(),
      'data' => $data,
      'expires' => $expires,
    ]);
  }

  public function getWebform(): WebformInterface {
    $webformId = $this->get('webform')->getString();
    return Webform::load($webformId);
  }

  public function getData(): array {
    return $this->get('data')->getValue();
  }

  public function getExpires(): int {
    return intval($this->get('expires')->getString());
  }

  protected function getSecret(): string {
    return $this->get('secret')->getString();
  }

  public function getUrl(): Url {
    return $this->getWebform()->toUrl('canonical', ['query' => [static::QUERY => $this->getSecret()]]);
  }

  public function recreateSecret(): void {
    $secret = static::createSecret();
    $this->set('secret', $secret);
  }

  public static function loadByRequestQuery(WebformInterface $webform): ?WebformPresetInterface {
    return static::loadByRequestQueryAndSecret($webform, static::getMaybeSecretFromRequest());
  }

  public static function getMaybeSecretFromRequest(): ?string {
    return \Drupal::request()->query->get(WebformPreset::QUERY);
  }

  public static function loadByRequestQueryAndSecret(WebformInterface $webform, ?string $secret): ?WebformPresetInterface {
    if (!$secret) {
      return NULL;
    }
    $now = \Drupal::time()->getRequestTime();
    $query = \Drupal::entityTypeManager()
      ->getStorage('webform_preset')
      ->getQuery();
    $unExpired = $query
      ->orConditionGroup()
      ->notExists('expires')
      ->condition('expires', $now, '>');
    $ids = $query
      ->condition('webform', $webform)
      ->condition('secret', $secret)
      ->condition($unExpired)
      ->accessCheck(FALSE)
      ->execute();
    if ($ids) {
      $id = reset($ids);
      return WebformPreset::load($id);
    }
    else {
      return NULL;
    }
  }

  public static function cron(): void {
    if (CronTool::create('webform_preset', 86400)->isDueAndSetDone()) {
      $now = \Drupal::time()->getRequestTime();
      $storage = \Drupal::entityTypeManager()
        ->getStorage('webform_preset');
      $ids = $storage
        ->getQuery()
        ->condition('expires', $now, '<=')
        ->accessCheck(FALSE)
        ->execute();
      foreach (array_chunk($ids, 50) as $chunkIds) {
        $storage->delete(WebformPreset::loadMultiple($chunkIds));
      }
    }
  }

}
