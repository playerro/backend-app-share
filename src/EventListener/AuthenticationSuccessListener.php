<?php


namespace App\EventListener;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }
        $userData = [
            'id'=>$user->getId(),
            'username'=>$user->getUsername(),
            'firstName'=>$user->getFirstName(),
            'lastName'=>$user->getLastName(),
            'email'=>$user->getEmail(),
            'gender'=>$user->getGender(),
            'isAdmin'=>$user->getIsAdmin(),
            'status'=>$user->getStatus(),
            'birthday'=>$user->getBirthday()
        ];
        $event->setData(array_merge($userData, $data));
    }
}
