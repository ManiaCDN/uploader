<?php

namespace App\Repository;

use App\Entity\ManiaplanetUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ManiaplanetUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method ManiaplanetUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method ManiaplanetUser[]    findAll()
 * @method ManiaplanetUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ManiaplanetUserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ManiaplanetUser::class);
    }

//    /**
//     * @return ManiaplanetUser[] Returns an array of ManiaplanetUser objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ManiaplanetUser
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
