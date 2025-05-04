<?php

namespace App\Repository;

use App\Entity\Homelog;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Homelog>
 *
 * @method Homelog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Homelog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Homelog[]    findAll()
 * @method Homelog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HomelogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Homelog::class);
    }

    public function getDataOfLastDays($lastDays): array
    {

        $cutoffDate = (new DateTime())->modify('-' . $lastDays . ' days');
        return $this->createQueryBuilder('h')
            ->where('h.datetime > :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('h.datetime', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
    //    /**
    //     * @return Homelog[] Returns an array of Homelog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Homelog
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
