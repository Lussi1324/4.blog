<?php

namespace BlogBundle\Controller;

use BlogBundle\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("/", name="blog_index")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {

        $article = $this->getDoctrine()->getRepository(Article::class)
            ->findBy([],['viewCount'=> 'DESC','dateAdded'=>'DESC']);


        // replace this example code with whatever you need
        return $this->render('home/index.html.twig',['articles' => $article]);
    }
}
