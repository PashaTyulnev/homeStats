<?php

namespace App\Repository;

use App\Entity\PermanentData;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PermanentData>
 *
 * @method PermanentData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PermanentData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PermanentData[]    findAll()
 * @method PermanentData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermanentDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PermanentData::class);
    }

    public function getDataOfLastDays($lastDays)
    {

        $cutoffDate = (new DateTime())->modify('-' . $lastDays . ' days');
        return $this->createQueryBuilder('p')
            ->where('p.datetime > :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('p.datetime', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return PermanentData[] Returns an array of PermanentData objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PermanentData
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
