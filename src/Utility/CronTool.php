<?php

declare(strict_types=1);

namespace Drupal\webform_preset\Utility;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

final class CronTool {

  protected string $fullKey;

  protected int $interval;

  protected StateInterface $state;

  protected TimeInterface $time;

  public function __construct(string $fullKey, int $interval, StateInterface $state, TimeInterface $time) {
    $this->fullKey = $fullKey;
    $this->interval = $interval;
    $this->state = $state;
    $this->time = $time;
  }

  public static function create(string $key, int $interval) {
    return new static("cron_last_run_{$key}", $interval, \Drupal::state(), \Drupal::time());
  }

  public function isDue(): bool {
    $dueOn = $this->state->get($this->fullKey, 0) + $this->interval;
    return $this->time->getRequestTime() >= $dueOn;
  }

  public function setDone(): void {
    $this->state->set($this->fullKey, $this->time->getRequestTime());
  }

  public function isDueAndSetDone(): bool {
    $isDue = $this->isDue();
    if ($isDue) {
      $this->setDone();
    }
    return $isDue;
  }

}
