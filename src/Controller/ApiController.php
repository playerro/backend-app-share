<?php


namespace App\Controller;


use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ApiController extends AbstractController
{
    protected $statusCode = 200;

    public function getStatusCode() {
        return $this->statusCode;
    }

    protected function setStatusCode($code) {
        $this->statusCode = $code;
        return $this;
    }

    public function response($data, $headers = []) {
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondWithErrors($errors, $headers = []) {
        $data = [
            'status' => $this->getStatusCode(),
            'errors' => $errors
        ];
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondWithSuccess($success, $headers = []) {
        $data = [
            'status' => $this->getStatusCode(),
            'success' => $success
        ];
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    public function respondUnauthorized($message = 'Not authorized!') {
        return $this->setStatusCode(401)->respondWithErrors($message);
    }

    public function respondValidationError($message = 'Validation errors') {
        return $this->setStatusCode(422)->respondWithErrors($message);
    }

    public function respondNotFound($message = 'Not found!'){
        return $this->setStatusCode(404)->respondWithErrors($message);
    }

    public function respondForbidden($message = 'Access forbidden!') {
        return $this->setStatusCode(403)->respondWithErrors($message);
    }

    public function respondCreated($data) {
        return $this->setStatusCode(201)->response($data);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request) {
        $data = json_decode($request->getContent() ,true);
        if ($data === null) {
            return $request;
        }
        $request->request->replace($data);
        return $request;
    }

    protected function formatJsonResponse($data) {
        return $this->get('serializer')->serialize($data, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['password',' apiToken','ip','roles','salt','folder'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }
    //TODO: refactor
    protected function getCurrentUser() : ?User
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The Security Bundle is not registered in your application.');
        }
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }
        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return  null;
        }
        return $user;
    }

    protected function checkUserExists($user) {
        if (!$user) return $this->respondNotFound("User not found");
        return true;
    }


}
