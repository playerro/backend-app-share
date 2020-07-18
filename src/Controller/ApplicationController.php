<?php


namespace App\Controller;


use App\Entity\App;
use App\Entity\User;
use App\Services\AppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationController extends ApiController
{
    /**
     * @Route("/api/apps", methods={"GET","OPTIONS"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserApps(Request $request) {

        $sort = $request->query->get('sort');
        $order = $request->query->get('order');
        $page = $request->query->get('page');
        // TODO: implement pagination

        $user = $this->getCurrentUser();
        $this->checkUserExists($user);
        $appRepo = $this->getDoctrine()->getRepository(App::class);
        $apps = $appRepo->findBy(['uploader'=>$user, 'deleted'=>null]);
        $data = $this->formatJsonResponse($apps);
        return $this->response($data);
    }


    /**
     * @Route("/api/apps", methods={"POST","OPTIONS"})
     * @param Request $request
     * @param AppService $appService
     * @return JsonResponse
     */
    public function sendApp(Request $request, AppService $appService) {

        /* @var User $user */
        $user = $this->getCurrentUser();
        $this->checkUserExists($user);

        $request = $this->transformJsonBody($request);
        $name = $request->get('name');
        $domain = $request->get('domain');
        $comment = $request->get('comment');
        $isStatic = $request->get('isStatic');
        $source = $request->get('source');

        if (empty($name) || empty($domain) || empty($comment) || is_null($isStatic) || empty($source)) {
            return $this->setStatusCode(422)->respondValidationError('Не заполнены обязательные поля');
        }

        if ($appService->checkDomainExists($domain)) {
            return $this->setStatusCode(422)->respondValidationError('Домен уже существует!');
        }

        try {
           $post = $appService->createApp($name, $domain, $comment, $isStatic, $source, $user);
        } catch (\Exception $e) {
            return $this->setStatusCode(500)->respondWithErrors($e->getMessage());
        }


        $data =  $this->formatJsonResponse($post);
        return $this->setStatusCode(200)->response($data);
    }

    /**
     * @Route("/api/apps/{domain}", methods={"GET","OPTIONS"})
     * @param string $domain
     * @param AppService $appService
     * @return JsonResponse
     */
    public function validateDomainExists(string $domain, AppService $appService) {
        $entryExists = $appService->checkDomainExists($domain);
        return $this->response($entryExists);
    }

    /**
     * @Route("/api/apps/{id}", methods={"DELETE","OPTIONS"})
     * @param int $id
     * @param AppService $appService
     * @return JsonResponse
     */
    public function deleteApp(int $id, AppService $appService) {
        /* @var User $user */
        $user = $this->getCurrentUser();
        $this->checkUserExists($user);

        $apps = $user->getApps();
        $belongs = false;

        //TODO: refactor
        foreach ($apps as $app) {
            if ($app->getId() === $id) $belongs = true;
        }

        if (!$belongs) {
            return $this->setStatusCode(403)->respondForbidden('Удаление приложения запрещено');
        }

        $appRepo = $this->getDoctrine()->getRepository(App::class);
        /* @var $app App */
        $app = $appRepo->findOneBy(['id'=>$id]);

        if (!$app) {
            return $this->setStatusCode(404)->respondNotFound('Приложение не найдено');
        }
        try {
            $result = $appService->deleteApp($app);
        } catch (\Exception $e) {
            return $this->setStatusCode(500)->respondWithErrors($e->getMessage());
        }

        $data = $this->formatJsonResponse($result);
        return $this->setStatusCode(200)->response($data);
    }
}
