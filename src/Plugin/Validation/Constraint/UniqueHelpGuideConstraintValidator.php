<?php

declare(strict_types=1);

namespace Drupal\pu_help_guide\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\pu_help_guide\HelpGuideInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Unique help guide constraint.
 */
final class UniqueHelpGuideConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    if (!$entity instanceof HelpGuideInterface) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\help_guide\HelpGuideInterface, %s was given.', get_debug_type($entity))
      );
    }
    $entity_type = $entity->get('entity_type')->value;
    $bundle = $entity->get('entity_bundle')->value;
    $field = $entity->get('field_name')->value;

    $query = $this->entityTypeManager->getStorage('help_guide')->getQuery();
    $query
      ->condition('entity_type', $entity_type)
      ->condition('entity_bundle', $bundle)
      ->condition('field_name', $field)
      ->condition('status', 1);

    if (!$entity->isNew()) {
      $query->condition('id', $entity->id(), '<>');
    }
    $existingGuides = $query
    ->accessCheck(FALSE)  // Explicitly disable access checks
    ->execute();

    if (!empty($existingGuides)) {
      /* @phpstan-ignore-next-line */
      $this->context->addViolation($constraint->message);
    }
  }

}
