<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    //    /**
    //     * @return Invoice[] Returns an array of Invoice objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Invoice
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countInvoicesThisMonth(): int
    {
        $start = new \DateTime('first day of this month midnight');
        $end = new \DateTime('last day of this month 23:59:59');

        return $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.createAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMonthlyRevenueByYear(int $year): array
    {
        $invoices = $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', 'payées')
            ->getQuery()
            ->getResult();

        $monthly = array_fill(1, 12, 0);
        foreach ($invoices as $invoice){
            if ($invoice->getCreateAt() && $invoice->getCreateAt()->format('Y') == $year){
                $month = (int) $invoice->getCreateAt()->format('n');
                $monthly[$month] += $invoice->getTotalTtc() ?? 0;
            }
        }

        return $monthly;
    }
}
