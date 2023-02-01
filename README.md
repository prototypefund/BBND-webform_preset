# Webform preset

An API module that prepopulates a webform via a secret URL.

## How to use

```php
$webform = \Drupal\webform\Entity\Webform::load('my_webform');
$data = ['submitter' => $submitter];
$expire = \Drupal::time()->getRequestTime() + 7 * 86400;
$webformPreset = \Drupal\webform_preset\Entity\WebformPreset::createItem($webform, $data, $expire);
$webformPreset->save();
$secretUrl = $webformPreset->getSecretUrl();
```
