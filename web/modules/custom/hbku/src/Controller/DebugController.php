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

    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if (!$user instanceof \Drupal\user\UserInterface) {
      throw new AccessDeniedHttpException();
    }
    $api_key = $user->get('api_key')->value;

    if (\Drupal::config('key_auth.settings')->get('auto_generate_keys')) {
      // Load the key auth service.
      $key_auth = \Drupal::service('key_auth');
      if (!$key_auth instanceof \Drupal\key_auth\KeyAuthInterface) {
        throw new AccessDeniedHttpException();
      }
      dpm($key_auth->generateKey());
      $user->set('api_key', $key_auth->generateKey())->save();
    }

//    return new TrustedRedirectResponse('https://hbku-soos.boufaied.com/hbku?api_key='.$api_key);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: debug'),
    ];
  }

}
