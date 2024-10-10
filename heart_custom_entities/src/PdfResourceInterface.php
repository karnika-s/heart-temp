<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a pdf resource entity type.
 */
interface PdfResourceInterface extends ContentEntityInterface, EntityChangedInterface {

}
