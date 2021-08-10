<?php

namespace Drupal\hbku\Plugin\RulesAction;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\key_auth\KeyAuth;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'RedirectUser' action.
 *
 * @RulesAction(
 *  id = "redirect_user",
 *  label = @Translation("Redirect student"),
 *  category = @Translation("Hbku"),
 *  context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("user"),
 *       description = @Translation("user")
 *     ),
 *  }
 * )
 */
class RedirectUser extends RulesActionBase implements ContainerFactoryPluginInterface
{

  /**
   * The Rules debug logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current request.
   *
   * @var \Drupal\key_auth\KeyAuthInterface
   */
  protected $key_auth;

  /**
   * Constructs a SystemPageRedirect object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, CurrentPathStack $current_path_stack, RequestStack $request_stack, KeyAuth $key_auth) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->key_auth = $key_auth;
    $this->currentPathStack = $current_path_stack;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.rules_debug'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('key_auth')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function doExecute(UserInterface $account) {
    // Insert code here.

    $api_key = $account->get('api_key')->value;
    if (empty($api_key)) {
      if ($this->key_auth instanceof \Drupal\key_auth\KeyAuthInterface) {
        $api_key = $this->key_auth->generateKey();
        $account->set('api_key', $api_key)->save();
      }
    }

    $url = 'https://hbku-soos.boufaied.com/hbku?api_key=' . $api_key;
    $this->request->attributes->set('_rules_redirect_action_url', $url);
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // Insert code here.
    return [];
  }

}
