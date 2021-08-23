<?php

namespace Drupal\hbku\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "student_progress_stats_rest_resource",
 *   label = @Translation("Student progress stats rest resource"),
 *   uri_paths = {
 *     "canonical" = "/api/stats/progress/students"
 *   }
 * )
 */
class StudentProgressStatsRestResource extends ResourceBase
{

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('hbku');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Responds to GET requests.
   *
   * @param string $payload
   *
   * @return ResourceResponse
   *   The HTTP response object.
   *
   * @throws HttpException
   *   Throws exception expected.
   */
  public function get($payload) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    try {
      $payload = $this->getStudentsTotalProgress();
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
      $payload = [];
    }

    return new ResourceResponse($payload, 200);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getStudentsTotalProgress() {
    $students = $this->getStudents();
    $total_progress = [];

    foreach ($students as $student) {
      $total_progress[] = $this->getStudentTotalProgress($student);
    }

    return $total_progress;
  }

  public function getStudents() {
    $query = \Drupal::entityQuery('user');
    $query->condition('roles', 'student');
    $query->condition('status', TRUE);

    return $query->execute();
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getStudentTotalProgress($sid = NULL) {

    if (!$sid) return [];

    $entity_manager = \Drupal::entityTypeManager();

    $progresses = $entity_manager->getStorage('node')->loadByProperties([
      'type' => 'student_progress',
      'status' => TRUE,
      'uid' => $sid,
    ]);

    $total_progress = 0;
    foreach ($progresses as $progress) {
      if (!$progress instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      $total_progress += $progress->get('field_process')->value;
    }

    $total_programs = $this->countPrograms();
    return [
      'uid' => $sid,
      'total_progress' => ($total_progress / $total_programs),
    ];
  }

  /**
   * Count Total programs available in the system
   *
   * @return array|int
   */
  public function countPrograms() {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'orientation_program');
    $query->condition('status', TRUE);

    return $query->count()->execute();
  }

}
