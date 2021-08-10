<?php

namespace Drupal\hbku\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Routing\TrustedRedirectResponse;
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

    return new TrustedRedirectResponse('https://hbku-soos.boufaied.com/hbku?api_key='.$api_key);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: debug'),
    ];
  }

}
