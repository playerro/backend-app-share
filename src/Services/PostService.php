<?php


namespace App\Services;


use App\Entity\App;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PostService
{
   protected $em;
   public function __construct(EntityManagerInterface $em)
   {
       $this->em = $em;
   }

   public function createUsualPost(UserInterface $user, string $text) {
       return $this->createPost($user, $text, false);
   }

    public function createAppPost(App $app) {
        return $this->createPost($app->getUploader(), $app->getComment(), true, $app);
    }

    private function createPost(UserInterface $user, string $text, bool $isApp, App $app = null) {
       $post = new Post();
       $post->setUser($user)->setCreated(time())->setIsApp($isApp)->setText($text)->setApplication($app);

       $this->em->persist($post);
       $this->em->flush();

       return $post;
   }

}
