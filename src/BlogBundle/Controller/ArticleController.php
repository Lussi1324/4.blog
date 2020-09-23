<?php

namespace BlogBundle\Controller;

use BlogBundle\Entity\Article;
use BlogBundle\Entity\User;
use BlogBundle\Form\ArticleType;
use BlogBundle\Service\Articles\ArticleServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends Controller
{
    /**
     * @var ArticleServiceInterface
     */
    private $articleService;

    /**
     * ArticleController constructor.
     * @param ArticleServiceInterface $articleService
     */
    public function __construct(ArticleServiceInterface $articleService)
    {
        $this->articleService = $articleService;
    }
    /**
     * @Route("/create",name="article_create",methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request)
    {
        return $this->render('articles/create.html.twig',
            array('form' => $this->createForm(ArticleType::class)->createView()));
    }
    /**
     * @Route("/create", methods={"POST"})
     * @param Request $request
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function createProccess(Request $request)
    {
        $article = new Article();

        $form = $this->createForm(ArticleType::class,$article);

        if($article->getImage() !==null){
            $oldImage = $article->getImage();
        }else {
            $oldImage = '';
        }
        $form->handleRequest($request);
        $this->uploadFile($form,$article, $oldImage);

         $this->articleService->create($article);

        $this->addFlash("info","Create article is successfully!");
            return $this->redirectToRoute("blog_index");
        }


    /**
     * @Route("/article/{id}",name ="article_view")
     * @param $id
     * @return Response
     */
    public function viewArticle($id){
        $article = $this
            ->getDoctrine()
            ->getRepository(Article::class)
            ->find($id);

        if(null === $article){
            return $this->redirectToRoute("blog_index");
        }

        $article->setViewCount($article->getViewCount()+1);
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        return $this->render('articles/article.html.twig',['article'=>$article]);
    }

    /**
     * @Route("/edit/{id}", name="article_edit",methods={"GET"})
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit($id,Request $request)
    {
        $article = $this
            ->getDoctrine()
            ->getRepository(Article::class)
            ->find($id);

        if(null === $article) {
            return $this->redirectToRoute("blog_index");
        }
           return $this->render('articles/edit.html.twig',
               array('form'=>$this->createForm(ArticleType::class),'article'=>$article));
    }

    /**
     * @Route("/edit/{id}",methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProcess($id,Request $request)
    {
        $article = $this->getDoctrine()->getManager()->find(Article::class,$id);

        $file = $article->getImage();
        $form = $this->createForm(ArticleType::class,$article);
        $form->handleRequest($request);

        if($file === null){
            $form->remove('image');

        }else {
            $oldImage = $file;

            $this->uploadFile($form,$article, $oldImage);
        }

        $this->articleService->edit($article);

        $this->addFlash("info","Update article is successfully!");
        return $this->redirectToRoute("article_view",['id'=>$id]);
    }

    /**
     * @Route("/delete/{id}", name="article_delete")
     * @param Request $request
     * @param $id
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function delete(Request $request,int $id)
    {
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);

        if(null === $article) {
            return $this->redirectToRoute("blog_index");
        }
        if(!$this->isAuthorOrAdmin($article)){
            return $this->redirectToRoute("blog_index");
        }
        $form = $this->createForm(ArticleType::class,$article);

        $form->handleRequest($request);

        if($form->isSubmitted()){
            $article->setAuthor($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();

            $this->removeFile($article->getImage());

            $this->addFlash("info","Delete article is successfully!");
            return $this->redirectToRoute("blog_index");
        }

        return $this->render('articles/delete.html.twig',
            array('form'=>$form->createView(),'article'=>$article));
    }

    /**
     * @param Article $article
     * @return bool
     */
    private function isAuthorOrAdmin(Article $article){
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if(!$currentUser->isAuthor($article) && !$currentUser->isAdmin()){
            return false;
        }
        return true;
    }

    /**
     * @Route("/articles/my_articles",name= "my_articles")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAllArtticleByUser(){
        $articles = $this->getDoctrine()->getRepository(Article::class)
            ->findBy(
                ['author'=>$this->getUser()],
                [   'viewCount'=> 'DESC',
                    'dateAdded'=>'DESC'
                ]
            );
        return $this->render("articles/myArticles.html.twig",
            ['articles'=>$articles]
        );
    }

    private function uploadFile(FormInterface $form,Article $article,$oldImage =null){

        /** @var UploadedFile $file */
        $imageFile = $form->get('image')->getData();

        $originalExtantion = pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);

        if($imageFile){
            if($oldImage){
                $this->removeFile($oldImage);
            }
            $fileName = md5(uniqid()).'.'.$originalExtantion;

            $imageFile->move(
                $this->getParameter('articles_directory'),
                $fileName
            );
            $article->setImage($fileName);
        }
    }

    private function removeFile($oldImage){

        $fs = new filesystem();
        $fileOld = $this->getParameter('articles_directory')
            .'/'
            .$oldImage;

        $fs->remove(array($fileOld));
    }
}
