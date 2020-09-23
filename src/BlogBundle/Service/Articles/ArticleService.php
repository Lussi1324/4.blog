<?php
namespace BlogBundle\Service\Articles;

use BlogBundle\Entity\Article;
use BlogBundle\Repository\ArticleRepository;
use BlogBundle\Service\Users\UserServiceInterface;
use Doctrine\ORM\ORMException;

class ArticleService implements ArticleServiceInterface
{
    /**
     * @var UserServiceInterface
     */
    private $userService;

    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * ArticleService constructor.
     * @param UserServiceInterface $userService
     */
    public function __construct(UserServiceInterface $userService,ArticleRepository $articleRepository)
    {
        $this->userService = $userService;
        $this->articleRepository = $articleRepository;
    }

    /**
     * @param Article $article
     * @return bool
     * @throws ORMException
     */
    public function create(Article $article): bool
    {
        $author = $this->userService->currentUser();
        $article->setAuthor($author);
        $article->setViewCount(0);

      return $this->articleRepository->insert($article);
    }

    public function edit(Article $article): bool
    {
        return $this->articleRepository->update( $article);
    }

    public function delete(Article $article): bool
    {
        return true;
    }

}