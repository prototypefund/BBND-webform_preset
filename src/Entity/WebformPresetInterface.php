<?php

namespace Drupal\webform_preset\Entity;


use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;

interface WebformPresetInterface extends EntityInterface {

  public function getWebform(): WebformInterface;

  public function getData(): array;

  public function getExpires(): int;

  public function getSecretUrl();

  public function recreateSecret(): void;

}
