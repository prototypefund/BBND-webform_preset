<?php

declare(strict_types=1);

namespace Drupal\webform_preset\Plugin\WebformHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_preset\Entity\WebformPreset;

/**
 * @WebformHandler(
 *   id = "webform_preset",
 *   label = @Translation("Webform Preset"),
 *   category = @Translation("Geeks4Change"),
 *   description = @Translation("Only allow create via preset link."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
final class WebformPresetWebformHandler extends WebformHandlerBase {

  protected $presets = [];

  public function prepareForm(WebformSubmissionInterface $webform_submission, $operation, FormStateInterface $form_state) {
    if ($operation === 'add') {
      $data = $this->getWebformPresetData($webform_submission->getWebform());
      if (isset($data)) {
        $webform_submission->setData($data);
      }
      else {
        // We can not do this by ::access.
        $cacheability = (new CacheableMetadata())
          ->addCacheableDependency($webform_submission->getWebform())
          ->addCacheTags(['request.query:' . WebformPreset::QUERY])
        ;
        throw new CacheableAccessDeniedHttpException($cacheability);
      }
    }
  }

  public function accessElement(array &$element, $operation, AccountInterface $account = NULL) {
    $accessResult = AccessResult::neutral();
    if ($operation === 'create') {
      $data = $this->getWebformPresetData($this->webform);
      if (isset($data)) {
        $key = $element['#webform_key']
          // Compound elements...
          ?? $element['#parents'][0] ?? NULL;
        // Neutral means allowed, so can not use ::allowedIf(). Sigh.
        $accessResult = AccessResult::forbiddenIf(isset($data[$key]));
      }
    }
    return $accessResult;
  }

  public function getWebformPresetData(WebformInterface $webform): ?array {
    $cacheId = $webform->id();
    if (!array_key_exists($cacheId, $this->presets)) {
      $webformPreset = WebformPreset::loadByRequestQuery($webform);
      $this->presets[$cacheId] = $webformPreset ? $webformPreset->getData() : NULL;
    }
    return $this->presets[$cacheId];
  }

}
