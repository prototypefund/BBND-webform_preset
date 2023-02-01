<?php

namespace Drupal\webform_preset\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the webform preset entity edit forms.
 */
class WebformPresetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case \SAVED_NEW:
        $this->messenger()->addStatus($this->t('New webform preset %label has been created.', $message_arguments));
        $this->logger('webform_preset')->notice('Created new webform preset %label', $logger_arguments);
        break;

      case \SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The webform preset %label has been updated.', $message_arguments));
        $this->logger('webform_preset')->notice('Updated webform preset %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.webform_preset.canonical', ['webform_preset' => $entity->id()]);

    return $result;
  }

}
