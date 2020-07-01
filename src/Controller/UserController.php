<?php


namespace App\Controller;



use App\Entity\User;
use App\Entity\UserAvatar;
use App\Repository\UserRepository;
use App\Services\FileUploader;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends ApiController
{
    /**
     * @Route("/api/users", methods={"GET","OPTIONS"})
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function getUsers(UserRepository $userRepository) {

        /* @var User $user */
        $user = $this->getCurrentUser();
        $this->checkUserExists($user);

        if (!$user->getIsAdmin()) {
            return $this->respondForbidden();
        }
        $users = $userRepository->findAll();
        $data =  $this->formatJsonResponse($users);
        return $this->response($data);
    }

    /**
     * @Route("/api/users/{username}", methods={"GET","OPTIONS"})
     * @param UserRepository $userRepository
     * @param UserService $userService
     * @param string $username
     * @return JsonResponse
     */
    public function getUserDetails(UserRepository $userRepository, UserService $userService, string $username) {
        $user = $userRepository->findOneBy(['username'=>$username]);

        if (!$user) {
            return $this->respondNotFound("User not found");
        }
        $userData = $userService->getDetails($user);
        $data =  $this->formatJsonResponse($userData);
        return $this->response($data);
    }

    /**
     * @Route("/api/users", methods={"POST","OPTIONS"})
     * @param Request $request
     * @param UserService $userService
     * @return JsonResponse
     */
    public function updateUser(Request $request, UserService $userService) {

        $user = $this->getCurrentUser();
        $this->checkUserExists($user);

        $request = $this->transformJsonBody($request);
        $wasUpdated = $userService->updateUser($request, $user);
        if (!$wasUpdated) {
            return $this->respondValidationError('Произошла ошибка. Пожалуйста, повторите попытку.');
        }
        $data =  $this->formatJsonResponse('ok');
        return $this->respondWithSuccess($data);
    }

    /**
     * @Route("/api/users/avatar", methods={"POST","OPTIONS"})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param FileUploader $fileUploader
     * @return JsonResponse
     */
    public function uploadAvatar(Request $request, EntityManagerInterface $em, FileUploader $fileUploader) {

        $currentUser = $this->getCurrentUser();
        $this->checkUserExists($currentUser);

        $image = $request->files->get('image');
        if (!$image) {
            return $this->respondValidationError('Отсутствует изображение.');
        }
        try {
            $avatarFileName = $fileUploader->upload($image);
        } catch (FileException $exception) {
            return $this->respondValidationError('Произошла ошибка при попытке обработать изображения.');
        }

        /* @var User $currentUser */
        $avatar = new UserAvatar();
        $avatar->setUser($currentUser)->setFilename($avatarFileName)->setUploaded(time());
        $em->persist($avatar);
        $em->flush();
        $data =  $this->formatJsonResponse($avatarFileName);
        return $this->respondWithSuccess($data);
    }

    /**
     * @Route("/api/users/{id}", methods={"DELETE","OPTIONS"})
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param $id
     * @return JsonResponse
     */
    public function deleteOneUser(EntityManagerInterface $entityManager, UserRepository $userRepository, $id) {

        /* @var User $user */
        $user = $this->getCurrentUser();
        if (!$user->getIsAdmin()) {
            return $this->respondForbidden();
        }
        $deletingUser = $userRepository->find($id);

        if (!$deletingUser) {
            $data = [
                'status' => 404,
                'errors' => "User not found"
            ];
            return $this->response($data);
        }
        $entityManager->remove($deletingUser);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'success' => "User deleted successfully",
        ];
        return $this->response($data);
    }

    /**
     * @Route("/register/username/{username}", methods={"GET","OPTIONS"})
     * @param string $username
     * @param UserService $userService
     * @return JsonResponse
     */
    public function validateUsernameExists(string $username, UserService $userService) {
        $entryExists = $userService->checkAttributeExists('username',$username);
        return $this->response($entryExists);
    }

    /**
     * @Route("/register/email/{email}", methods={"GET","OPTIONS"})
     * @param string $email
     * @param UserService $userService
     * @return JsonResponse
     */
    public function validateEmailExists(string $email, UserService $userService) {
        $entryExists = $userService->checkAttributeExists('email',$email);
        return $this->response($entryExists);
    }
}
