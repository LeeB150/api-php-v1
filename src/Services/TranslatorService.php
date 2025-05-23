<?php

namespace App\Services;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\HttpFoundation\Request;

class TranslatorService
{
    private $translator;

    public function __construct(Request $request)
    {
        $this->initializeTranslator($request);
    }

    private function initializeTranslator(Request $request)
    {
        $locale = $request ? $request->getPreferredLanguage(['es', 'en']) : 'es'; // Fallback
        $this->translator = new Translator($locale);
        $this->translator->addLoader('xliff', new XliffFileLoader());
        $this->translator->addResource('xliff', __DIR__ . '/../../translations/validators.es.xlf', 'es', 'validators');
        $this->translator->addResource('xliff', __DIR__ . '/../../translations/validators.en.xlf', 'en', 'validators');
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}