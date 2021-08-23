<?php

namespace Drupal\hbku\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\key_auth\KeyAuth;
use Drupal\key_auth\KeyAuthInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DebugController.
 */
class DebugController extends ControllerBase
{

  /**
   * Debug.
   *
   * @return string
   *   Return Hello string.
   */
  public function debug() {
//    dpm('test');
    $entity_manager = \Drupal::entityTypeManager();
    $users = $entity_manager->getStorage('user')->load(4);
    dpm($users);

    $entity_manager = \Drupal::entityTypeManager();
    $progress = $entity_manager->getStorage('node')->loadByProperties([
      'type' => 'student_progress',
      'status' => TRUE,
      'uid' => 4,
    ]);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'orientation_program');
    $query->condition('status', TRUE);

    dpm($query->count()->execute());

//    dpm($progress[202]);
    if ($progress[202] instanceof \Drupal\node\NodeInterface) {

      dpm($progress[202]->get('field_process')->value);
//      dpm($progress->get('field_process')->getValue());
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'student_progress');
    $query->condition('status', TRUE);
    $query->condition('uid', 4);

    $result = $query->execute();
    dpm($result);

//    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
//    if (!$user instanceof \Drupal\user\UserInterface) {
//      throw new AccessDeniedHttpException();
//    }
//    $api_key = $user->get('api_key')->value;
//
//    if (\Drupal::config('key_auth.settings')->get('auto_generate_keys')) {
//      // Load the key auth service.
//      $key_auth = \Drupal::service('key_auth');
//      if (!$key_auth instanceof \Drupal\key_auth\KeyAuthInterface) {
//        throw new AccessDeniedHttpException();
//      }
//      dpm($key_auth->generateKey());
//      $user->set('api_key', $key_auth->generateKey())->save();
//    }

//    return new TrustedRedirectResponse('https://hbku-soos.boufaied.com/hbku?api_key='.$api_key);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: debug'),
    ];
  }

}
