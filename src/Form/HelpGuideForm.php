<?php

declare(strict_types=1);

namespace Drupal\pu_help_guide\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Url;

/**
 * Form controller for the help guide entity edit forms.
 */
final class HelpGuideForm extends ContentEntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a HelpGuideForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Get default values from entity.
    $entity = $this->entity;
    $entiy_type_default = $entity->get('entity_type')->value;
    $bundle_default = $entity->get('entity_bundle')->value;
    $field_name_default = $entity->get('field_name')->value;

    // Get values from form state if rebuilding.
    if ($form_state->getValue("entity_type")) {
      $entiy_type_default = $form_state->getValue("entity_type");
    }
    if ($form_state->getValue("entity_bundle")) {
      $bundle_default = $form_state->getValue("entity_bundle");
    }
    if ($form_state->getValue("field_name")) {
      $field_name_default = $form_state->getValue("field_name");
    }

    // Create a list of entity types.
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $exclude_list = ['contact_message', 'file', 'menu_link_content', 'path_alias', 'shortcut'];
    $entity_types_list = ["" => "--choose--"];
    foreach ($entity_definitions as $entity_name => $entity_definition) {
      if ($entity_definition instanceof ContentEntityType && !in_array($entity_name, $exclude_list)) {
        $entity_types_list[$entity_name] = (string) $entity_definition->getLabel();
      }
    }
    asort($entity_types_list);

    // Get bundles list.
    $bundle_options = ["" => "--choose--"];
    if ($entiy_type_default) {
      $bundles_list = $this->entityTypeBundleInfo->getBundleInfo($entiy_type_default);
      foreach ($bundles_list as $key => $bundle) {
        if ($key == $entiy_type_default) {
          $bundle_options[$key] = 'Default';
        }
        else {
          $bundle_options[$key] = $bundle['label'];
        }
      }
      asort($bundle_options);
    }

    // Get fields list.
    $field_options = ["" => "--choose--"];
    if ($entiy_type_default && $bundle_default) {
      $fields = $this->entityFieldManager->getFieldDefinitions($entiy_type_default, $bundle_default);
      $fields = array_reduce(array_keys($fields), fn($carry, $item) => [...$carry, $item => $fields[$item]->getLabel()], []);

      asort($fields);
      $field_options = array_merge($field_options, $fields);
    }

    $form["entity_type"] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entity_types_list,
      '#default_value' => $entiy_type_default,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundleCallback',
        'wrapper' => 'field--bundle_wrapper',
        'event' => 'change',
      ],
      '#weight' => 1
    ];

    $form["entity_bundle"] = [
      '#type' => 'select',
      '#title' => $this->t('Entity bundle'),
      '#options' => $bundle_options,
      '#prefix' => '<div id="field--bundle_wrapper">',
      '#suffix' => '</div>',
      '#default_value' => $bundle_default,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::fieldsCallback',
        'wrapper' => 'field--field_wrapper',
        'event' => 'change',
      ],
      '#weight' => 1
    ];

    $form["field_name"] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $field_options,
      '#prefix' => '<div id="field--field_wrapper">',
      '#suffix' => '</div>',
      '#default_value' => $field_name_default,
      '#required' => TRUE,
      '#weight' => 1
    ];

    $form['actions']['submit']['#weight'] = 1;
    $form['actions']['submit_another'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and add another'),
      '#name' => 'submit_another',
      '#submit' => $form['actions']['submit']['#submit'],
      '#weight' => 2,
    ];

    // Changes vertical tabs to container.
    $form['#theme'] = ['node_edit_form'];
    $form['#attached']['library'][] = 'claro/form-two-columns';

    $form['advanced']['#type'] = 'container';
    $form['advanced']['#accordion'] = TRUE;
    $form['author_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
    ];
    $form['author_info']['uid'] = $form['uid'];
    $form['author_info']['created'] = $form['created'];
    unset($form['uid']);
    unset($form['created']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    // Map custom form values to entity fields.
    $this->entity->set('entity_type', $form_state->getValue('entity_type'));
    $this->entity->set('entity_bundle', $form_state->getValue('entity_bundle'));
    $this->entity->set('field_name', $form_state->getValue('field_name'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New help guide %label has been created.', $message_args));
        $this->logger('help_guide')->notice('New help guide %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The help guide %label has been updated.', $message_args));
        $this->logger('help_guide')->notice('The help guide %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }
    $triggering_element = $form_state->getTriggeringElement();

    if ($triggering_element && $triggering_element['#name'] == 'submit_another') {
      $form_state->setIgnoreDestination();
      $form_state->setRedirectUrl(URL::fromRoute("entity.help_guide.add_form"));

    }
    else {
      $form_state->setRedirectUrl(Url::fromRoute('view.help_guides.page_1'));
    }

    return $result;
  }

  /**
   * Ajax callback to check availability of property.
   */
  public function bundleCallback(array &$form, FormStateInterface $form_state) {
    return $form["entity_bundle"];
  }

  /**
   * Ajax callback to check availability of property.
   */
  public function fieldsCallback(array &$form, FormStateInterface $form_state) {
    return $form["field_name"];
  }

}
