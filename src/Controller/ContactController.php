<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from('contact@vite-et-gourmand.fr')
                ->to('contact@vite-et-gourmand.fr')
                ->subject('Nouveau contact : ' . $data['titre'])
                ->html(
                    '<p><strong>Nom :</strong> ' . $data['nom'] . '</p>' .
                    '<p><strong>Email :</strong> ' . $data['email'] . '</p>' .
                    '<p><strong>Message :</strong><br>' . nl2br($data['description']) . '</p>'
                );

            $mailer->send($email);

            $this->addFlash('success', 'Votre demande a bien été envoyée.');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
