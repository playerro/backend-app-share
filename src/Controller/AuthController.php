<?php


namespace App\Controller;


use App\Entity\User;
use App\Services\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Controller\ApiController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Annotation\Route;
class AuthController extends ApiController
{
    /**
     * @Route("/register", methods={"POST"})
     * @param Request $request
     * @param UserService $userService
     * @return JsonResponse
     */
    public function register(Request $request, UserService $userService){
        $em = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $username = $request->get('username');
        $password = $request->get('password');
        $firstName = $request->get('firstName');
        $lastName = $request->get('lastName');
        $email = $request->get('email');

        if (empty($username) || empty($password) || empty($firstName) || empty($lastName)) {
            return $this->respondValidationError("Invalid username or password or name ");
        }
        $user = $userService->createUser($password,$username,$firstName,$lastName,$email);

        $em->persist($user);
        $em->flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getUsername()));

    }

    /**
     * @Route("/api/login_check")
     * @param Request $request
     * @param JWTTokenManagerInterface $JWTManager
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function getTokenUser(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordEncoderInterface $encoder) {
        $request = $this->transformJsonBody($request);
        $username = $request->get('username');
        $password = $request->get('password');
        if (empty($username) || empty($password)) {
            return $this->respondValidationError("Invalid username or password");
        }
        $em = $this->getDoctrine()->getManager();
        /* @var $user User */
        $user = $em->getRepository(User::class)->findOneBy([
            'username' =>$username,
        ]);
        if (!$user) {
            return $this->respondValidationError("User with these credentials not found");
        }
        if ($user->getPassword() !== $encoder->encodePassword($user, $password)) {
            return $this->respondValidationError("User with these credentials not found");
        }
        return new JsonResponse(['test' => $JWTManager->create($user)]);
    }

}
