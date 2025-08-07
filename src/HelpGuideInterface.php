<?php

declare(strict_types=1);

namespace Drupal\pu_help_guide;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a help guide entity type.
 */
interface HelpGuideInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
