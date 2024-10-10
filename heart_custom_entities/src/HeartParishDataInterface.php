<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a heart parish data entity type.
 */
interface HeartParishDataInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
