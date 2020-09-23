<?php
namespace BlogBundle\Service\Articles;

use BlogBundle\Entity\Article;

interface ArticleServiceInterface
{
    public function create(Article $article):bool;
    public function edit(Article $article):bool;
    public function delete(Article $article):bool;
}