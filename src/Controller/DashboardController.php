<?php
namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        Request $request,
        InvoiceRepository $invoiceRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $year = (int) $request->query->get('year', date('Y'));

        $monthlyData = $invoiceRepository->getMonthlyRevenueByYear($year);

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            'datasets' => [
                [
                    'label' => 'Chiffre d\'affaires',
                    'backgroundColor' => '#3b82f6',
                    'data' => array_values($monthlyData),
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ]);

        $totalPaid = $invoiceRepository->createQueryBuilder('i')
            ->select('SUM(i.totalTtc)')
            ->where('i.status = :status')
            ->setParameter('status', 'payées')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $pendingCount = count($invoiceRepository->findBy(['status' => 'en_attente']));
        $clientCount = count($clientRepository->findAll());
        $productCount = count($productRepository->findAll());

        return $this->render('dashboard/index.html.twig', [
            'chart' => $chart,
            'year' => $year,
            'totalPaid' => $totalPaid,
            'pendingCount' => $pendingCount,
            'clientCount' => $clientCount,
            'productCount' => $productCount,
        ]);
    }
}