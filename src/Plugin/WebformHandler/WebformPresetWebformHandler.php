<?php

declare(strict_types=1);

namespace Drupal\webform_preset\Plugin\WebformHandler;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\webform\Plugin\WebformHandlerBase;
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

  public function prepareForm(WebformSubmissionInterface $webform_submission, $operation, FormStateInterface $form_state) {
    if ($operation === 'add') {
      $webformPreset = WebformPreset::loadByRequestQuery($webform_submission->getWebform());
      $data = $webformPreset ? $webformPreset->getData() : NULL;
      if ($data) {
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

}
