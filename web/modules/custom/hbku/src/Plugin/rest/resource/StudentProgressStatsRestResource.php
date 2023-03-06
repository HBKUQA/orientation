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
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
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
    public function get($payload)
    {

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
    public function getStudentsTotalProgress()
    {

        try {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'orientation_program');
            $query->condition('status', TRUE);

            $keys = $query->execute();

            $courses = \Drupal\node\Entity\Node::loadMultiple($keys);
            $courses = array_map(function ($item) {
                return $item->title->value;
            }, array_values($courses));

            $students = $this->getStudents();
            $total_progress = [];

            foreach ($students as $student) {
                $total_progress[] = $this->getStudentTotalProgress($courses,$student);
            }



            return $total_progress;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getStudents()
    {
        $query = \Drupal::entityQuery('user');
        $query->condition('roles', 'student');
        $query->condition('status', TRUE);

        return $query->execute();
    }

    /**
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */

    public function getStudentTotalProgress($programs, $sid = NULL)
    {

        if (!$sid) return ['test' => 2];

        $entity_manager = \Drupal::entityTypeManager();

        $progresses = $entity_manager->getStorage('node')->loadByProperties([
            'type' => 'student_progress',
            'status' => 1,
            'uid' => $sid,
        ]);

        $progress_result = ['test' => 1];

        $total_progress = 0;
        foreach ($progresses as $progress) {
            if (!$progress instanceof \Drupal\node\NodeInterface) {
                continue;
            }
            $program = $entity_manager->getStorage('node')->load(intval($progress->get('field_program')->getValue()[0]['target_id']));
            $total_progress += $progress->get('field_process')->value ? $progress->get('field_process')->value : 0;
            $progress_result['program_obj'] = $program->label();  //$entity_manager->getStorage('orientation_program')->load($progress->get('field_program')->target_id)->label();
            $progress_result[$progress_result['program_obj'] . '_progress'] = $progress->get('field_process')->value ? $progress->get('field_process')->value : 0;

        }

        foreach ($programs as $program){
            if(!isset($progress_result[$program . '_progress'])){
                $progress_result[$program . '_progress'] = 0;
            }
        }

        $total_programs = $this->countPrograms();
        $student_name = '';
        $student = $entity_manager->getStorage('user')->load($sid);
        if ($student instanceof \Drupal\user\UserInterface) {
            $student_name = $student->label();
        }
        $total = round($total_progress / $total_programs);
        if ($total > 100) $total = 100;
        $progress_result['uid'] = $sid;
        $progress_result['email'] = $student->mail->value;
        $progress_result['user'] = $student;
        $progress_result['student_name'] = $student_name;
        $progress_result['total_progress'] = $total ? $total : 0;


        return $progress_result;
    }
    /*
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
        $student_name = '';
        $student = $entity_manager->getStorage('user')->load($sid);
        if ($student instanceof \Drupal\user\UserInterface) {
          $student_name = $student->label();
        }
        $total = round($total_progress / $total_programs);
        if ($total > 100) $total = 100;
        return [
          'uid' => $sid,
          'student_name' => $student_name,
          'total_progress' => $total,
        ];
      }
    */
    /**
     * Count Total programs available in the system
     *
     * @return array|int
     */
    public function countPrograms()
    {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'orientation_program');
        $query->condition('status', TRUE);

        return $query->count()->execute();
    }

}
