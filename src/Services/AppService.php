<?php


namespace App\Services;


use App\Entity\App;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AppService
{
    protected $em;
    protected $postService;
    protected $logger;

    protected const STATUS_CREATING = 'creating';
    protected const STATUS_RUNNING = 'running';
    protected const STATUS_STOPPED = 'stopped';
    protected const STATUS_REMOVED = 'removed';
    //TODO: вынести константы и параметры
    protected const DOCKER_ROOT_FOLDER = '~';
    protected const STATIC_ROOT_FOLDER = '../static/apps';

    public function __construct(EntityManagerInterface $em, PostService $postService, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->postService = $postService;
        $this->logger = $logger;
    }

    /**
     * @param string $name
     * @param string $domain
     * @param string $comment
     * @param bool $isStatic
     * @param string $source
     * @param User $user
     * @return Post
     * @throws \Exception
     */
    public function createApp(string $name, string $domain, string $comment, bool $isStatic, string $source, User $user) {
        $app = new App();
        $app->setCreated(time())
             ->setName($name)
             ->setDomain($domain)
             ->setComment($comment)
             ->setIsStatic($isStatic)
             ->setUploader($user)
            ->setSource($source)
            ->setStatus(self::STATUS_CREATING);

        $this->em->persist($app);
        $this->em->flush();
            try {
                if($app->getIsStatic()) {
                    $output = $this->createStaticApp($app);
                } else {
                    $output = $this->startDockerApplication($app);
                }

            } catch (ProcessFailedException $exception) {
                throw new \Exception($exception->getProcess()->getErrorOutput());
            }

        $app->setStatus(self::STATUS_RUNNING);
        $app->setFolder($this->getFolder($app));
        $this->em->persist($app);

        return $this->postService->createAppPost($app);
    }

    /**
     * @param string $domain
     * @return bool
     */
    public function checkDomainExists(string $domain) {
        $appRepository = $this->em->getRepository(App::class);
        return ($appRepository->findBy(['domain'=>$domain, 'deleted'=>null]) != null);
    }

    public function deleteApp(App $app) {
        try {
            $output = $this->deletefromOS($app);
        } catch (ProcessFailedException $exception) {
            throw new \Exception($exception->getProcess()->getErrorOutput());
        }

        $post = $app->getPost();
        $app->setDeleted(time());
        $post->setDeleted(time());

        $this->em->persist($app);
        $this->em->flush();
        return $app->getDeleted();
    }

    //TODO: подумать над более надежным механизмом запуска
    private function startDockerApplication(App $app) {
        $params = $app->getId().' '.$app->getSource().' '.'app-'.$app->getId().' '.$app->getDomain();
        # $1 - id, $2 - image, $3 - service, $4 domain
        $process = Process::fromShellCommandline('echo "../executor/build-compose.sh '.$params.'" > ../../pipe/docker_executor_host');
        $process->setTimeout(60000);
        $process->start();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $this->logger->info(print_r($process->getOutput(), true));
        return  $process->getOutput();
    }

    private function createStaticApp(App $app) {
        $params = $app->getId().' '.$app->getSource().' '.$app->getDomain();
        # $1 - id, $2 - image, $3 - domain
        $process = Process::fromShellCommandline('echo "../executor/build-static.sh '.$params.'" > ../../pipe/docker_executor_host');
        $process->setTimeout(60000);
        $process->start();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return  $process->getOutput();
    }

    private function getFolder(App $app) {
        if (!$app->getIsStatic()) {
            return self::DOCKER_ROOT_FOLDER.__DIR__.$app->getId();
        }
        return self::STATIC_ROOT_FOLDER.__DIR__.$app->getDomain();
    }

    private function deleteFromOS(App $app) {
        if ($app->getIsStatic()) {
            $params = $app->getDomain();
            $process = Process::fromShellCommandline('echo "../executor/delete-static.sh '.$params.'" > ../../pipe/docker_executor_host');
        } else {
            $params = $app->getId();
            $process = Process::fromShellCommandline('echo "../executor/delete-compose.sh '.$params.'" > ../../pipe/docker_executor_host');
        }
        $process->setTimeout(60000);
        $process->start();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return  $process->getOutput();
    }
}
