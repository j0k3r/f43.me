<?php

namespace App\Controller;

use App\Content\Extractor;
use App\Content\SiteConfigTest;
use App\Form\Type\ItemTestType;
use App\Parser\Internal;
use Graby\Graby;
use Graby\Monolog\Handler\GrabyHandler;
use Monolog\Level;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    /**
     * The only purpose is to be able to quickly see a convert article
     * - check bug
     * - improve parser
     * - test a site configuration.
     */
    #[Route(path: '/feed/test', name: 'feed_test', methods: ['GET', 'POST'])]
    public function indexAction(Request $request, Extractor $contentExtractor, GrabyHandler $grabyHandler, SiteConfigTest $siteConfigTest): Response
    {
        $form = $this->createForm(ItemTestType::class);
        $form->handleRequest($request);

        $content = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $siteConfig = (string) $form->get('siteconfig')->getData();
            $url = $form->get('link')->getData();

            try {
                $contentExtractor->init();
                $content = '' === trim($siteConfig)
                    ? $contentExtractor->parseContent($url)
                    : $siteConfigTest->run(
                        $url,
                        $siteConfig,
                        $form->get('siteconfig_replace')->getData(),
                        static fn (Graby $graby): Extractor => $contentExtractor->parseContent($url, null, new Internal($graby)),
                    );
            } catch (IOExceptionInterface|\InvalidArgumentException $exception) {
                $form->addError(new FormError('Unable to test the siteconfig: ' . $exception->getMessage()));
            }
        }

        return $this->render('default/Test/index.html.twig', [
            'menu' => 'test',
            'content' => $content,
            'form' => $form,
            'logs' => $grabyHandler->getRecords(),
            'logsHasWarning' => $grabyHandler->hasRecords(Level::Warning),
        ]);
    }
}
