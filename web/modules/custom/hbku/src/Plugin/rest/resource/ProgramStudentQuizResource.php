<?php

namespace Drupal\hbku\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "program_student_quiz_resource",
 *   label = @Translation("Program Student quiz resource"),
 *   uri_paths = {
 *     "create" = "/api/program/quiz/student"
 *   }
 * )
 */
class ProgramStudentQuizResource extends ResourceBase
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
    $instance->logger = $container->get('logger.factory')->get('boufaied');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param string $payload
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws EntityStorageException
   *   Throws exception expected.
   */
  public function post($payload) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $programID = $payload['program_id'] ?? 0;
    $relatedQuiz = $payload['quiz_id'] ?? 0;
    $studentID = $payload['student_id'] ?? 0;
    $title = 'Student Quiz ' . $programID . '-' . $studentID;

    $studentQuiz = \Drupal::entityTypeManager()->getStorage('node')->create(
      [
        'type' => 'program_quiz_answers',
        'title' => $title,
      ]
    );

    $studentQuiz->set('field_program', $programID);
    $studentQuiz->set('field_related_quiz', $relatedQuiz);
    $studentQuiz->set('field_student', $studentID);
    $studentQuiz->set('uid', $studentID);

    $quizQuestionParagraphs = $this->saveQuizQuestion($payload['quiz_multiple_questions']);

    $studentQuiz->set('field_quiz_multiple_questions', $quizQuestionParagraphs);

    try {
      $studentQuiz->save();
      return new ModifiedResourceResponse([
        "student_quiz_answer_id" => $studentQuiz->id(),
      ], 200);
    } catch (EntityStorageException $e) {
      throw new AccessDeniedHttpException();
    }
  }

  private function saveQuizQuestion($quizMultipleQuestion) {
    $questionParagraphs = [];
    foreach ($quizMultipleQuestion as $quizAnswers) {
      $quizQuestionParagraph = \Drupal::entityTypeManager()
        ->getStorage('paragraph')
        ->create(['type' => 'quiz_question']);

      $quizQuestionParagraph->set('field_quiz_question', $quizAnswers['quiz_question']);

      $questionParagraphAnswers = $this->saveQuizQuestionAnswers($quizAnswers['quiz_answers']);

      $quizQuestionParagraph->set('field_answers', $questionParagraphAnswers);

      try {
        $quizQuestionParagraph->save();
        $questionParagraphs[] = [
          'target_id' => $quizQuestionParagraph->id(),
          'target_revision_id' => $quizQuestionParagraph->getRevisionId(),
        ];
      } catch (EntityStorageException $e) {
        throw new AccessDeniedHttpException();
      }
    }

    return $questionParagraphs;
  }

  private function saveQuizQuestionAnswers($answers) {
    $answersParagraphs = [];

    foreach ($answers as $answer) {
      $quizQuestionAnswer = \Drupal::entityTypeManager()->getStorage('paragraph')
        ->create(['type' => 'question_answers']);

      $quizQuestionAnswer->set('field_quiz_answer', $answer['answer']);
      $quizQuestionAnswer->set('field_quiz_answer_is_correct', $answer['correct']);

      try {

        $quizQuestionAnswer->save();
        $answersParagraphs[] = [
          'target_id' => $quizQuestionAnswer->id(),
          'target_revision_id' => $quizQuestionAnswer->getRevisionId(),
        ];
      } catch (EntityStorageException $e) {
        throw new AccessDeniedHttpException();
      }
    }

    return $answersParagraphs;
  }

}
