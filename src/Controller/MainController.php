<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
class MainController extends AbstractController
{

    //TODO: удалить ненужные методы из MainController
    /**
     * @Route("/main/form", methods={"GET"})
     * @return Response
     */
    public function actionForm() {

        return $this->render('form.html.twig');
    }


    /**
     * @Route("/main/proccess", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function actionProccessForm( Request $request) {

        $name =  htmlspecialchars($request->request->get('docker'));
        $id = '12345';
        $image = 'schmunk42/yii2-app-basic';
        $domain = 'yii2';
        $params = $id.' '.$image.' '.'app-'.$id.' '.$domain;
        # $1 - id, $2 - image, $3 - service, $4 container, $5 domain
        $process = Process::fromShellCommandline('echo "ls .." > ../../pipe/docker_executor_host');
        $process->setTimeout(3600);
        $process->start();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        return $this->render('result.html.twig',[
            'result' => $process->getOutput(),
            'error'=> $process->getErrorOutput()
        ]);
    }
}

