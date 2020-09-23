<?php
namespace BlogBundle\Service\Roles;


interface RoleServiceInterface
{
    public function findOneBy(string $criteria);
}