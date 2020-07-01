<?php


namespace App\Controller;


use App\Entity\Post;
use App\Entity\User;
use App\Services\PostService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;

class PostController extends ApiController
{
    private $offset;

    public function __construct(int $postOffset)
    {
        $this->offset = $postOffset;
    }

    /**
     * @Route("/api/posts/{username}/{page<\d+>}", methods={"GET","OPTIONS"})
     * @param EntityManagerInterface $em
     * @param string $username
     * @param int $page
     * @return JsonResponse
     */
    public function getUserPosts(EntityManagerInterface $em, string $username, int $page = 0) {
        $user = $em->getRepository(User::class)->findOneBy(['username'=>$username]);

        if (!$user) {
            return $this->setStatusCode(404)->respondWithErrors("User not found");
        }

        /* @var PostRepository $postRepository */
        $postRepository =  $em->getRepository(Post::class);

        $offset = $page * $this->offset;
        $posts = $postRepository->getUserPosts($user->getId(), $offset, $this->offset);

        $data =  $this->formatJsonResponse($posts);
        return $this->response($data);
    }

    /**
     * @Route("/api/posts", methods={"POST","OPTIONS"})
     * @param Request $request
     * @param PostService $postService
     * @return JsonResponse
     */
    public function sendPost(Request $request, PostService $postService) {

        /* @var User $user */
        $user = $this->getCurrentUser();
        $this->checkUserExists($user);

        $request = $this->transformJsonBody($request);
        $text = $request->get('text');

        if (!$text) {
            return $this->respondValidationError('Не заполнено поле текст сообщения.');
        }

        $post = $postService->createUsualPost($user, $text);

        $data =  $this->formatJsonResponse($post);
        return $this->response($data);
    }
}
