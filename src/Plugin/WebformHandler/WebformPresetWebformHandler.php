<?php

declare(strict_types=1);

namespace Drupal\webform_preset\Plugin\WebformHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
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

  public function access(WebformSubmissionInterface $webform_submission, $operation, AccountInterface $account = NULL) {
    return AccessResult::allowedIf(
      $operation === 'create'
      && WebformPreset::loadByRequestQuery($webform_submission->getWebform())
    )->addCacheTags(['request.query:' . WebformPreset::QUERY]);
  }

  public function prepareForm(WebformSubmissionInterface $webform_submission, $operation, FormStateInterface $form_state) {
    if ($preset = WebformPreset::loadByRequestQuery($webform_submission->getWebform())) {
      $webform_submission->setData($preset->getData());
    }
  }

}
