<?php

namespace Drupal\webform_preset\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

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
 *     "list_builder" = "Drupal\webform_preset\Entity\ListBuilder\WebformPresetListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\webform_preset\Entity\Form\WebformPresetForm",
 *       "edit" = "Drupal\webform_preset\Entity\Form\WebformPresetForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "webform_preset",
 *   admin_permission = "administer webform preset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/webform-preset",
 *     "add-form" = "/webform-preset/add",
 *     "canonical" = "/webform-preset/{webform_preset}",
 *     "edit-form" = "/webform-preset/{webform_preset}/edit",
 *     "delete-form" = "/webform-preset/{webform_preset}/delete",
 *   },
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

  public function getUrl() {
    $this->getWebform()->toUrl('canonical', ['query' => [static::QUERY => $this->getSecret()]]);
  }

  public function recreateSecret(): void {
    $secret = static::createSecret();
    $this->set('secret', $secret);
  }

}
