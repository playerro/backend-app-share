<?php


namespace App\EventListener;


use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthenticationFailureListener
{
    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        $message = 'Неправильная связка логин/пароль';

        $response = new JWTAuthenticationFailureResponse($message, $statusCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        $event->setResponse($response);
    }
}
