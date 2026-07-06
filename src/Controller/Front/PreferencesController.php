<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\DisplayCurrencyProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreferencesController extends AbstractController
{
    #[Route('/devise', name: 'app_display_currency', methods: ['GET'])]
    public function setDisplayCurrency(Request $request, DisplayCurrencyProvider $displayCurrencyProvider): Response
    {
        $displayCurrencyProvider->setCurrency($request->query->getString('currency', 'EUR'));

        $redirect = $request->query->getString('redirect');
        $isSafeLocalPath = str_starts_with($redirect, '/') && !str_starts_with($redirect, '//');

        return $isSafeLocalPath ? $this->redirect($redirect) : $this->redirectToRoute('app_dashboard');
    }
}
