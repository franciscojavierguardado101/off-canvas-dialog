<?php

declare(strict_types=1);

namespace Drupal\pu_help_guide\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Provides an Unique constraint for Help guide entity.
 *
 * @see https://www.drupal.org/node/2015723.
 */
#[Constraint(
  id: 'UniqueHelpGuide',
  label: new TranslatableMarkup('Unique help guide', options: ['context' => 'Validation'])
)]
final class UniqueHelpGuideConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public string $message = 'A help guide entity already exists for the selected field!';

}
