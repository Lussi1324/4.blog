<?php

namespace BlogBundle\Controller;

use BlogBundle\Entity\Role;
use BlogBundle\Entity\User;
use BlogBundle\Form\UserType;
use BlogBundle\Service\Users\UserServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends Controller
{
    /**
     * @var UserServiceInterface
     */
    private $userService;
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }
    /**
     * @Route("register",name="user_register",methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function register(Request $request)
    {
        return $this->render('users/register.html.twig',
            ['form'=>$this->createForm(UserType::class)->createView()]);
    }

    /**
     * @Route("register",methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerProcess(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class,$user);
        $form->handleRequest($request);


        if(null !== $this->userService->findOneByEmail($form['email']->getData())){
            $email = $this->userService->findOneByEmail($form['email']->getData())->getEmail();
            $this->addFlash("error","Email $email already taken");
            return $this->render("users/register.html.twig",
                [
                    'user' => $user,
                    'form'=>$this->createForm(UserType::class)->createView()]
            );
        }
        if($form['password']['first']->getData() !== $form['password']['second']->getData()){
            $this->addFlash("error","Password mismatch!");
            return $this->render("users/register.html.twig",
                [
                    'user' => $user,
                    'form'=>$this->createForm(UserType::class)->createView()]
            );
        }

        $this->uploadFile($form,$user);

        $this->userService ->save($user);

            return $this->redirectToRoute("security_login");

 }
    /**
     * @Route("/profile/edit",name="edit_profile",methods={"GET"})
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request)
    {
        $currentUser = $this->userService->currentUser();

        return $this->render('users/edit.html.twig',
            [
                'user' => $currentUser,
                'form' => $this->createForm(UserType::class)
                ->createView()
            ]);
    }

    /**
     * @Route("/profile/edit",methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProcess(Request $request)
    {
        $currentUser = $this->userService->currentUser();

        $form = $this->createForm(UserType::class,$currentUser);
        $form->remove('password');

        if($currentUser->getImage() !==null){
            $oldImage = $currentUser->getImage();
        }

        $form->handleRequest($request);
        $this->uploadFile($form,$currentUser, $oldImage);
        $this->userService->update($currentUser);

        return $this->redirectToRoute("user_profile");



    }

    /**
     * @Route("/profile",name="user_profile")
     */
    public function profile(){

        return $this->render("users/profile.html.twig",
            ['user'=>$this->userService->currentUser()]);
    }

    /**
     * @Route("/logout",name="security_logout")
     * @throws \Exception
     */
    public function logout(){
        throw new \Exception("Logout failed!");
    }

    private function uploadFile(FormInterface $form,User $user,$oldImage =null){

        /** @var UploadedFile $file */
        $file = $form['image']->getData();

        if($file){
          //  var_dump($this->userService->currentUser()->getImage());
          //  exit;
            if($oldImage){
                $this->removeFile($oldImage);
            }
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move(
                $this->getParameter('users_directory'),
                $fileName
            );
            $user->setImage($fileName);
        }
    }

    public function removeFile( $oldImage){

        $fs = new filesystem();
        $fileOld = $this->getParameter('users_directory')
            .'/'
            .$oldImage;

        $fs->remove(array($fileOld));
    }
}
