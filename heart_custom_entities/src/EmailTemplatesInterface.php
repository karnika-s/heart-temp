<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an emailtemplates entity type.
 */
interface EmailTemplatesInterface extends ContentEntityInterface, EntityChangedInterface {

}
