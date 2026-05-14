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
    #[Route('/', name: 'app_home')]
    public function home() : Response
    {
        return $this->redirectToRoute('app_dashboard');
    }
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        Request $request,
        InvoiceRepository $invoiceRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $user = $this->getUser();
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

        $totalPaid = $invoiceRepository->getTotalPaidByUser($user);

        $pendingCount = count($invoiceRepository->findByUserAndStatus($user, 'en_attente'));
        $clientCount = count($clientRepository->findByUser($user));
        $productCount = count($productRepository->findByUser($user));

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